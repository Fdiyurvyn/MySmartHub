<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/tasks/tasks.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi login diperlukan.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body) || !verifyCsrfToken((string) ($body['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid.']);
    exit;
}

$message = trim((string) ($body['message'] ?? ''));
if ($message === '' || mb_strlen($message, 'UTF-8') > 1000) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Pesan wajib diisi dan maksimal 1000 karakter.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$pdo = getDatabaseConnection();

function aiContext(PDO $pdo, int $userId): array {
    $tasks = $pdo->prepare("SELECT title, priority, status, deadline FROM tasks WHERE user_id = :user_id AND status != 'done' ORDER BY deadline IS NULL, deadline ASC LIMIT 10");
    $tasks->execute(['user_id' => $userId]);
    $events = $pdo->prepare('SELECT title, start_date, location FROM calendar_events WHERE user_id = :user_id AND start_date >= NOW() ORDER BY start_date ASC LIMIT 5');
    $events->execute(['user_id' => $userId]);
    $finance = $pdo->prepare("SELECT type, COALESCE(SUM(amount), 0) AS total FROM finances WHERE user_id = :user_id AND MONTH(trans_date) = MONTH(CURRENT_DATE()) AND YEAR(trans_date) = YEAR(CURRENT_DATE()) GROUP BY type");
    $finance->execute(['user_id' => $userId]);

    return [
        'active_tasks' => $tasks->fetchAll(),
        'upcoming_events' => $events->fetchAll(),
        'finance_this_month' => $finance->fetchAll(),
    ];
}

function localAiReply(string $message, array $context): string {
    $lower = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
    if (str_contains($lower, 'tugas') || str_contains($lower, 'todo')) {
        $count = count($context['active_tasks']);
        return $count > 0 ? "Anda memiliki {$count} tugas aktif. Tugas terdekat: **{$context['active_tasks'][0]['title']}**." : 'Belum ada tugas aktif. Anda bisa menambahkan todo dari halaman Todo List.';
    }
    if (str_contains($lower, 'keuangan') || str_contains($lower, 'pengeluaran') || str_contains($lower, 'finance')) {
        $income = 0;
        $expense = 0;
        foreach ($context['finance_this_month'] as $row) {
            if ($row['type'] === 'income') $income = (float) $row['total'];
            if ($row['type'] === 'expense') $expense = (float) $row['total'];
        }
        return 'Ringkasan bulan ini: pemasukan **Rp ' . number_format($income, 0, ',', '.') . '**, pengeluaran **Rp ' . number_format($expense, 0, ',', '.') . '**, saldo **Rp ' . number_format($income - $expense, 0, ',', '.') . '**.';
    }
    return "Saya memahami pesan Anda: \"{$message}\". Saya bisa membantu mengatur tugas, membaca jadwal, dan merangkum keuangan. Ceritakan tujuan Anda atau tanyakan sesuatu yang lebih spesifik.";
}

function getConversationHistory(PDO $pdo, int $userId): array {
    $history = $pdo->prepare('SELECT role, message FROM ai_history WHERE user_id = :user_id ORDER BY id DESC LIMIT 12');
    $history->execute(['user_id' => $userId]);
    return array_reverse($history->fetchAll());
}

function callGemini(string $message, array $context, array $history, string $apiKey, string $model): ?string {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
    $contents = [];
    foreach ($history as $item) {
        $contents[] = [
            'role' => $item['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $item['message']]],
        ];
    }
    $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];
    $system = "Anda adalah SmartHub Assistant, asisten pribadi yang hangat, cerdas, dan praktis. Jawab dalam Bahasa Indonesia kecuali user meminta bahasa lain. Pahami konteks percakapan sebelumnya, jawab langsung, dan gunakan Markdown sederhana bila membantu. Jangan mengarang data; gunakan konteks MySmartHub berikut hanya jika relevan:\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $payload = json_encode(['system_instruction' => ['parts' => [['text' => $system]]], 'contents' => $contents]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $status < 200 || $status >= 300) return null;
    $decoded = json_decode($response, true);
    return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

try {
    $context = aiContext($pdo, $userId);
    $history = getConversationHistory($pdo, $userId);
    $apiKey = trim((string) ($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));
    $model = trim((string) ($_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?: 'gemini-2.0-flash'));
    $reply = $apiKey !== '' ? callGemini($message, $context, $history, $apiKey, $model) : null;
    $reply = $reply ?: localAiReply($message, $context);

    $save = $pdo->prepare('INSERT INTO ai_history (user_id, role, message) VALUES (:user_id, :role, :message)');
    $save->execute(['user_id' => $userId, 'role' => 'user', 'message' => $message]);
    $save->execute(['user_id' => $userId, 'role' => 'assistant', 'message' => $reply]);
    echo json_encode(['success' => true, 'reply' => $reply, 'provider' => $apiKey !== '' ? 'gemini' : 'local']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Asisten sedang mengalami kendala.']);
}

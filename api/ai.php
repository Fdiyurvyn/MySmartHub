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
    
    // 1. Tugas / Todo
    if (str_contains($lower, 'tugas') || str_contains($lower, 'todo') || str_contains($lower, 'pekerjaan') || str_contains($lower, 'aktivitas') || str_contains($lower, 'rencana')) {
        $count = count($context['active_tasks']);
        if ($count === 0) {
            return "Tidak ada tugas aktif saat ini. Bagus sekali! Anda bisa menambahkan tugas baru di menu **Todo List**.";
        }
        $reply = "Anda memiliki **{$count} tugas aktif**:\n";
        foreach ($context['active_tasks'] as $index => $task) {
            $num = $index + 1;
            $priority = ucfirst($task['priority']);
            $deadline = !empty($task['deadline']) ? date('d M Y, H:i', strtotime($task['deadline'])) : 'Tanpa deadline';
            $reply .= "{$num}. **{$task['title']}** (Prioritas: *{$priority}*, Batas: *{$deadline}*)\n";
        }
        return $reply;
    }
    
    // 2. Jadwal / Agenda / Event
    if (str_contains($lower, 'jadwal') || str_contains($lower, 'agenda') || str_contains($lower, 'event') || str_contains($lower, 'kalender') || str_contains($lower, 'acara')) {
        $count = count($context['upcoming_events']);
        if ($count === 0) {
            return "Tidak ada jadwal atau event mendatang dalam waktu dekat. Anda bisa membuat agenda baru di menu **Calendar**.";
        }
        $reply = "Berikut **{$count} jadwal/event mendatang** Anda:\n";
        foreach ($context['upcoming_events'] as $index => $event) {
            $num = $index + 1;
            $date = date('d M Y, H:i', strtotime($event['start_date']));
            $location = !empty($event['location']) ? " di *{$event['location']}*" : '';
            $reply .= "{$num}. **{$event['title']}** pada *{$date}*{$location}\n";
        }
        return $reply;
    }
    
    // 3. Keuangan / Finance
    if (str_contains($lower, 'keuangan') || str_contains($lower, 'pengeluaran') || str_contains($lower, 'pemasukan') || str_contains($lower, 'finance') || str_contains($lower, 'saldo') || str_contains($lower, 'transaksi')) {
        $income = 0;
        $expense = 0;
        foreach ($context['finance_this_month'] as $row) {
            if ($row['type'] === 'income') $income = (float) $row['total'];
            if ($row['type'] === 'expense') $expense = (float) $row['total'];
        }
        $balance = $income - $expense;
        $reply = "Berikut ringkasan **keuangan Anda bulan ini**:\n";
        $reply .= "- Total Pemasukan: **Rp " . number_format($income, 0, ',', '.') . "**\n";
        $reply .= "- Total Pengeluaran: **Rp " . number_format($expense, 0, ',', '.') . "**\n";
        $reply .= "- Saldo Bersih: **Rp " . number_format($balance, 0, ',', '.') . "**\n\n";
        if ($balance < 0) {
            $reply .= "⚠️ *Peringatan: Pengeluaran Anda melebihi pemasukan bulan ini. Tetap hemat ya!*";
        } else {
            $reply .= "✅ *Kondisi keuangan aman. Pertahankan kebiasaan menabung Anda!*";
        }
        return $reply;
    }

    // 4. Greetings
    if (preg_match('/\b(halo|hai|hi|pagi|siang|sore|malam|assalamualaikum|hello)\b/i', $lower)) {
        return "Halo! Saya **SmartHub Assistant**.\nAda yang bisa saya bantu hari ini?\n\nAnda bisa bertanya tentang:\n- 📝 **Tugas** (contoh: *Tampilkan tugas saya*)\n- 📅 **Jadwal** (contoh: *Apa agenda saya berikutnya?*)\n- 💰 **Keuangan** (contoh: *Ringkasan keuangan bulan ini*)";
    }

    // 5. Identity
    if (str_contains($lower, 'siapa kamu') || str_contains($lower, 'siapa anda') || str_contains($lower, 'nama kamu') || str_contains($lower, 'nama anda')) {
        return "Saya adalah **SmartHub Assistant**, asisten AI personal Anda di **MySmartHub** yang dirancang untuk mempermudah produktivitas harian Anda. Saya dapat terhubung dengan modul Tugas, Kalender, dan Keuangan Anda untuk memberikan info terkini secara instan.";
    }

    // 6. Gratitude
    if (str_contains($lower, 'terima kasih') || str_contains($lower, 'makasih') || str_contains($lower, 'thanks') || str_contains($lower, 'thank you')) {
        return "Sama-sama! Senang bisa membantu Anda. Tetap produktif dan semangat ya! 💪";
    }

    // 7. Help
    if (str_contains($lower, 'bantuan') || str_contains($lower, 'help') || str_contains($lower, 'tolong') || str_contains($lower, 'fitur')) {
        return "Tentu! Saya bisa membantu Anda mengelola beberapa hal berikut:\n\n1. 📝 **Tugas & Todo**: Ketik 'tugas' atau 'pekerjaan' untuk melihat daftar todo aktif Anda.\n2. 📅 **Kalender & Event**: Ketik 'jadwal' atau 'event' untuk melihat agenda mendatang.\n3. 💰 **Keuangan**: Ketik 'keuangan' atau 'pengeluaran' untuk melihat ringkasan keuangan bulan ini.\n\nKetik salah satu kata kunci di atas untuk mulai!";
    }
    
    return "Saya memahami pesan Anda. Saya bisa membantu mengatur tugas, membaca jadwal agenda, dan merangkum keuangan Anda. Ceritakan tujuan Anda atau tanyakan sesuatu yang lebih spesifik (contoh: *'Apa tugas saya hari ini?'*).";
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

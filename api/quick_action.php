<?php
/**
 * API Endpoint: Quick Action Handler
 * Handles: create_event, create_finance
 * Method: POST (JSON)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Must be logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

// CSRF check
$csrfToken = $body['csrf_token'] ?? '';
if (!verifyCsrfToken((string) $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token tidak valid']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $body['action'] ?? '';
$pdo    = getDatabaseConnection();

// ─── Ensure calendar_events table exists ─────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS calendar_events (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        title       VARCHAR(200) NOT NULL,
        description TEXT,
        start_date  DATETIME,
        end_date    DATETIME,
        location    VARCHAR(255),
        color       VARCHAR(20) DEFAULT '#3B82F6',
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// ─── Ensure finances & finance_categories tables exist ───────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS finance_categories (
        id      INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        name    VARCHAR(100),
        type    ENUM('income','expense'),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS finances (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT,
        category_id INT,
        title       VARCHAR(200),
        amount      DECIMAL(15,2),
        type        ENUM('income','expense'),
        trans_date  DATE,
        note        TEXT,
        FOREIGN KEY(user_id)     REFERENCES users(id)              ON DELETE CASCADE,
        FOREIGN KEY(category_id) REFERENCES finance_categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB
");

// ─── Route ────────────────────────────────────────────────────────────────────
try {
    switch ($action) {

        // ── Tambah Event ─────────────────────────────────────────────────────
        case 'create_event':
            $title      = trim($body['title'] ?? '');
            $startDate  = trim($body['start_date'] ?? '');
            $endDate    = trim($body['end_date'] ?? '');
            $location   = trim($body['location'] ?? '');
            $description = trim($body['description'] ?? '');

            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Judul event wajib diisi.']);
                exit;
            }
            if ($startDate === '') {
                echo json_encode(['success' => false, 'message' => 'Tanggal mulai wajib diisi.']);
                exit;
            }

            // Validate datetime format
            $startTs = strtotime($startDate);
            if ($startTs === false) {
                echo json_encode(['success' => false, 'message' => 'Format tanggal mulai tidak valid.']);
                exit;
            }
            $endDateFinal = ($endDate !== '' && strtotime($endDate) !== false) ? $endDate : null;

            $stmt = $pdo->prepare("
                INSERT INTO calendar_events (user_id, title, description, start_date, end_date, location, color)
                VALUES (:user_id, :title, :description, :start_date, :end_date, :location, :color)
            ");
            $stmt->execute([
                'user_id'     => $userId,
                'title'       => $title,
                'description' => $description ?: null,
                'start_date'  => $startDate,
                'end_date'    => $endDateFinal,
                'location'    => $location ?: null,
                'color'       => '#3B82F6',
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Event berhasil ditambahkan!',
                'id'      => (int) $pdo->lastInsertId(),
            ]);
            break;

        // ── Catat Pengeluaran ─────────────────────────────────────────────────
        case 'create_finance':
            $title     = trim($body['title'] ?? '');
            $amount    = $body['amount'] ?? '';
            $type      = $body['type'] ?? 'expense';
            $transDate = trim($body['trans_date'] ?? date('Y-m-d'));
            $note      = trim($body['note'] ?? '');

            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Keterangan transaksi wajib diisi.']);
                exit;
            }
            $amountFloat = filter_var($amount, FILTER_VALIDATE_FLOAT);
            if ($amountFloat === false || $amountFloat <= 0) {
                echo json_encode(['success' => false, 'message' => 'Jumlah harus berupa angka positif.']);
                exit;
            }
            if (!in_array($type, ['income', 'expense'], true)) {
                $type = 'expense';
            }

            $stmt = $pdo->prepare("
                INSERT INTO finances (user_id, category_id, title, amount, type, trans_date, note)
                VALUES (:user_id, NULL, :title, :amount, :type, :trans_date, :note)
            ");
            $stmt->execute([
                'user_id'    => $userId,
                'title'      => $title,
                'amount'     => $amountFloat,
                'type'       => $type,
                'trans_date' => $transDate,
                'note'       => $note ?: null,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Transaksi berhasil dicatat!',
                'id'      => (int) $pdo->lastInsertId(),
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server. Silakan coba lagi.']);
}

<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/finance/index.php');
}

// Verify CSRF Token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken((string) $_POST['csrf_token'])) {
    $_SESSION['finance_error'] = 'CSRF token tidak valid.';
    redirect('modules/finance/index.php');
}

$action = $_POST['action'] ?? '';
$pdo = getDatabaseConnection();

try {
    if ($action === 'create_transaction') {
        $title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
        $type = isset($_POST['type']) && in_array($_POST['type'], ['income', 'expense'], true) ? $_POST['type'] : 'expense';
        $transDate = isset($_POST['trans_date']) && trim((string) $_POST['trans_date']) !== '' ? trim((string) $_POST['trans_date']) : date('Y-m-d');
        $categoryId = isset($_POST['category_id']) && trim((string) $_POST['category_id']) !== '' ? (int) $_POST['category_id'] : null;
        $note = isset($_POST['note']) ? trim((string) $_POST['note']) : '';

        if ($title === '') {
            throw new Exception('Keterangan transaksi wajib diisi.');
        }
        if ($amount <= 0) {
            throw new Exception('Jumlah transaksi harus lebih besar dari 0.');
        }

        // Validate category ownership if selected
        if ($categoryId !== null) {
            $stmtCat = $pdo->prepare('SELECT id FROM finance_categories WHERE id = :id AND user_id = :user_id');
            $stmtCat->execute(['id' => $categoryId, 'user_id' => $userId]);
            if (!$stmtCat->fetch()) {
                $categoryId = null; // Reset if category does not belong to this user
            }
        }

        $stmt = $pdo->prepare('INSERT INTO finances (user_id, category_id, title, amount, type, trans_date, note) VALUES (:user_id, :category_id, :title, :amount, :type, :trans_date, :note)');
        $stmt->execute([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'trans_date' => $transDate,
            'note' => $note !== '' ? $note : null
        ]);

        $_SESSION['finance_success'] = 'Transaksi berhasil dicatat!';

    } elseif ($action === 'edit_transaction') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0;
        $type = isset($_POST['type']) && in_array($_POST['type'], ['income', 'expense'], true) ? $_POST['type'] : 'expense';
        $transDate = isset($_POST['trans_date']) && trim((string) $_POST['trans_date']) !== '' ? trim((string) $_POST['trans_date']) : date('Y-m-d');
        $categoryId = isset($_POST['category_id']) && trim((string) $_POST['category_id']) !== '' ? (int) $_POST['category_id'] : null;
        $note = isset($_POST['note']) ? trim((string) $_POST['note']) : '';

        if ($id <= 0) {
            throw new Exception('ID transaksi tidak valid.');
        }
        if ($title === '') {
            throw new Exception('Keterangan transaksi wajib diisi.');
        }
        if ($amount <= 0) {
            throw new Exception('Jumlah transaksi harus lebih besar dari 0.');
        }

        // Verify ownership of the transaction
        $stmtCheck = $pdo->prepare('SELECT id FROM finances WHERE id = :id AND user_id = :user_id');
        $stmtCheck->execute(['id' => $id, 'user_id' => $userId]);
        if (!$stmtCheck->fetch()) {
            throw new Exception('Transaksi tidak ditemukan atau bukan milik Anda.');
        }

        // Validate category ownership if selected
        if ($categoryId !== null) {
            $stmtCat = $pdo->prepare('SELECT id FROM finance_categories WHERE id = :id AND user_id = :user_id');
            $stmtCat->execute(['id' => $categoryId, 'user_id' => $userId]);
            if (!$stmtCat->fetch()) {
                $categoryId = null;
            }
        }

        $stmt = $pdo->prepare('UPDATE finances SET category_id = :category_id, title = :title, amount = :amount, type = :type, trans_date = :trans_date, note = :note WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'category_id' => $categoryId,
            'title' => $title,
            'amount' => $amount,
            'type' => $type,
            'trans_date' => $transDate,
            'note' => $note !== '' ? $note : null,
            'id' => $id,
            'user_id' => $userId
        ]);

        $_SESSION['finance_success'] = 'Transaksi berhasil diperbarui!';

    } elseif ($action === 'delete_transaction') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            throw new Exception('ID transaksi tidak valid.');
        }

        // Verify ownership
        $stmtCheck = $pdo->prepare('SELECT id FROM finances WHERE id = :id AND user_id = :user_id');
        $stmtCheck->execute(['id' => $id, 'user_id' => $userId]);
        if (!$stmtCheck->fetch()) {
            throw new Exception('Transaksi tidak ditemukan atau bukan milik Anda.');
        }

        $stmt = $pdo->prepare('DELETE FROM finances WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        $_SESSION['finance_success'] = 'Transaksi berhasil dihapus!';

    } elseif ($action === 'create_category') {
        $name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
        $type = isset($_POST['type']) && in_array($_POST['type'], ['income', 'expense'], true) ? $_POST['type'] : 'expense';

        if ($name === '') {
            throw new Exception('Nama kategori wajib diisi.');
        }

        // Check duplicate
        $stmtCheck = $pdo->prepare('SELECT id FROM finance_categories WHERE user_id = :user_id AND name = :name AND type = :type');
        $stmtCheck->execute(['user_id' => $userId, 'name' => $name, 'type' => $type]);
        if ($stmtCheck->fetch()) {
            throw new Exception('Kategori dengan nama dan tipe tersebut sudah ada.');
        }

        $stmt = $pdo->prepare('INSERT INTO finance_categories (user_id, name, type) VALUES (:user_id, :name, :type)');
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type
        ]);

        $_SESSION['finance_success'] = 'Kategori baru berhasil ditambahkan!';
        
        // Redirect to create.php if referrer came from there
        if (isset($_POST['redirect_to_create']) && $_POST['redirect_to_create'] === '1') {
            redirect('modules/finance/create.php');
        }

    } else {
        throw new Exception('Aksi tidak dikenal.');
    }
} catch (Exception $e) {
    $_SESSION['finance_error'] = $e->getMessage();
}

redirect('modules/finance/index.php');

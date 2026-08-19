<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Hapus Transaksi | MySmartHub';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['finance_error'] = 'ID transaksi tidak valid.';
    redirect('modules/finance/index.php');
}

$pdo = getDatabaseConnection();

// Fetch transaction detail
$stmtTrans = $pdo->prepare('SELECT * FROM finances WHERE id = :id AND user_id = :user_id');
$stmtTrans->execute(['id' => $id, 'user_id' => $userId]);
$transaction = $stmtTrans->fetch();

if (!$transaction) {
    $_SESSION['finance_error'] = 'Transaksi tidak ditemukan atau bukan milik Anda.';
    redirect('modules/finance/index.php');
}

$csrfToken = generateCsrfToken('finance');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="../../assets/img/myis.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .finance-shell { max-width: 600px; margin: 5rem auto; padding: 0 1rem 3rem; }
        .finance-card {
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
        }
        .warning-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .details-box {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <main class="finance-shell">
        <div class="finance-card">
            <span class="warning-icon">⚠️</span>
            <h1 style="font-size: 1.75rem; font-weight: 500; margin-bottom: 0.5rem;">Konfirmasi Hapus</h1>
            <p style="color: var(--text-muted);">Apakah Anda yakin ingin menghapus catatan transaksi berikut? Tindakan ini tidak dapat dibatalkan.</p>

            <div class="details-box">
                <p style="margin-bottom: 0.5rem;"><strong>Keterangan:</strong> <?= htmlspecialchars($transaction['title'], ENT_QUOTES, 'UTF-8') ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Tipe:</strong> <?= $transaction['type'] === 'income' ? '📈 Pemasukan' : '📉 Pengeluaran' ?></p>
                <p style="margin-bottom: 0.5rem;"><strong>Jumlah:</strong> Rp <?= number_format($transaction['amount'], 0, ',', '.') ?></p>
                <p style="margin-bottom: 0;"><strong>Tanggal:</strong> <?= date('d M Y', strtotime($transaction['trans_date'])) ?></p>
            </div>

            <form action="handler.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="delete_transaction">
                <input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>">

                <div style="display: flex; gap: 0.75rem; justify-content: center; margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-danger" style="font-weight: 500;">Ya, Hapus</button>
                </div>
            </form>
        </div>
    </main>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

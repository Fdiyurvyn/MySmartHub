<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Keuangan | MySmartHub';

$pdo = getDatabaseConnection();

// Get filter inputs (default to current month/year)
$currentMonth = date('m');
$currentYear = date('Y');

$filterMonth = isset($_GET['month']) && $_GET['month'] !== '' ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : $currentMonth;
$filterYear = isset($_GET['year']) && $_GET['year'] !== '' ? (int) $_GET['year'] : (int) $currentYear;

// Get available years for filter selection
$stmtYears = $pdo->prepare('
    SELECT DISTINCT YEAR(trans_date) as yr 
    FROM finances 
    WHERE user_id = :user_id 
    ORDER BY yr DESC
');
$stmtYears->execute(['user_id' => $userId]);
$yearsList = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
if (empty($yearsList)) {
    $yearsList = [$currentYear];
}

// 1. Calculate Stats (Total Income, Total Expense, Net Balance for the filtered period)
$stmtStats = $pdo->prepare('
    SELECT 
        SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as total_expense
    FROM finances 
    WHERE user_id = :user_id 
      AND MONTH(trans_date) = :month 
      AND YEAR(trans_date) = :year
');
$stmtStats->execute([
    'user_id' => $userId,
    'month' => $filterMonth,
    'year' => $filterYear
]);
$stats = $stmtStats->fetch();
$totalIncome = (float) ($stats['total_income'] ?? 0);
$totalExpense = (float) ($stats['total_expense'] ?? 0);
$netBalance = $totalIncome - $totalExpense;

// 2. Fetch Transactions for the filtered period
$stmtTrans = $pdo->prepare('
    SELECT f.id, f.title, f.amount, f.type, f.trans_date, f.note, 
           c.name as category_name
    FROM finances f
    LEFT JOIN finance_categories c ON c.id = f.category_id
    WHERE f.user_id = :user_id 
      AND MONTH(f.trans_date) = :month 
      AND YEAR(f.trans_date) = :year
    ORDER BY f.trans_date DESC, f.id DESC
');
$stmtTrans->execute([
    'user_id' => $userId,
    'month' => $filterMonth,
    'year' => $filterYear
]);
$transactions = $stmtTrans->fetchAll();

// 3. Prepare Chart Data (Category Breakdown for Expenses & Income)
$stmtChart = $pdo->prepare('
    SELECT c.name as category_name, SUM(f.amount) as total_amount, f.type
    FROM finances f
    LEFT JOIN finance_categories c ON c.id = f.category_id
    WHERE f.user_id = :user_id 
      AND MONTH(f.trans_date) = :month 
      AND YEAR(f.trans_date) = :year
    GROUP BY c.id, f.type
');
$stmtChart->execute([
    'user_id' => $userId,
    'month' => $filterMonth,
    'year' => $filterYear
]);
$chartRawData = $stmtChart->fetchAll();

$expenseChartLabels = [];
$expenseChartData = [];
$incomeChartLabels = [];
$incomeChartData = [];

foreach ($chartRawData as $row) {
    $catName = $row['category_name'] ?? 'Tanpa Kategori';
    if ($row['type'] === 'expense') {
        $expenseChartLabels[] = $catName;
        $expenseChartData[] = (float) $row['total_amount'];
    } else {
        $incomeChartLabels[] = $catName;
        $incomeChartData[] = (float) $row['total_amount'];
    }
}

// Flash messages
$success = $_SESSION['finance_success'] ?? '';
$error = $_SESSION['finance_error'] ?? '';
unset($_SESSION['finance_success'], $_SESSION['finance_error']);

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .finance-shell { max-width: 1200px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .finance-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 992px) {
            .finance-grid { grid-template-columns: 2.2fr 0.8fr; }
        }
        .finance-card {
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .finance-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-item {
            background: var(--background-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease;
        }
        .stat-item:hover {
            transform: translateY(-2px);
        }
        .stat-icon-wrapper {
            font-size: 2rem;
            background: rgba(255,255,255,0.05);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        .stat-details h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .stat-details p {
            font-size: 1.35rem;
            font-weight: 600;
            margin: 0;
        }
        .text-income { color: var(--success-color); }
        .text-expense { color: var(--danger-color); }
        .text-balance { color: var(--primary-color); }
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.5rem;
            background: var(--card-color);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .filter-section select {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: var(--background-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            outline: none;
        }
        .filter-section button {
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .chart-box {
            background: var(--background-color);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .chart-container-canvas {
            position: relative;
            margin: auto;
            height: 250px;
            width: 100%;
        }
        .transaction-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--background-color);
        }
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .transaction-table th, .transaction-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .transaction-table th {
            background: rgba(255,255,255,0.02);
            color: var(--text-muted);
            font-weight: 500;
        }
        .transaction-table tr:last-child td {
            border-bottom: none;
        }
        .badge-type {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-income { background: rgba(34, 197, 94, 0.15); color: var(--success-color); }
        .badge-expense { background: rgba(239, 68, 68, 0.15); color: var(--danger-color); }
        
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success-color); color: #86EFAC; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger-color); color: #FCA5A5; }
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        .action-link {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        .btn-edit { background: rgba(59, 130, 246, 0.1); color: var(--secondary-color); }
        .btn-edit:hover { background: var(--primary-color); color: #FFF; }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .btn-delete:hover { background: var(--danger-color); color: #FFF; }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div>
            <div class="navbar-cta">
                <a href="../../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="finance-shell">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 500;">💰 Modul Finance</h1>
                <p style="color: var(--text-muted); margin: 0;">Kelola catatan pemasukan dan pengeluaran Anda.</p>
            </div>
            <div>
                <a href="create.php" class="btn btn-primary" style="font-weight: 500;">+ Catat Transaksi Baru</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <form method="GET" class="filter-section">
            <label for="month" style="font-size: 0.9rem;">Bulan:</label>
            <select name="month" id="month">
                <?php
                $months = [
                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                ];
                foreach ($months as $m => $name) {
                    $selected = ($m === $filterMonth) ? 'selected' : '';
                    echo "<option value=\"$m\" $selected>$name</option>";
                }
                ?>
            </select>

            <label for="year" style="font-size: 0.9rem;">Tahun:</label>
            <select name="year" id="year">
                <?php
                foreach ($yearsList as $yr) {
                    $selected = ($yr == $filterYear) ? 'selected' : '';
                    echo "<option value=\"$yr\" $selected>$yr</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>

        <!-- Stats Grid -->
        <div class="finance-stats-grid">
            <div class="stat-item">
                <div class="stat-icon-wrapper text-income">💵</div>
                <div class="stat-details">
                    <h3>Total Pemasukan</h3>
                    <p class="text-income">Rp <?= number_format($totalIncome, 0, ',', '.') ?></p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon-wrapper text-expense">💸</div>
                <div class="stat-details">
                    <h3>Total Pengeluaran</h3>
                    <p class="text-expense">Rp <?= number_format($totalExpense, 0, ',', '.') ?></p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon-wrapper text-balance">⚖️</div>
                <div class="stat-details">
                    <h3>Saldo Bersih</h3>
                    <p class="<?= $netBalance >= 0 ? 'text-income' : 'text-expense' ?>">
                        Rp <?= number_format($netBalance, 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="finance-grid">
            <!-- Left Side: Table of Transactions -->
            <div class="finance-card">
                <h2 style="font-size: 1.25rem; font-weight: 500; margin-bottom: 1.25rem;">📋 Riwayat Transaksi Bulan Ini</h2>

                <?php if (empty($transactions)): ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                        <p style="font-size: 1.5rem; margin-bottom: 0.5rem;">📭</p>
                        <p style="margin: 0;">Belum ada data transaksi untuk periode ini.</p>
                    </div>
                <?php else: ?>
                    <div class="transaction-table-wrapper">
                        <table class="transaction-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Keterangan</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                    <th style="width: 130px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($t['trans_date'])) ?></td>
                                        <td>
                                            <span class="badge-type <?= $t['type'] === 'income' ? 'badge-income' : 'badge-expense' ?>">
                                                <?= $t['type'] === 'income' ? 'Masuk' : 'Keluar' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($t['note'])): ?>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">
                                                    <?= htmlspecialchars($t['note'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color: var(--text-muted); font-size: 0.9rem;">
                                                <?= htmlspecialchars($t['category_name'] ?? 'Tanpa Kategori', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="<?= $t['type'] === 'income' ? 'text-income' : 'text-expense' ?>" style="font-weight: 600;">
                                            Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="edit.php?id=<?= $t['id'] ?>" class="action-link btn-edit">✏️</a>
                                                <a href="delete.php?id=<?= $t['id'] ?>" class="action-link btn-delete">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Side: Chart breakdown & Summary -->
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="finance-card">
                    <h2 style="font-size: 1.2rem; font-weight: 500; margin-bottom: 1rem;">📊 Grafik Pemasukan vs Pengeluaran</h2>
                    <div class="chart-box">
                        <div class="chart-container-canvas">
                            <canvas id="ratioChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="finance-card">
                    <h2 style="font-size: 1.2rem; font-weight: 500; margin-bottom: 1rem;">🏷️ Pengeluaran Kategori</h2>
                    <?php if (empty($expenseChartData)): ?>
                        <p style="color: var(--text-muted); font-size: 0.9rem; font-style: italic;">Tidak ada data pengeluaran berkategori.</p>
                    <?php else: ?>
                        <div class="chart-box">
                            <div class="chart-container-canvas">
                                <canvas id="expenseCategoryChart"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">&copy; 2026 MySmartHub. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script>
        // Data for Income vs Expense ratio
        const totalIncome = <?= json_encode($totalIncome) ?>;
        const totalExpense = <?= json_encode($totalExpense) ?>;

        const ctxRatio = document.getElementById('ratioChart').getContext('2d');
        new Chart(ctxRatio, {
            type: 'doughnut',
            data: {
                labels: ['Pemasukan', 'Pengeluaran'],
                datasets: [{
                    data: [totalIncome, totalExpense],
                    backgroundColor: ['#10B981', '#EF4444'],
                    borderColor: '#1E293B',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#F1F5F9',
                            font: { family: 'Poppins' }
                        }
                    }
                }
            }
        });

        // Data for Category breakdown (Expenses)
        <?php if (!empty($expenseChartData)): ?>
        const expenseLabels = <?= json_encode($expenseChartLabels) ?>;
        const expenseValues = <?= json_encode($expenseChartData) ?>;

        const ctxExpense = document.getElementById('expenseCategoryChart').getContext('2d');
        new Chart(ctxExpense, {
            type: 'pie',
            data: {
                labels: expenseLabels,
                datasets: [{
                    data: expenseValues,
                    backgroundColor: [
                        '#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#3B82F6', 
                        '#10B981', '#EF4444', '#14B8A6', '#F43F5E', '#84CC16'
                    ],
                    borderColor: '#1E293B',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#F1F5F9',
                            font: { family: 'Poppins' }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

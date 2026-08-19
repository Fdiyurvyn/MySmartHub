<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Ubah Transaksi | MySmartHub';

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

// Fetch categories for this user
$stmtCats = $pdo->prepare('SELECT id, name, type FROM finance_categories WHERE user_id = :user_id ORDER BY name ASC');
$stmtCats->execute(['user_id' => $userId]);
$categories = $stmtCats->fetchAll();

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
        .finance-shell { max-width: 800px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .finance-card {
            background: var(--card-color);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-color);
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 0.75rem 1rem;
            background: var(--background-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: var(--font-family);
            font-size: 0.95rem;
            width: 100%;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 576px) {
            .form-row { grid-template-columns: 1fr 1fr; }
        }
        .type-toggle-btn {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--background-color);
            color: var(--text-muted);
            font-family: var(--font-family);
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }
        .type-toggle-btn.active[data-type="expense"] {
            background: rgba(239, 68, 68, 0.15);
            border-color: var(--danger-color);
            color: var(--danger-color);
            font-weight: 500;
        }
        .type-toggle-btn.active[data-type="income"] {
            background: rgba(34, 197, 94, 0.15);
            border-color: var(--success-color);
            color: var(--success-color);
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div>
            <div class="navbar-cta">
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </nav>

    <main class="finance-shell">
        <h1 style="font-size: 2rem; font-weight: 500; margin-bottom: 0.25rem;">✏️ Ubah Transaksi</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Perbarui detail catatan transaksi Anda.</p>

        <div class="finance-card">
            <form action="handler.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="edit_transaction">
                <input type="hidden" name="id" value="<?= (int) $transaction['id'] ?>">
                
                <div class="form-group">
                    <label>Tipe Transaksi <span style="color: var(--danger-color)">*</span></label>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" id="btn-expense" class="type-toggle-btn" data-type="expense" onclick="setTransactionType('expense')">💸 Pengeluaran</button>
                        <button type="button" id="btn-income" class="type-toggle-btn" data-type="income" onclick="setTransactionType('income')">💵 Pemasukan</button>
                    </div>
                    <input type="hidden" id="trans-type-value" name="type" value="<?= htmlspecialchars($transaction['type'], ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-group">
                    <label for="title">Keterangan <span style="color: var(--danger-color)">*</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($transaction['title'], ENT_QUOTES, 'UTF-8') ?>" required maxlength="200">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Jumlah (Rp) <span style="color: var(--danger-color)">*</span></label>
                        <input type="number" id="amount" name="amount" value="<?= (float) $transaction['amount'] ?>" min="1" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="trans_date">Tanggal <span style="color: var(--danger-color)">*</span></label>
                        <input type="date" id="trans_date" name="trans_date" value="<?= htmlspecialchars($transaction['trans_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>" <?= $transaction['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note">Catatan Tambahan</label>
                    <textarea id="note" name="note" rows="3" placeholder="Tulis catatan opsional di sini..."><?= htmlspecialchars($transaction['note'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" style="font-weight: 500;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">&copy; 2026 MySmartHub. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script>
        // Filters category selector based on selected transaction type
        const categorySelect = document.getElementById('category_id');
        const categoryOptions = Array.from(categorySelect.options);
        const initialCategoryId = <?= json_encode($transaction['category_id']) ?>;

        function setTransactionType(type) {
            document.getElementById('trans-type-value').value = type;
            
            // Toggle active classes on buttons
            document.getElementById('btn-expense').classList.toggle('active', type === 'expense');
            document.getElementById('btn-income').classList.toggle('active', type === 'income');

            // Filter category options
            categorySelect.innerHTML = '';
            
            categoryOptions.forEach(opt => {
                const optType = opt.getAttribute('data-type');
                // Always add empty option, and match types for others
                if (opt.value === '' || optType === type) {
                    categorySelect.appendChild(opt);
                }
            });

            // Restore selection if it matches the current filtered options
            const matchedOpt = Array.from(categorySelect.options).find(opt => opt.value == initialCategoryId);
            if (matchedOpt) {
                categorySelect.value = initialCategoryId;
            } else {
                categorySelect.value = '';
            }
        }

        // Initialize view based on saved transaction type
        setTransactionType(<?= json_encode($transaction['type']) ?>);
    </script>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

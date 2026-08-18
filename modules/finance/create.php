<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Tambah Transaksi | MySmartHub';

$pdo = getDatabaseConnection();

// Fetch categories for this user
$stmtCats = $pdo->prepare('SELECT id, name, type FROM finance_categories WHERE user_id = :user_id ORDER BY name ASC');
$stmtCats->execute(['user_id' => $userId]);
$categories = $stmtCats->fetchAll();

$csrfToken = generateCsrfToken('finance');

// Check for errors/success from session (since category creation can redirect here)
$success = $_SESSION['finance_success'] ?? '';
$error = $_SESSION['finance_error'] ?? '';
unset($_SESSION['finance_success'], $_SESSION['finance_error']);
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
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success { background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success-color); color: #86EFAC; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger-color); color: #FCA5A5; }
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
        <h1 style="font-size: 2rem; font-weight: 500; margin-bottom: 0.25rem;">✍️ Catat Transaksi Baru</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Tambahkan detail pemasukan atau pengeluaran Anda.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="finance-card">
            <form action="handler.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="create_transaction">
                
                <div class="form-group">
                    <label>Tipe Transaksi <span style="color: var(--danger-color)">*</span></label>
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" id="btn-expense" class="type-toggle-btn active" data-type="expense" onclick="setTransactionType('expense')">💸 Pengeluaran</button>
                        <button type="button" id="btn-income" class="type-toggle-btn" data-type="income" onclick="setTransactionType('income')">💵 Pemasukan</button>
                    </div>
                    <input type="hidden" id="trans-type-value" name="type" value="expense">
                </div>

                <div class="form-group">
                    <label for="title">Keterangan <span style="color: var(--danger-color)">*</span></label>
                    <input type="text" id="title" name="title" placeholder="Contoh: Belanja Bulanan" required maxlength="200">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Jumlah (Rp) <span style="color: var(--danger-color)">*</span></label>
                        <input type="number" id="amount" name="amount" placeholder="Contoh: 100000" min="1" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="trans_date">Tanggal <span style="color: var(--danger-color)">*</span></label>
                        <input type="date" id="trans_date" name="trans_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>">
                                <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note">Catatan Tambahan</label>
                    <textarea id="note" name="note" rows="3" placeholder="Tulis catatan opsional di sini..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary" style="font-weight: 500;">Simpan Transaksi</button>
                </div>
            </form>
        </div>

        <!-- Add Category Box -->
        <h2 style="font-size: 1.35rem; font-weight: 500; margin-bottom: 0.25rem; margin-top: 2rem;">🏷️ Buat Kategori Baru</h2>
        <p style="color: var(--text-muted); margin-bottom: 1rem;">Jika kategori yang Anda inginkan belum tersedia, tambahkan di sini.</p>
        <div class="finance-card">
            <form action="handler.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="create_category">
                <input type="hidden" name="redirect_to_create" value="1">

                <div class="form-row">
                    <div class="form-group">
                        <label for="new-cat-name">Nama Kategori <span style="color: var(--danger-color)">*</span></label>
                        <input type="text" id="new-cat-name" name="name" placeholder="Contoh: Transportasi" required>
                    </div>
                    <div class="form-group">
                        <label for="new-cat-type">Tipe Kategori <span style="color: var(--danger-color)">*</span></label>
                        <select id="new-cat-type" name="type" required>
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                    <button type="submit" class="btn btn-secondary" style="font-weight: 500;">+ Tambah Kategori</button>
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
        }

        // Initialize category options based on default 'expense' type
        setTransactionType('expense');
    </script>
</body>
</html>

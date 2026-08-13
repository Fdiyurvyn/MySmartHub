<?php
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}

$page_title = 'MySmartHub | Dashboard';

$pdo = getDatabaseConnection();
$stmt = $pdo->prepare('SELECT full_name, photo FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
$userName = $user['full_name'] ?? 'Pengguna';
$userPhoto = $user['photo'] ?? 'default.png';

// CSRF token untuk quick actions
$csrfToken = generateCsrfToken('dashboard');

// 1. Hitung Todo Aktif (status selain 'done')
$stmtTasks = $pdo->prepare('SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND status != "done"');
$stmtTasks->execute(['user_id' => $_SESSION['user_id']]);
$activeTasksCount = (int) $stmtTasks->fetchColumn();

// 2. Hitung Event Bulan Ini
$stmtEvents = $pdo->prepare('SELECT COUNT(*) FROM calendar_events WHERE user_id = :user_id AND MONTH(start_date) = MONTH(CURRENT_DATE()) AND YEAR(start_date) = YEAR(CURRENT_DATE())');
$stmtEvents->execute(['user_id' => $_SESSION['user_id']]);
$monthlyEventsCount = (int) $stmtEvents->fetchColumn();

// 3. Hitung Total Pengeluaran
$stmtFinance = $pdo->prepare('SELECT SUM(amount) FROM finances WHERE user_id = :user_id AND type = "expense"');
$stmtFinance->execute(['user_id' => $_SESSION['user_id']]);
$totalExpense = (float) ($stmtFinance->fetchColumn() ?? 0);

// 4. Ambil 5 Aktivitas Terbaru
$stmtRecent = $pdo->prepare('
    (SELECT "task" AS source_type, title, updated_at AS activity_time, status AS extra_info 
     FROM tasks 
     WHERE user_id = :user_id_tasks)
    UNION ALL
    (SELECT "event" AS source_type, title, start_date AS activity_time, location AS extra_info 
     FROM calendar_events 
     WHERE user_id = :user_id_events)
    UNION ALL
    (SELECT "finance" AS source_type, title, trans_date AS activity_time, CONCAT(type, ":", amount) AS extra_info 
     FROM finances 
     WHERE user_id = :user_id_finances)
    ORDER BY activity_time DESC 
    LIMIT 5
');
$stmtRecent->execute([
    'user_id_tasks' => $_SESSION['user_id'],
    'user_id_events' => $_SESSION['user_id'],
    'user_id_finances' => $_SESSION['user_id']
]);
$recentActivities = $stmtRecent->fetchAll();

// Fungsi helper format waktu aktivitas
function formatActivityTime($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return $datetime;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datetime)) {
        return date('d M Y', $timestamp);
    }
    return date('d M Y, H:i', $timestamp);
}

// Avatar HTML
$avatarPath = 'uploads/' . $userPhoto;
if (!empty($userPhoto) && $userPhoto !== 'default.png' && file_exists(__DIR__ . '/' . $avatarPath)) {
    $avatarHtml = '<img src="' . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '" class="user-avatar">';
} else {
    $initial = strtoupper(substr($userName, 0, 1));
    $avatarHtml = '<div class="user-avatar-initials">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard MySmartHub">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/img/myis.png">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <a href="index.php">MySmartHub</a>
            </div>
            <div class="navbar-menu">
                <ul class="nav-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="modules/profile/index.php">Profile</a></li>
                    <li><a href="index.php">Keluar</a></li>
                </ul>
            </div>
            <div class="navbar-cta">
                <div class="user-avatar-container">
                    <?= $avatarHtml ?>
                    <span class="user-greeting">Halo, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="dashboard-wrapper">
            <!-- SIDEBAR -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Navigasi</h3>
                    <nav class="sidebar-nav">
                        <a href="dashboard.php" class="nav-item active">
                            📊 Dashboard
                        </a>
                        <a href="modules/tasks/index.php" class="nav-item">
                            📝 Todo List
                        </a>
                        <a href="#" class="nav-item">
                            📅 Calendar
                        </a>
                        <a href="#" class="nav-item">
                            💰 Finance
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- MAIN SECTION -->
            <section class="dashboard-content">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="welcome-header">
                        <h1 class="welcome-title">Selamat datang kembali, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
                        <p class="welcome-subtitle">Smart hub Anda siap untuk meningkatkan produktivitas.</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-content">
                            <h3 class="stat-label">Todo Aktif</h3>
                            <p class="stat-value"><?= (int) $activeTasksCount ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3 class="stat-label">Event Bulan Ini</h3>
                            <p class="stat-value"><?= (int) $monthlyEventsCount ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3 class="stat-label">Total Pengeluaran</h3>
                            <p class="stat-value">Rp <?= number_format($totalExpense, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="modules/tasks/index.php" id="btn-tambah-todo" class="btn btn-primary">+ Tambah Todo</a>
                        <a href="#" id="btn-event-baru" class="btn btn-secondary" onclick="openModal('modal-event'); return false;">+ Event Baru</a>
                        <a href="#" id="btn-catat-pengeluaran" class="btn btn-secondary" onclick="openModal('modal-finance'); return false;">+ Catat Pengeluaran</a>
                    </div>
                </div>

                <!-- Recent Items -->
                <div class="recent-section">
                    <h2 class="section-title">Aktivitas Terbaru</h2>
                    <?php if (empty($recentActivities)): ?>
                        <div class="empty-state">
                            <p class="empty-text">Belum ada aktivitas. Mulai dengan membuat todo pertama Anda!</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivities as $act): ?>
                                <div class="activity-item">
                                    <div class="activity-icon-wrapper">
                                        <?php if ($act['source_type'] === 'task'): ?>
                                            📝
                                        <?php elseif ($act['source_type'] === 'event'): ?>
                                            📅
                                        <?php else: ?>
                                            💰
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-details">
                                        <p class="activity-desc">
                                            <?php if ($act['source_type'] === 'task'): ?>
                                                Mengupdate tugas: <strong><?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?></strong> 
                                                <span class="badge badge-<?= htmlspecialchars($act['extra_info'], ENT_QUOTES, 'UTF-8') ?>"><?= strtoupper(htmlspecialchars($act['extra_info'], ENT_QUOTES, 'UTF-8')) ?></span>
                                            <?php elseif ($act['source_type'] === 'event'): ?>
                                                Event terjadwal: <strong><?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?></strong> 
                                                <?php if (!empty($act['extra_info'])): ?>
                                                    di <em><?= htmlspecialchars($act['extra_info'], ENT_QUOTES, 'UTF-8') ?></em>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php 
                                                $parts = explode(':', $act['extra_info']);
                                                $type = $parts[0] ?? 'expense';
                                                $amount = $parts[1] ?? '0';
                                                $formattedAmount = number_format((float)$amount, 0, ',', '.');
                                                $typeName = $type === 'income' ? 'Pemasukan' : 'Pengeluaran';
                                                ?>
                                                Mencatat <?= $typeName ?>: <strong><?= htmlspecialchars($act['title'], ENT_QUOTES, 'UTF-8') ?></strong> sebesar <span class="text-<?= $type === 'income' ? 'success' : 'danger' ?>">Rp <?= $formattedAmount ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <span class="activity-time"><?= htmlspecialchars(formatActivityTime($act['activity_time']), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">&copy; 2024 MySmartHub. Semua hak dilindungi.</p>
        </div>
    </footer>

    <!-- ═══ MODAL: Event Baru ═══════════════════════════════════════════════ -->
    <div id="modal-event" class="qa-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-event-title" hidden>
        <div class="qa-modal">
            <div class="qa-modal-header">
                <h2 id="modal-event-title" class="qa-modal-title">📅 Tambah Event Baru</h2>
                <button class="qa-modal-close" onclick="closeModal('modal-event')" aria-label="Tutup modal">&times;</button>
            </div>
            <div id="modal-event-alert" class="qa-alert" hidden></div>
            <form id="form-event" class="qa-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="qa-form-group">
                    <label for="event-title">Judul Event <span class="qa-required">*</span></label>
                    <input type="text" id="event-title" name="title" placeholder="Contoh: Rapat Tim" required maxlength="200">
                </div>
                <div class="qa-form-row">
                    <div class="qa-form-group">
                        <label for="event-start">Tanggal Mulai <span class="qa-required">*</span></label>
                        <input type="datetime-local" id="event-start" name="start_date" required>
                    </div>
                    <div class="qa-form-group">
                        <label for="event-end">Tanggal Selesai</label>
                        <input type="datetime-local" id="event-end" name="end_date">
                    </div>
                </div>
                <div class="qa-form-group">
                    <label for="event-location">Lokasi</label>
                    <input type="text" id="event-location" name="location" placeholder="Contoh: Ruang Meeting A" maxlength="255">
                </div>
                <div class="qa-form-group">
                    <label for="event-description">Deskripsi</label>
                    <textarea id="event-description" name="description" rows="3" placeholder="Deskripsi singkat event..."></textarea>
                </div>
                <div class="qa-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-event')">Batal</button>
                    <button type="submit" id="btn-submit-event" class="btn btn-primary">Simpan Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══ MODAL: Catat Pengeluaran ════════════════════════════════════════ -->
    <div id="modal-finance" class="qa-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-finance-title" hidden>
        <div class="qa-modal">
            <div class="qa-modal-header">
                <h2 id="modal-finance-title" class="qa-modal-title">💰 Catat Transaksi</h2>
                <button class="qa-modal-close" onclick="closeModal('modal-finance')" aria-label="Tutup modal">&times;</button>
            </div>
            <div id="modal-finance-alert" class="qa-alert" hidden></div>
            <form id="form-finance" class="qa-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="qa-form-group">
                    <label for="finance-type">Tipe Transaksi</label>
                    <div class="qa-type-toggle">
                        <button type="button" id="type-expense" class="qa-type-btn active" data-type="expense" onclick="setFinanceType('expense')">💸 Pengeluaran</button>
                        <button type="button" id="type-income" class="qa-type-btn" data-type="income" onclick="setFinanceType('income')">💵 Pemasukan</button>
                    </div>
                    <input type="hidden" id="finance-type-value" name="type" value="expense">
                </div>
                <div class="qa-form-group">
                    <label for="finance-title">Keterangan <span class="qa-required">*</span></label>
                    <input type="text" id="finance-title" name="title" placeholder="Contoh: Makan siang" required maxlength="200">
                </div>
                <div class="qa-form-row">
                    <div class="qa-form-group">
                        <label for="finance-amount">Jumlah (Rp) <span class="qa-required">*</span></label>
                        <input type="number" id="finance-amount" name="amount" placeholder="50000" min="1" step="1" required>
                    </div>
                    <div class="qa-form-group">
                        <label for="finance-date">Tanggal</label>
                        <input type="date" id="finance-date" name="trans_date" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="qa-form-group">
                    <label for="finance-note">Catatan</label>
                    <textarea id="finance-note" name="note" rows="2" placeholder="Catatan tambahan..."></textarea>
                </div>
                <div class="qa-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-finance')">Batal</button>
                    <button type="submit" id="btn-submit-finance" class="btn btn-primary">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    /* ── Quick Action Modal Styles ───────────────────────────────────────── */
    .qa-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 1rem;
        opacity: 0;
        transition: opacity 0.25s ease;
    }
    .qa-modal-overlay:not([hidden]) {
        display: flex;
        opacity: 1;
    }
    .qa-modal-overlay[hidden] {
        display: none !important;
    }
    .qa-modal-overlay.qa-entering {
        opacity: 0;
        display: flex;
    }
    .qa-modal {
        background: var(--card-color);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 2rem;
        width: 100%;
        max-width: 520px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        transform: translateY(20px);
        transition: transform 0.25s ease;
    }
    .qa-modal-overlay:not([hidden]) .qa-modal {
        transform: translateY(0);
    }
    .qa-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .qa-modal-title {
        font-size: 1.25rem;
        font-weight: 500;
        color: var(--text-color);
        margin: 0;
    }
    .qa-modal-close {
        background: transparent;
        border: none;
        color: var(--text-muted);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        line-height: 1;
        transition: background 0.2s ease, color 0.2s ease;
    }
    .qa-modal-close:hover {
        background: var(--background-color);
        color: var(--text-color);
    }
    .qa-form { display: flex; flex-direction: column; gap: 1rem; }
    .qa-form-group { display: flex; flex-direction: column; gap: 0.4rem; }
    .qa-form-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-color);
    }
    .qa-required { color: var(--danger-color); }
    .qa-form-group input,
    .qa-form-group textarea,
    .qa-form-group select {
        padding: 0.65rem 0.9rem;
        background: var(--background-color);
        color: var(--text-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: var(--font-family);
        font-size: 0.95rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        width: 100%;
    }
    .qa-form-group input:focus,
    .qa-form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .qa-form-group textarea { resize: vertical; min-height: 72px; }
    .qa-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .qa-form-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        margin-top: 0.5rem;
    }
    .qa-alert {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        border: 1px solid;
    }
    .qa-alert.qa-alert-success {
        background: rgba(34, 197, 94, 0.1);
        border-color: var(--success-color);
        color: #86EFAC;
    }
    .qa-alert.qa-alert-error {
        background: rgba(239, 68, 68, 0.1);
        border-color: var(--danger-color);
        color: #FCA5A5;
    }
    .qa-type-toggle {
        display: flex;
        gap: 0.5rem;
    }
    .qa-type-btn {
        flex: 1;
        padding: 0.6rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--background-color);
        color: var(--text-muted);
        font-family: var(--font-family);
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .qa-type-btn.active {
        background: rgba(59,130,246,0.15);
        border-color: var(--primary-color);
        color: var(--primary-color);
        font-weight: 500;
    }
    .qa-type-btn[data-type="income"].active {
        background: rgba(34, 197, 94, 0.15);
        border-color: var(--success-color);
        color: var(--success-color);
    }
    .btn[disabled], button[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
    }
    @media (max-width: 480px) {
        .qa-modal { padding: 1.25rem; }
        .qa-form-row { grid-template-columns: 1fr; }
        .qa-form-actions { flex-direction: column-reverse; }
        .qa-form-actions .btn { width: 100%; text-align: center; }
    }
    </style>

    <script>
    /* ── Modal helpers ───────────────────────────────────────────────────── */
    function openModal(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.removeAttribute('hidden');
        // Trap focus
        const firstInput = overlay.querySelector('input:not([type=hidden]), textarea, select, button');
        if (firstInput) setTimeout(() => firstInput.focus(), 50);
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.setAttribute('hidden', '');
        document.body.style.overflow = '';
        // Reset alert
        const alertEl = overlay.querySelector('.qa-alert');
        if (alertEl) { alertEl.hidden = true; alertEl.textContent = ''; alertEl.className = 'qa-alert'; }
    }

    // Close on overlay click
    document.querySelectorAll('.qa-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.qa-modal-overlay:not([hidden])').forEach(function(o) {
                closeModal(o.id);
            });
        }
    });

    /* ── Show modal alert ────────────────────────────────────────────────── */
    function showModalAlert(alertId, message, type) {
        var el = document.getElementById(alertId);
        if (!el) return;
        el.textContent = message;
        el.className = 'qa-alert qa-alert-' + type;
        el.hidden = false;
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ── Generic AJAX POST ───────────────────────────────────────────────── */
    function qaPost(action, formEl, alertId, submitBtnId, successCallback) {
        var submitBtn = document.getElementById(submitBtnId);
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';

        var data = { action: action };
        new FormData(formEl); // trigger browser validation

        // Collect form fields
        var inputs = formEl.querySelectorAll('input, textarea, select');
        inputs.forEach(function(inp) {
            if (inp.name) data[inp.name] = inp.value;
        });

        fetch('api/quick_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            if (json.success) {
                showModalAlert(alertId, '✅ ' + json.message, 'success');
                formEl.reset();
                if (typeof successCallback === 'function') successCallback(json);
                // Auto-close after 1.8s
                setTimeout(function() {
                    var modal = formEl.closest('.qa-modal-overlay');
                    if (modal) closeModal(modal.id);
                }, 1800);
            } else {
                showModalAlert(alertId, '❌ ' + json.message, 'error');
            }
        })
        .catch(function() {
            showModalAlert(alertId, '❌ Gagal terhubung ke server. Coba lagi.', 'error');
        })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.textContent = submitBtnId === 'btn-submit-event' ? 'Simpan Event' : 'Simpan Transaksi';
        });
    }

    /* ── Finance type toggle ─────────────────────────────────────────────── */
    function setFinanceType(type) {
        document.getElementById('finance-type-value').value = type;
        document.querySelectorAll('.qa-type-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
    }

    /* ── Event form submit ───────────────────────────────────────────────── */
    document.getElementById('form-event').addEventListener('submit', function(e) {
        e.preventDefault();
        var title = document.getElementById('event-title').value.trim();
        var start = document.getElementById('event-start').value;
        if (!title || !start) {
            showModalAlert('modal-event-alert', '❌ Judul dan tanggal mulai wajib diisi.', 'error');
            return;
        }
        qaPost('create_event', this, 'modal-event-alert', 'btn-submit-event');
    });

    /* ── Finance form submit ─────────────────────────────────────────────── */
    document.getElementById('form-finance').addEventListener('submit', function(e) {
        e.preventDefault();
        var title  = document.getElementById('finance-title').value.trim();
        var amount = document.getElementById('finance-amount').value;
        if (!title || !amount || parseFloat(amount) <= 0) {
            showModalAlert('modal-finance-alert', '❌ Keterangan dan jumlah wajib diisi dengan benar.', 'error');
            return;
        }
        qaPost('create_finance', this, 'modal-finance-alert', 'btn-submit-finance');
    });
    </script>
</body>
</html>

<?php
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}

$page_title = 'MySmartHub | Dashboard';

$pdo = getDatabaseConnection();
$stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
$userName = $user['full_name'] ?? 'Pengguna';
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
                    <li><a href="#profile">Profile</a></li>
                    <li><a href="logout.php" >Keluar</a></li>
                </ul>
            </div>
            <div class="navbar-cta">
                <span class="user-greeting">Halo, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
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
                            <p class="stat-value">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3 class="stat-label">Event Bulan Ini</h3>
                            <p class="stat-value">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3 class="stat-label">Total Pengeluaran</h3>
                            <p class="stat-value">Rp 0</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="action-buttons">
                        <a href="#" class="btn btn-primary">+ Tambah Todo</a>
                        <a href="#" class="btn btn-secondary">+ Event Baru</a>
                        <a href="#" class="btn btn-secondary">+ Catat Pengeluaran</a>
                    </div>
                </div>

                <!-- Recent Items -->
                <div class="recent-section">
                    <h2 class="section-title">Aktivitas Terbaru</h2>
                    <div class="empty-state">
                        <p class="empty-text">Belum ada aktivitas. Mulai dengan membuat todo pertama Anda!</p>
                    </div>
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
</body>
</html>

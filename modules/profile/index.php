<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Ubah Profil | MySmartHub';

$pdo = getDatabaseConnection();

// Fetch current user details
$stmt = $pdo->prepare('SELECT full_name, username, email, photo FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('login.php');
}

$userName = $user['full_name'] ?? 'Pengguna';
$userPhoto = $user['photo'] ?? 'default.png';

// Generate CSRF token
$csrfToken = generateCsrfToken('profile');

// Success and error messages from session flash
$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Avatar HTML
$avatarPath = '../../uploads/' . $userPhoto;
if (!empty($userPhoto) && $userPhoto !== 'default.png' && file_exists(__DIR__ . '/../../' . $avatarPath)) {
    $avatarHtml = '<img src="' . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '" class="profile-preview-img">';
    $navAvatarHtml = '<img src="' . htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '" class="user-avatar">';
} else {
    $initial = strtoupper(substr($userName, 0, 1));
    $avatarHtml = '<div class="profile-preview-initials">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</div>';
    $navAvatarHtml = '<div class="user-avatar-initials">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ubah profil Anda di MySmartHub">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="icon" type="image/png" href="../../assets/img/myis.png">
    <style>
        /* CSS khusus untuk halaman profil */
        .profile-container {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: var(--spacing-lg);
            max-width: var(--max-width-container);
            margin: 0 auto;
            padding: var(--spacing-lg);
        }
        .profile-card {
            background-color: var(--card-color);
            border: var(--border-width) solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-2xl);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            border-bottom: var(--border-width) solid var(--border-color);
            padding-bottom: var(--spacing-lg);
        }
        .profile-preview-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
        .profile-preview-initials {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 2.5rem;
            border: 3px solid var(--border-color);
        }
        .profile-info h2 {
            margin: 0;
            color: var(--text-color);
        }
        .profile-info p {
            margin: var(--spacing-xs) 0 0 0;
            color: var(--text-muted);
        }
        .profile-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
            max-width: 600px;
        }
        .file-upload-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        .file-upload-btn {
            display: inline-block;
            padding: var(--spacing-xs) var(--spacing-sm);
            background-color: var(--background-color);
            color: var(--text-color);
            border: var(--border-width) solid var(--border-color);
            border-radius: var(--border-radius);
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }
        .file-upload-btn:hover {
            border-color: var(--primary-color);
        }
        .file-upload-input {
            display: none;
        }
        .file-name-preview {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .read-only-input {
            background-color: var(--background-color) !important;
            opacity: 0.7;
            cursor: not-allowed;
        }
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <a href="../../dashboard.php">MySmartHub</a>
            </div>
            <div class="navbar-menu">
                <ul class="nav-links">
                    <li><a href="../../dashboard.php">Dashboard</a></li>
                    <li><a href="index.php">Profile</a></li>
                    <li><a href="../../logout.php">Keluar</a></li>
                </ul>
            </div>
            <div class="navbar-cta">
                <div class="user-avatar-container">
                    <?= $navAvatarHtml ?>
                    <span class="user-greeting">Halo, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="profile-container">
            <!-- SIDEBAR -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Navigasi</h3>
                    <nav class="sidebar-nav">
                        <a href="../../dashboard.php" class="nav-item">
                            📊 Dashboard
                        </a>
                        <a href="../tasks/index.php" class="nav-item">
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
            <section class="profile-card">
                <div class="profile-header">
                    <?= $avatarHtml ?>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form id="form-profile" method="post" action="handler.php" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" class="read-only-input" value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" class="read-only-input" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Nama Lengkap</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>" required maxlength="100">
                    </div>

                    <div class="form-group file-upload-wrapper">
                        <label for="photo">Foto Profil (Maksimal 2MB, JPG/PNG/GIF)</label>
                        <label class="file-upload-btn" for="photo">Pilih File Gambar...</label>
                        <input type="file" id="photo" name="photo" class="file-upload-input" accept="image/png, image/jpeg, image/gif, image/jpg" onchange="previewFileName(this)">
                        <span id="file-name-text" class="file-name-preview">Tidak ada file terpilih</span>
                    </div>

                    <div class="qa-form-actions" style="justify-content: flex-start; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="../../dashboard.php" class="btn btn-secondary" style="margin-left: 0.5rem;">Batal</a>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">&copy; 2026 MySmartHub. Semua hak dilindungi.</p>
        </div>
    </footer>

    <script>
        function previewFileName(input) {
            const textEl = document.getElementById('file-name-text');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    textEl.textContent = '❌ Ukuran file melebihi 2MB!';
                    textEl.style.color = '#EF4444';
                    input.value = ''; // Reset input
                } else {
                    textEl.textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                    textEl.style.color = '#94A3B8';
                }
            } else {
                textEl.textContent = 'Tidak ada file terpilih';
                textEl.style.color = '#94A3B8';
            }
        }
    </script>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

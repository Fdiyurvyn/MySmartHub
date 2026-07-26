<?php
require_once __DIR__ . '/config/database.php';

$page_title = 'MySmartHub | Login';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Masukkan email dan password.';
    } else {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                redirect('dashboard.php');
            } else {
                $errors[] = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Login gagal. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login ke MySmartHub">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <a href="index.php">MySmartHub</a>
            </div>
            <div class="navbar-cta">
                <a href="register.php" class="btn btn-secondary">Daftar</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <section class="auth-section">
            <div class="auth-container">
                <div class="auth-card">
                    <h1 class="auth-title">Masuk ke MySmartHub</h1>
                    <p class="auth-subtitle">Kelola produktivitas Anda dengan lebih baik</p>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="nama@email.com" 
                                required
                                autocomplete="email"
                            >
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Minimal 6 karakter" 
                                required
                                autocomplete="current-password"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-large auth-button">
                            Masuk
                        </button>
                    </form>

                    <p class="auth-footer">
                        Belum punya akun? <a href="register.php">Daftar sekarang</a>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">&copy; 2024 MySmartHub. Semua hak dilindungi.</p>
        </div>
    </footer>
</body>
</html>

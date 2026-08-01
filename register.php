<?php
require_once __DIR__ . '/config/database.php';

$page_title = 'MySmartHub | Register';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Mohon isi semua field.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Password tidak cocok.';
    } else {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah terdaftar.';
            } else {
                $usernameBase = strtolower(preg_replace('/[^a-z0-9]+/', '', $name));
                if ($usernameBase === '') {
                    $usernameBase = 'user';
                }
                $username = $usernameBase . '_' . substr(md5($email), 0, 6);

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, password) VALUES (:full_name, :username, :email, :password)');
                $stmt->execute([
                    'full_name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => $hashedPassword,
                ]);
                $success = 'Akun berhasil dibuat! Silakan <a href="login.php">login di sini</a>.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Registrasi gagal. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Daftar akun MySmartHub">
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
                <a href="login.php" class="btn btn-secondary">Masuk</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <section class="auth-section">
            <div class="auth-container">
                <div class="auth-card">
                    <h1 class="auth-title">Buat Akun MySmartHub</h1>
                    <p class="auth-subtitle">Gratis, tanpa perlu kartu kredit</p>
                    
                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="auth-form">
                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                placeholder="Masukkan nama Anda" 
                                required
                                autocomplete="name"
                            >
                        </div>

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
                                autocomplete="new-password"
                            >
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Ulangi password" 
                                required
                                autocomplete="new-password"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary btn-large auth-button">
                            Daftar Gratis
                        </button>
                    </form>

                    <p class="auth-footer">
                        Sudah punya akun? <a href="login.php">Masuk di sini</a>
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

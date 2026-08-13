<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDatabaseConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbName = $_ENV['DB_NAME'] ?? 'mysmarthub';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASS'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                photo VARCHAR(255) DEFAULT 'default.png',
                role ENUM('admin','user') DEFAULT 'user',
                is_verified TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS task_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                name VARCHAR(100),
                color VARCHAR(20),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category_id INT,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                priority ENUM('low','medium','high') DEFAULT 'medium',
                status ENUM('todo','doing','done') DEFAULT 'todo',
                deadline DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY(category_id) REFERENCES task_categories(id) ON DELETE SET NULL
            ) ENGINE=InnoDB"
        );
    }

    return $pdo;
}

function getApplicationBasePath(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
    $trimmed = trim($scriptName, '/');

    if ($trimmed === '') {
        return '';
    }

    $parts = explode('/', $trimmed);

    // If the application is served from a subfolder (e.g. /MySmartHub/...)
    // the first path segment will be the folder name. If the first segment
    // looks like a file (contains a dot), assume the app is at document root.
    $first = $parts[0] ?? '';
    if ($first === '' || strpos($first, '.') !== false) {
        return '';
    }

    return '/' . $first;
}

function redirect(string $path): void {
    $normalizedPath = $path;

    if ($path !== '' && $path[0] !== '/' && strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0) {
        $basePath = getApplicationBasePath();
        $normalizedPath = ($basePath !== '' ? $basePath : '') . '/' . ltrim($path, '/');
    }

    header('Location: ' . $normalizedPath);
    exit;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function generateCsrfToken(string $action = 'default'): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

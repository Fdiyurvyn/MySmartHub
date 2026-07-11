<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDatabaseConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dbFile = __DIR__ . '/../database.sqlite';
        $dsn = 'sqlite:' . $dbFile;

        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    return $pdo;
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

<?php
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$pdo = getDatabaseConnection();

$stmt = $pdo->prepare('SELECT role, message, created_at FROM ai_history WHERE user_id = :user_id ORDER BY id DESC LIMIT 30');
$stmt->execute(['user_id' => $userId]);
$history = array_reverse($stmt->fetchAll());

$csrfToken = generateCsrfToken('ai');
$page_title = 'AI Assistant | MySmartHub';
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
        .ai-shell { max-width: 900px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .ai-card { background: var(--card-color); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; box-shadow: 0 12px 30px rgba(15,23,42,.15); }
        .ai-heading { padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; gap: 1rem; align-items: center; }
        .ai-heading h1 { margin: 0; font-size: 1.6rem; }
        .ai-heading p { color: var(--text-muted); margin-top: .35rem; }
        .ai-presence { color: #16805c; font-size: .8rem; white-space: nowrap; }
        .ai-messages { min-height: 420px; max-height: 58vh; overflow-y: auto; padding: 1.5rem; display: grid; gap: 1rem; background: var(--background-color); }
        .ai-message { max-width: 78%; padding: .85rem 1rem; border-radius: 14px; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.55; }
        .ai-message.user { margin-left: auto; background: var(--primary-color); color: #fff; border-bottom-right-radius: 4px; }
        .ai-message.assistant { background: var(--card-color); border: 1px solid var(--border-color); border-bottom-left-radius: 4px; }
        .ai-empty { align-self: center; text-align: center; color: var(--text-muted); }
        .ai-form { display: flex; gap: .75rem; padding: 1rem; border-top: 1px solid var(--border-color); }
        .ai-form textarea { flex: 1; resize: none; min-height: 48px; max-height: 120px; padding: .75rem; border: 1px solid var(--border-color); border-radius: 10px; background: var(--input-color); color: var(--text-color); font: inherit; }
        .ai-form textarea:focus { outline: 2px solid var(--primary-color); outline-offset: 1px; }
        .ai-form button { align-self: stretch; min-width: 100px; }
        .ai-status { padding: 0 1rem 1rem; color: var(--text-muted); font-size: .8rem; }
        .ai-quick-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .ai-quick-actions button { border: 1px solid var(--border-color); background: transparent; color: var(--text-color); border-radius: 999px; padding: .45rem .7rem; cursor: pointer; }
        @media (max-width: 600px) { .ai-message { max-width: 92%; } .ai-form { align-items: stretch; flex-direction: column; } .ai-form button { min-height: 44px; } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="navbar-container"><div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div><div class="navbar-cta"><a href="../../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a></div></div></nav>
    <main class="ai-shell"><div class="ai-card">
        <header class="ai-heading"><div><h1>✨ SmartHub Assistant</h1><p>Asisten produktivitas untuk tugas, jadwal, dan keuangan Anda.</p><div class="ai-quick-actions"><button type="button" data-prompt="Apa yang perlu saya kerjakan hari ini?">Rencanakan hari ini</button><button type="button" data-prompt="Bantu saya memahami kondisi keuangan bulan ini.">Ringkas keuangan</button></div></div><span class="ai-presence">● Online</span></header>
        <section id="ai-messages" class="ai-messages" aria-live="polite">
            <?php if (!$history): ?><div class="ai-empty">Tanyakan sesuatu tentang aktivitas Anda hari ini.</div><?php endif; ?>
            <?php foreach ($history as $item): ?><div class="ai-message <?= $item['role'] === 'user' ? 'user' : 'assistant' ?>"><?= htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8') ?></div><?php endforeach; ?>
        </section>
        <div id="ai-status" class="ai-status" hidden>Assistant sedang berpikir...</div>
        <form id="ai-form" class="ai-form"><textarea id="ai-input" maxlength="1000" placeholder="Contoh: Apa tugas saya yang belum selesai?" required></textarea><button id="ai-submit" type="submit" class="btn btn-primary">Kirim</button></form>
    </div></main>
    <script>window.smartHubAi = { endpoint: '../../api/ai.php', csrfToken: <?= json_encode($csrfToken) ?> };</script>
    <script src="../../assets/js/ai.js"></script>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

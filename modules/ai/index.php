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
        .ai-messages { min-height: 420px; max-height: 58vh; overflow-y: auto; padding: 1.5rem; display: grid; gap: 1rem; background: var(--background-color); scroll-behavior: smooth; }
        .ai-message { max-width: 78%; padding: .85rem 1rem; border-radius: 14px; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.55; opacity: 0; animation: slideInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .ai-message.user { margin-left: auto; background: var(--primary-color); color: #fff; border-bottom-right-radius: 4px; }
        .ai-message.assistant { background: var(--card-color); border: 1px solid var(--border-color); border-bottom-left-radius: 4px; }
        
        /* Markdown styles for assistant replies */
        .ai-message.assistant ul, .ai-message.assistant ol { margin-left: 1.25rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
        .ai-message.assistant li { margin-bottom: 0.25rem; }
        .ai-message.assistant p { margin-bottom: 0.75rem; }
        .ai-message.assistant p:last-child { margin-bottom: 0; }
        .ai-message.assistant pre { background: #0f172a; padding: 0.75rem; border-radius: 8px; overflow-x: auto; font-family: monospace; font-size: 0.875rem; margin: 0.5rem 0; border: 1px solid var(--border-color); }
        .ai-message.assistant code { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; padding: 0.125rem 0.25rem; border-radius: 4px; font-family: monospace; font-size: 0.9em; }

        /* Typing Indicator Bubble */
        .typing-indicator { display: flex; align-items: center; gap: 5px; padding: 0.85rem 1.2rem; width: fit-content; }
        .typing-indicator span { width: 8px; height: 8px; background-color: var(--text-muted); border-radius: 50%; display: inline-block; animation: bounce 1.4s infinite ease-in-out both; }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ai-empty { align-self: center; text-align: center; color: var(--text-muted); }
        .ai-form { display: flex; gap: .75rem; padding: 1rem; border-top: 1px solid var(--border-color); }
        .ai-form textarea { flex: 1; resize: none; min-height: 48px; max-height: 120px; padding: .75rem; border: 1px solid var(--border-color); border-radius: 10px; background: var(--input-color); color: var(--text-color); font: inherit; transition: border-color 0.2s ease, outline 0.2s ease; }
        .ai-form textarea:focus { outline: 2px solid var(--primary-color); outline-offset: 1px; }
        .ai-form button { align-self: stretch; min-width: 100px; transition: all 0.2s ease; }
        .ai-status { padding: 0 1rem 1rem; color: var(--text-muted); font-size: .8rem; display: none; }
        .ai-quick-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .ai-quick-actions button { border: 1px solid var(--border-color); background: transparent; color: var(--text-color); border-radius: 999px; padding: .45rem .7rem; cursor: pointer; transition: all 0.2s ease; font-size: 0.85rem; }
        .ai-quick-actions button:hover { background: var(--primary-color); border-color: var(--primary-color); color: #fff; transform: translateY(-1px); }
        @media (max-width: 600px) { .ai-message { max-width: 92%; } .ai-form { align-items: stretch; flex-direction: column; } .ai-form button { min-height: 44px; } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="navbar-container"><div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div><div class="navbar-cta"><a href="../../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a></div></div></nav>
    <main class="ai-shell"><div class="ai-card">
        <header class="ai-heading"><div><h1>✨ SmartHub Assistant</h1><p>Asisten produktivitas untuk tugas, jadwal, dan keuangan Anda.</p><div class="ai-quick-actions"><button type="button" data-prompt="Apa yang perlu saya kerjakan hari ini?">📝 Rencanakan Hari Ini</button><button type="button" data-prompt="Bantu saya memahami kondisi keuangan bulan ini.">💰 Ringkas Keuangan</button><button type="button" data-prompt="Tampilkan jadwal atau agenda saya berikutnya.">📅 Jadwal Terdekat</button><button type="button" data-prompt="Tolong tunjukkan apa saja yang bisa kamu lakukan.">❓ Butuh Bantuan</button></div></div><span class="ai-presence">● Online</span></header>
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

<?php
require_once __DIR__ . '/config/database.php';

$page_title = 'MySmartHub | Home';
$page_css = [];

include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h1>Welcome to MySmartHub</h1>
    <p>Your all-in-one personal productivity dashboard.</p>
    <?php if (!empty($_SESSION['user_id'])): ?>
        <p>You are signed in. Head to your <a href="dashboard.php">dashboard</a>.</p>
    <?php else: ?>
        <p><a href="login.php">Login</a> or <a href="register.php">create an account</a> to get started.</p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

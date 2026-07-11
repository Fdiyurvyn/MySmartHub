<?php
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    redirect('login.php');
}

$page_title = 'MySmartHub | Dashboard';
$page_css = [];

include __DIR__ . '/includes/header.php';
?>
<div class="dashboard-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <section class="card">
        <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') ?>!</h2>
        <p>Your smart hub is ready. Add tasks, notes, habits, and more from here.</p>
        <ul>
            <li>Track your tasks</li>
            <li>Capture notes</li>
            <li>Plan your calendar</li>
            <li>Review your finances</li>
        </ul>
    </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

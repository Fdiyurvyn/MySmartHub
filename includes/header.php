<?php
$page_title = $page_title ?? 'MySmartHub';
$page_css = $page_css ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php foreach ($page_css as $css): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="container">

<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/tasks.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$page_title = 'Todo List | MySmartHub';
$errors = [];
$success = '';
$mode = 'create';
$task = null;

$pdo = getDatabaseConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken((string) $_POST['csrf_token'])) {
        $errors[] = 'CSRF token tidak valid.';
    } else {
        $action = $_POST['action'] ?? 'create';

        try {
            if ($action === 'delete') {
                $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
                if ($taskId === false || $taskId === null) {
                    $errors[] = 'ID tugas tidak valid.';
                } else {
                    if (deleteTaskForUser($pdo, $userId, $taskId)) {
                        $success = 'Tugas berhasil dihapus.';
                    } else {
                        $errors[] = 'Tugas tidak ditemukan atau bukan milik Anda.';
                    }
                }
            } elseif ($action === 'edit') {
                $taskId = filter_input(INPUT_POST, 'task_id', FILTER_VALIDATE_INT);
                if ($taskId === false || $taskId === null) {
                    $errors[] = 'ID tugas tidak valid.';
                } else {
                    $updated = updateTaskForUser($pdo, $userId, $taskId, $_POST);
                    if ($updated) {
                        $success = 'Tugas berhasil diperbarui.';
                    } else {
                        $errors[] = 'Tugas tidak ditemukan atau bukan milik Anda.';
                    }
                }
            } else {
                $createdId = createTaskForUser($pdo, $userId, $_POST);
                $success = 'Tugas berhasil ditambahkan.';
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            $errors[] = 'Operasi gagal. Silakan coba lagi.';
        }
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $mode = 'edit';
    $task = getTaskByIdForUser($pdo, $userId, (int) $_GET['edit']);
}

$tasks = listTasksForUser($pdo, $userId);

$stmt = $pdo->prepare('SELECT id, name, color FROM task_categories WHERE user_id = :user_id ORDER BY name ASC');
$stmt->execute(['user_id' => $userId]);
$categories = $stmt->fetchAll();

$csrfToken = generateCsrfToken('tasks');
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
        body { background: var(--background-color); color: var(--text-color); }
        .task-shell { max-width: 1200px; color: var(--text-color); margin: 2rem auto; padding: 0 1rem 3rem; }
        .task-card { background: var(--card-color); border: 1px solid var(--border-color); border-radius: 16px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(15,23,42,0.05); }
        .task-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.35rem; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem 0.9rem; border-radius: 10px; border: 1px solid var(--border-color); background: var(--input-color); color: var(--text-color); font: inherit; }
        .task-list { display: grid; gap: 1rem; }
        .task-item { border: 1px solid var(--border-color); border-radius: 14px; padding: 1rem; background: var(--background-color); }
        .task-top { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; }
        .task-meta { color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem; }
        .badge { display: inline-block; padding: 0.3rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; margin-right: 0.4rem; }
        .badge-high { background: #FEE2E2; color: #B91C1C; }
        .badge-medium { background: #FEF3C7; color: #B45309; }
        .badge-low { background: #DBEAFE; color: #1D4ED8; }
        .badge-done { background: #DCFCE7; color: #15803D; }
        .badge-doing { background: #E0F2FE; color: #0369A1; }
        .badge-todo { background: #F3F4F6; color: #374151; }
        .actions { display: flex; gap: 0.5rem; margin-top: 0.8rem; }
        .btn-sm { padding: 0.5rem 0.75rem; font-size: 0.9rem; }
        .alert { padding: 0.9rem 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: var(--success-color); }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: var(--danger-color); }
        @media (max-width: 900px) { .task-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div>
            <div class="navbar-cta"><a href="../../dashboard.php" class="btn btn-secondary">Kembali</a></div>
        </div>
    </nav>

    <main class="task-shell">
        <div class="task-card">
            <h1 style="margin-bottom: 1rem; color: var(--text-color);">Todo List</h1>
            <p style="margin-bottom: 1.5rem; color: var(--text-muted);">Kelola tugas harian Anda dengan aman dan terorganisir.</p>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="task-grid">
                <section>
                    <h2 style="margin-bottom: 1rem; color: var(--text-color);">Daftar Tugas</h2>
                    <div class="task-list">
                        <?php if (empty($tasks)): ?>
                            <div class="task-item">Belum ada tugas. Tambahkan tugas pertama Anda.</div>
                        <?php else: ?>
                            <?php foreach ($tasks as $taskItem): ?>
                                <div class="task-item">
                                    <div class="task-top">
                                        <div>
                                            <h3><?= htmlspecialchars($taskItem['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <?php if (!empty($taskItem['description'])): ?>
                                                <p style="margin: 0.4rem 0;"><?= htmlspecialchars($taskItem['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                            <div class="task-meta">
                                                <span class="badge badge-<?= htmlspecialchars($taskItem['priority'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(strtoupper($taskItem['priority']), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="badge badge-<?= htmlspecialchars($taskItem['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(strtoupper($taskItem['status']), ENT_QUOTES, 'UTF-8') ?></span>
                                                
                                                <?php if (!empty($taskItem['category_name'])): ?>
                                                    <span class="badge" style="background: #E2E8F0; color: #334155;"><?= htmlspecialchars($taskItem['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="text-align:right; color:var(--text-muted); font-size:0.9rem;">
                                            <?php if (!empty($taskItem['deadline'])): ?><div>Deadline: <?= htmlspecialchars($taskItem['deadline'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                                            <div>Updated: <?= htmlspecialchars($taskItem['updated_at'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    </div>
                                    <div class="actions">
                                        <a href="index.php?edit=<?= (int) $taskItem['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="task_id" value="<?= (int) $taskItem['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus tugas ini?');">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <aside>
                    <h2 style="margin-bottom: 1rem; color: var(--text-color);"><?= $mode === 'edit' ? 'Edit Tugas' : 'Tambah Tugas' ?></h2>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'edit' : 'create' ?>">
                        <?php if ($mode === 'edit' && $task): ?>
                            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title">Judul</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="priority">Prioritas</label>
                            <select id="priority" name="priority">
                                <option value="low" <?= (($task['priority'] ?? 'medium') === 'low') ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= (($task['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= (($task['priority'] ?? 'medium') === 'high') ? 'selected' : '' ?>>High</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="todo" <?= (($task['status'] ?? 'todo') === 'todo') ? 'selected' : '' ?>>Todo</option>
                                <option value="doing" <?= (($task['status'] ?? 'todo') === 'doing') ? 'selected' : '' ?>>Doing</option>
                                <option value="done" <?= (($task['status'] ?? 'todo') === 'done') ? 'selected' : '' ?>>Done</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="deadline">Deadline</label>
                            <input type="datetime-local" id="deadline" name="deadline" value="<?= htmlspecialchars(formatDeadlineForInput($task['deadline'] ?? null), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- <div class="form-group">
                            <label for="category_id">Kategori</label>
                            <select id="category_id" name="category_id">
                                <option value="">Tidak ada kategori</option>
                                <option value="">Biasa</option>
                                <option value="">Penting</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (($task['category_id'] ?? null) == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div> -->

                        <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah Tugas' ?></button>
                        <?php if ($mode === 'edit'): ?>
                            <a href="index.php" class="btn btn-secondary" style="margin-left: 0.5rem;">Batal</a>
                        <?php endif; ?>
                    </form>
                </aside>
            </div>
        </div>
    </main>
    <script src="../../assets/js/app.js"></script>
</body>
</html>

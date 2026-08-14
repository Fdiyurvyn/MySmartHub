<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$pdo = getDatabaseConnection();

$csrfToken = generateCsrfToken('calendar');

$month = $_GET['month'] ?? (int) date('n');
$year = $_GET['year'] ?? (int) date('Y');

$month = (int) $month;
$year = (int) $year;

if ($month < 1 || $month > 12) {
    $month = (int) date('n');
}

$selectedDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$prevMonth = $selectedDate->modify('-1 month');
$nextMonth = $selectedDate->modify('+1 month');

$errors = [];
$success = '';
$editingEvent = null;

if (isset($_GET['edit_event']) && is_numeric($_GET['edit_event'])) {
    $editId = (int) $_GET['edit_event'];
    $editingEventStmt = $pdo->prepare('SELECT id, title, description, start_date, end_date, location FROM calendar_events WHERE id = :id AND user_id = :user_id LIMIT 1');
    $editingEventStmt->execute(['id' => $editId, 'user_id' => $userId]);
    $editingEvent = $editingEventStmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken((string) $_POST['csrf_token'])) {
        $errors[] = 'CSRF token tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete_event') {
            $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
            if ($eventId === false || $eventId === null) {
                $errors[] = 'ID event tidak valid.';
            } else {
                $deleteStmt = $pdo->prepare('DELETE FROM calendar_events WHERE id = :id AND user_id = :user_id');
                $deleteStmt->execute(['id' => $eventId, 'user_id' => $userId]);
                if ($deleteStmt->rowCount() > 0) {
                    $success = 'Event berhasil dihapus.';
                } else {
                    $errors[] = 'Event tidak ditemukan atau bukan milik Anda.';
                }
            }
        } elseif ($action === 'update_event') {
            $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
            $title = trim((string) ($_POST['title'] ?? ''));
            $startDate = trim((string) ($_POST['start_date'] ?? ''));
            $endDate = trim((string) ($_POST['end_date'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if ($eventId === false || $eventId === null) {
                $errors[] = 'ID event tidak valid.';
            }
            if ($title === '') {
                $errors[] = 'Judul event wajib diisi.';
            }
            if ($startDate === '') {
                $errors[] = 'Tanggal mulai event wajib diisi.';
            }
            if ($endDate !== '' && strtotime($endDate) === false) {
                $errors[] = 'Format tanggal selesai tidak valid.';
            }
            if ($startDate !== '' && strtotime($startDate) === false) {
                $errors[] = 'Format tanggal mulai tidak valid.';
            }

            if (empty($errors)) {
                $updateStmt = $pdo->prepare(
                    'UPDATE calendar_events
                     SET title = :title,
                         description = :description,
                         start_date = :start_date,
                         end_date = :end_date,
                         location = :location
                     WHERE id = :id AND user_id = :user_id'
                );
                $updateStmt->execute([
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'start_date' => $startDate,
                    'end_date' => $endDate !== '' ? $endDate : null,
                    'location' => $location !== '' ? $location : null,
                    'id' => $eventId,
                    'user_id' => $userId,
                ]);
                $success = 'Event berhasil diperbarui.';
            }
        } elseif ($action === 'create_event') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $startDate = trim((string) ($_POST['start_date'] ?? ''));
            $endDate = trim((string) ($_POST['end_date'] ?? ''));
            $location = trim((string) ($_POST['location'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if ($title === '') {
                $errors[] = 'Judul event wajib diisi.';
            }
            if ($startDate === '') {
                $errors[] = 'Tanggal mulai event wajib diisi.';
            }

            if (empty($errors)) {
                $startTimestamp = strtotime($startDate);
                if ($startTimestamp === false) {
                    $errors[] = 'Format tanggal mulai tidak valid.';
                }

                if ($endDate !== '' && strtotime($endDate) === false) {
                    $errors[] = 'Format tanggal selesai tidak valid.';
                }
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare(
                    'INSERT INTO calendar_events (user_id, title, description, start_date, end_date, location, color)
                     VALUES (:user_id, :title, :description, :start_date, :end_date, :location, :color)'
                );
                $stmt->execute([
                    'user_id' => $userId,
                    'title' => $title,
                    'description' => $description !== '' ? $description : null,
                    'start_date' => $startDate,
                    'end_date' => $endDate !== '' ? $endDate : null,
                    'location' => $location !== '' ? $location : null,
                    'color' => '#3B82F6',
                ]);

                $success = 'Event berhasil ditambahkan.';
            }
        }
    }
}

$firstWeekday = (int) $selectedDate->format('N');
$calendarStart = $selectedDate->modify('-' . ($firstWeekday - 1) . ' days');
$calendarDays = [];
for ($i = 0; $i < 42; $i++) {
    $calendarDays[] = $calendarStart->modify('+' . $i . ' days');
}

$rangeStart = $calendarDays[0]->format('Y-m-d');
$rangeEnd = $calendarDays[count($calendarDays) - 1]->format('Y-m-d');

$eventStmt = $pdo->prepare(
    'SELECT id, title, description, start_date, end_date, location, color
     FROM calendar_events
     WHERE user_id = :user_id
       AND (
           DATE(start_date) BETWEEN :range_start AND :range_end
           OR DATE(end_date) BETWEEN :range_start2 AND :range_end2
       )
     ORDER BY start_date ASC'
);
$eventStmt->execute([
    'user_id' => $userId,
    'range_start' => $rangeStart,
    'range_end' => $rangeEnd,
    'range_start2' => $rangeStart,
    'range_end2' => $rangeEnd,
]);
$events = $eventStmt->fetchAll();

$eventsByDate = [];
foreach ($events as $event) {
    $eventStart = date('Y-m-d', strtotime($event['start_date']));
    $eventEnd = !empty($event['end_date']) ? date('Y-m-d', strtotime($event['end_date'])) : $eventStart;

    $current = new DateTimeImmutable($eventStart);
    $end = new DateTimeImmutable($eventEnd);
    while ($current <= $end) {
        $dateKey = $current->format('Y-m-d');
        $eventsByDate[$dateKey][] = $event;
        $current = $current->modify('+1 day');
    }
}

$taskStmt = $pdo->prepare(
    'SELECT id, title, status, deadline
     FROM tasks
     WHERE user_id = :user_id
       AND deadline IS NOT NULL
       AND DATE(deadline) BETWEEN :range_start AND :range_end
     ORDER BY deadline ASC'
);
$taskStmt->execute([
    'user_id' => $userId,
    'range_start' => $rangeStart,
    'range_end' => $rangeEnd,
]);
$tasksByDate = [];
foreach ($taskStmt->fetchAll() as $task) {
    $dateKey = date('Y-m-d', strtotime($task['deadline']));
    $tasksByDate[$dateKey][] = $task;
}

$overdueStmt = $pdo->prepare(
    'SELECT id, title, deadline, status
     FROM tasks
     WHERE user_id = :user_id
       AND status != "done"
       AND deadline IS NOT NULL
       AND deadline < NOW()
     ORDER BY deadline ASC'
);
$overdueStmt->execute(['user_id' => $userId]);
$overdueTasks = $overdueStmt->fetchAll();

function formatDateTimeId($value): string {
    if (empty($value)) {
        return '';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $timestamp);
}

function formatDisplayDate($value): string {
    if (empty($value)) {
        return '';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }
    return date('d M Y, H:i', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar | MySmartHub</title>
    <link rel="icon" type="image/png" href="../../assets/img/myis.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #0f172a; color: #f8fafc; }
        .calendar-shell { max-width: 1180px; margin: 2rem auto; padding: 0 1rem 3rem; }
        .calendar-topbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .calendar-title { font-size: clamp(1.6rem, 2vw, 2.3rem); }
        .calendar-nav { display: flex; align-items: center; gap: 0.75rem; }
        .calendar-month { font-size: 1.25rem; font-weight: 600; }
        .calendar-btn { border: 1px solid rgba(148, 163, 184, 0.5); background: rgba(15, 23, 42, 0.75); color: #e2e8f0; border-radius: 10px; padding: 0.6rem 0.9rem; }
        .calendar-grid { display: grid; grid-template-columns: 1.5fr 0.8fr; gap: 1.5rem; }
        .calendar-panel, .calendar-side { background: rgba(30, 41, 59, 0.85); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 18px; padding: 1.2rem; box-shadow: 0 15px 40px rgba(15, 23, 42, 0.25); }
        .calendar-weekdays, .calendar-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .calendar-weekdays { margin-bottom: 0.75rem; }
        .calendar-weekday { text-align: center; padding: 0.75rem 0.25rem; color: #cbd5e1; font-size: 0.82rem; font-weight: 600; text-transform: uppercase; }
        .calendar-day { min-height: 130px; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(148, 163, 184, 0.12); padding: 0.7rem 0.55rem; }
        .calendar-day.other-month { opacity: 0.45; }
        .calendar-day.today { outline: 2px solid rgba(59, 130, 246, 0.8); }
        .day-number { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; }
        .mini-list { display: flex; flex-direction: column; gap: 0.35rem; }
        .mini-item { display: block; background: rgba(59, 130, 246, 0.15); border-left: 3px solid #60a5fa; color: #dbeafe; border-radius: 8px; padding: 0.28rem 0.45rem; font-size: 0.72rem; line-height: 1.35; }
        .mini-item.task { background: rgba(251, 191, 36, 0.12); border-left-color: #fbbf24; color: #fef3c7; }
        .mini-item.deadline { background: rgba(239, 68, 68, 0.12); border-left-color: #f87171; color: #fecaca; }
        .section-title { font-size: 1.1rem; margin-bottom: 0.9rem; }
        .alert-box { border-radius: 14px; padding: 0.9rem 1rem; margin-bottom: 1.1rem; border: 1px solid rgba(248, 113, 113, 0.4); background: rgba(239, 68, 68, 0.08); color: #fecaca; }
        .alert-box.success { border-color: rgba(34, 197, 94, 0.4); background: rgba(34, 197, 94, 0.09); color: #bbf7d0; }
        .notification-list, .event-list { display: flex; flex-direction: column; gap: 0.8rem; }
        .notify-item, .event-item { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(148, 163, 184, 0.18); border-radius: 12px; padding: 0.8rem 0.9rem; }
        .notify-item h4 { margin: 0 0 0.2rem; font-size: 0.95rem; }
        .notify-item small, .event-item small { color: #cbd5e1; }
        .event-form { display: grid; gap: 0.85rem; }
        .field { display: flex; flex-direction: column; gap: 0.35rem; }
        .field label { font-size: 0.82rem; color: #dfe7f4; }
        .field input, .field textarea { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 10px; background: rgba(15, 23, 42, 0.7); color: #f8fafc; padding: 0.7rem 0.8rem; }
        .field textarea { min-height: 90px; resize: vertical; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }
        .empty-state { color: #cbd5e1; font-size: 0.9rem; }
        @media (max-width: 960px) {
            .calendar-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .two-col { grid-template-columns: 1fr; }
            .calendar-day { min-height: 110px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo"><a href="../../dashboard.php">MySmartHub</a></div>
            
            <div class="navbar-cta">
                <a href="../../dashboard.php" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </nav>

    <main class="calendar-shell">
        <div class="calendar-topbar">
            <h1 class="calendar-title">Kalender</h1>
            <div class="calendar-nav">
                <a class="calendar-btn" href="?month=<?= $prevMonth->format('n') ?>&year=<?= $prevMonth->format('Y') ?>">‹ Sebelumnya</a>
                <div class="calendar-month"><?= date('F Y', $selectedDate->getTimestamp()) ?></div>
                <a class="calendar-btn" href="?month=<?= $nextMonth->format('n') ?>&year=<?= $nextMonth->format('Y') ?>">Berikutnya ›</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-box">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success !== ''): ?>
            <div class="alert-box success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($overdueTasks)): ?>
            <div class="alert-box">
                <strong>⚠️ Deadline sudah lewat:</strong>
                <ul style="margin: 0.4rem 0 0 1.2rem;">
                    <?php foreach ($overdueTasks as $task): ?>
                        <li>
                            <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                            <small>(<?= htmlspecialchars(formatDisplayDate($task['deadline']), ENT_QUOTES, 'UTF-8') ?>)</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="calendar-grid">
            <section class="calendar-panel">
                <div class="calendar-weekdays">
                    <div class="calendar-weekday">Sen</div>
                    <div class="calendar-weekday">Sel</div>
                    <div class="calendar-weekday">Rab</div>
                    <div class="calendar-weekday">Kam</div>
                    <div class="calendar-weekday">Jum</div>
                    <div class="calendar-weekday">Sab</div>
                    <div class="calendar-weekday">Min</div>
                </div>

                <div class="calendar-days">
                    <?php foreach ($calendarDays as $day): ?>
                        <?php
                        $dayKey = $day->format('Y-m-d');
                        $isCurrentMonth = $day->format('n') == $month;
                        $isToday = $dayKey === date('Y-m-d');
                        $dayEvents = $eventsByDate[$dayKey] ?? [];
                        $dayTasks = $tasksByDate[$dayKey] ?? [];
                        ?>
                        <div class="calendar-day <?= !$isCurrentMonth ? 'other-month' : '' ?> <?= $isToday ? 'today' : '' ?>">
                            <div class="day-number"><?= $day->format('d') ?></div>
                            <div class="mini-list">
                                <?php foreach ($dayTasks as $task): ?>
                                    <span class="mini-item <?= $task['status'] === 'done' ? 'task' : 'deadline' ?>">
                                        <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php foreach ($dayEvents as $event): ?>
                                    <span class="mini-item">
                                        <?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <aside class="calendar-side">
                <h2 class="section-title"><?= $editingEvent ? 'Edit Event' : 'Tambah Event' ?></h2>
                <form method="post" class="event-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="<?= $editingEvent ? 'update_event' : 'create_event' ?>">
                    <?php if ($editingEvent): ?>
                        <input type="hidden" name="event_id" value="<?= (int) $editingEvent['id'] ?>">
                    <?php endif; ?>

                    <div class="field">
                        <label for="event-title">Judul</label>
                        <input type="text" id="event-title" name="title" value="<?= htmlspecialchars($editingEvent['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="two-col">
                        <div class="field">
                            <label for="event-start">Mulai</label>
                            <input type="datetime-local" id="event-start" name="start_date" value="<?= htmlspecialchars(formatDateTimeId($editingEvent['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="field">
                            <label for="event-end">Selesai</label>
                            <input type="datetime-local" id="event-end" name="end_date" value="<?= htmlspecialchars(formatDateTimeId($editingEvent['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label for="event-location">Lokasi</label>
                        <input type="text" id="event-location" name="location" value="<?= htmlspecialchars($editingEvent['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="event-description">Deskripsi</label>
                        <textarea id="event-description" name="description"><?= htmlspecialchars($editingEvent['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary"><?= $editingEvent ? 'Simpan Perubahan' : 'Simpan Event' ?></button>
                        <?php if ($editingEvent): ?>
                            <a href="index.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div style="margin-top: 1.5rem;">
                    <h2 class="section-title">Notifikasi Deadline</h2>
                    <?php if (empty($overdueTasks)): ?>
                        <div class="empty-state">Belum ada tugas yang melewati deadline.</div>
                    <?php else: ?>
                        <div class="notification-list">
                            <?php foreach (array_slice($overdueTasks, 0, 5) as $task): ?>
                                <div class="notify-item">
                                    <h4><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <small>Lewat sejak <?= htmlspecialchars(formatDisplayDate($task['deadline']), ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 1.5rem;">
                    <h2 class="section-title">Agenda Bulan Ini</h2>
                    <?php
                    $agenda = $events ?? [];
                    if (empty($agenda)): ?>
                        <div class="empty-state">Belum ada agenda untuk bulan ini.</div>
                    <?php else: ?>
                        <div class="event-list">
                            <?php foreach (array_slice($events, 0, 5) as $event): ?>
                                <div class="event-item">
                                    <strong><?= htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                    <small><?= htmlspecialchars(formatDisplayDate($event['start_date']), ENT_QUOTES, 'UTF-8') ?></small>
                                    <div style="margin-top: 0.6rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                                        <a href="?month=<?= $month ?>&year=<?= $year ?>&edit_event=<?= (int) $event['id'] ?>" class="btn btn-secondary" style="padding:0.4rem 0.7rem; font-size:0.75rem;">Edit</a>
                                        <form method="post" style="display:inline; margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding:0.4rem 0.7rem; font-size:0.75rem;" onclick="return confirm('Hapus event ini?');">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>

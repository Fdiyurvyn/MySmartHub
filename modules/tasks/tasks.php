<?php
require_once __DIR__ . '/../../config/database.php';

function normalizeDeadlineForDatabase(?string $deadline): ?string {
    if ($deadline === null) {
        return null;
    }

    $deadline = trim($deadline);
    if ($deadline === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $deadline)) {
        return date('Y-m-d H:i:s', strtotime($deadline));
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(?::\d{2})?$/', $deadline)) {
        return date('Y-m-d H:i:s', strtotime($deadline));
    }

    return $deadline;
}

function formatDeadlineForInput(?string $deadline): string {
    if ($deadline === null || trim($deadline) === '') {
        return '';
    }

    $timestamp = strtotime($deadline);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $timestamp);
}

function sanitizeTaskInput(array $data): array {
    $sanitized = [];

    if (isset($data['title'])) {
        $sanitized['title'] = trim((string) $data['title']);
    }

    if (isset($data['description'])) {
        $sanitized['description'] = trim((string) $data['description']);
    }

    if (isset($data['priority'])) {
        $sanitized['priority'] = in_array($data['priority'], ['low', 'medium', 'high'], true) ? $data['priority'] : 'medium';
    }

    if (isset($data['status'])) {
        $sanitized['status'] = in_array($data['status'], ['todo', 'doing', 'done'], true) ? $data['status'] : 'todo';
    }

    if (isset($data['deadline'])) {
        $deadline = trim((string) $data['deadline']);
        $sanitized['deadline'] = $deadline !== '' ? normalizeDeadlineForDatabase($deadline) : null;
    }

    if (isset($data['category_id'])) {
        $categoryId = filter_var($data['category_id'], FILTER_VALIDATE_INT);
        $sanitized['category_id'] = $categoryId !== false ? $categoryId : null;
    }

    return $sanitized;
}

function validateTaskInput(array $data): array {
    $errors = [];

    if (empty($data['title'])) {
        $errors[] = 'Judul tugas wajib diisi.';
    } elseif (mb_strlen($data['title'], 'UTF-8') > 200) {
        $errors[] = 'Judul maksimal 200 karakter.';
    }

    if (!empty($data['description']) && mb_strlen($data['description'], 'UTF-8') > 1000) {
        $errors[] = 'Deskripsi maksimal 1000 karakter.';
    }

    if (isset($data['deadline']) && $data['deadline'] !== null && strtotime($data['deadline']) === false) {
        $errors[] = 'Format deadline tidak valid.';
    }

    return $errors;
}

function listTasksForUser(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT t.id, t.title, t.description, t.priority, t.status, t.deadline, t.created_at, t.updated_at,
                c.id AS category_id, c.name AS category_name, c.color AS category_color
         FROM tasks t
         LEFT JOIN task_categories c ON c.id = t.category_id AND c.user_id = :user_id_join
         WHERE t.user_id = :user_id
         ORDER BY t.created_at DESC'
    );
    $stmt->execute(['user_id' => $userId, 'user_id_join' => $userId]);
    return $stmt->fetchAll();
}

function getTaskByIdForUser(PDO $pdo, int $userId, int $taskId): array|false {
    $stmt = $pdo->prepare(
        'SELECT id, title, description, priority, status, deadline, category_id
         FROM tasks
         WHERE id = :task_id AND user_id = :user_id'
    );
    $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
    return $stmt->fetch();
}

function createTaskForUser(PDO $pdo, int $userId, array $input): int {
    $data = sanitizeTaskInput($input);
    $errors = validateTaskInput($data);

    if ($errors !== []) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $stmt = $pdo->prepare(
        'INSERT INTO tasks (user_id, category_id, title, description, priority, status, deadline)
         VALUES (:user_id, :category_id, :title, :description, :priority, :status, :deadline)'
    );

    $stmt->execute([
        'user_id' => $userId,
        'category_id' => $data['category_id'] ?? null,
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'priority' => $data['priority'] ?? 'medium',
        'status' => $data['status'] ?? 'todo',
        'deadline' => $data['deadline'] ?? null,
    ]);

    return (int) $pdo->lastInsertId();
}

function updateTaskForUser(PDO $pdo, int $userId, int $taskId, array $input): bool {
    $data = sanitizeTaskInput($input);
    $errors = validateTaskInput($data);

    if ($errors !== []) {
        throw new InvalidArgumentException(implode(' ', $errors));
    }

    $stmt = $pdo->prepare(
        'UPDATE tasks
         SET title = :title,
             description = :description,
             priority = :priority,
             status = :status,
             deadline = :deadline,
             category_id = :category_id
         WHERE id = :task_id AND user_id = :user_id'
    );

    $stmt->execute([
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'priority' => $data['priority'] ?? 'medium',
        'status' => $data['status'] ?? 'todo',
        'deadline' => $data['deadline'] ?? null,
        'category_id' => $data['category_id'] ?? null,
        'task_id' => $taskId,
        'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
}

function deleteTaskForUser(PDO $pdo, int $userId, int $taskId): bool {
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :task_id AND user_id = :user_id');
    $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

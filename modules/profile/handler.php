<?php
require_once __DIR__ . '/../../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/profile/index.php');
}

$userId = (int) $_SESSION['user_id'];

// Verify CSRF Token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken((string) $_POST['csrf_token'])) {
    $_SESSION['profile_error'] = 'CSRF token tidak valid.';
    redirect('modules/profile/index.php');
}

$fullName = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';

// Validation for name
if ($fullName === '') {
    $_SESSION['profile_error'] = 'Nama lengkap wajib diisi.';
    redirect('modules/profile/index.php');
}

if (mb_strlen($fullName, 'UTF-8') > 100) {
    $_SESSION['profile_error'] = 'Nama lengkap maksimal 100 karakter.';
    redirect('modules/profile/index.php');
}

$pdo = getDatabaseConnection();

try {
    // Fetch current user photo to delete it later if replaced
    $stmt = $pdo->prepare('SELECT photo FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    $oldPhoto = $user['photo'] ?? 'default.png';
    $newPhoto = null;

    // Check if photo file is uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];

        // Validate upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Terjadi kesalahan saat mengunggah file.');
        }

        // Validate size (max 2MB as per Rule 4)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Ukuran file maksimal adalah 2MB.');
        }

        // Validate extensions (jpg, jpeg, png, gif as per Rule 4)
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExts, true)) {
            throw new Exception('Format file tidak didukung. Hanya diperbolehkan: jpg, jpeg, png, gif.');
        }

        // Validate MIME type using finfo
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/pjpeg', 'image/x-png'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                throw new Exception('MIME-type file tidak valid. Pastikan Anda mengunggah gambar asli.');
            }
        }

        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique name
        $newPhoto = 'profile_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
        $targetPath = $uploadDir . $newPhoto;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Gagal menyimpan file di server.');
        }

        // Delete old profile picture if not default
        if ($oldPhoto !== 'default.png' && !empty($oldPhoto)) {
            $oldPhotoPath = $uploadDir . $oldPhoto;
            if (file_exists($oldPhotoPath)) {
                unlink($oldPhotoPath);
            }
        }
    }

    // Update DB
    if ($newPhoto !== null) {
        $stmtUpdate = $pdo->prepare('UPDATE users SET full_name = :full_name, photo = :photo WHERE id = :id');
        $stmtUpdate->execute([
            'full_name' => $fullName,
            'photo' => $newPhoto,
            'id' => $userId
        ]);
    } else {
        $stmtUpdate = $pdo->prepare('UPDATE users SET full_name = :full_name WHERE id = :id');
        $stmtUpdate->execute([
            'full_name' => $fullName,
            'id' => $userId
        ]);
    }

    $_SESSION['profile_success'] = 'Profil Anda berhasil diperbarui!';
} catch (Exception $e) {
    $_SESSION['profile_error'] = $e->getMessage();
}

redirect('modules/profile/index.php');

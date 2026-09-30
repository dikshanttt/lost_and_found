<?php
/**
 * CivicFind – Delete Item Report Handler (user/delete-item.php)
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('/user/my-reports.php'));
    exit;
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$user = current_user();

if ($id <= 0) {
    header('Location: ' . app_url('/user/my-reports.php'));
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('error', 'Item report not found.');
    header('Location: ' . app_url('/user/my-reports.php'));
    exit;
}

if ($item['user_id'] != $user['id'] && !is_admin()) {
    set_flash('error', 'You do not have permission to delete this report.');
    header('Location: ' . app_url('/user/my-reports.php'));
    exit;
}

// Delete item
$del = $db->prepare("DELETE FROM items WHERE id = :id");
$del->execute(['id' => $id]);

// Delete uploaded image file if exists
if (!empty($item['image_path'])) {
    $file = __DIR__ . '/../' . $item['image_path'];
    if (file_exists($file)) {
        @unlink($file);
    }
}

set_flash('success', 'The item report has been removed.');
header('Location: ' . app_url('/user/my-reports.php'));
exit;

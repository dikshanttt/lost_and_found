<?php
/**
 * CivicFind – Edit Item Report (user/edit-item.php)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_login();

$db = get_db();
$user = current_user();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /user/my-reports.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('error', 'Item report not found.');
    header('Location: /user/my-reports.php');
    exit;
}

// Ownership check
if ($item['user_id'] != $user['id'] && !is_admin()) {
    set_flash('error', 'You do not have permission to edit this report.');
    header('Location: /browse.php');
    exit;
}

$categories = get_categories($db);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    $item_date   = $_POST['item_date'] ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');
    $status      = $_POST['status'] ?? $item['status'];

    $allowed_statuses = ['active', 'claimed', 'returned', 'closed'];
    if (!in_array($status, $allowed_statuses, true)) {
        $status = $item['status'];
    }

    if (empty($title) || empty($location) || empty($description) || $category_id <= 0) {
        $error = 'Please fill out all required fields.';
    } else {
        $image_path = $item['image_path'];
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_item_image($_FILES['image']);
            if ($uploaded === false) {
                $error = 'Invalid image file. Please upload a valid JPG, PNG, or WebP image under 2MB.';
            } else {
                $image_path = $uploaded;
            }
        }

        if (empty($error)) {
            $upd = $db->prepare("
                UPDATE items
                SET category_id = :cid, title = :title, description = :desc,
                    location = :loc, item_date = :date, image_path = :img, status = :status
                WHERE id = :id
            ");
            $upd->execute([
                'cid'    => $category_id,
                'title'  => $title,
                'desc'   => $description,
                'loc'    => $location,
                'date'   => $item_date,
                'img'    => $image_path,
                'status' => $status,
                'id'     => $id
            ]);

            set_flash('success', 'Report updated successfully!');
            header("Location: /item-detail.php?id=$id");
            exit;
        }
    }
}

$page_title = 'Edit Item Report – CivicFind';
$current_page = 'my-reports';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="max-width: 720px; padding: 40px 24px;">
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Edit Report: <?= e($item['title']) ?></h1>
        <p style="color: var(--slate-500); margin-top: 4px;">Change the report details or status.</p>
    </div>

    <?php if ($error): ?>
        <div class="flash flash-error" style="margin-bottom: 24px;"><?= e($error) ?></div>
    <?php endif; ?>

    <div style="background: var(--white); border: 1px solid var(--slate-200); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm);">
        <form action="/user/edit-item.php?id=<?= $id ?>" method="post" enctype="multipart/form-data">
            <?= csrf_token() ?>

            <div class="form-group">
                <label>Report Type</label>
                <div style="padding: 10px 0;">
                    <?= type_badge($item['type']) ?>
                    <span style="font-size: 13px; color: var(--slate-500); margin-left: 8px;">(Type cannot be altered once created)</span>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Item status *</label>
                <select id="status" name="status" required>
                    <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active (Listing visible for searches)</option>
                    <option value="claimed" <?= $item['status'] === 'claimed' ? 'selected' : '' ?>>Claimed (Ownership verified)</option>
                    <option value="returned" <?= $item['status'] === 'returned' ? 'selected' : '' ?>>Returned to Owner</option>
                    <option value="closed" <?= $item['status'] === 'closed' ? 'selected' : '' ?>>Closed / Archived</option>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Item Title / Name *</label>
                <input type="text" id="title" name="title" required value="<?= e($_POST['title'] ?? $item['title']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_date">Date Lost / Found *</label>
                    <input type="date" id="item_date" name="item_date" required value="<?= e($_POST['item_date'] ?? $item['item_date']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location / Area *</label>
                <input type="text" id="location" name="location" required value="<?= e($_POST['location'] ?? $item['location']) ?>">
            </div>

            <div class="form-group">
                <label for="description">Detailed Description *</label>
                <textarea id="description" name="description" rows="4" required><?= e($_POST['description'] ?? $item['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Replace Photograph (Optional)</label>
                <?php if ($item['image_path']): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="/<?= e($item['image_path']) ?>" alt="Current image" style="height: 90px; border-radius: 6px;">
                    </div>
                <?php endif; ?>
                <div class="file-upload-area">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <div style="font-size: 24px; margin-bottom: 6px;">📷</div>
                    <div class="file-upload-text">Click to upload replacement image</div>
                    <div class="file-preview"></div>
                </div>
            </div>

            <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
                <a href="/user/my-reports.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

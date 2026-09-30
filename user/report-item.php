<?php
/**
 * CivicFind – Report Item (user/report-item.php)
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth.php';
// mailer removed for demo;

require_login();

$db = get_db();
$categories = get_categories($db);
$user = current_user();

$initial_type = ($_GET['type'] ?? 'lost') === 'found' ? 'found' : 'lost';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type        = ($_POST['type'] ?? '') === 'found' ? 'found' : 'lost';
    $title       = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    $item_date   = $_POST['item_date'] ?? date('Y-m-d');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($location) || empty($description) || $category_id <= 0) {
        $error = 'Please complete all required fields.';
    } else {
        $image_path = null;
        if (!empty($_FILES['image']['name'])) {
            $uploaded = upload_item_image($_FILES['image']);
            if ($uploaded === false) {
                $error = 'Invalid image file. Please upload a valid JPG, PNG, or WebP image under 2MB.';
            } else {
                $image_path = $uploaded;
            }
        }

        if (empty($error)) {
            $stmt = $db->prepare("
                INSERT INTO items (user_id, category_id, type, title, description, location, item_date, image_path, status)
                VALUES (:uid, :cid, :type, :title, :desc, :loc, :date, :img, 'active')
            ");
            $stmt->execute([
                'uid'   => $user['id'],
                'cid'   => $category_id,
                'type'  => $type,
                'title' => $title,
                'desc'  => $description,
                'loc'   => $location,
                'date'  => $item_date,
                'img'   => $image_path
            ]);

            $new_item_id = (int)$db->lastInsertId();
            $adminEmail = env_value('ADMIN_NOTIFICATION_EMAIL');
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                email_user($adminEmail, 'Administrator', 'New item report', 'A new item report was submitted', 'A new ' . strtoupper($type) . ' report, "' . $title . '", was submitted. Sign in to the admin dashboard to review it.');
            }
            set_flash('success', 'Your ' . strtoupper($type) . ' report was added.');
            header('Location: ' . app_url('/item-detail.php?id=' . $new_item_id));
            exit;
        }
    }
}

$page_title   = 'Report an Item – CivicFind';
$current_page = $initial_type === 'found' ? 'report-found' : 'report-lost';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 720px; padding: 40px 24px;">
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Report an Item</h1>
        <p style="color: var(--slate-500); margin-top: 4px;">Add the details so others can search for it.</p>
    </div>

    <?php if ($error): ?>
        <div class="flash flash-error" style="margin-bottom: 24px;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div style="background: var(--white); border: 1px solid var(--slate-200); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm);">
        <form action="<?= e(app_url('/user/report-item.php')) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_token() ?>

            <!-- Type Selector -->
            <div class="form-group">
                <label>Report Type *</label>
                <div class="type-toggle">
                    <label class="<?= $initial_type === 'lost' ? 'selected' : '' ?>">
                        <input type="radio" name="type" value="lost" <?= $initial_type === 'lost' ? 'checked' : '' ?>>
                        🔍 I Lost an Item
                    </label>
                    <label class="<?= $initial_type === 'found' ? 'selected' : '' ?>">
                        <input type="radio" name="type" value="found" <?= $initial_type === 'found' ? 'checked' : '' ?>>
                        ✨ I Found an Item
                    </label>
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label for="title">Item name *</label>
                <input type="text" id="title" name="title" required placeholder="e.g. iPhone 14 Pro - Space Gray, Blue Carabiner Keys" value="<?= e($_POST['title'] ?? '') ?>">
            </div>

            <!-- Category & Date -->
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_date">Date Lost / Found *</label>
                    <input type="date" id="item_date" name="item_date" required value="<?= e($_POST['item_date'] ?? date('Y-m-d')) ?>">
                </div>
            </div>

            <!-- Location -->
            <div class="form-group">
                <label for="location">Where was it lost or found? *</label>
                <input type="text" id="location" name="location" required placeholder="e.g. Central Library 2nd Floor, Downtown Station Bus 41" value="<?= e($_POST['location'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="4" required placeholder="Add the color, brand, or other details..."><?= e($_POST['description'] ?? '') ?></textarea>
                <p class="form-help">For a found item, consider keeping one identifying detail private. It may help when reviewing a claim.</p>
            </div>

            <!-- Photo Upload -->
            <div class="form-group">
                <label>Item Photograph (Optional)</label>
                <div class="file-upload-area">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <div style="font-size: 32px; margin-bottom: 8px;">📷</div>
                <div class="file-upload-text" style="font-weight: 500;">Choose an image or drop one here</div>
                <p style="font-size: 12px; margin-top: 4px; color: var(--slate-400);">JPG, PNG, or WebP. Maximum size: 2 MB.</p>
                    <div class="file-preview"></div>
                </div>
            </div>

            <div style="margin-top: 32px; display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?= e(app_url('/browse.php')) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">Add Report</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * CivicFind – Admin Items Manager (admin/items.php)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_admin();

$db = get_db();

// Handle quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $item_id = (int)($_POST['item_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, ['active', 'claimed', 'returned', 'closed'], true)) {
        $u = $db->prepare("UPDATE items SET status = :st WHERE id = :id");
        $u->execute(['st' => $new_status, 'id' => $item_id]);
        set_flash('success', "Item #$item_id status changed to $new_status.");
    }
    header('Location: /admin/items.php');
    exit;
}

// Fetch all items with category and reporter
$items = $db->query("
    SELECT i.*, c.name AS category_name, u.full_name AS reporter_name, u.email AS reporter_email
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u ON u.id = i.user_id
    ORDER BY i.created_at DESC
")->fetchAll();

$page_title   = 'Manage Items – CivicFind';
$current_page = 'admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding: 36px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Manage Items</h1>
            <p style="color: var(--slate-500); margin-top: 4px;">View reports and change their status.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="/admin/dashboard.php" class="btn btn-outline">&larr; Dashboard</a>
            <a href="/user/report-item.php" class="btn btn-primary">+ Add Report</a>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Reporter</th>
                    <th>Location and Date</th>
                    <th>Status</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <a href="/item-detail.php?id=<?= $it['id'] ?>" style="font-weight: 600;">
                            <?= e($it['title']) ?>
                        </a>
                        <div style="font-size: 12px; color: var(--slate-400);">ID #<?= $it['id'] ?></div>
                    </td>
                    <td><?= type_badge($it['type']) ?></td>
                    <td><?= e($it['category_name']) ?></td>
                    <td>
                        <div><?= e($it['reporter_name']) ?></div>
                        <div style="font-size: 12px; color: var(--slate-400);"><?= e($it['reporter_email']) ?></div>
                    </td>
                    <td>
                        <div>📍 <?= e($it['location']) ?></div>
                        <div style="font-size: 12px; color: var(--slate-500);"><?= date('M j, Y', strtotime($it['item_date'])) ?></div>
                    </td>
                    <td><?= status_badge($it['status']) ?></td>
                    <td>
                        <form action="/admin/items.php" method="post" style="display: flex; gap: 6px;">
                            <?= csrf_token() ?>
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                            <select name="status" style="padding: 4px 8px; font-size: 13px;" onchange="this.form.submit()">
                                <option value="active" <?= $it['status'] === 'active' ? 'selected' : '' ?>>active</option>
                                <option value="claimed" <?= $it['status'] === 'claimed' ? 'selected' : '' ?>>claimed</option>
                                <option value="returned" <?= $it['status'] === 'returned' ? 'selected' : '' ?>>returned</option>
                                <option value="closed" <?= $it['status'] === 'closed' ? 'selected' : '' ?>>closed</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="/user/edit-item.php?id=<?= $it['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form action="/user/delete-item.php" method="post" onsubmit="return confirm('Delete this report?');">
                                <?= csrf_token() ?>
                                <input type="hidden" name="id" value="<?= $it['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

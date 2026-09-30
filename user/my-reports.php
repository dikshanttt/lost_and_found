<?php
/**
 * CivicFind – User Portal: My Reports & Claims (user/my-reports.php)
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth.php';

require_login();

$user = current_user();
$db = get_db();

$tab = ($_GET['tab'] ?? 'reports') === 'claims' ? 'claims' : 'reports';

// Fetch reported items
$items_stmt = $db->prepare("
    SELECT i.*, c.name AS category_name,
           (SELECT COUNT(*) FROM claims cl WHERE cl.item_id = i.id) AS claim_count
    FROM items i
    JOIN categories c ON c.id = i.category_id
    WHERE i.user_id = :uid
    ORDER BY i.created_at DESC
");
$items_stmt->execute(['uid' => $user['id']]);
$my_items = $items_stmt->fetchAll();

// Fetch my claims
$claims_stmt = $db->prepare("
    SELECT cl.*, i.title AS item_title, i.type AS item_type, i.image_path, c.name AS category_name
    FROM claims cl
    JOIN items i ON i.id = cl.item_id
    JOIN categories c ON c.id = i.category_id
    WHERE cl.claimant_id = :uid
    ORDER BY cl.created_at DESC
");
$claims_stmt->execute(['uid' => $user['id']]);
$my_claims = $claims_stmt->fetchAll();

$page_title = 'My Reports & Claims – CivicFind';
$current_page = 'my-reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 36px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">My Reports and Claims</h1>
            <p style="color: var(--slate-500); margin-top: 4px;">View and update your reports, and check your claims.</p>
        </div>
        <a href="<?= e(app_url('/user/report-item.php')) ?>" class="btn btn-primary">+ Report Another Item</a>
    </div>

    <!-- Tab navigation -->
    <div class="user-reports-tabs">
        <a href="?tab=reports" class="<?= $tab === 'reports' ? 'active' : '' ?>">
            My Reported Items (<?= count($my_items) ?>)
        </a>
        <a href="?tab=claims" class="<?= $tab === 'claims' ? 'active' : '' ?>">
            My Ownership Claims (<?= count($my_claims) ?>)
        </a>
    </div>

    <?php if ($tab === 'reports'): ?>
        <?php if (empty($my_items)): ?>
            <div class="empty-state">
                <h3>You haven't reported any items yet</h3>
                <p>Add a report if you lost something or found an item.</p>
                <div style="margin-top: 16px;">
                    <a href="<?= e(app_url('/user/report-item.php')) ?>" class="btn btn-blue">Report Item Now</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Date / Location</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_items as $item): ?>
                        <tr>
                            <td style="font-weight: 600;">
                                <a href="<?= e(app_url('/item-detail.php?id=' . (int)$item['id'])) ?>" style="color: var(--navy-800);">
                                    <?= e($item['title']) ?>
                                </a>
                                <div style="font-size: 12px; color: var(--slate-400); font-weight: normal;">
                                    Reported <?= time_ago($item['created_at']) ?>
                                </div>
                            </td>
                            <td><?= type_badge($item['type']) ?></td>
                            <td><?= e($item['category_name']) ?></td>
                            <td>
                                <div>📍 <?= e($item['location']) ?></div>
                                <div style="font-size: 12px; color: var(--slate-500);"><?= date('M j, Y', strtotime($item['item_date'])) ?></div>
                            </td>
                            <td><?= status_badge($item['status']) ?></td>
                            <td>
                                <?php if ($item['type'] === 'found'): ?>
                                    <span style="font-weight: 600;"><?= (int)$item['claim_count'] ?></span>
                                <?php else: ?>
                                    <span style="color: var(--slate-400);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?= e(app_url('/user/edit-item.php?id=' . (int)$item['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <form action="<?= e(app_url('/user/delete-item.php')) ?>" method="post" onsubmit="return confirm('Remove this report?');" style="display:inline;">
                                        <?= csrf_token() ?>
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Claims Tab -->
        <?php if (empty($my_claims)): ?>
            <div class="empty-state">
                <h3>No claims submitted yet</h3>
                <p>If a found item may be yours, you can submit a claim for review.</p>
                <div style="margin-top: 16px;">
                    <a href="<?= e(app_url('/browse.php?type=found')) ?>" class="btn btn-teal">Browse Found Items</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Found Item</th>
                            <th>Category</th>
                            <th>Claim Statement</th>
                            <th>Status</th>
                            <th>Review Notes</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_claims as $cl): ?>
                        <tr>
                            <td>
                                <a href="<?= e(app_url('/item-detail.php?id=' . (int)$cl['item_id'])) ?>" style="font-weight: 600; color: var(--navy-800);">
                                    <?= e($cl['item_title']) ?>
                                </a>
                            </td>
                            <td><?= e($cl['category_name']) ?></td>
                            <td style="max-width: 260px;">
                                <div style="font-size: 13px;"><?= e(truncate_text($cl['claim_message'], 90)) ?></div>
                            </td>
                            <td><?= status_badge($cl['status']) ?></td>
                            <td>
                                <?php if (!empty($cl['admin_note'])): ?>
                                    <div style="font-size: 13px; color: var(--slate-700); background: var(--slate-100); padding: 4px 8px; border-radius: var(--radius-sm);">
                                        <?= e($cl['admin_note']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--slate-400); font-size: 13px;">Pending review</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px; color: var(--slate-500); white-space: nowrap;">
                                <?= time_ago($cl['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

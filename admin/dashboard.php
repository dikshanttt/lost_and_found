<?php
/**
 * CivicFind – Administrator Dashboard (admin/dashboard.php)
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth.php';

require_admin();

$db = get_db();

// Aggregate stats
$total_items    = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$active_lost    = (int)$db->query("SELECT COUNT(*) FROM items WHERE type = 'lost' AND status = 'active'")->fetchColumn();
$active_found   = (int)$db->query("SELECT COUNT(*) FROM items WHERE type = 'found' AND status = 'active'")->fetchColumn();
$pending_claims = (int)$db->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetchColumn();
$total_users    = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// Recent pending claims
$pending_list = $db->query("
    SELECT cl.*, i.title AS item_title, u.full_name AS claimant_name
    FROM claims cl
    JOIN items i ON i.id = cl.item_id
    JOIN users u ON u.id = cl.claimant_id
    WHERE cl.status = 'pending'
    ORDER BY cl.created_at DESC LIMIT 5
")->fetchAll();

// Recent items reported
$recent_items = $db->query("
    SELECT i.*, c.name AS category_name, u.full_name AS reporter_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u ON u.id = i.user_id
    ORDER BY i.created_at DESC LIMIT 5
")->fetchAll();

$page_title   = 'Administrator Dashboard – CivicFind';
$current_page = 'admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 36px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="hero-badge" style="margin-bottom: 8px;">ADMIN</span>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Admin Dashboard</h1>
            <p style="color: var(--slate-500); margin-top: 4px;">Review claims, item reports, and user accounts.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= e(app_url('/admin/claims.php')) ?>" class="btn btn-teal">Claims to review (<?= $pending_claims ?>)</a>
            <a href="<?= e(app_url('/admin/items.php')) ?>" class="btn btn-outline">All Items</a>
            <a href="<?= e(app_url('/admin/users.php')) ?>" class="btn btn-outline">Users</a>
        </div>
    </div>

    <!-- Stats Counter Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" style="color: var(--primary);"><?= $total_items ?></div>
            <div class="stat-label">Total items</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--amber-500);"><?= $active_lost ?></div>
            <div class="stat-label">Active lost reports</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--teal-600);"><?= $active_found ?></div>
            <div class="stat-label">Active found reports</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--amber-500);">
            <div class="stat-value" style="color: #b45309;"><?= $pending_claims ?></div>
            <div class="stat-label">Pending claims</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--navy-800);"><?= $total_users ?></div>
            <div class="stat-label">Users</div>
        </div>
    </div>

    <!-- Recent Pending Claims Section -->
    <div style="margin-top: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--navy-800);">Claims to review</h2>
            <a href="<?= e(app_url('/admin/claims.php')) ?>" class="btn btn-outline btn-sm">View All Claims</a>
        </div>

        <?php if (empty($pending_list)): ?>
            <div class="flash flash-success" style="margin-bottom: 0;">
                There are no pending claims.
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Found Item</th>
                            <th>Claimant</th>
                            <th>Message</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_list as $cl): ?>
                        <tr>
                            <td>#<?= $cl['id'] ?></td>
                            <td>
                                <a href="<?= e(app_url('/item-detail.php?id=' . (int)$cl['item_id'])) ?>" style="font-weight: 600;">
                                    <?= e($cl['item_title']) ?>
                                </a>
                            </td>
                            <td><?= e($cl['claimant_name']) ?></td>
                            <td><?= e(truncate_text($cl['claim_message'], 60)) ?></td>
                            <td><?= time_ago($cl['created_at']) ?></td>
                            <td>
                                <a href="<?= e(app_url('/admin/claims.php?review=' . (int)$cl['id'])) ?>" class="btn btn-teal btn-sm">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Items Section -->
    <div style="margin-top: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--navy-800);">Latest reports</h2>
            <a href="<?= e(app_url('/admin/items.php')) ?>" class="btn btn-outline btn-sm">Manage All Items</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Reporter</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_items as $item): ?>
                    <tr>
                        <td>
                            <a href="<?= e(app_url('/item-detail.php?id=' . (int)$item['id'])) ?>" style="font-weight: 600;">
                                <?= e($item['title']) ?>
                            </a>
                        </td>
                        <td><?= type_badge($item['type']) ?></td>
                        <td><?= e($item['category_name']) ?></td>
                        <td><?= e($item['reporter_name']) ?></td>
                        <td><?= e($item['location']) ?></td>
                        <td><?= status_badge($item['status']) ?></td>
                        <td>
                            <a href="<?= e(app_url('/user/edit-item.php?id=' . (int)$item['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

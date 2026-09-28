<?php
/**
 * CivicFind – Admin User Management (admin/users.php)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_admin();

$db = get_db();
$admin = current_user();

// Handle status toggle (active vs blocked) or role promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $user_id = (int)($_POST['user_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($user_id > 0 && $user_id != $admin['id']) {
        if ($action === 'toggle_status') {
            $current_status = $_POST['current_status'] ?? 'active';
            $new_status = $current_status === 'active' ? 'blocked' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = :st WHERE id = :id");
            $stmt->execute(['st' => $new_status, 'id' => $user_id]);
            set_flash('success', "User status set to $new_status.");
        } elseif ($action === 'toggle_role') {
            $current_role = $_POST['current_role'] ?? 'user';
            $new_role = $current_role === 'user' ? 'admin' : 'user';
            $stmt = $db->prepare("UPDATE users SET role = :r WHERE id = :id");
            $stmt->execute(['r' => $new_role, 'id' => $user_id]);
            set_flash('success', "User role set to $new_role.");
        }
    }

    header('Location: /admin/users.php');
    exit;
}

$users = $db->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM items i WHERE i.user_id = u.id) AS item_count,
           (SELECT COUNT(*) FROM claims cl WHERE cl.claimant_id = u.id) AS claim_count
    FROM users u
    ORDER BY u.created_at DESC
")->fetchAll();

$page_title   = 'Manage Users – CivicFind';
$current_page = 'admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding: 36px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Users</h1>
            <p style="color: var(--slate-500); margin-top: 4px;">View accounts and change roles or status.</p>
        </div>
        <a href="/admin/dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Reports</th>
                    <th>Claims</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td style="font-weight: 600;"><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'badge-claimed' : 'badge-closed' ?>">
                            <?= strtoupper(e($u['role'])) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['status'] === 'active' ? 'badge-found' : 'badge-lost' ?>">
                            <?= strtoupper(e($u['status'])) ?>
                        </span>
                    </td>
                    <td><?= (int)$u['item_count'] ?></td>
                    <td><?= (int)$u['claim_count'] ?></td>
                    <td style="font-size: 12px; color: var(--slate-500);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] != $admin['id']): ?>
                            <div class="actions">
                                <form action="/admin/users.php" method="post" style="display:inline;">
                                    <?= csrf_token() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="current_status" value="<?= e($u['status']) ?>">
                                    <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-teal' ?>">
                                        <?= $u['status'] === 'active' ? 'Block' : 'Activate' ?>
                                    </button>
                                </form>

                                <form action="/admin/users.php" method="post" style="display:inline;">
                                    <?= csrf_token() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="toggle_role">
                                    <input type="hidden" name="current_role" value="<?= e($u['role']) ?>">
                                    <button type="submit" class="btn btn-outline btn-sm">
                                        <?= $u['role'] === 'admin' ? 'Demote' : 'Make Admin' ?>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--slate-400); font-size: 13px;">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

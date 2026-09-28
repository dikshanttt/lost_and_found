<?php
/**
 * CivicFind – Admin Claims Management (admin/claims.php)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/mailer.php';

require_admin();

$db = get_db();
$admin = current_user();

// Handle Claim Resolution (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    $claim_id   = (int)($_POST['claim_id'] ?? 0);
    $requestedAction = $_POST['action'] ?? '';
    if (!in_array($requestedAction, ['approve', 'reject'], true)) {
        http_response_code(400);
        exit('Invalid claim action.');
    }
    $action     = $requestedAction === 'approve' ? 'approved' : 'rejected';
    $admin_note = trim($_POST['admin_note'] ?? '');

    $cstmt = $db->prepare("SELECT * FROM claims WHERE id = :id");
    $cstmt->execute(['id' => $claim_id]);
    $claim = $cstmt->fetch();

    if ($claim) {
        // Update claim record
        $upd = $db->prepare("
            UPDATE claims
            SET status = :status, reviewed_by = :admin_id, reviewed_at = NOW(), admin_note = :note
            WHERE id = :id
        ");
        $upd->execute([
            'status'   => $action,
            'admin_id' => $admin['id'],
            'note'     => $admin_note,
            'id'       => $claim_id
        ]);

        // If approved, mark the item as 'claimed'
        if ($action === 'approved') {
            $item_upd = $db->prepare("UPDATE items SET status = 'claimed' WHERE id = :item_id");
            $item_upd->execute(['item_id' => $claim['item_id']]);
            set_flash('success', "Claim #$claim_id approved. The item is now marked as claimed.");
        } else {
            set_flash('info', "Claim #$claim_id rejected.");
        }

        $recipient = $db->prepare('SELECT u.email, u.full_name, i.title FROM users u JOIN items i ON i.id = :item_id WHERE u.id = :claimant_id');
        $recipient->execute(['item_id' => $claim['item_id'], 'claimant_id' => $claim['claimant_id']]);
        if ($claimant = $recipient->fetch()) {
            $decision = $action === 'approved' ? 'approved' : 'not approved';
            $message = 'Your claim for "' . $claimant['title'] . '" was ' . $decision . '.';
            if ($admin_note !== '') $message .= "\n\nAdministrator note: " . $admin_note;
            email_user($claimant['email'], $claimant['full_name'], 'Claim review update', 'Your claim has been reviewed', $message);
        }
    }

    header('Location: /admin/claims.php');
    exit;
}

$status_filter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];

if (in_array($status_filter, ['pending', 'approved', 'rejected'], true)) {
    $where = 'cl.status = :st';
    $params['st'] = $status_filter;
}

$claims = $db->prepare("
    SELECT cl.*, i.title AS item_title, i.image_path, c.name AS category_name,
           u.full_name AS claimant_name, u.email AS claimant_email,
           rev.full_name AS reviewer_name
    FROM claims cl
    JOIN items i ON i.id = cl.item_id
    JOIN categories c ON c.id = i.category_id
    JOIN users u ON u.id = cl.claimant_id
    LEFT JOIN users rev ON rev.id = cl.reviewed_by
    WHERE $where
    ORDER BY (cl.status = 'pending') DESC, cl.created_at DESC
");
$claims->execute($params);
$list = $claims->fetchAll();

$review_id = (int)($_GET['review'] ?? 0);
$review_claim = null;
if ($review_id > 0) {
    foreach ($list as $c) {
        if ($c['id'] == $review_id) {
            $review_claim = $c;
            break;
        }
    }
}

$page_title   = 'Manage Claims – CivicFind';
$current_page = 'admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding: 36px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 700; color: var(--navy-800);">Claims</h1>
            <p style="color: var(--slate-500); margin-top: 4px;">Review claim details and approve or reject requests.</p>
        </div>
        <a href="/admin/dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
    </div>

    <!-- Filter chips -->
    <div style="display: flex; gap: 8px; margin-bottom: 24px;">
        <a href="/admin/claims.php" class="btn btn-sm <?= $status_filter === '' ? 'btn-primary' : 'btn-outline' ?>">All Claims</a>
        <a href="/admin/claims.php?status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline' ?>">Pending Only</a>
        <a href="/admin/claims.php?status=approved" class="btn btn-sm <?= $status_filter === 'approved' ? 'btn-primary' : 'btn-outline' ?>">Approved</a>
        <a href="/admin/claims.php?status=rejected" class="btn btn-sm <?= $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline' ?>">Rejected</a>
    </div>

    <?php if (empty($list)): ?>
        <div class="empty-state">
            <h3>No claims found</h3>
            <p>No claims match this filter.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Found Item</th>
                        <th>Claimant</th>
                        <th>Claim Message</th>
                        <th>Extra Details</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $cl): ?>
                    <tr>
                        <td>#<?= $cl['id'] ?></td>
                        <td>
                            <a href="/item-detail.php?id=<?= $cl['item_id'] ?>" style="font-weight: 600;">
                                <?= e($cl['item_title']) ?>
                            </a>
                            <div style="font-size: 12px; color: var(--slate-400);"><?= e($cl['category_name']) ?></div>
                        </td>
                        <td>
                            <strong><?= e($cl['claimant_name']) ?></strong>
                            <div style="font-size: 12px; color: var(--slate-500);"><?= e($cl['claimant_email']) ?></div>
                        </td>
                        <td style="max-width: 200px;">
                            <div style="font-size: 13px;"><?= e(truncate_text($cl['claim_message'], 70)) ?></div>
                        </td>
                        <td style="max-width: 220px;">
                            <div style="font-size: 13px; background: var(--slate-100); padding: 4px 8px; border-radius: var(--radius-sm); border: 1px dashed var(--slate-300);">
                                <?= e(truncate_text($cl['proof_description'], 70)) ?>
                            </div>
                        </td>
                        <td><?= status_badge($cl['status']) ?></td>
                        <td style="font-size: 12px;">
                            <?php if ($cl['reviewer_name']): ?>
                                <?= e($cl['reviewer_name']) ?>
                                <div style="color: var(--slate-400);"><?= time_ago($cl['reviewed_at']) ?></div>
                            <?php else: ?>
                                <span style="color: var(--slate-400);">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-teal btn-sm" data-modal="reviewModal-<?= $cl['id'] ?>">Review</button>
                        </td>
                    </tr>

                    <!-- Review Modal for this claim -->
                    <div class="modal-overlay" id="reviewModal-<?= $cl['id'] ?>">
                        <div class="modal" style="max-width: 600px;">
                            <div class="modal-header">
                                <h2>Claim Review: #<?= $cl['id'] ?> (<?= e($cl['item_title']) ?>)</h2>
                                <button class="modal-close" type="button">&times;</button>
                            </div>
                            <form action="/admin/claims.php" method="post">
                                <?= csrf_token() ?>
                                <input type="hidden" name="claim_id" value="<?= $cl['id'] ?>">
                                <div class="modal-body">
                                    <div style="margin-bottom: 16px;">
                                        <strong>Claimant:</strong> <?= e($cl['claimant_name']) ?> (<?= e($cl['claimant_email']) ?>)<br>
                                        <strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($cl['created_at'])) ?>
                                    </div>

                                    <div style="margin-bottom: 16px;">
                                        <label style="font-weight: 700; display: block; margin-bottom: 4px;">Claim message:</label>
                                        <div style="background: var(--slate-50); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--slate-200); font-size: 14px;">
                                            <?= nl2br(e($cl['claim_message'])) ?>
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 16px;">
                                        <label style="font-weight: 700; display: block; margin-bottom: 4px; color: var(--teal-600);">Extra details:</label>
                                        <div style="background: #f0fdf4; padding: 12px; border-radius: var(--radius-sm); border: 1px solid #bbf7d0; font-size: 14px;">
                                            <?= nl2br(e($cl['proof_description'])) ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="admin_note_<?= $cl['id'] ?>">Note for the claimant (optional)</label>
                                        <textarea id="admin_note_<?= $cl['id'] ?>" name="admin_note" rows="3" placeholder="Add a short note..."><?= e($cl['admin_note'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer" style="justify-content: space-between;">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this claim?');">Reject Claim</button>
                                    <div>
                                        <button type="button" class="btn btn-outline modal-cancel">Close</button>
                                        <button type="submit" name="action" value="approve" class="btn btn-teal" onclick="return confirm('Approve this claim and mark the item as claimed?');">Approve Claim</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

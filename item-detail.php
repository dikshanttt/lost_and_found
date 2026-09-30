<?php
/**
 * CivicFind – Item Details & Claim Page (item-detail.php)
 */
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/auth.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . app_url('/browse.php'));
    exit;
}

$stmt = $db->prepare("
    SELECT i.*, c.name AS category_name, u.full_name AS reporter_name, u.email AS reporter_email
    FROM items i
    JOIN categories c ON c.id = i.category_id
    JOIN users u ON u.id = i.user_id
    WHERE i.id = :id
");
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('error', 'Item not found.');
    header('Location: ' . app_url('/browse.php'));
    exit;
}

$page_title   = e($item['title']) . ' – CivicFind';
$current_page = 'browse';
$user = current_user();
$is_owner = $user && ($user['id'] == $item['user_id']);

// Check if current user already submitted a pending claim on this item
$existing_claim = null;
if ($user && $item['type'] === 'found') {
    $cstmt = $db->prepare("SELECT * FROM claims WHERE item_id = :item_id AND claimant_id = :claimant_id");
    $cstmt->execute(['item_id' => $id, 'claimant_id' => $user['id']]);
    $existing_claim = $cstmt->fetch();
}

// Related items
$rel_stmt = $db->prepare("
    SELECT i.*, c.name AS category_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    WHERE i.category_id = :cat AND i.id != :id AND i.status = 'active'
    ORDER BY i.created_at DESC LIMIT 3
");
$rel_stmt->execute(['cat' => $item['category_id'], 'id' => $id]);
$related = $rel_stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 24px 0;">
    <p style="margin-bottom: 20px;">
        <a href="<?= e(app_url('/browse.php')) ?>" class="text-muted">&larr; Back to items</a>
    </p>

    <div class="detail-layout">
        <!-- Item Photo -->
        <div class="detail-image">
            <?php if (!empty($item['image_path'])): ?>
                <img src="<?= e(app_url('/' . $item['image_path'])) ?>" alt="<?= e($item['title']) ?>" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
            <?php else: ?>
                <div class="img-placeholder" style="border-radius: var(--radius-lg); height: 360px; font-size: 72px;">
                    <?= category_icon($item['category_name']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Item Details -->
        <div class="detail-info">
            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                <span class="item-card-category" style="font-size: 13px;"><?= e($item['category_name']) ?></span>
                <?= type_badge($item['type']) ?>
                <?= status_badge($item['status']) ?>
            </div>

            <h1><?= e($item['title']) ?></h1>

            <div class="detail-meta">
                <div class="detail-meta-item">
                    <span>📍</span> <strong>Location:</strong> <?= e($item['location']) ?>
                </div>
                <div class="detail-meta-item">
                    <span>📅</span> <strong>Date <?= $item['type'] === 'found' ? 'Found' : 'Lost' ?>:</strong> <?= date('F j, Y', strtotime($item['item_date'])) ?>
                </div>
                <div class="detail-meta-item">
                    <span>🕒</span> <strong>Added:</strong> <?= time_ago($item['created_at']) ?>
                </div>
                <div class="detail-meta-item">
                    <span>👤</span> <strong>Reported by:</strong> <?= e($item['reporter_name']) ?>
                </div>
            </div>

            <div style="margin: 24px 0;">
                <h3 style="font-size: 16px; margin-bottom: 8px; color: var(--navy-800);">Description</h3>
                <div class="detail-description">
                    <?= nl2br(e($item['description'])) ?>
                </div>
            </div>

            <!-- Action Controls -->
            <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
                <?php if ($is_owner || is_admin()): ?>
                    <a href="<?= e(app_url('/user/edit-item.php?id=' . (int)$item['id'])) ?>" class="btn btn-outline">✏️ Edit Report</a>
                    <form action="<?= e(app_url('/user/delete-item.php')) ?>" method="post" onsubmit="return confirm('Are you sure you want to remove this report?');" style="display:inline;">
                        <?= csrf_token() ?>
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Delete Report</button>
                    </form>
                <?php endif; ?>

                <?php if ($item['type'] === 'found'): ?>
                    <?php if ($item['status'] === 'active'): ?>
                        <?php if ($is_owner): ?>
                            <button class="btn btn-outline" disabled title="You posted this report">You posted this report</button>
                        <?php elseif ($existing_claim): ?>
                            <div class="flash flash-info" style="margin: 0;">
                                Your claim is currently: <?= status_badge($existing_claim['status']) ?>
                            </div>
                        <?php elseif (!is_logged_in()): ?>
                            <a href="<?= e(app_url('/login.php')) ?>" class="btn btn-teal btn-lg">Sign In to Claim This Item</a>
                        <?php else: ?>
                            <button class="btn btn-teal btn-lg" data-modal="claimModal">✨ Claim This Item</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flash flash-warning" style="margin:0;">
                            This report is <?= e($item['status']) ?> and cannot receive new claims.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= e(app_url('/user/report-item.php?type=found')) ?>" class="btn btn-blue btn-lg">Report a found item</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Items -->
    <?php if (!empty($related)): ?>
    <section class="items-section" style="padding-top: 48px; border-top: 1px solid var(--slate-200); margin-top: 48px;">
        <div class="section-header">
            <div>
                <h2>More in <?= e($item['category_name']) ?></h2>
                <p>Other active reports in this category.</p>
            </div>
            <a href="<?= e(app_url('/browse.php?category=' . (int)$item['category_id'])) ?>" class="btn btn-outline btn-sm">View Category</a>
        </div>
        <div class="items-grid-3">
            <?php foreach ($related as $rel): ?>
            <div class="item-card">
                <?php if ($rel['image_path']): ?>
                    <a href="<?= e(app_url('/item-detail.php?id=' . (int)$rel['id'])) ?>">
                        <img src="<?= e(app_url('/' . $rel['image_path'])) ?>" alt="<?= e($rel['title']) ?>" class="item-card-img">
                    </a>
                <?php else: ?>
                    <a href="<?= e(app_url('/item-detail.php?id=' . (int)$rel['id'])) ?>"><div class="img-placeholder"><?= category_icon($rel['category_name']) ?></div></a>
                <?php endif; ?>
                <div class="item-card-body">
                    <div class="item-card-meta">
                        <span class="item-card-category"><?= e($rel['category_name']) ?></span>
                        <?= type_badge($rel['type']) ?>
                    </div>
                    <div class="item-card-title">
                        <a href="<?= e(app_url('/item-detail.php?id=' . (int)$rel['id'])) ?>"><?= e($rel['title']) ?></a>
                    </div>
                    <div class="item-card-info">📍 <?= e($rel['location']) ?></div>
                    <div class="item-card-info">📅 <?= time_ago($rel['created_at']) ?></div>
                    <div class="item-card-actions">
                        <a href="<?= e(app_url('/item-detail.php?id=' . (int)$rel['id'])) ?>" class="btn btn-outline btn-block">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- ═══ CLAIM SUBMISSION MODAL ═══ -->
<?php if (is_logged_in() && $item['type'] === 'found' && $item['status'] === 'active' && !$is_owner): ?>
<div class="modal-overlay" id="claimModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Submit a claim: <?= e($item['title']) ?></h2>
            <button class="modal-close" type="button">&times;</button>
        </div>
        <form action="<?= e(app_url('/user/submit-claim.php')) ?>" method="post">
            <?= csrf_token() ?>
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <div class="modal-body">
                <p style="font-size: 14px; color: var(--slate-500); margin-bottom: 16px;">
                    Tell us why you think this item is yours. The administrator will review your claim.
                </p>

                <div class="form-group">
                    <label for="claim_message">Why is this item yours? *</label>
                    <textarea id="claim_message" name="claim_message" rows="3" required placeholder="Say where and when you lost it..."></textarea>
                </div>

                <div class="form-group">
                    <label for="proof_description">Details that can help identify it *</label>
                    <textarea id="proof_description" name="proof_description" rows="3" required placeholder="Mention a mark, sticker, or other detail not shown in the report..."></textarea>
                    <p class="form-help">This information is shown to the administrator reviewing your claim.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-teal">Submit claim</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

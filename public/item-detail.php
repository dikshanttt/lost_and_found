<?php
/**
 * CivicFind – Item Details & Claim Page (item-detail.php)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: /browse.php');
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
    set_flash('error', 'Item record not found in registry.');
    header('Location: /browse.php');
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 24px 0;">
    <p style="margin-bottom: 20px;">
        <a href="/browse.php" class="text-muted">&larr; Back to Directory Registry</a>
    </p>

    <div class="detail-layout">
        <!-- Item Photo -->
        <div class="detail-image">
            <?php if (!empty($item['image_path'])): ?>
                <img src="/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" style="border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
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
                    <span>🕒</span> <strong>Recorded:</strong> <?= time_ago($item['created_at']) ?>
                </div>
                <div class="detail-meta-item">
                    <span>👤</span> <strong>Reported by:</strong> <?= e($item['reporter_name']) ?>
                </div>
            </div>

            <div style="margin: 24px 0;">
                <h3 style="font-size: 16px; margin-bottom: 8px; color: var(--navy-800);">Description &amp; Identifying Details</h3>
                <div class="detail-description">
                    <?= nl2br(e($item['description'])) ?>
                </div>
            </div>

            <!-- Action Controls -->
            <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
                <?php if ($is_owner || is_admin()): ?>
                    <a href="/user/edit-item.php?id=<?= $item['id'] ?>" class="btn btn-outline">✏️ Edit Report</a>
                    <form action="/user/delete-item.php" method="post" onsubmit="return confirm('Are you sure you want to remove this report?');" style="display:inline;">
                        <?= csrf_token() ?>
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Delete Report</button>
                    </form>
                <?php endif; ?>

                <?php if ($item['type'] === 'found'): ?>
                    <?php if ($item['status'] === 'active'): ?>
                        <?php if ($is_owner): ?>
                            <button class="btn btn-outline" disabled title="You reported this item">You reported this found item</button>
                        <?php elseif ($existing_claim): ?>
                            <div class="flash flash-info" style="margin: 0;">
                                Your claim is currently: <?= status_badge($existing_claim['status']) ?>
                            </div>
                        <?php elseif (!is_logged_in()): ?>
                            <a href="/auth/login.php" class="btn btn-teal btn-lg">Sign In to Claim This Item</a>
                        <?php else: ?>
                            <button class="btn btn-teal btn-lg" data-modal="claimModal">✨ Claim This Item</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="flash flash-warning" style="margin:0;">
                            This item is marked as <strong><?= strtoupper(e($item['status'])) ?></strong> and is no longer available for new claims.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/user/report-item.php?type=found" class="btn btn-blue btn-lg">Have you found this? Report Found Item</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Related Items -->
    <?php if (!empty($related)): ?>
    <section class="items-section" style="padding-top: 48px; border-top: 1px solid var(--slate-200); margin-top: 48px;">
        <div class="section-header">
            <div>
                <h2>Related Items in <?= e($item['category_name']) ?></h2>
                <p>Other active lost and found records in this category</p>
            </div>
            <a href="/browse.php?category=<?= $item['category_id'] ?>" class="btn btn-outline btn-sm">View Category</a>
        </div>
        <div class="items-grid-3">
            <?php foreach ($related as $rel): ?>
            <div class="item-card">
                <?php if ($rel['image_path']): ?>
                    <a href="/item-detail.php?id=<?= $rel['id'] ?>">
                        <img src="/<?= e($rel['image_path']) ?>" alt="<?= e($rel['title']) ?>" class="item-card-img">
                    </a>
                <?php else: ?>
                    <a href="/item-detail.php?id=<?= $rel['id'] ?>"><div class="img-placeholder"><?= category_icon($rel['category_name']) ?></div></a>
                <?php endif; ?>
                <div class="item-card-body">
                    <div class="item-card-meta">
                        <span class="item-card-category"><?= e($rel['category_name']) ?></span>
                        <?= type_badge($rel['type']) ?>
                    </div>
                    <div class="item-card-title">
                        <a href="/item-detail.php?id=<?= $rel['id'] ?>"><?= e($rel['title']) ?></a>
                    </div>
                    <div class="item-card-info">📍 <?= e($rel['location']) ?></div>
                    <div class="item-card-info">📅 <?= time_ago($rel['created_at']) ?></div>
                    <div class="item-card-actions">
                        <a href="/item-detail.php?id=<?= $rel['id'] ?>" class="btn btn-outline btn-block">View Details</a>
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
            <h2>Claim Ownership: <?= e($item['title']) ?></h2>
            <button class="modal-close" type="button">&times;</button>
        </div>
        <form action="/user/submit-claim.php" method="post">
            <?= csrf_token() ?>
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <div class="modal-body">
                <p style="font-size: 14px; color: var(--slate-500); margin-bottom: 16px;">
                    Please provide detailed identifying information so municipal administrators can verify you are the rightful owner.
                </p>

                <div class="form-group">
                    <label for="claim_message">Statement of Ownership *</label>
                    <textarea id="claim_message" name="claim_message" rows="3" required placeholder="Explain where, when, and how you lost this item..."></textarea>
                </div>

                <div class="form-group">
                    <label for="proof_description">Secret Identifying Marks / Proof Details *</label>
                    <textarea id="proof_description" name="proof_description" rows="3" required placeholder="Provide non-public details (e.g. serial numbers, engravings, phone wallpaper, inside contents, passcode hints)..."></textarea>
                    <p class="form-help">Only authorized administrators will inspect this verification description.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline modal-cancel">Cancel</button>
                <button type="submit" class="btn btn-teal">Submit Ownership Claim</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

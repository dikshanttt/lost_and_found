<?php
/**
 * CivicFind – Landing Page (index.php)
 */
$current_page = 'home';
$page_title   = 'CivicFind – Lost and Found';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();

// Category counts
$cats = $db->query("
    SELECT c.*, COUNT(i.id) AS item_count
    FROM categories c
    LEFT JOIN items i ON i.category_id = c.id AND i.status = 'active'
    GROUP BY c.id ORDER BY c.name
")->fetchAll();

// Recent items
$recent = $db->query("
    SELECT i.*, c.name AS category_name
    FROM items i
    JOIN categories c ON c.id = i.category_id
    WHERE i.status = 'active'
    ORDER BY i.created_at DESC LIMIT 4
")->fetchAll();
?>

<!-- ═══ HERO ═══ -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div>
                <span class="hero-badge">LOST AND FOUND</span>
                <h1>Looking for something you lost?</h1>
                <p>Search the reports on this site or add a report for something you lost or found.</p>

                <form class="hero-search" action="/browse.php" method="get">
                    <div class="search-input">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="keyword" placeholder="Search by item name or details">
                    </div>
                    <div class="search-input">
                        <span class="search-icon">📍</span>
                        <input type="text" name="location" placeholder="Location">
                    </div>
                    <button type="submit" class="btn btn-blue">Search Items</button>
                </form>
            </div>
            <div class="hero-illustration">
                🔐
            </div>
        </div>
    </div>
</section>

<!-- ═══ CATEGORIES ═══ -->
<section class="categories">
    <div class="container">
        <div class="section-header">
            <div>
                <h2>Browse by Category</h2>
                <p>Choose a category to narrow your search.</p>
            </div>
            <a href="/browse.php" class="btn btn-outline btn-sm">View All Categories</a>
        </div>
        <div class="category-grid">
            <?php foreach (array_slice($cats, 0, 6) as $cat): ?>
            <a href="/browse.php?category=<?= $cat['id'] ?>" class="category-card">
                <div class="cat-icon"><?= category_icon($cat['name']) ?></div>
                <div class="cat-name"><?= e($cat['name']) ?></div>
                <div class="cat-count"><?= (int)$cat['item_count'] ?> items</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ RECENTLY REGISTERED ═══ -->
<section class="items-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2>Recent Reports</h2>
                <p>Items most recently added to the site.</p>
            </div>
            <a href="/browse.php" class="btn btn-outline btn-sm">Browse Live Directory</a>
        </div>
        <div class="items-grid">
            <?php if (empty($recent)): ?>
                <div class="empty-state" style="grid-column: 1 / -1; padding: 48px 24px; background: var(--white); border: 1px dashed var(--slate-300); border-radius: var(--radius-lg);">
                    <div style="font-size: 40px; margin-bottom: 12px;">📋</div>
                    <h3 style="font-size: 20px; font-weight: 700; color: var(--navy-800); margin-bottom: 8px;">No reports yet</h3>
                    <p style="color: var(--slate-500); margin-bottom: 20px;">When someone adds a report, it will show up here.</p>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        <a href="/user/report-item.php?type=lost" class="btn btn-primary">Report Lost Item</a>
                        <a href="/user/report-item.php?type=found" class="btn btn-teal">Report Found Item</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recent as $item): ?>
                <div class="item-card">
                    <?php if ($item['image_path']): ?>
                        <img src="/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" class="item-card-img">
                    <?php else: ?>
                        <div class="img-placeholder">📦</div>
                    <?php endif; ?>
                    <div class="item-card-body">
                        <div class="item-card-meta">
                            <span class="item-card-category"><?= e($item['category_name']) ?></span>
                            <?= type_badge($item['type']) ?>
                        </div>
                        <div class="item-card-title">
                            <a href="/item-detail.php?id=<?= $item['id'] ?>"><?= e($item['title']) ?></a>
                        </div>
                        <div class="item-card-info">📍 <?= e($item['location']) ?></div>
                        <div class="item-card-info">📅 <?= $item['type'] === 'found' ? 'Registered' : 'Reported' ?> <?= time_ago($item['created_at']) ?></div>
                        <div class="item-card-actions">
                            <?php if ($item['type'] === 'found'): ?>
                                <a href="/item-detail.php?id=<?= $item['id'] ?>" class="btn btn-teal btn-block">Claim Item</a>
                            <?php else: ?>
                                <a href="/item-detail.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-block">Report Match</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══ HOW IT WORKS ═══ -->
<section class="how-it-works">
    <div class="container">
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <h3>Add a Report</h3>
                <p>Enter the item details, date, and location. You can add a photo too.</p>
            </div>
            <div class="step-card">
                <div class="step-number">02</div>
                <h3>Search Reports</h3>
                <p>Use the search and filters to look through the reports.</p>
            </div>
            <div class="step-card">
                <div class="step-number">03</div>
                <h3>Make a Claim</h3>
                <p>If you see a found item that may be yours, send a claim for an administrator to review.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<section>
    <div class="container">
        <div class="cta-banner">
            <div>
                <h2>Have you found something of value?</h2>
                <p>If you found an item, add a report so its owner can search for it.</p>
            </div>
            <a href="/user/report-item.php?type=found" class="btn btn-outline-white btn-lg">Report a Found Item</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

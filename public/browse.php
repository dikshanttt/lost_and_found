<?php
/**
 * CivicFind – Browse / Directory Registry
 */
$current_page = 'browse';
$page_title   = 'Browse Items – CivicFind';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$categories = get_categories($db);

// Read filters
$keyword   = mb_substr(trim($_GET['keyword'] ?? ''), 0, 100);
$type      = $_GET['type']          ?? '';
$catFilter  = $_GET['category']     ?? '';
$location  = mb_substr(trim($_GET['location'] ?? ''), 0, 100);
$dateRange = $_GET['date_range']    ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 9;

// Build query
$where  = ['1=1'];
$params = [];

if ($keyword !== '') {
    $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
    $where[]  = "(i.title LIKE :kw ESCAPE '\\\\' OR i.description LIKE :kw2 ESCAPE '\\\\' OR i.location LIKE :kw3 ESCAPE '\\\\')";
    $params['kw'] = $params['kw2'] = $params['kw3'] = "%$term%";
}
if ($type === 'found' || $type === 'lost') {
    $where[]  = 'i.type = :type';
    $params['type'] = $type;
}
if ($catFilter !== '' && is_numeric($catFilter)) {
    $where[]  = 'i.category_id = :cat';
    $params['cat'] = (int)$catFilter;
}
if ($location !== '') {
    $locationTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $location);
    $where[]  = "i.location LIKE :loc ESCAPE '\\\\'";
    $params['loc'] = "%$locationTerm%";
}
if ($dateRange !== '') {
    $days = match($dateRange) {
        '1' => 1, '7' => 7, '30' => 30, '90' => 90, default => 0
    };
    if ($days > 0) {
        $where[]  = 'i.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';
        $params['days'] = $days;
    }
}

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM items i WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pag   = paginate($total, $perPage, $page);

// Fetch items
$sql = "SELECT i.*, c.name AS category_name
        FROM items i JOIN categories c ON c.id = i.category_id
        WHERE $whereSQL
        ORDER BY i.created_at DESC
        LIMIT {$pag['per_page']} OFFSET {$pag['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Build query string helper
function qs(array $overrides = []): string {
    $p = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') unset($p[$k]); else $p[$k] = $v;
    }
    unset($p['page']);
    return http_build_query($p);
}
?>

<section class="directory">
    <div class="container">
        <!-- Header -->
        <div class="directory-header">
            <div>
                <h1>Browse Items</h1>
                <p class="subtitle"><?= $total ?> items found</p>
            </div>
            <div class="directory-search">
                <form method="get" style="display:flex;gap:8px">
                    <?php foreach ($_GET as $k => $v): if ($k !== 'keyword'): ?>
                        <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                    <?php endif; endforeach; ?>
                    <input type="text" name="keyword" placeholder="Search items..." value="<?= e($keyword) ?>">
                    <button class="btn btn-outline btn-sm" type="submit">Search</button>
                </form>
            </div>
        </div>

        <!-- Active filters -->
        <?php
        $activeFilters = [];
        if ($keyword !== '')   $activeFilters[] = ['label' => $keyword, 'remove' => 'keyword'];
        if ($type !== '')      $activeFilters[] = ['label' => strtoupper($type), 'remove' => 'type'];
        if ($catFilter !== '') {
            $cn = array_filter($categories, fn($c) => $c['id'] == $catFilter);
            $cn = $cn ? strtoupper(reset($cn)['name']) : 'CATEGORY';
            $activeFilters[] = ['label' => $cn, 'remove' => 'category'];
        }
        if ($location !== '')  $activeFilters[] = ['label' => strtoupper($location), 'remove' => 'location'];
        if ($dateRange !== '') {
            $dl = match($dateRange) { '1'=>'TODAY','7'=>'LAST 7 DAYS','30'=>'LAST 30 DAYS','90'=>'LAST 90 DAYS', default=>'ALL' };
            $activeFilters[] = ['label' => $dl, 'remove' => 'date_range'];
        }
        ?>
        <?php if ($activeFilters): ?>
        <div class="active-filters">
            <span style="font-size:13px;font-weight:600;color:var(--slate-500);padding:5px 0;">Filters:</span>
            <?php foreach ($activeFilters as $af): ?>
                <span class="filter-chip">
                    <?= e($af['label']) ?>
                    <a href="?<?= qs([$af['remove'] => null]) ?>">&times;</a>
                </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="directory-layout">
            <!-- Sidebar -->
            <aside class="filter-sidebar">
                <h3>Filters <a href="/browse.php">Clear</a></h3>
                <form method="get" id="filterForm">
                    <?php if ($keyword !== ''): ?>
                        <input type="hidden" name="keyword" value="<?= e($keyword) ?>">
                    <?php endif; ?>

                    <div class="filter-group">
                        <div class="filter-group-label">Item Type</div>
                        <label><input type="radio" name="type" value="" <?= $type === '' ? 'checked' : '' ?> onchange="this.form.submit()"> All Items</label>
                        <label><input type="radio" name="type" value="found" <?= $type === 'found' ? 'checked' : '' ?> onchange="this.form.submit()"> Found items</label>
                        <label><input type="radio" name="type" value="lost" <?= $type === 'lost' ? 'checked' : '' ?> onchange="this.form.submit()"> Lost items</label>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-label">Category</div>
                        <?php foreach ($categories as $cat): ?>
                        <label><input type="radio" name="category" value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'checked' : '' ?> onchange="this.form.submit()"> <?= e($cat['name']) ?></label>
                        <?php endforeach; ?>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-label">Location</div>
                        <input type="text" name="location" value="<?= e($location) ?>" placeholder="Enter a location" onchange="this.form.submit()">
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-label">Added</div>
                        <select name="date_range" onchange="this.form.submit()">
                            <option value="">All Time</option>
                            <option value="1" <?= $dateRange === '1' ? 'selected' : '' ?>>Last 24 hours</option>
                            <option value="7" <?= $dateRange === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="30" <?= $dateRange === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                            <option value="90" <?= $dateRange === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        </select>
                    </div>
                </form>
            </aside>

            <!-- Items -->
            <div>
                <?php if (empty($items)): ?>
                    <div class="empty-state" style="padding: 48px 24px; background: var(--white); border: 1px dashed var(--slate-300); border-radius: var(--radius-lg); text-align: center;">
                        <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
                        <h3 style="font-size: 20px; font-weight: 700; color: var(--navy-800); margin-bottom: 8px;">No items found</h3>
                        <p style="color: var(--slate-500); margin-bottom: 20px;">Try changing your search or clearing the filters.</p>
                        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                            <?php if (!empty($activeFilters)): ?>
                                <a href="/browse.php" class="btn btn-outline">Clear filters</a>
                            <?php endif; ?>
                            <a href="/user/report-item.php" class="btn btn-primary">+ Report an Item</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="items-grid-3">
                        <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <?php if ($item['image_path']): ?>
                                <a href="/item-detail.php?id=<?= $item['id'] ?>">
                                    <img src="/<?= e($item['image_path']) ?>" alt="<?= e($item['title']) ?>" class="item-card-img">
                                </a>
                            <?php else: ?>
                                <a href="/item-detail.php?id=<?= $item['id'] ?>"><div class="img-placeholder">📦</div></a>
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
                                <div class="item-card-info">📅 <?= time_ago($item['created_at']) ?></div>
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
                    </div>

                    <!-- Pagination -->
                    <?php if ($pag['pages'] > 1): ?>
                    <div class="pagination">
                        <a href="?<?= qs() ?>&page=<?= max(1, $pag['current']-1) ?>" class="<?= $pag['current'] <= 1 ? 'disabled' : '' ?>">Previous</a>
                        <div class="pagination-pages">
                            <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
                                <?php if ($i == $pag['current']): ?>
                                    <span class="active"><?= $i ?></span>
                                <?php elseif ($i <= 3 || $i >= $pag['pages'] - 1 || abs($i - $pag['current']) <= 1): ?>
                                    <a href="?<?= qs() ?>&page=<?= $i ?>"><?= $i ?></a>
                                <?php elseif ($i == 4 || $i == $pag['pages'] - 2): ?>
                                    <span>…</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <a href="?<?= qs() ?>&page=<?= min($pag['pages'], $pag['current']+1) ?>" class="<?= $pag['current'] >= $pag['pages'] ? 'disabled' : '' ?>">Next</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

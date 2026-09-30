<!-- ═══ NAVBAR ═══ -->
<nav class="navbar">
    <div class="container navbar-inner">
        <!-- Logo -->
        <a href="<?= e(app_url('/index.php')) ?>" class="navbar-brand">
            <span class="brand-icon">Q</span>
            <span class="brand-text">CivicFind</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <!-- Nav links -->
        <div class="navbar-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="<?= e(app_url('/index.php')) ?>" class="<?= ($current_page ?? '') === 'home' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= e(app_url('/browse.php')) ?>" class="<?= ($current_page ?? '') === 'browse' ? 'active' : '' ?>">Browse</a></li>
                <li><a href="<?= e(app_url('/user/report-item.php?type=lost')) ?>" class="<?= ($current_page ?? '') === 'report-lost' ? 'active' : '' ?>">Report Lost</a></li>
                <li><a href="<?= e(app_url('/user/report-item.php?type=found')) ?>" class="<?= ($current_page ?? '') === 'report-found' ? 'active' : '' ?>">Report Found</a></li>
            </ul>

            <div class="nav-actions">
                <?php if (is_logged_in()): ?>
                    <div class="user-dropdown">
                        <button class="user-dropdown-toggle" id="userDropToggle">
                            <span class="user-avatar"><?= mb_substr(e(current_user()['full_name']), 0, 1) ?></span>
                            <span class="user-name"><?= e(current_user()['full_name']) ?></span>
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="user-dropdown-menu" id="userDropMenu">
                            <a href="<?= e(app_url('/user/my-reports.php')) ?>">My Reports</a>
                            <?php if (is_admin()): ?>
                                <a href="<?= e(app_url('/admin/dashboard.php')) ?>">Admin Dashboard</a>
                            <?php endif; ?>
                            <hr>
                            <form action="<?= e(app_url('/logout.php')) ?>" method="post" class="logout-form">
                                <?= csrf_token() ?>
                                <button type="submit" class="logout-link">Sign Out</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= e(app_url('/login.php')) ?>" class="nav-signin">Sign In</a>
                    <a href="<?= e(app_url('/login.php')) ?>" class="btn btn-primary btn-sm">Report an Item</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

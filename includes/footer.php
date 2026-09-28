</main>

<!-- ═══ FOOTER ═══ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <a href="<?= e(app_url('/')) ?>" class="navbar-brand">
                    <span class="brand-icon">Q</span>
                    <span class="brand-text">CivicFind</span>
                </a>
                <p>A simple place to post lost and found items and search reports from other people.</p>
            </div>
            <!-- Links -->
            <div class="footer-col">
                <h4>Browse</h4>
                <ul>
                    <li><a href="<?= e(app_url('/browse.php')) ?>">All reports</a></li>
                    <li><a href="<?= e(app_url('/browse.php?type=lost')) ?>">Lost items</a></li>
                    <li><a href="<?= e(app_url('/browse.php?type=found')) ?>">Found items</a></li>
                    <li><a href="<?= e(app_url('/user/report-item.php')) ?>">Add a report</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <li><a href="<?= e(app_url('/user/my-reports.php')) ?>">My reports</a></li>
                    <li><a href="<?= e(app_url('/user/my-reports.php?tab=claims')) ?>">My claims</a></li>
                    <li><a href="<?= e(app_url('/auth/login.php')) ?>">Sign in</a></li>
                    <li><a href="<?= e(app_url('/auth/register.php')) ?>">Create account</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>About</h4>
                <ul>
                    <li><a href="<?= e(app_url('/')) ?>">Home</a></li>
                    <li><a href="<?= e(app_url('/browse.php')) ?>">Browse reports</a></li>
                    <li><a href="<?= e(app_url('/auth/register.php')) ?>">Join CivicFind</a></li>
                    <li><a href="<?= e(app_url('/auth/login.php')) ?>">Your account</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Help</h4>
                <ul>
                    <li><a href="<?= e(app_url('/browse.php')) ?>">Search for an item</a></li>
                    <li><a href="<?= e(app_url('/user/report-item.php')) ?>">Report a lost item</a></li>
                    <li><a href="<?= e(app_url('/user/report-item.php?type=found')) ?>">Report a found item</a></li>
                    <li><a href="<?= e(app_url('/user/my-reports.php')) ?>">Check your reports</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 CivicFind. Lost and found reports in one place.</p>
            <div class="footer-social">
                <a href="<?= e(app_url('/')) ?>" aria-label="Home">Home</a>
                <a href="<?= e(app_url('/browse.php')) ?>" aria-label="Browse reports">Browse</a>
                <a href="<?= e(app_url('/auth/login.php')) ?>" aria-label="Sign in">Sign in</a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= e(app_url('/assets/js/main.js')) ?>"></script>
</body>
</html>

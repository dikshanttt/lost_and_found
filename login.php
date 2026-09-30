<?php
/**
 * CivicFind – Sign In (login.php)
 */
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/auth.php';

// On POST, just redirect back (refresh) – demo only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . app_url('/login.php'));
    exit;
}

$page_title   = 'Sign In – CivicFind';
$current_page = 'login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Sign In to CivicFind</h1>
        <p class="auth-subtitle">Sign in to post reports and check your claims.</p>

        <form action="<?= e(app_url('/login.php')) ?>" method="post">
            <?= csrf_token() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="name@domain.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
            </div>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--slate-500);">
            New here? <a href="<?= e(app_url('/register.php')) ?>">Create an account</a>
        </p>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--slate-200); font-size: 12px; color: var(--slate-400); text-align: center;">
            Sign in with an administrator account provisioned for this installation.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

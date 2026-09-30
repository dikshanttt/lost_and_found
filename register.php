<?php
/**
 * CivicFind – Register (register.php)
 */
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/auth/auth.php';

// On POST, just redirect back (refresh) – demo only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . app_url('/register.php'));
    exit;
}

$page_title   = 'Create Account – CivicFind';
$current_page = 'register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Create CivicFind Account</h1>
        <p class="auth-subtitle">Create an account to post reports and make claims.</p>

        <form action="<?= e(app_url('/register.php')) ?>" method="post">
            <?= csrf_token() ?>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required placeholder="e.g. Jane Doe">
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="name@domain.com">
            </div>

            <div class="form-group">
                <label for="password">Password (12–72 characters) *</label>
                <input type="password" id="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password" placeholder="12 to 72 characters">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" placeholder="Repeat your password">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
            </div>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--slate-500);">
            Already have an account? <a href="<?= e(app_url('/login.php')) ?>">Sign in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

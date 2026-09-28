<?php
/**
 * CivicFind – Sign In (auth/login.php)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_check.php';

if (is_logged_in()) {
    header('Location: ' . app_url('/'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $db = get_db();
        $key = env_value('APP_KEY', 'change-this-application-key');
        $emailHash = hash_hmac('sha256', $email, $key);
        $ipHash = hash_hmac('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $key);
        $db->exec('DELETE FROM auth_login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        $attempts = $db->prepare('SELECT COUNT(*) FROM auth_login_attempts WHERE email_hash = :email_hash AND ip_hash = :ip_hash AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $attempts->execute(['email_hash' => $emailHash, 'ip_hash' => $ipHash]);
        if ((int)$attempts->fetchColumn() >= 5) {
        $error = 'Too many tries. Wait 15 minutes and try again.';
        }

        if ($error === '') {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'blocked') {
                    $error = 'This account is blocked. Contact the site administrator.';
                } else {
                    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                        $rehash = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                        $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
                    }
                    login_user($user);
                    $clearAttempts = $db->prepare('DELETE FROM auth_login_attempts WHERE email_hash = :email_hash AND ip_hash = :ip_hash');
                    $clearAttempts->execute(['email_hash' => $emailHash, 'ip_hash' => $ipHash]);
                    set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                    $redirect = is_admin() ? '/admin/dashboard.php' : '/browse.php';
                    header("Location: $redirect");
                    exit;
                }
            } else {
                $recordAttempt = $db->prepare('INSERT INTO auth_login_attempts (email_hash, ip_hash) VALUES (:email_hash, :ip_hash)');
                $recordAttempt->execute(['email_hash' => $emailHash, 'ip_hash' => $ipHash]);
            $error = 'Email or password is incorrect.';
            }
        }
    }
}

$page_title   = 'Sign In – CivicFind';
$current_page = 'login';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Sign In to CivicFind</h1>
        <p class="auth-subtitle">Sign in to post reports and check your claims.</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom: 20px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(app_url('/auth/login.php')) ?>" method="post">
            <?= csrf_token() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@domain.com">
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
            New here? <a href="<?= e(app_url('/auth/register.php')) ?>">Create an account</a>
        </p>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--slate-200); font-size: 12px; color: var(--slate-400); text-align: center;">
            Sign in with an administrator account provisioned for this installation.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

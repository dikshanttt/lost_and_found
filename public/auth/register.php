<?php
/**
 * CivicFind – Register (auth/register.php)
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
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (mb_strlen($full_name) > 100 || strlen($email) > 150) {
        $error = 'Your name or email address is too long.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Choose a password between 12 and 72 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match. Please verify.';
    } else {
        $db = get_db();
        $check = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, password_hash, role, status)
                VALUES (:name, :email, :hash, 'user', 'active')
            ");
            $stmt->execute([
                'name'  => $full_name,
                'email' => $email,
                'hash'  => $hash
            ]);
            $new_id = (int)$db->lastInsertId();

            require_once __DIR__ . '/../../includes/mailer.php';
            email_user($email, $full_name, 'Welcome to LostAndFound', 'Your account is ready', 'Your LostAndFound account has been created. You can now search reports and submit item claims.');

            login_user([
                'id'        => $new_id,
                'full_name' => $full_name,
                'email'     => $email,
                'role'      => 'user'
            ]);

            set_flash('success', 'Your account has been created successfully!');
            header('Location: ' . app_url('/browse.php'));
            exit;
        }
    }
}

$page_title   = 'Create Account – CivicFind';
$current_page = 'register';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Create CivicFind Account</h1>
        <p class="auth-subtitle">Create an account to post reports and make claims.</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom: 20px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(app_url('/auth/register.php')) ?>" method="post">
            <?= csrf_token() ?>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Jane Doe">
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="name@domain.com">
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
            Already have an account? <a href="<?= e(app_url('/auth/login.php')) ?>">Sign in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

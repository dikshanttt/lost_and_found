<?php
/**
 * CivicFind – includes/auth_check.php
 * Session handling & auth guard helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'user',
    ];
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function login_user(array $user): void {
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'domain' => $p['domain'],
            'secure' => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please sign in to continue.');
        header('Location: /auth/login.php');
        exit;
    }

    require_once __DIR__ . '/../config/db.php';
    $stmt = get_db()->prepare('SELECT full_name, email, role, status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        logout_user();
        session_start();
        set_flash('error', 'Your session has ended. Please sign in again.');
        header('Location: /auth/login.php');
        exit;
    }

    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Access denied. Administrators only.');
        header('Location: /index.php');
        exit;
    }
}

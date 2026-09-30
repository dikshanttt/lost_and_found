<?php
/**
 * Authentication & Session Management (auth/auth.php)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
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
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . app_url('/login.php'));
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: ' . app_url('/index.php'));
        exit;
    }
}

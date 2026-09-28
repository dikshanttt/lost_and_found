<?php
/**
 * CivicFind – Logout (auth/logout.php)
 */
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

verify_csrf();
logout_user();
session_start();
set_flash('info', 'You are signed out.');
header('Location: /');
exit;

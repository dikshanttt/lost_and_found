<?php
/**
 * CivicFind – Logout (logout.php)
 */
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('/'));
    exit;
}

verify_csrf();
logout_user();
session_start();
set_flash('info', 'You are signed out.');
header('Location: ' . app_url('/'));
exit;

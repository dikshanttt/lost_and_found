<?php
/**
 * CivicFind – includes/header.php
 * Renders <!DOCTYPE html> through <main>.
 */
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/functions.php';

$page_title   = $page_title   ?? 'CivicFind';
$current_page = $current_page ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('/assets/css/style.css')) ?>">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- ═══ FLASH MESSAGES ═══ -->
<?php $flash = get_flash(); if ($flash): ?>
<div class="container flash-container">
    <?= $flash ?>
</div>
<?php endif; ?>

<main>

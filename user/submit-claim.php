<?php
/**
 * CivicFind – Submit Claim Handler (user/submit-claim.php)
 */
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth.php';
// mailer removed for demo;

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('/browse.php'));
    exit;
}

verify_csrf();

$user = current_user();
$item_id = (int)($_POST['item_id'] ?? 0);
$claim_message = trim($_POST['claim_message'] ?? '');
$proof_description = trim($_POST['proof_description'] ?? '');

if ($item_id <= 0 || empty($claim_message) || empty($proof_description)) {
    set_flash('error', 'Please fill in both claim fields.');
    header('Location: ' . app_url('/item-detail.php?id=' . $item_id));
    exit;
}

$db = get_db();

// Verify item exists, is type 'found', and status is 'active'
$stmt = $db->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute(['id' => $item_id]);
$item = $stmt->fetch();

if (!$item) {
    set_flash('error', 'Item record not found.');
    header('Location: ' . app_url('/browse.php'));
    exit;
}

if ($item['type'] !== 'found') {
    set_flash('error', 'You can only claim found items.');
    header('Location: ' . app_url('/item-detail.php?id=' . $item_id));
    exit;
}

if ($item['status'] !== 'active') {
    set_flash('error', 'This found item is no longer accepting claims.');
    header('Location: ' . app_url('/item-detail.php?id=' . $item_id));
    exit;
}

if ($item['user_id'] == $user['id']) {
    set_flash('error', 'You cannot claim an item you reported yourself.');
    header('Location: ' . app_url('/item-detail.php?id=' . $item_id));
    exit;
}

// Check for existing claim
$chk = $db->prepare("SELECT id, status FROM claims WHERE item_id = :item_id AND claimant_id = :uid");
$chk->execute(['item_id' => $item_id, 'uid' => $user['id']]);
$existing = $chk->fetch();

if ($existing) {
    set_flash('warning', 'You already have an existing claim for this item with status: ' . strtoupper($existing['status']));
    header('Location: ' . app_url('/item-detail.php?id=' . $item_id));
    exit;
}

// Insert claim
$ins = $db->prepare("
    INSERT INTO claims (item_id, claimant_id, claim_message, proof_description, status)
    VALUES (:item_id, :uid, :msg, :proof, 'pending')
");
$ins->execute([
    'item_id' => $item_id,
    'uid'     => $user['id'],
    'msg'     => $claim_message,
    'proof'   => $proof_description
]);

email_user($user['email'], $user['full_name'], 'Claim submitted', 'Your claim is awaiting review', 'Your claim for "' . $item['title'] . '" was submitted. We will email you when an administrator reviews it.');
$adminEmail = env_value('ADMIN_NOTIFICATION_EMAIL');
if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    email_user($adminEmail, 'Administrator', 'New item claim', 'A new claim needs review', 'A new ownership claim was submitted for "' . $item['title'] . '". Sign in to the admin dashboard to review it.');
}

set_flash('success', 'Your claim was sent for review.');
header('Location: ' . app_url('/user/my-reports.php?tab=claims'));
exit;

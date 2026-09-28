<?php
/**
 * CivicFind – System Verification Test Suite (tests/verify_system.php)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found. Run verification from the command line.');
}

echo "=================================================\n";
echo "CivicFind – Live System Verification Suite\n";
echo "=================================================\n\n";

$passed = 0;
$failed = 0;

function assert_test($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $description\n";
        $passed++;
    } else {
        echo "[FAIL] $description\n";
        $failed++;
    }
}

try {
    $db = get_db();

    // 1. Database Connection
    assert_test("Database connection is active", $db instanceof PDO);

    // 2. Categories Loaded
    $cats = get_categories($db);
    assert_test("Categories exist and count >= 10", count($cats) >= 10);

    // 3. Admin Account Exists
    $adminStmt = $db->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
    $admin = $adminStmt->fetch();
    assert_test("Administrator account exists", !empty($admin));
    assert_test("Admin role is 'admin'", ($admin['role'] ?? '') === 'admin');
    assert_test("Administrator password is stored as a password hash", !empty($admin['password_hash']) && password_get_info($admin['password_hash'])['algo'] !== null);

    // 4. Verify only admin exists in clean database
    $userCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    assert_test("At least one administrator account exists ($userCount user total)", $userCount >= 1 && !empty($admin));

    // 5. Verify items and claims tables are clean
    $itemCount = (int)$db->query("SELECT COUNT(*) FROM items")->fetchColumn();
    assert_test("Items table is empty and ready for actual data ($itemCount items)", $itemCount === 0);

    $claimCount = (int)$db->query("SELECT COUNT(*) FROM claims")->fetchColumn();
    assert_test("Claims table is empty ($claimCount claims)", $claimCount === 0);

    // 6. Security Helpers
    assert_test("HTML escaping function e() works", e("<script>alert('xss')</script>") === "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;");

    // 7. Time Ago Helper
    $nowAgo = time_ago(date('Y-m-d H:i:s'));
    assert_test("time_ago() returns valid relative string ('$nowAgo')", in_array($nowAgo, ['Just now', '1 minute ago', '0 minutes ago']));

    // 8. Badges
    $foundBadge = type_badge('found');
    assert_test("type_badge('found') contains 'FOUND'", str_contains($foundBadge, 'FOUND'));
    $lostBadge = type_badge('lost');
    assert_test("type_badge('lost') contains 'LOST'", str_contains($lostBadge, 'LOST'));

    // 9. File upload directory exists and is writable
    $uploadDir = __DIR__ . '/../public/assets/uploads';
    assert_test("Uploads directory exists and is writable", is_dir($uploadDir) && is_writable($uploadDir));

} catch (Exception $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=================================================\n";
echo "RESULTS: $passed passed, $failed failed.\n";
echo "=================================================\n";

if ($failed > 0) exit(1);

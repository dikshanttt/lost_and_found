<?php
/**
 * CivicFind – Initial Setup (config/setup.php)
 * Ensures starter categories, auth support tables, and the configured admin exist.
 * Leaves the database clean for real citizen and item data.
 */
require_once __DIR__ . '/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found. Run the setup script from the command line.');
}

echo "<pre>\n";
echo "========================================\n";
echo "CivicFind – Database Setup Runner\n";
echo "========================================\n\n";

try {
    $pdo = get_db();
    echo "[✓] Connected to MySQL database 'lost_found_hub'.\n";

    // 1. Ensure starter categories exist
    $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount === 0) {
        $pdo->exec("
            INSERT INTO categories (name) VALUES
            ('Electronics'), ('Documents'), ('Wallet / Money'), ('Keys'),
            ('Clothing'), ('Bags'), ('Jewelry'), ('Books'), ('Accessories'), ('Other')
        ");
        echo "[✓] Initialized standard categories.\n";
    } else {
        echo "[*] Categories table ready ($catCount categories).\n";
    }

    // 2. Create the first admin only from local environment settings
    $adminEmail = strtolower(trim(env_value('ADMIN_EMAIL')));
    $adminPassword = env_value('ADMIN_PASSWORD');
    if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)
        && !str_ends_with($adminEmail, '@example.com')
        && strlen($adminPassword) >= 12
        && !str_contains(strtolower($adminPassword), 'replace-with')) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $adminEmail]);
        $admin = $stmt->fetch();
        if (!$admin) {
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, role, status)
                VALUES ('System Administrator', :email, :hash, 'admin', 'active')
            ");
            $ins->execute(['email' => $adminEmail, 'hash' => $hash]);
            echo "[✓] Administrator created: $adminEmail.\n";
        } else {
            echo "[*] Administrator account already exists: $adminEmail\n";
        }
    } else {
        echo "[!] Set a valid ADMIN_EMAIL and an ADMIN_PASSWORD of at least 12 characters in .env to create the first administrator.\n";
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS auth_login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email_hash CHAR(64) NOT NULL,
        ip_hash CHAR(64) NOT NULL,
        attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_lookup (email_hash, ip_hash, attempted_at),
        INDEX idx_login_attempts_age (attempted_at)
    )");
    echo "[✓] Authentication protection table ready.\n";

    $ageIndex = $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'auth_login_attempts' AND index_name = 'idx_login_attempts_age'")->fetchColumn();
    if (!(int)$ageIndex) $pdo->exec('ALTER TABLE auth_login_attempts ADD INDEX idx_login_attempts_age (attempted_at)');

    $claimIndex = $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'claims' AND index_name = 'uq_claim_item_claimant'")->fetchColumn();
    if (!(int)$claimIndex) {
        $duplicates = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT item_id, claimant_id FROM claims GROUP BY item_id, claimant_id HAVING COUNT(*) > 1) AS duplicate_claims")->fetchColumn();
        if ($duplicates === 0) {
            $pdo->exec('ALTER TABLE claims ADD UNIQUE KEY uq_claim_item_claimant (item_id, claimant_id)');
            echo "[✓] Duplicate claims are prevented by the database.\n";
        } else {
            echo "[!] Duplicate claim pairs exist; remove duplicates before adding the database uniqueness rule.\n";
        }
    }

    $itemCount = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "\nCurrent Registry Status:\n";
    echo " - Registered Users: $userCount\n";
    echo " - Total Items:      $itemCount\n";

    echo "\n========================================\n";
    echo "SUCCESS: CivicFind is ready for live data!\n";
    echo "========================================\n";
    echo "Visit: http://localhost:8000\n";

} catch (Exception $e) {
    echo "\n[ERROR] Setup failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";

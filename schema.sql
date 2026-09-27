-- Lost and Found Hub Management System
-- Beginner-level college project
-- MySQL 8.x

CREATE DATABASE IF NOT EXISTS lost_found_hub
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lost_found_hub;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS auth_login_attempts;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- USERS
-- =========================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- Failed sign-in tracking supports per-account and per-address rate limits.
CREATE TABLE auth_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempts_lookup (email_hash, ip_hash, attempted_at),
    INDEX idx_login_attempts_age (attempted_at)
);

-- =========================================================
-- CATEGORIES
-- =========================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- ITEMS
-- type = whether the report is for a lost or found item
-- status = current state of the item
-- =========================================================
CREATE TABLE items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,

    type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    item_date DATE NOT NULL,

    image_path VARCHAR(255) NULL,

    status ENUM('active', 'claimed', 'returned', 'closed')
        NOT NULL DEFAULT 'active',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_items_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT,

    INDEX idx_items_user (user_id),
    INDEX idx_items_category (category_id),
    INDEX idx_items_type_status (type, status),
    INDEX idx_items_date (item_date),
    INDEX idx_items_title (title)
);

-- =========================================================
-- CLAIMS
-- A claim can only be made against a FOUND item.
-- The application should enforce that rule in PHP.
-- =========================================================
CREATE TABLE claims (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    claimant_id INT UNSIGNED NOT NULL,

    claim_message TEXT NOT NULL,
    proof_description TEXT NULL,

    status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'pending',

    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    admin_note TEXT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_claims_item
        FOREIGN KEY (item_id) REFERENCES items(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_claims_claimant
        FOREIGN KEY (claimant_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_claims_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_claims_item (item_id),
    INDEX idx_claims_claimant (claimant_id),
    INDEX idx_claims_status (status),
    UNIQUE KEY uq_claim_item_claimant (item_id, claimant_id)
);

-- =========================================================
-- STARTER CATEGORIES
-- =========================================================
INSERT INTO categories (name) VALUES
('Electronics'),
('Documents'),
('Wallet / Money'),
('Keys'),
('Clothing'),
('Bags'),
('Jewelry'),
('Books'),
('Accessories'),
('Other');

-- =========================================================
-- IMPORTANT:
-- Do NOT insert a real admin password into this schema.
-- Create the admin account through a setup script using
-- PHP password_hash(), or insert a generated password hash.
-- =========================================================

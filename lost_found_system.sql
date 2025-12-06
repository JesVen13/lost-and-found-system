CREATE DATABASE IF NOT EXISTS lost_found_system;
USE lost_found_system;

DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS found_items;
DROP TABLE IF EXISTS lost_items;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('user','staff','admin') NOT NULL DEFAULT 'user',
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lost_items (
    lost_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    date_lost DATE NOT NULL,
    color VARCHAR(50) NOT NULL,
    location_lost VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('lost','found','claimed') DEFAULT 'lost',
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    claim_status ENUM('unclaimed','pending_claim','claimed') DEFAULT 'unclaimed',
    image_path VARCHAR(255) DEFAULT NULL,
    contact_info VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_reporter (reporter_id),
    INDEX idx_status (status),
    INDEX idx_approval (approval_status),
    INDEX idx_claim_status (claim_status),
    INDEX idx_date_lost (date_lost),
    INDEX idx_location (location_lost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE found_items (
    found_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    date_found DATE NOT NULL,
    color VARCHAR(50) NOT NULL,
    location_found VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('available','claimed') DEFAULT 'available',
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    claim_status ENUM('unclaimed','pending_claim','claimed') DEFAULT 'unclaimed',
    claimed_by INT DEFAULT NULL,
    claimed_at TIMESTAMP NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    contact_info VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (claimed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_reporter (reporter_id),
    INDEX idx_status (status),
    INDEX idx_approval (approval_status),
    INDEX idx_claim_status (claim_status),
    INDEX idx_claimed_by (claimed_by),
    INDEX idx_date_found (date_found),
    INDEX idx_location (location_found)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_type ENUM('lost', 'found') NOT NULL,
    claimer_id INT NOT NULL,
    claim_message TEXT NOT NULL,
    claim_status ENUM('pending','approved','rejected','claimed') DEFAULT 'pending',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    admin_processed_by INT NULL,
    admin_processed_at TIMESTAMP NULL,
    FOREIGN KEY (claimer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_claimer (claimer_id),
    INDEX idx_claim_status (claim_status),
    INDEX idx_item (item_id, item_type),
    INDEX idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
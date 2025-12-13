-- =========================================
-- GATEWAY DATABASE: sekolah_gateway
-- =========================================

-- Tabel tenants untuk menyimpan informasi koneksi database tiap tenant
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nama tenant/sekolah',
    `db_host` VARCHAR(255) NOT NULL DEFAULT '127.0.0.1',
    `db_port` INT NOT NULL DEFAULT 3306,
    `db_name` VARCHAR(255) NOT NULL COMMENT 'Nama database tenant',
    `db_user` VARCHAR(255) NOT NULL,
    `db_pass` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_tenants_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabel untuk menyimpan konfigurasi koneksi database tiap tenant';

-- =========================================
-- Contoh: Membuat tabel enrollment_1 untuk tenant_id=1
-- =========================================
CREATE TABLE IF NOT EXISTS `enrollment_1` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(255) NOT NULL COMMENT 'Nama/identitas untuk enrollment',
    `face_encoding` JSON NOT NULL COMMENT 'Face embedding 512-dimensional vector',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_enrollment_1_user` FOREIGN KEY (`user_id`) 
        REFERENCES `user_1`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX `idx_enrollment_1_user_id` (`user_id`),
    INDEX `idx_enrollment_1_label` (`label`),
    INDEX `idx_enrollment_1_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabel enrollment untuk tenant 1';

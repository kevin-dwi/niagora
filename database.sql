-- =======================================================
-- Database Schema for Kopdesia
-- =======================================================

CREATE DATABASE IF NOT EXISTS `kopdesia` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kopdesia`;

-- 1. Table: users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('penjual', 'pembeli') NOT NULL DEFAULT 'pembeli',
    `no_wa` VARCHAR(20) NULL DEFAULT NULL,
    `alamat` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Table: rating_produk
CREATE TABLE IF NOT EXISTS `rating_produk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produk_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `pesanan_id` INT NULL DEFAULT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `ulasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`produk_id`) REFERENCES `produk`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pembeli_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_product` (`pembeli_id`, `produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Table: rating_toko
CREATE TABLE IF NOT EXISTS `rating_toko` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `penjual_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `pesanan_id` INT NULL DEFAULT NULL,
    `rating` TINYINT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `ulasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`penjual_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pembeli_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_seller` (`pembeli_id`, `penjual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Table: masukan_penjual
CREATE TABLE IF NOT EXISTS `masukan_penjual` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `penjual_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `subjek` VARCHAR(150) NOT NULL,
    `pesan` TEXT NOT NULL,
    `status` ENUM('belum_dibaca', 'dibaca', 'dibalas') DEFAULT 'belum_dibaca',
    `balasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`penjual_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pembeli_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table: kategori
CREATE TABLE IF NOT EXISTS `kategori` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `icon` VARCHAR(10) DEFAULT '📦',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table: produk
CREATE TABLE IF NOT EXISTS `produk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `penjual_id` INT NOT NULL,
    `kategori_id` INT NULL,
    `nama_produk` VARCHAR(150) NOT NULL,
    `deskripsi` TEXT NULL,
    `harga` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `stok` INT NOT NULL DEFAULT 0,
    `terjual` INT NOT NULL DEFAULT 0,
    `gambar` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`penjual_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table: pesanan
CREATE TABLE IF NOT EXISTS `pesanan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pembeli_id` INT NOT NULL,
    `penjual_id` INT NOT NULL,
    `total_harga` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `status` ENUM('Menunggu Pembayaran', 'Diproses', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Selesai',
    `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai / COD',
    `nama_penerima` VARCHAR(100) NULL,
    `no_wa_penerima` VARCHAR(20) NULL,
    `alamat_pengiriman` TEXT NULL,
    `catatan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pembeli_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`penjual_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table: detail_pesanan
CREATE TABLE IF NOT EXISTS `detail_pesanan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pesanan_id` INT NOT NULL,
    `produk_id` INT NULL,
    `nama_produk` VARCHAR(150) NOT NULL,
    `harga` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `jumlah` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produk_id`) REFERENCES `produk`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Categories
INSERT INTO `kategori` (`id`, `nama_kategori`, `slug`, `icon`) VALUES
(1, 'Makanan', 'makanan', '🍜'),
(2, 'Minuman', 'minuman', '🥤'),
(3, 'Sembako', 'sembako', '🛒'),
(4, 'Rumah Tangga', 'rumah-tangga', '🏠'),
(5, 'Pertanian & Ternak', 'pertanian-ternak', '🌱'),
(6, 'Fashion & Kerajinan', 'fashion-kerajinan', '👕')
ON DUPLICATE KEY UPDATE `nama_kategori`=VALUES(`nama_kategori`);

-- Default demo users (Password: admin123 / user123)
-- Password hash for 'password123': $2y$10$tZ8QW6QZ44YlR0WnUu9vCe80WmsdFvGj.95p2xZ3W0b6Wz72dKj3y
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `no_wa`, `alamat`) VALUES
(1, 'Koperasi Penjual Berkah', 'penjual@kopdesia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'penjual', '081234567890', 'Desa Maju Makmur RT 02 RW 01'),
(2, 'Budi Pembeli Santoso', 'pembeli@kopdesia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pembeli', '089876543210', 'Dusun Harapan Blok C No. 12')
ON DUPLICATE KEY UPDATE `nama`=VALUES(`nama`);

-- Note: default password for both accounts above is: password

<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "kopdesia";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Pastikan tabel rating & masukan selalu ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `rating_produk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produk_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `pesanan_id` INT NULL DEFAULT NULL,
    `rating` TINYINT NOT NULL,
    `ulasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`produk_id`),
    INDEX (`pembeli_id`),
    UNIQUE KEY `unique_user_product` (`pembeli_id`, `produk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `rating_toko` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `penjual_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `pesanan_id` INT NULL DEFAULT NULL,
    `rating` TINYINT NOT NULL,
    `ulasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`penjual_id`),
    INDEX (`pembeli_id`),
    UNIQUE KEY `unique_user_seller` (`pembeli_id`, `penjual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `masukan_penjual` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `penjual_id` INT NOT NULL,
    `pembeli_id` INT NOT NULL,
    `subjek` VARCHAR(150) NOT NULL,
    `pesan` TEXT NOT NULL,
    `status` ENUM('belum_dibaca', 'dibaca', 'dibalas') DEFAULT 'belum_dibaca',
    `balasan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`penjual_id`),
    INDEX (`pembeli_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Update icon kategori jika default
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '🍜', `slug` = 'makanan' WHERE `nama_kategori` LIKE '%Makanan%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '🥤', `slug` = 'minuman' WHERE `nama_kategori` LIKE '%Minuman%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '🛒', `slug` = 'sembako' WHERE `nama_kategori` LIKE '%Sembako%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '🏠', `slug` = 'rumah' WHERE `nama_kategori` LIKE '%Rumah%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '🎒', `slug` = 'sekolah' WHERE `nama_kategori` LIKE '%Sekolah%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '👕', `slug` = 'fashion' WHERE `nama_kategori` LIKE '%Fashion%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");
mysqli_query($conn, "UPDATE `kategori` SET `icon` = '✨', `slug` = 'lainnya' WHERE `nama_kategori` LIKE '%Lainnya%' AND (`icon` = '📦' OR `icon` IS NULL OR `icon` = '')");

/**
 * Helper: Ambil rata-rata rating & total ulasan produk
 */
function get_product_rating($conn, $produkId) {
    $produkId = intval($produkId);
    $q = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ulasan FROM rating_produk WHERE produk_id = $produkId");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        return [
            'rating' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0,
            'count' => intval($row['total_ulasan'])
        ];
    }
    return ['rating' => 0, 'count' => 0];
}

/**
 * Helper: Ambil rata-rata rating & total ulasan toko/penjual
 */
function get_seller_rating($conn, $penjualId) {
    $penjualId = intval($penjualId);
    $q = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ulasan FROM rating_toko WHERE penjual_id = $penjualId");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        return [
            'rating' => $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0,
            'count' => intval($row['total_ulasan'])
        ];
    }
    return ['rating' => 0, 'count' => 0];
}

/**
 * Helper: Render rating bintang badge HTML
 */
function render_rating_stars($rating, $count = null, $size = 'sm') {
    $rating = (float)$rating;
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $stars .= '<span class="star filled">★</span>';
        } elseif ($rating >= ($i - 0.5)) {
            $stars .= '<span class="star half">★</span>';
        } else {
            $stars .= '<span class="star empty">☆</span>';
        }
    }
    $ratingText = $rating > 0 ? number_format($rating, 1) : 'Baru';
    $countText = ($count !== null && $count > 0) ? " ({$count})" : '';

    return '<span class="rating-badge rating-' . $size . '" title="' . ($rating > 0 ? "Rating $rating dari 5" : "Belum ada ulasan") . '">'
        . '<span class="stars-icons">' . $stars . '</span>'
        . '<span class="rating-val">' . $ratingText . $countText . '</span>'
        . '</span>';
}
?>
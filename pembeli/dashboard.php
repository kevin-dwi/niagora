<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$buyerName = $_SESSION['nama'] ?? 'Pembeli';

// Parameters Filter & Search
$keyword = trim($_GET['search'] ?? '');
$kategori = intval($_GET['kategori'] ?? 0);
$harga_min = !empty($_GET['harga_min']) ? floatval($_GET['harga_min']) : 0;
$harga_max = !empty($_GET['harga_max']) ? floatval($_GET['harga_max']) : 0;
$sort = trim($_GET['sort'] ?? 'terbaru');

// Ambil Kategori untuk filter
$categories = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Query dasar Produk
$sql = "
    SELECT
        produk.*,
        kategori.nama_kategori,
        kategori.icon AS kategori_icon,
        users.nama AS nama_penjual,
        users.no_wa AS no_wa_penjual,
        users.alamat AS alamat_penjual
    FROM produk
    LEFT JOIN kategori ON produk.kategori_id = kategori.id
    INNER JOIN users ON produk.penjual_id = users.id
    WHERE produk.stok > 0
";

if (!empty($keyword)) {
    $safeKeyword = mysqli_real_escape_string($conn, $keyword);
    $sql .= " AND (produk.nama_produk LIKE '%$safeKeyword%' OR produk.deskripsi LIKE '%$safeKeyword%' OR users.nama LIKE '%$safeKeyword%') ";
}

if ($kategori > 0) {
    $sql .= " AND produk.kategori_id = $kategori ";
}

if ($harga_min > 0) {
    $sql .= " AND produk.harga >= $harga_min ";
}

if ($harga_max > 0) {
    $sql .= " AND produk.harga <= $harga_max ";
}

// Sorting
switch ($sort) {
    case 'termurah':
        $sql .= " ORDER BY produk.harga ASC ";
        break;
    case 'termahal':
        $sql .= " ORDER BY produk.harga DESC ";
        break;
    case 'terlaris':
        $sql .= " ORDER BY produk.terjual DESC, produk.created_at DESC ";
        break;
    default:
        $sql .= " ORDER BY produk.created_at DESC ";
        break;
}

$products = mysqli_query($conn, $sql);

// Hitung total item di keranjang
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $jumlah) {
        $cartCount += intval($jumlah);
    }
}

$addedSuccess = isset($_GET['added']) ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etalase Produk Desa — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f6f8f7;
            font-family: 'DM Sans', sans-serif;
            color: #17251e;
            margin: 0;
        }

        .buyer-navbar {
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6%;
            background: white;
            border-bottom: 1px solid #e7ece9;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: #17251e;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-link {
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #55635c;
            transition: .2s;
            text-decoration: none;
        }
        .nav-link:hover, .nav-link.active {
            color: #dc2626;
            background: #fee2e2;
        }

        .btn-cart {
            position: relative;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            background: #dc2626;
            color: white;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: .2s;
        }
        .btn-cart:hover {
            background: #991b1b;
            transform: translateY(-2px);
        }
        .cart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ff4757;
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
            margin-left: 3px;
        }

        .hero-buyer {
            padding: 40px 6% 25px;
            background: radial-gradient(circle at 90% 10%, #fee2e2, transparent 40%), #ffffff;
            border-bottom: 1px solid #f2e6e6;
        }
        .hero-buyer h1 {
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #1c1113;
        }
        .hero-buyer h1 span { color: #dc2626; }
        .hero-buyer p {
            color: #716668;
            font-size: 14px;
            margin-top: 6px;
            margin-bottom: 0;
        }

        /* SEARCH & FILTER SECTION */
        .filter-section {
            padding: 25px 6% 15px;
        }
        .filter-card {
            background: white;
            border: 1px solid #e5ece8;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0, 40, 20, 0.04);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1.2fr 1fr 1fr 1.2fr auto auto;
            gap: 12px;
            align-items: center;
        }
        .filter-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #dce4df;
            border-radius: 10px;
            outline: none;
            background: #fbfcfb;
            font-family: inherit;
            font-size: 12px;
            box-sizing: border-box;
            color: #213228;
            transition: .2s;
        }
        .filter-control:focus {
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220,38,38,.1);
        }
        .btn-filter {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: #dc2626;
            color: white;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: .2s;
            white-space: nowrap;
        }
        .btn-filter:hover { background: #991b1b; }
        .btn-reset {
            padding: 12px 15px;
            border: 1px solid #dce4df;
            border-radius: 10px;
            background: #faf5f5;
            color: #616f67;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            text-align: center;
            white-space: nowrap;
            transition: .2s;
        }
        .btn-reset:hover { background: #fee2e2; color: #dc2626; }

        /* CATEGORY CHIPS */
        .category-chips {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #f0f4f1;
        }
        .chip {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #faf5f5;
            color: #55635b;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .chip:hover, .chip.active {
            background: #dc2626;
            color: white;
        }

        /* PRODUCTS CATALOG */
        .catalog-container {
            padding: 20px 6% 60px;
        }
        .catalog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .catalog-header h2 {
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 20px;
            font-weight: 700;
        }
        .catalog-header span {
            color: #7b8881;
            font-size: 13px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: white;
            border: 1px solid #e6ede8;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: .3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(55, 22, 25, 0.09);
            border-color: #fca5a5;
        }
        .product-thumb {
            width: 100%;
            height: 190px;
            position: relative;
            background: #fdf5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .3s;
        }
        .product-card:hover .product-thumb img {
            transform: scale(1.05);
        }
        .no-img { font-size: 55px; }

        .badge-cat {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            color: #dc2626;
            font-size: 10px;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .product-title {
            font-size: 15px;
            font-weight: 700;
            color: #1c2b23;
            margin: 0 0 6px;
            line-height: 1.35;
        }
        .product-desc {
            font-size: 11px;
            color: #7b8881;
            line-height: 1.45;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 32px;
        }
        .seller-info {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #5d6d64;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e8efe9;
        }
        .seller-icon {
            font-size: 13px;
        }
        .product-pricing {
            margin-top: auto;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .price-val {
            font-size: 17px;
            font-weight: 800;
            color: #dc2626;
        }
        .stock-val {
            font-size: 11px;
            color: #8c9891;
        }

        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .btn-action-buy {
            padding: 10px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: .2s;
        }
        .btn-add-cart {
            background: #dc2626;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-add-cart:hover {
            background: #991b1b;
        }
        .btn-chat-wa {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .btn-chat-wa:hover {
            background: #25d366;
            color: white;
            border-color: #25d366;
        }
        .btn-feedback-sm {
            grid-column: 1 / -1;
            padding: 8px;
            background: #fdf2f2;
            color: #b91c1c;
            border: 1px dashed #fca5a5;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-feedback-sm:hover {
            background: #fee2e2;
            color: #7f1d1d;
        }

        .toast-notify {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: #7f1d1d;
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            font-size: 13px;
            font-weight: 600;
            z-index: 1000;
            animation: slideIn .3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .empty-box {
            padding: 80px 20px;
            background: white;
            border: 1px solid #e7ece9;
            border-radius: 20px;
            text-align: center;
        }
        .empty-icon { font-size: 55px; margin-bottom: 12px; }

        @media(max-width: 1050px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }
            .filter-grid input[name="search"] {
                grid-column: 1 / -1;
            }
        }
        @media(max-width: 650px) {
            .buyer-navbar { padding: 0 15px; }
            .hero-buyer, .filter-section, .catalog-container { padding-left: 15px; padding-right: 15px; }
            .filter-grid { grid-template-columns: 1fr; }
            .btn-group { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<!-- NAVBAR -->
<nav class="buyer-navbar">
    <a href="dashboard.php" class="nav-brand">
        <div class="logo-icon">K</div>
        <span>Niagora</span>
    </a>

    <div class="nav-links">
        <a href="dashboard.php" class="nav-link active">Etalase</a>
        <a href="pesanan.php" class="nav-link">Pesanan Saya</a>
        <a href="masukan_saya.php" class="nav-link">Masukan Saya</a>
        <a href="keranjang.php" class="btn-cart">
            🛒 Keranjang
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <a href="../auth/logout.php" class="nav-link" style="color:#d45555;font-weight:700;">
            Keluar
        </a>
    </div>
</nav>

<!-- HERO -->
<section class="hero-buyer">
    <h1>Selamat Berbelanja, <span><?= htmlspecialchars($buyerName) ?></span>! 👋</h1>
    <p>Dukung produk & UMKM desa lokal. Belanja mudah, cepat, dan terpercaya langsung dari penjual.</p>
</section>

<!-- SEARCH & ADVANCED FILTER -->
<section class="filter-section">
    <div class="filter-card">
        <form method="GET" action="dashboard.php">
            <div class="filter-grid">
                <!-- Search Keyword -->
                <input
                    type="text"
                    name="search"
                    class="filter-control"
                    placeholder="🔍 Cari nama produk, deskripsi, atau toko..."
                    value="<?= htmlspecialchars($keyword) ?>"
                >

                <!-- Filter Kategori Dropdown -->
                <select name="kategori" class="filter-control">
                    <option value="0">Semua Kategori</option>
                    <?php
                    mysqli_data_seek($categories, 0);
                    while ($c = mysqli_fetch_assoc($categories)):
                    ?>
                        <option value="<?= $c['id'] ?>" <?= $kategori == $c['id'] ? 'selected' : '' ?>>
                            <?= $c['icon'] ?? '📦' ?> <?= htmlspecialchars($c['nama_kategori']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Harga Min -->
                <input
                    type="number"
                    name="harga_min"
                    class="filter-control"
                    placeholder="Harga Min (Rp)"
                    value="<?= $harga_min > 0 ? $harga_min : '' ?>"
                >

                <!-- Harga Max -->
                <input
                    type="number"
                    name="harga_max"
                    class="filter-control"
                    placeholder="Harga Max (Rp)"
                    value="<?= $harga_max > 0 ? $harga_max : '' ?>"
                >

                <!-- Sorting -->
                <select name="sort" class="filter-control">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>⚡ Terbaru</option>
                    <option value="terlaris" <?= $sort === 'terlaris' ? 'selected' : '' ?>>🔥 Terlaris</option>
                    <option value="termurah" <?= $sort === 'termurah' ? 'selected' : '' ?>>💰 Harga Terendah</option>
                    <option value="termahal" <?= $sort === 'termahal' ? 'selected' : '' ?>>💎 Harga Tertinggi</option>
                </select>

                <!-- Submit Filter -->
                <button type="submit" class="btn-filter">
                    Terapkan Filter
                </button>

                <!-- Reset -->
                <?php if (!empty($keyword) || $kategori > 0 || $harga_min > 0 || $harga_max > 0 || $sort !== 'terbaru'): ?>
                    <a href="dashboard.php" class="btn-reset">
                        ✕ Reset
                    </a>
                <?php endif; ?>
            </div>

            <!-- Quick Category Badges -->
            <div class="category-chips">
                <a href="dashboard.php" class="chip <?= $kategori === 0 ? 'active' : '' ?>">
                    ✨ Semua Kategori
                </a>
                <?php
                mysqli_data_seek($categories, 0);
                while ($c = mysqli_fetch_assoc($categories)):
                ?>
                    <a href="dashboard.php?kategori=<?= $c['id'] ?>&search=<?= urlencode($keyword) ?>&sort=<?= $sort ?>" class="chip <?= $kategori == $c['id'] ? 'active' : '' ?>">
                        <?= $c['icon'] ?? '📦' ?> <?= htmlspecialchars($c['nama_kategori']) ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </form>
    </div>
</section>

<!-- PRODUCTS CATALOG -->
<main class="catalog-container">
    <div class="catalog-header">
        <div>
            <h2>Produk Tersedia</h2>
            <span>Menampilkan <?= mysqli_num_rows($products) ?> produk yang siap dipesan</span>
        </div>
    </div>

    <?php if (mysqli_num_rows($products) > 0): ?>
        <div class="product-grid">
            <?php while ($prod = mysqli_fetch_assoc($products)):
                // Format WhatsApp link for seller
                $waNum = preg_replace('/[^0-9]/', '', $prod['no_wa_penjual'] ?? '');
                if (!empty($waNum) && substr($waNum, 0, 1) === '0') {
                    $waNum = '62' . substr($waNum, 1);
                }
                $waMessage = "Halo " . ($prod['nama_penjual'] ?? 'Penjual') . ", saya ingin bertanya tentang produk '" . $prod['nama_produk'] . "' di Niagora.";
                $waLink = !empty($waNum) ? "https://wa.me/" . $waNum . "?text=" . urlencode($waMessage) : "#";
            ?>
                <article class="product-card">
                    <div class="product-thumb">
                        <?php if (!empty($prod['gambar'])): ?>
                            <img src="../assets/img/<?= htmlspecialchars($prod['gambar']) ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>">
                        <?php else: ?>
                            <span class="no-img">📦</span>
                        <?php endif; ?>
                        <span class="badge-cat">
                            <?= htmlspecialchars($prod['nama_kategori'] ?? 'Umum') ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;margin-bottom:6px;">
                            <h3 class="product-title" style="margin-bottom:0;">
                                <?= htmlspecialchars($prod['nama_produk']) ?>
                            </h3>
                            <?php
                            $pRating = get_product_rating($conn, $prod['id']);
                            echo render_rating_stars($pRating['rating'], $pRating['count'], 'sm');
                            ?>
                        </div>

                        <p class="product-desc">
                            <?= htmlspecialchars($prod['deskripsi'] ?: 'Produk berkualitas dari pelaku usaha desa.') ?>
                        </p>

                        <div class="seller-info" style="justify-content:space-between;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span class="seller-icon">🏪</span>
                                <span><strong><?= htmlspecialchars($prod['nama_penjual']) ?></strong></span>
                            </div>
                            <?php
                            $sRating = get_seller_rating($conn, $prod['penjual_id']);
                            echo render_rating_stars($sRating['rating'], null, 'sm');
                            ?>
                        </div>

                        <div class="product-pricing">
                            <div class="price-val">
                                Rp <?= number_format($prod['harga'], 0, ',', '.') ?>
                            </div>
                            <div class="stock-val">
                                Stok: <strong><?= $prod['stok'] ?></strong>
                            </div>
                        </div>

                        <div class="btn-group">
                            <a href="tambah_keranjang.php?id=<?= $prod['id'] ?>" class="btn-action-buy btn-add-cart">
                                🛒 + Keranjang
                            </a>

                            <?php if (!empty($waNum)): ?>
                                <a href="<?= $waLink ?>" target="_blank" class="btn-action-buy btn-chat-wa" title="Tanya penjual di WhatsApp">
                                    💬 Tanya WA
                                </a>
                            <?php else: ?>
                                <button class="btn-action-buy btn-chat-wa" style="opacity:0.5;cursor:not-allowed;" title="Penjual belum menambahkan nomor WA">
                                    💬 No WA -
                                </button>
                            <?php endif; ?>

                            <button type="button" class="btn-feedback-sm" onclick="openFeedbackModal(<?= $prod['penjual_id'] ?>, '<?= htmlspecialchars(addslashes($prod['nama_penjual'])) ?>', '<?= htmlspecialchars(addslashes($prod['nama_produk'])) ?>')">
                                ✉️ Beri Masukan ke Penjual
                            </button>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <div class="empty-icon">🔍</div>
            <h3 style="font-size:18px;margin-bottom:8px;">Produk Tidak Ditemukan</h3>
            <p style="color:#78847d;font-size:13px;max-width:400px;margin:0 auto 20px;">
                Maaf, tidak ada produk yang cocok dengan kata kunci atau filter pencarian Anda.
            </p>
            <a href="dashboard.php" class="btn-filter" style="display:inline-block;text-decoration:none;">
                Lihat Semua Produk
            </a>
        </div>
    <?php endif; ?>
</main>

<!-- MODAL BERI MASUKAN -->
<div class="modal-overlay" id="feedbackModal">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeFeedbackModal()">&times;</button>
        <div style="margin-bottom:18px;">
            <h2 style="font-size:20px;font-family:'Plus Jakarta Sans',sans-serif;margin:0 0 6px;color:#dc2626;">
                ✉️ Beri Masukan ke Penjual
            </h2>
            <p style="font-size:12px;color:#78847d;margin:0;">
                Toko: <strong id="feedbackSellerName" style="color:#1e1315;">-</strong>
            </p>
        </div>

        <form action="kirim_masukan.php" method="POST">
            <input type="hidden" name="penjual_id" id="feedbackSellerId" value="">
            <input type="hidden" name="redirect" value="dashboard">

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;">Subjek / Topik Masukan</label>
                <input
                    type="text"
                    name="subjek"
                    id="feedbackSubject"
                    required
                    placeholder="Contoh: Saran Kualitas Produk / Pertanyaan Stok"
                    style="width:100%;padding:12px;border:1px solid #dce4df;border-radius:10px;font-size:13px;box-sizing:border-box;outline:none;"
                >
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;">Pesan / Masukan / Saran Anda</label>
                <textarea
                    name="pesan"
                    rows="4"
                    required
                    placeholder="Tuliskan masukan, saran atau pertanyaan Anda secara sopan..."
                    style="width:100%;padding:12px;border:1px solid #dce4df;border-radius:10px;font-size:13px;box-sizing:border-box;outline:none;font-family:inherit;resize:vertical;"
                ></textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" onclick="closeFeedbackModal()" style="padding:10px 18px;border:1px solid #dce4df;border-radius:9px;background:#faf5f5;font-size:12px;font-weight:700;cursor:pointer;">
                    Batal
                </button>
                <button type="submit" style="padding:10px 22px;border:none;border-radius:9px;background:#dc2626;color:white;font-size:12px;font-weight:700;cursor:pointer;">
                    ✉️ Kirim Masukan
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($addedSuccess): ?>
    <div class="toast-notify" id="toastMsg">
        🛒 Produk berhasil dimasukkan ke keranjang belanja!
    </div>
    <script>
        setTimeout(function(){
            const t = document.getElementById('toastMsg');
            if (t) t.style.display = 'none';
        }, 3000);
    </script>
<?php endif; ?>

<?php if (isset($_GET['feedback_sent'])): ?>
    <div class="toast-notify" id="toastFb">
        ✉️ Masukan Anda berhasil dikirimkan ke penjual!
    </div>
    <script>
        setTimeout(function(){
            const t = document.getElementById('toastFb');
            if (t) t.style.display = 'none';
        }, 4000);
    </script>
<?php endif; ?>

<script>
function openFeedbackModal(sellerId, sellerName, productName) {
    document.getElementById('feedbackSellerId').value = sellerId;
    document.getElementById('feedbackSellerName').textContent = sellerName;
    if (productName) {
        document.getElementById('feedbackSubject').value = 'Masukan produk: ' + productName;
    } else {
        document.getElementById('feedbackSubject').value = '';
    }
    document.getElementById('feedbackModal').classList.add('active');
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.remove('active');
}
</script>

</body>
</html>

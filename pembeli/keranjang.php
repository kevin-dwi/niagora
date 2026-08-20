<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$products = [];
$totalHarga = 0;
$totalItem = 0;

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $idString = implode(',', $ids);

    $query = mysqli_query(
        $conn,
        "SELECT
            produk.*,
            kategori.nama_kategori,
            users.nama AS nama_penjual,
            users.no_wa AS no_wa_penjual
         FROM produk
         LEFT JOIN kategori ON produk.kategori_id = kategori.id
         INNER JOIN users ON produk.penjual_id = users.id
         WHERE produk.id IN ($idString)"
    );

    while ($product = mysqli_fetch_assoc($query)) {
        $qty = intval($cart[$product['id']] ?? 0);
        if ($qty > $product['stok']) {
            $qty = $product['stok'];
            $_SESSION['cart'][$product['id']] = $qty;
        }

        if ($qty > 0) {
            $subtotal = $product['harga'] * $qty;
            $totalHarga += $subtotal;
            $totalItem += $qty;

            $product['qty'] = $qty;
            $product['subtotal'] = $subtotal;
            $products[] = $product;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
            font-family: 'DM Sans', sans-serif;
            color: #1e1315;
            margin: 0;
        }

        .buyer-navbar {
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6%;
            background: white;
            border-bottom: 1px solid #f2e6e6;
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
            color: #1e1315;
            text-decoration: none;
        }
        .nav-links { display: flex; align-items: center; gap: 15px; }
        .nav-link {
            padding: 9px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #736769;
            text-decoration: none;
            transition: .2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #dc2626;
            background: #fee2e2;
        }

        .page-container {
            width: min(1080px, calc(100% - 40px));
            margin: 35px auto 60px;
        }

        .page-heading {
            margin-bottom: 25px;
        }
        .page-heading h1 {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 6px;
        }
        .page-heading p {
            color: #7b8881;
            font-size: 13px;
            margin: 0;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .cart-card {
            background: white;
            border: 1px solid #e5ece8;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
        }

        .cart-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #edf2ee;
            margin-bottom: 15px;
        }
        .cart-header-row h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #f0f4f1;
        }
        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: #edf4ef;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }
        .item-category {
            font-size: 10px;
            font-weight: 700;
            color: #dc2626;
            text-transform: uppercase;
        }
        .item-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e1315;
            margin: 3px 0 4px;
        }
        .item-seller {
            font-size: 11px;
            color: #7d8b83;
            margin-bottom: 6px;
        }
        .item-unit-price {
            font-size: 12px;
            color: #5b6961;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #faf5f5;
            padding: 4px 8px;
            border-radius: 8px;
            border: 1px solid #f2e6e6;
        }
        .qty-btn {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: white;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: .2s;
        }
        .qty-btn:hover {
            background: #dc2626;
            color: white;
        }
        .qty-value {
            font-weight: 700;
            font-size: 13px;
            min-width: 22px;
            text-align: center;
        }

        .item-subtotal {
            text-align: right;
            min-width: 100px;
        }
        .subtotal-price {
            font-size: 15px;
            font-weight: 800;
            color: #dc2626;
        }
        .btn-delete {
            color: #c94e5c;
            text-decoration: none;
            font-size: 11px;
            display: block;
            margin-top: 6px;
        }
        .btn-delete:hover {
            text-decoration: underline;
        }

        /* SUMMARY */
        .summary-card h3 {
            margin: 0 0 18px;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 18px;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6a7870;
            margin-bottom: 12px;
        }
        .summary-line strong {
            color: #17251e;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px dashed #d5ded8;
            font-size: 16px;
            font-weight: 800;
            color: #17251e;
        }
        .summary-total .total-amount {
            color: #dc2626;
            font-size: 20px;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            background: #dc2626;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            box-sizing: border-box;
            transition: .25s;
        }
        .btn-checkout:hover {
            background: #991b1b;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.25);
        }

        .btn-clear-cart {
            display: block;
            text-align: center;
            color: #8c9891;
            font-size: 12px;
            margin-top: 14px;
            text-decoration: none;
        }
        .btn-clear-cart:hover {
            color: #c94e5c;
        }

        .empty-cart-card {
            background: white;
            border: 1px solid #e6ede8;
            border-radius: 20px;
            padding: 80px 20px;
            text-align: center;
        }
        .empty-icon { font-size: 60px; margin-bottom: 15px; }

        @media (max-width: 800px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-item {
                flex-wrap: wrap;
            }
            .item-subtotal {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px dashed #edf2ee;
            }
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
        <a href="dashboard.php" class="nav-link">← Kembali ke Etalase</a>
        <a href="pesanan.php" class="nav-link">Pesanan Saya</a>
        <a href="masukan_saya.php" class="nav-link">Masukan Saya</a>
        <a href="../auth/logout.php" class="nav-link" style="color:#d45555;font-weight:700;">
            Keluar
        </a>
    </div>
</nav>

<div class="page-container">
    <div class="page-heading">
        <h1>Keranjang Belanja 🛒</h1>
        <p>Tinjau barang pilihan Anda sebelum melanjutkan ke proses transaksi dan cetak struk.</p>
    </div>

    <?php if (!empty($products)): ?>
        <div class="cart-layout">
            <!-- ITEMS LIST -->
            <div class="cart-card">
                <div class="cart-header-row">
                    <h3>Daftar Produk (<?= count($products) ?> jenis)</h3>
                    <a href="dashboard.php" style="color:#dc2626;font-size:12px;font-weight:700;text-decoration:none;">
                        + Tambah Produk Lain
                    </a>
                </div>

                <?php foreach ($products as $item):
                    $waSeller = preg_replace('/[^0-9]/', '', $item['no_wa_penjual'] ?? '');
                    if (!empty($waSeller) && substr($waSeller, 0, 1) === '0') {
                        $waSeller = '62' . substr($waSeller, 1);
                    }
                ?>
                    <div class="cart-item">
                        <div class="item-image">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                            <?php else: ?>
                                <span style="font-size:32px;">📦</span>
                            <?php endif; ?>
                        </div>

                        <div class="item-details">
                            <span class="item-category"><?= htmlspecialchars($item['nama_kategori'] ?? 'Umum') ?></span>
                            <div class="item-name"><?= htmlspecialchars($item['nama_produk']) ?></div>
                            <div class="item-seller">
                                🏪 Penjual: <strong><?= htmlspecialchars($item['nama_penjual']) ?></strong>
                                <?php if (!empty($waSeller)): ?>
                                    • <a href="https://wa.me/<?= $waSeller ?>?text=Halo%20<?= urlencode($item['nama_penjual']) ?>,%20saya%20tertarik%20dengan%20produk%20<?= urlencode($item['nama_produk']) ?>" target="_blank" style="color:#dc2626;text-decoration:none;font-weight:600;">
                                        💬 Chat WA
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="item-unit-price">
                                Rp <?= number_format($item['harga'], 0, ',', '.') ?> / unit (Stok: <?= $item['stok'] ?>)
                            </div>
                        </div>

                        <div class="qty-controls">
                            <a href="update_keranjang.php?id=<?= $item['id'] ?>&aksi=kurang" class="qty-btn" title="Kurangi jumlah">−</a>
                            <span class="qty-value"><?= $item['qty'] ?></span>
                            <a href="update_keranjang.php?id=<?= $item['id'] ?>&aksi=tambah" class="qty-btn" title="Tambah jumlah">+</a>
                        </div>

                        <div class="item-subtotal">
                            <div class="subtotal-price">
                                Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                            </div>
                            <a href="update_keranjang.php?id=<?= $item['id'] ?>&aksi=hapus" class="btn-delete" onclick="return confirm('Hapus produk ini dari keranjang?')">
                                🗑️ Hapus
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- SUMMARY & CHECKOUT -->
            <div class="cart-card summary-card">
                <h3>Ringkasan Pesanan</h3>

                <div class="summary-line">
                    <span>Total Kuantitas Barang</span>
                    <strong><?= $totalItem ?> pcs</strong>
                </div>

                <div class="summary-line">
                    <span>Subtotal Produk</span>
                    <strong>Rp <?= number_format($totalHarga, 0, ',', '.') ?></strong>
                </div>

                <div class="summary-line">
                    <span>Biaya Layanan Aplikasi</span>
                    <strong style="color:#dc2626;">GRATIS</strong>
                </div>

                <div class="summary-total">
                    <span>Total Pembayaran</span>
                    <span class="total-amount">Rp <?= number_format($totalHarga, 0, ',', '.') ?></span>
                </div>

                <a href="checkout.php" class="btn-checkout">
                    Lanjut ke Transaksi & Struk →
                </a>

                <a href="update_keranjang.php?aksi=clear" class="btn-clear-cart" onclick="return confirm('Kosongkan semua barang dari keranjang belanja?')">
                    🗑️ Kosongkan Keranjang
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-cart-card">
            <div class="empty-icon">🛒</div>
            <h2 style="font-size:22px;margin:0 0 8px;font-family:'Plus Jakarta Sans',sans-serif;">Keranjang Belanja Masih Kosong</h2>
            <p style="color:#78847d;font-size:14px;max-width:420px;margin:0 auto 25px;">
                Anda belum menambahkan produk apapun ke keranjang. Jelajahi etalase desa untuk menemukan produk yang Anda inginkan!
            </p>
            <a href="dashboard.php" class="btn-checkout" style="display:inline-block;width:auto;padding:12px 28px;">
                🛍️ Mulai Belanja Sekarang
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

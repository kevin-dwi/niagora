<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT
        pesanan.*,
        penjual.nama AS nama_penjual,
        penjual.no_wa AS no_wa_penjual
    FROM pesanan
    INNER JOIN users penjual ON pesanan.penjual_id = penjual.id
    WHERE pesanan.pembeli_id = $userId
";

if (!empty($statusFilter)) {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $sql .= " AND pesanan.status = '$safeStatus' ";
}

$sql .= " ORDER BY pesanan.created_at DESC";
$query = mysqli_query($conn, $sql);

// Hitung keranjang
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $jumlah) {
        $cartCount += intval($jumlah);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya — Niagora</title>
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
        .nav-link:hover, .nav-link.active { color: #dc2626; background: #fee2e2; }

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

        .page-container {
            width: min(960px, calc(100% - 40px));
            margin: 35px auto 70px;
        }

        .page-header {
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 6px;
        }
        .page-header p {
            color: #7b8881;
            font-size: 13px;
            margin: 0;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 25px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        .tab-btn {
            padding: 9px 16px;
            background: white;
            border: 1px solid #dce4df;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #5d6c63;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s;
        }
        .tab-btn.active, .tab-btn:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .orders-list {
            display: grid;
            gap: 16px;
        }

        .order-card {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: .2s;
        }
        .order-card:hover {
            border-color: #fca5a5;
            box-shadow: 0 10px 30px rgba(50,20,25,0.06);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 14px;
            border-bottom: 1px solid #fbf2f2;
            margin-bottom: 14px;
        }
        .order-code {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-weight: 800;
            font-size: 15px;
            color: #dc2626;
        }
        .order-date {
            font-size: 11px;
            color: #8b9991;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-selesai { background: #fee2e2; color: #991b1b; }
        .status-diproses { background: #e0e7ff; color: #3730a3; }
        .status-menunggu { background: #fff8e6; color: #b8860b; }
        .status-dibatalkan { background: #fdf2f2; color: #c94e5c; }

        .card-middle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .items-info {
            flex: 1;
            min-width: 250px;
        }
        .seller-txt {
            font-size: 12px;
            color: #55635b;
            margin-bottom: 6px;
        }
        .product-names {
            font-size: 13px;
            font-weight: 600;
            color: #17251e;
            line-height: 1.4;
        }

        .price-section {
            text-align: right;
        }
        .price-section span {
            font-size: 11px;
            color: #8b9991;
            display: block;
        }
        .price-section strong {
            font-size: 17px;
            color: #dc2626;
            font-weight: 800;
        }

        .card-bottom {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed #f2e6e6;
        }
        .btn-struk {
            padding: 9px 16px;
            background: #dc2626;
            color: white;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s;
        }
        .btn-struk:hover {
            background: #991b1b;
            transform: translateY(-1px);
        }
        .btn-wa-sm {
            padding: 9px 14px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-wa-sm:hover {
            background: #25d366;
            color: white;
        }
        .btn-rate-sm {
            padding: 9px 14px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s;
        }
        .btn-rate-sm:hover {
            background: #f59e0b;
            color: white;
        }
            font-weight: 700;
            text-decoration: none;
        }
        .btn-wa-sm:hover {
            background: #25d366;
            color: white;
        }

        .empty-history {
            background: white;
            border: 1px solid #e5ece8;
            border-radius: 20px;
            padding: 70px 20px;
            text-align: center;
        }
        .empty-icon { font-size: 55px; margin-bottom: 12px; }

        @media (max-width: 700px) {
            .buyer-navbar { padding: 0 15px; }
            .page-container { padding: 0 5px; }
            .card-middle { flex-direction: column; align-items: flex-start; }
            .price-section { text-align: left; }
            .card-bottom { justify-content: stretch; }
            .card-bottom a { flex: 1; text-align: center; }
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
        <a href="dashboard.php" class="nav-link">Etalase</a>
        <a href="pesanan.php" class="nav-link active">Pesanan Saya</a>
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

<div class="page-container">
    <div class="page-header">
        <h1>Riwayat Pesanan Saya 📦</h1>
        <p>Pantau semua riwayat transaksi pembelian dan unduh kembali struk digital Anda kapan saja.</p>
    </div>

    <!-- STATUS TABS -->
    <div class="filter-tabs">
        <a href="pesanan.php" class="tab-btn <?= empty($statusFilter) ? 'active' : '' ?>">Semua Pesanan</a>
        <a href="pesanan.php?status=Selesai" class="tab-btn <?= $statusFilter === 'Selesai' ? 'active' : '' ?>">✓ Selesai</a>
        <a href="pesanan.php?status=Diproses" class="tab-btn <?= $statusFilter === 'Diproses' ? 'active' : '' ?>">⏳ Diproses</a>
        <a href="pesanan.php?status=Menunggu Pembayaran" class="tab-btn <?= $statusFilter === 'Menunggu Pembayaran' ? 'active' : '' ?>">💵 Menunggu Pembayaran</a>
        <a href="pesanan.php?status=Dibatalkan" class="tab-btn <?= $statusFilter === 'Dibatalkan' ? 'active' : '' ?>">✕ Dibatalkan</a>
    </div>

    <?php if (mysqli_num_rows($query) > 0): ?>
        <div class="orders-list">
            <?php while ($ord = mysqli_fetch_assoc($query)):
                $oId = $ord['id'];
                $detQuery = mysqli_query($conn, "SELECT * FROM detail_pesanan WHERE pesanan_id = $oId");
                $itemNames = [];
                while ($d = mysqli_fetch_assoc($detQuery)) {
                    $itemNames[] = htmlspecialchars($d['nama_produk']) . ' (' . $d['jumlah'] . 'x)';
                }

                $badgeClass = 'status-selesai';
                if ($ord['status'] === 'Diproses') $badgeClass = 'status-diproses';
                elseif ($ord['status'] === 'Menunggu Pembayaran') $badgeClass = 'status-menunggu';
                elseif ($ord['status'] === 'Dibatalkan') $badgeClass = 'status-dibatalkan';

                $waPenjual = preg_replace('/[^0-9]/', '', $ord['no_wa_penjual'] ?? '');
                if (!empty($waPenjual) && substr($waPenjual, 0, 1) === '0') {
                    $waPenjual = '62' . substr($waPenjual, 1);
                }
            ?>
                <div class="order-card">
                    <div class="card-top">
                        <div>
                            <span class="order-code">#KPD<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?></span>
                            <div class="order-date"><?= date('d M Y, H:i', strtotime($ord['created_at'])) ?> WIB • <?= htmlspecialchars($ord['metode_pembayaran'] ?? 'Tunai') ?></div>
                        </div>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($ord['status']) ?>
                        </span>
                    </div>

                    <div class="card-middle">
                        <div class="items-info">
                            <div class="seller-txt">
                                🏪 Penjual: <strong><?= htmlspecialchars($ord['nama_penjual']) ?></strong>
                            </div>
                            <div class="product-names">
                                <?= implode(' • ', $itemNames) ?>
                            </div>
                        </div>

                        <div class="price-section">
                            <span>Total Pembayaran</span>
                            <strong>Rp <?= number_format($ord['total_harga'], 0, ',', '.') ?></strong>
                        </div>
                    </div>

                    <div class="card-bottom">
                        <a href="beri_rating.php?pesanan_id=<?= $ord['id'] ?>" class="btn-rate-sm">
                            ⭐ Beri Rating & Ulasan
                        </a>

                        <a href="kirim_masukan.php?penjual_id=<?= $ord['penjual_id'] ?>" class="btn-wa-sm" style="background:#fee2e2;color:#991b1b;">
                            ✉️ Beri Masukan
                        </a>

                        <?php if (!empty($waPenjual)): ?>
                            <a href="https://wa.me/<?= $waPenjual ?>?text=Halo%20<?= urlencode($ord['nama_penjual']) ?>,%20saya%20ingin%20menanyakan%20pesanan%20%23KPD<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?>" target="_blank" class="btn-wa-sm">
                                💬 Hubungi Penjual
                            </a>
                        <?php endif; ?>

                        <a href="struk.php?id=<?= $ord['id'] ?>" class="btn-struk">
                            🧾 Lihat Struk Digital / Cetak PDF
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-history">
            <div class="empty-icon">🧾</div>
            <h2 style="font-size:20px;margin:0 0 8px;font-family:'Plus Jakarta Sans',sans-serif;">Belum Ada Pesanan</h2>
            <p style="color:#78847d;font-size:13px;max-width:400px;margin:0 auto 20px;">
                Anda belum memiliki riwayat pesanan dengan status ini. Mulai belanja produk desa sekarang!
            </p>
            <a href="dashboard.php" class="btn-struk" style="display:inline-block;padding:12px 24px;">
                🛍️ Jelajahi Etalase Produk
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

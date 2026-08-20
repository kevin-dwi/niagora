<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Rating Toko Summary
$storeRating = get_seller_rating($conn, $userId);

// Ulasan Toko List
$storeReviewsQuery = mysqli_query($conn, "
    SELECT
        rating_toko.*,
        pembeli.nama AS nama_pembeli
    FROM rating_toko
    INNER JOIN users pembeli ON rating_toko.pembeli_id = pembeli.id
    WHERE rating_toko.penjual_id = $userId
    ORDER BY rating_toko.created_at DESC
");

// 2. Ulasan Produk List
$productReviewsQuery = mysqli_query($conn, "
    SELECT
        rating_produk.*,
        produk.nama_produk,
        produk.gambar,
        pembeli.nama AS nama_pembeli
    FROM rating_produk
    INNER JOIN produk ON rating_produk.produk_id = produk.id
    INNER JOIN users pembeli ON rating_produk.pembeli_id = pembeli.id
    WHERE produk.penjual_id = $userId
    ORDER BY rating_produk.created_at DESC
");

$tab = trim($_GET['tab'] ?? 'toko');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating & Ulasan — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 25px 18px 20px;
            background: white;
            border-right: 1px solid #f2e6e6;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            box-sizing: border-box;
            z-index: 50;
        }

        .sidebar-logo {
            padding: 0 10px 25px;
        }

        .sidebar-menu {
            display: grid;
            gap: 6px;
        }

        .menu-title {
            margin: 20px 12px 8px;
            color: #a0aaa4;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #6c7871;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
            text-decoration: none;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #dc2626;
            background: #fee2e2;
        }

        .menu-icon {
            width: 25px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #f7ecec;
        }

        .profile-mini {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #faf5f5;
            border: 1px solid #f2e6e6;
            border-radius: 12px;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: #dc2626;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .profile-mini strong {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #1e1315;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 135px;
        }

        .profile-mini span {
            display: block;
            color: #89948e;
            font-size: 10px;
            margin-top: 2px;
        }

        .dashboard-main {
            width: calc(100% - 250px);
            margin-left: 250px;
        }

        .dashboard-top {
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
            background: white;
            border-bottom: 1px solid #f2e6e6;
        }

        .dashboard-content {
            padding: 35px;
        }

        .review-box {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
        }

        .tab-btn {
            padding: 9px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #f2e6e6;
            background: white;
            color: #716668;
        }

        .tab-btn.active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .score-hero {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .score-big {
            font-size: 48px;
            font-weight: 800;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #dc2626;
            line-height: 1;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="../index.php" class="logo">
                    <div class="logo-icon">N</div>
                    <span>Niagora</span>
                </a>
            </div>
            <div class="sidebar-menu">
                <div class="menu-title">Menu utama</div>
                <a href="dashboard.php"><span class="menu-icon">▦</span>Dashboard</a>
                <a href="produk.php"><span class="menu-icon">📦</span>Produk</a>
                <a href="pesanan.php"><span class="menu-icon">🛒</span>Pesanan Masuk</a>
                <a href="ulasan.php" class="active"><span class="menu-icon">⭐</span>Rating & Ulasan</a>
                <a href="masukan.php"><span class="menu-icon">✉️</span>Masukan Pembeli</a>
                <div class="menu-title">Pengaturan</div>
                <a href="profil.php"><span class="menu-icon">👤</span>Profil & No. WhatsApp</a>
            </div>
            <div class="sidebar-bottom">
                <div class="profile-mini">
                    <div class="profile-avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1)) ?></div>
                    <div><strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Penjual') ?></strong><span>Penjual</span></div>
                </div>
                <a href="../auth/logout.php" style="display:block;text-align:center;color:#d45555;font-size:11px;margin-top:12px;font-weight:600;">Keluar</a>
            </div>
        </aside>

        <main class="dashboard-main">
            <header class="dashboard-top">
                <div>
                    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:18px;margin:0 0 2px;">Rating & Ulasan Pelanggan</h1>
                    <p style="color:#8a958f;font-size:11px;margin:0;">Pantau reputasi toko dan ulasan kualitas produk dari para pembeli.</p>
                </div>
            </header>

            <div class="dashboard-content">
                <!-- SCORE HERO -->
                <div class="score-hero">
                    <div>
                        <div class="score-big">
                            <?= $storeRating['rating'] > 0 ? number_format($storeRating['rating'], 1) : '0.0' ?>
                        </div>
                        <div style="color:#f59e0b;font-size:22px;letter-spacing:2px;margin:4px 0;">
                            <?php
                            $r = $storeRating['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $r >= $i ? '★' : ($r >= ($i - 0.5) ? '★' : '☆');
                            }
                            ?>
                        </div>
                        <span style="font-size:12px;color:#736769;">Rating Keseluruhan Toko</span>
                    </div>

                    <div style="border-left:1px solid #f2e6e6;padding-left:25px;">
                        <div style="font-size:18px;font-weight:700;color:#1e1315;">
                            <?= $storeRating['count'] ?> Ulasan Toko Diterima
                        </div>
                        <p style="font-size:12px;color:#736769;margin:4px 0 0;max-width:400px;">
                            Rating dan ulasan ini diberikan secara transparan oleh pembeli yang telah berbelanja di toko Anda.
                        </p>
                    </div>
                </div>

                <!-- TABS -->
                <div style="display:flex;gap:10px;margin-bottom:22px;">
                    <a href="ulasan.php?tab=toko" class="tab-btn <?= $tab === 'toko' ? 'active' : '' ?>">
                        🏪 Ulasan Toko (<?= mysqli_num_rows($storeReviewsQuery) ?>)
                    </a>
                    <a href="ulasan.php?tab=produk" class="tab-btn <?= $tab === 'produk' ? 'active' : '' ?>">
                        📦 Ulasan Produk (<?= mysqli_num_rows($productReviewsQuery) ?>)
                    </a>
                </div>

                <?php if ($tab === 'toko'): ?>
                    <!-- LIST ULASAN TOKO -->
                    <?php if (mysqli_num_rows($storeReviewsQuery) > 0): ?>
                        <div>
                            <?php while ($rev = mysqli_fetch_assoc($storeReviewsQuery)): ?>
                                <div class="review-box">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <div>
                                            <strong style="font-size:14px;color:#1e1315;"><?= htmlspecialchars($rev['nama_pembeli']) ?></strong>
                                            <div style="font-size:11px;color:#9aa8a0;margin-top:2px;"><?= date('d M Y, H:i', strtotime($rev['created_at'])) ?> WIB</div>
                                        </div>
                                        <div>
                                            <?= render_rating_stars($rev['rating'], null, 'md') ?>
                                        </div>
                                    </div>
                                    <p style="font-size:13px;color:#716668;line-height:1.6;margin:0;">
                                        <?= !empty($rev['ulasan']) ? nl2br(htmlspecialchars($rev['ulasan'])) : '<em style="color:#9aa8a0;">(Pembeli hanya memberikan rating tanpa ulasan tertulis)</em>' ?>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="background:white;border:1px solid #f2e6e6;border-radius:18px;padding:60px 20px;text-align:center;">
                            <div style="font-size:45px;margin-bottom:10px;">⭐</div>
                            <h3 style="font-size:16px;margin-bottom:4px;">Belum Ada Ulasan Toko</h3>
                            <p style="color:#8a958f;font-size:12px;">Ulasan toko dari pembeli akan muncul di sini setelah mereka menyelesaikan pesanan.</p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- LIST ULASAN PRODUK -->
                    <?php if (mysqli_num_rows($productReviewsQuery) > 0): ?>
                        <div>
                            <?php while ($pRev = mysqli_fetch_assoc($productReviewsQuery)): ?>
                                <div class="review-box">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                                        <div style="display:flex;align-items:center;gap:12px;">
                                            <div style="width:45px;height:45px;border-radius:8px;background:#fee2e2;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                                                <?php if (!empty($pRev['gambar'])): ?>
                                                    <img src="../assets/img/<?= htmlspecialchars($pRev['gambar']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                                <?php else: ?>
                                                    📦
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong style="font-size:14px;color:#1e1315;"><?= htmlspecialchars($pRev['nama_produk']) ?></strong>
                                                <div style="font-size:11px;color:#736769;">Oleh: <strong><?= htmlspecialchars($pRev['nama_pembeli']) ?></strong> • <?= date('d M Y', strtotime($pRev['created_at'])) ?></div>
                                            </div>
                                        </div>
                                        <div>
                                            <?= render_rating_stars($pRev['rating'], null, 'md') ?>
                                        </div>
                                    </div>
                                    <p style="font-size:13px;color:#716668;line-height:1.6;margin:0;">
                                        <?= !empty($pRev['ulasan']) ? nl2br(htmlspecialchars($pRev['ulasan'])) : '<em style="color:#9aa8a0;">(Pembeli hanya memberikan rating tanpa ulasan tertulis)</em>' ?>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="background:white;border:1px solid #f2e6e6;border-radius:18px;padding:60px 20px;text-align:center;">
                            <div style="font-size:45px;margin-bottom:10px;">📦</div>
                            <h3 style="font-size:16px;margin-bottom:4px;">Belum Ada Ulasan Produk</h3>
                            <p style="color:#8a958f;font-size:12px;">Ulasan untuk produk-produk Anda akan muncul di sini.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>
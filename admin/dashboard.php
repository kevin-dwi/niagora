<?php

session_start();

require_once "../config/database.php";


/* =========================================
   CEK LOGIN
========================================= */

if (!isset($_SESSION['login'])) {

    header("Location: ../auth/login.php");

    exit;

}


if ($_SESSION['role'] !== 'penjual') {

    header("Location: ../index.php");

    exit;

}


$userId = $_SESSION['user_id'];


/* =========================================
   STATISTIK
========================================= */

$queryProduk = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produk
     WHERE penjual_id = $userId"
);

$totalProduk =
    mysqli_fetch_assoc($queryProduk)['total'];


$queryStok = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM produk
     WHERE penjual_id = $userId
     AND stok <= 5"
);

$stokMenipis =
    mysqli_fetch_assoc($queryStok)['total'];


$queryPenjualan = mysqli_query(
    $conn,
    "SELECT COALESCE(
        SUM(detail_pesanan.subtotal),
        0
    ) AS total

    FROM detail_pesanan

    JOIN produk
        ON detail_pesanan.produk_id = produk.id

    WHERE produk.penjual_id = $userId"
);

$totalPenjualan =
    mysqli_fetch_assoc(
        $queryPenjualan
    )['total'];


$queryPesanan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pesanan
     WHERE pesanan.penjual_id = $userId"
);

$totalPesanan =
    mysqli_fetch_assoc(
        $queryPesanan
    )['total'] ?? 0;

/* Pesanan Terbaru */
$queryRecentOrders = mysqli_query(
    $conn,
    "SELECT
        pesanan.*,
        pembeli.nama AS nama_pembeli
     FROM pesanan
     INNER JOIN users pembeli ON pesanan.pembeli_id = pembeli.id
     WHERE pesanan.penjual_id = $userId
     ORDER BY pesanan.created_at DESC
     LIMIT 5"
);

/* Rating Toko & Masukan */
$storeRating = get_seller_rating($conn, $userId);

$queryUnreadFeedback = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM masukan_penjual WHERE penjual_id = $userId AND status = 'belum_dibaca'"
);
$unreadFeedbackCount = mysqli_fetch_assoc($queryUnreadFeedback)['total'] ?? 0;

$queryRecentFeedback = mysqli_query(
    $conn,
    "SELECT
        masukan_penjual.*,
        pembeli.nama AS nama_pembeli
     FROM masukan_penjual
     INNER JOIN users pembeli ON masukan_penjual.pembeli_id = pembeli.id
     WHERE masukan_penjual.penjual_id = $userId
     ORDER BY masukan_penjual.created_at DESC
     LIMIT 4"
);

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard — Niagora</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        body {

            background: #fdf8f8;

        }


        .dashboard {

            display: flex;

            min-height: 100vh;

        }


        /* SIDEBAR */

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


        /* MAIN */

        .dashboard-main {

            width: calc(100% - 250px);

            margin-left: 250px;

        }


        .dashboard-top {

            height: 75px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 35px;

            background: white;

            border-bottom:
                1px solid #e6ebe8;

        }


        .dashboard-top h1 {

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 18px;

        }


        .dashboard-top p {

            color: #8a958f;

            font-size: 11px;

        }


        .notification {

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border: 1px solid #e6ebe8;

            border-radius: 10px;

            background: white;

        }


        .dashboard-content {

            padding: 35px;

        }


        .welcome {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 30px;

        }


        .welcome h2 {

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 25px;

        }


        .welcome p {

            color: #7a857f;

            font-size: 13px;

            margin-top: 4px;

        }


        .add-product {

            padding: 12px 17px;

            color: white;

            background: #dc2626;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 700;

        }

        .add-product:hover {
            background: #991b1b;
        }


        /* STAT CARDS */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 25px;

        }


        .stat-card {

            padding: 20px;

            background: white;

            border:
                1px solid #e6ebe8;

            border-radius: 17px;

        }


        .stat-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 18px;

        }


        .stat-icon {

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #fee2e2;

            border-radius: 10px;

        }


        .stat-card h3 {

            font-size: 22px;

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

        }


        .stat-card p {

            color: #89948e;

            font-size: 11px;

        }


        .stat-change {

            color: #dc2626;

            font-size: 10px;

            font-weight: 700;

        }


        /* CONTENT GRID */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                1.4fr .8fr;

            gap: 20px;

        }


        .panel {

            padding: 22px;

            background: white;

            border:
                1px solid #e6ebe8;

            border-radius: 17px;

        }


        .panel-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;

        }


        .panel-header h3 {

            font-size: 14px;

        }


        .panel-header a {

            color: #dc2626;

            font-size: 10px;

            font-weight: 700;

        }


        .empty-state {

            padding: 45px 20px;

            text-align: center;

            color: #8b968f;

        }


        .empty-icon {

            font-size: 40px;

            margin-bottom: 10px;

        }


        .empty-state p {

            font-size: 12px;

        }

        /* ORDER ROW (pesanan terbaru) */

        .order-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 12px;

            border: 1px solid #edf1ef;

            border-radius: 12px;

            background: #fbfdfc;

            transition: background-color .4s ease, border-color .4s ease;

        }

        .order-code {

            font-weight: 700;

            font-size: 13px;

            color: #1c2b23;

        }

        .order-date {

            font-size: 11px;

            color: #89948e;

            margin-top: 3px;

        }

        .status-badge {

            padding: 4px 8px;

            border-radius: 12px;

            font-size: 10px;

            font-weight: 700;

            background: #fee2e2;

            color: #991b1b;

        }

        .btn-struk {

            padding: 6px 10px;

            background: #dc2626;

            color: white;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 700;

            text-decoration: none;

        }

        .btn-struk:hover {
            background: #991b1b;
        }

        @media (max-width: 1000px) {

            .stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .dashboard-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                display: none;

            }

            .dashboard-main {

                width: 100%;

                margin-left: 0;

            }

            .dashboard-content {

                padding: 20px;

            }

            .stats {

                grid-template-columns: 1fr 1fr;

            }

            .welcome {

                align-items: flex-start;

                gap: 15px;

            }

        }

        /* =========================================
           DARK MODE (mode malam)
        ========================================= */

        [data-theme="dark"] body {

            background: var(--bg);

        }

        [data-theme="dark"] .notification {

            background: var(--bg-card);

            border-color: var(--border);

        }

        [data-theme="dark"] .stat-icon {

            background: var(--primary-light);

        }

        [data-theme="dark"] .stat-card p,
        [data-theme="dark"] .menu-title,
        [data-theme="dark"] .profile-mini span,
        [data-theme="dark"] .empty-state {

            color: var(--text-muted);

        }

        [data-theme="dark"] .sidebar-menu a:hover,
        [data-theme="dark"] .sidebar-menu a.active {

            color: var(--primary);

            background: var(--primary-light);

        }

        [data-theme="dark"] .order-row {

            background: var(--light-gray);

            border-color: var(--border);

        }

        [data-theme="dark"] .order-code {

            color: var(--text);

        }

        [data-theme="dark"] .order-date {

            color: var(--text-muted);

        }

        [data-theme="dark"] .status-badge {

            background: var(--primary-light);

            color: var(--primary);

        }

    </style>

</head>

<body>


<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <a
                href="../index.php"
                class="logo"
            >

                <div class="logo-icon">
                    N
                </div>

                <span>
                    Niagora
                </span>

            </a>

        </div>


        <div class="sidebar-menu">


            <div class="menu-title">
                Menu utama
            </div>

            <a
                href="dashboard.php"
                class="active"
            >
                <span class="menu-icon">▦</span>
                Dashboard
            </a>

            <a href="produk.php">
                <span class="menu-icon">📦</span>
                Produk
            </a>

            <a href="pesanan.php">
                <span class="menu-icon">🛒</span>
                Pesanan Masuk
            </a>

            <a href="ulasan.php">
                <span class="menu-icon">⭐</span>
                Rating & Ulasan
            </a>

            <a href="masukan.php">
                <span class="menu-icon">✉️</span>
                Masukan Pembeli
                <?php if ($unreadFeedbackCount > 0): ?>
                    <span style="margin-left:auto;background:#dc2626;color:white;font-size:10px;padding:2px 7px;border-radius:20px;font-weight:800;"><?= $unreadFeedbackCount ?></span>
                <?php endif; ?>
            </a>

            <div class="menu-title">
                Pengaturan
            </div>

            <a href="profil.php">
                <span class="menu-icon">👤</span>
                Profil & No. WhatsApp
            </a>


        </div>


        <div class="sidebar-bottom">


            <div class="profile-mini">

                <div class="profile-avatar">

                    <?= strtoupper(
                        substr(
                            $_SESSION['nama'],
                            0,
                            1
                        )
                    ) ?>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $_SESSION['nama']
                        ) ?>

                    </strong>

                    <span>
                        Penjual
                    </span>

                </div>

            </div>


            <a
                href="../auth/logout.php"
                style="
                    display:block;
                    text-align:center;
                    color:#d45555;
                    font-size:11px;
                    margin-top:12px;
                "
            >
                Keluar
            </a>


        </div>


    </aside>


    <!-- MAIN -->

    <main class="dashboard-main">


        <header class="dashboard-top">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Pantau perkembangan usahamu hari ini.
                </p>

            </div>


            <div style="display:flex;align-items:center;gap:10px;">
                <button class="theme-toggle" type="button" title="Ganti mode siang/malam">🌙</button>
                <button class="notification">
                    🔔
                </button>
            </div>

        </header>


        <div class="dashboard-content">


            <div class="welcome">

                <div>

                    <h2>
                        Halo, <?= htmlspecialchars(
                            $_SESSION['nama']
                        ) ?> 👋
                    </h2>

                    <p>
                        Berikut ringkasan usaha kamu.
                    </p>

                </div>


                <a
                    href="tambah_produk.php"
                    class="add-product btn-shimmer"
                >
                    + Tambah produk
                </a>

            </div>


            <!-- STATS -->

            <div class="stats" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));">


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            📦
                        </div>

                        <span class="stat-change">
                            Produk
                        </span>

                    </div>

                    <h3>
                        <?= $totalProduk ?>
                    </h3>

                    <p>
                        Total produk
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            🛒
                        </div>

                        <span class="stat-change">
                            Pesanan
                        </span>

                    </div>

                    <h3>
                        <?= $totalPesanan ?>
                    </h3>

                    <p>
                        Total pesanan
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            💰
                        </div>

                        <span class="stat-change">
                            Penjualan
                        </span>

                    </div>

                    <h3>
                        Rp <?= number_format(
                            $totalPenjualan,
                            0,
                            ',',
                            '.'
                        ) ?>
                    </h3>

                    <p>
                        Total penjualan
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            ⭐
                        </div>

                        <span class="stat-change" style="color:#f59e0b;">
                            Rating
                        </span>

                    </div>

                    <h3>
                        <?= $storeRating['rating'] > 0 ? number_format($storeRating['rating'], 1) . ' ⭐' : 'Baru' ?>
                    </h3>

                    <p>
                        <?= $storeRating['count'] ?> Ulasan Toko
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            ✉️
                        </div>

                        <span class="stat-change" style="color:#dc2626;">
                            Masukan
                        </span>

                    </div>

                    <h3>
                        <?= $unreadFeedbackCount ?> Baru
                    </h3>

                    <p>
                        Masukan Pembeli
                    </p>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">
                            ⚠️
                        </div>

                        <span
                            style="
                                color:#d88a24;
                                font-size:10px;
                                font-weight:700;
                            "
                        >
                            Perhatian
                        </span>

                    </div>

                    <h3>
                        <?= $stokMenipis ?>
                    </h3>

                    <p>
                        Stok menipis
                    </p>

                </div>


            </div>


            <!-- PANELS -->

            <div class="dashboard-grid">


                <div class="panel">

                    <div class="panel-header">

                        <h3>
                            Pesanan Terbaru
                        </h3>

                        <a href="pesanan.php">
                            Lihat semua pesanan →
                        </a>

                    </div>

                    <?php if (mysqli_num_rows($queryRecentOrders) > 0): ?>

                        <div style="display:grid;gap:12px;">
                            <?php while ($rOrder = mysqli_fetch_assoc($queryRecentOrders)): ?>
                                <div class="order-row">
                                    <div>
                                        <div class="order-code">
                                            #KPD<?= str_pad($rOrder['id'], 5, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($rOrder['nama_pembeli']) ?>
                                        </div>
                                        <div class="order-date">
                                            <?= date('d M Y, H:i', strtotime($rOrder['created_at'])) ?> • <strong style="color:#dc2626;">Rp <?= number_format($rOrder['total_harga'], 0, ',', '.') ?></strong>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span class="status-badge">
                                            <?= htmlspecialchars($rOrder['status']) ?>
                                        </span>
                                        <a href="struk.php?id=<?= $rOrder['id'] ?>" target="_blank" class="btn-struk">
                                            🖨️ Struk PDF
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                    <?php else: ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                📊
                            </div>

                            <p>
                                Belum ada pesanan masuk dari pembeli.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- MASUKAN PEMBELI PANEL -->
                <div class="panel" style="grid-column: 1 / -1; margin-top: 10px;">
                    <div class="panel-header">
                        <h3>
                            ✉️ Masukan & Saran dari Pembeli
                            <?php if ($unreadFeedbackCount > 0): ?>
                                <span style="background:#dc2626;color:white;font-size:10px;padding:3px 8px;border-radius:20px;margin-left:6px;"><?= $unreadFeedbackCount ?> Belum Dibaca</span>
                            <?php endif; ?>
                        </h3>
                        <a href="masukan.php">
                            Lihat semua masukan →
                        </a>
                    </div>

                    <?php if (mysqli_num_rows($queryRecentFeedback) > 0): ?>
                        <div style="display:grid;gap:12px;">
                            <?php while ($fb = mysqli_fetch_assoc($queryRecentFeedback)): ?>
                                <div class="order-row" style="border-left: 4px solid <?= $fb['status'] === 'belum_dibaca' ? '#dc2626' : '#d1d5db' ?>;">
                                    <div style="flex:1;">
                                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                                            <strong style="font-size:13px;color:#1e1315;">
                                                <?= htmlspecialchars($fb['subjek']) ?>
                                            </strong>
                                            <span style="font-size:10px;padding:2px 7px;border-radius:12px;background:<?= $fb['status'] === 'belum_dibaca' ? '#fee2e2' : ($fb['status'] === 'dibalas' ? '#dcfce7' : '#e0e7ff') ?>;color:<?= $fb['status'] === 'belum_dibaca' ? '#991b1b' : ($fb['status'] === 'dibalas' ? '#166534' : '#3730a3') ?>;font-weight:700;">
                                                <?= $fb['status'] === 'belum_dibaca' ? 'Baru' : ($fb['status'] === 'dibalas' ? 'Dibalas' : 'Dibaca') ?>
                                            </span>
                                        </div>
                                        <p style="font-size:12px;color:#716668;margin:0 0 4px;line-height:1.4;">
                                            <?= htmlspecialchars(mb_strimwidth($fb['pesan'], 0, 140, '...')) ?>
                                        </p>
                                        <div style="font-size:10px;color:#9aa8a0;">
                                            Dari: <strong><?= htmlspecialchars($fb['nama_pembeli']) ?></strong> • <?= date('d M Y, H:i', strtotime($fb['created_at'])) ?> WIB
                                        </div>
                                    </div>
                                    <div style="margin-left:15px;">
                                        <a href="masukan.php?id=<?= $fb['id'] ?>" class="btn-struk" style="font-size:11px;padding:7px 12px;">
                                            Lihat & Balas
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding:30px 20px;">
                            <div class="empty-icon" style="font-size:32px;">✉️</div>
                            <p style="margin:0;">Belum ada masukan dari pembeli.</p>
                        </div>
                    <?php endif; ?>
                </div>


            </div>


        </div>


    </main>


</div>

<script src="../assets/js/script.js"></script>

</body>

</html>
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

// Update status pesanan jika ada request
if (isset($_POST['update_status'])) {
    $pesananId = intval($_POST['pesanan_id']);
    $statusBaru = trim($_POST['status']);

    $allowedStatus = ['Menunggu Pembayaran', 'Diproses', 'Selesai', 'Dibatalkan'];
    if (in_array($statusBaru, $allowedStatus)) {
        $stmt = mysqli_prepare($conn, "UPDATE pesanan SET status = ? WHERE id = ? AND penjual_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $statusBaru, $pesananId, $userId);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Status pesanan #KPD" . str_pad($pesananId, 5, '0', STR_PAD_LEFT) . " berhasil diperbarui menjadi: " . htmlspecialchars($statusBaru);
        } else {
            $error = "Gagal memperbarui status pesanan.";
        }
    }
}

// Filter status
$statusFilter = trim($_GET['status'] ?? '');
$sql = "
    SELECT
        pesanan.*,
        pembeli.nama AS nama_pembeli,
        pembeli.email AS email_pembeli,
        pembeli.no_wa AS no_wa_pembeli
    FROM pesanan
    INNER JOIN users pembeli ON pesanan.pembeli_id = pembeli.id
    WHERE pesanan.penjual_id = $userId
";

if (!empty($statusFilter)) {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $sql .= " AND pesanan.status = '$safeStatus' ";
}

$sql .= " ORDER BY pesanan.created_at DESC";
$query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #fdf8f8; }
        .dashboard { display: flex; min-height: 100vh; }
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

        .sidebar-logo { padding: 0 10px 25px; }
        .sidebar-menu { display: grid; gap: 6px; }
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
        .sidebar-menu a:hover, .sidebar-menu a.active {
            color: #dc2626;
            background: #fee2e2;
        }
        .menu-icon { width: 25px; text-align: center; font-size: 16px; }
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
            border-bottom: 1px solid #e6ebe8;
        }
        .dashboard-top h1 { font-family: "Plus Jakarta Sans", sans-serif; font-size: 18px; }
        .dashboard-top p { color: #8a958f; font-size: 11px; }
        .dashboard-content { padding: 35px; }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-heading h2 { font-family: "Plus Jakarta Sans", sans-serif; font-size: 25px; }
        .page-heading p { color: #7a857f; font-size: 13px; margin-top: 4px; }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 15px;
            background: white;
            border: 1px solid #e1e7e3;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #6c7871;
            transition: .2s;
        }
        .filter-tab.active, .filter-tab:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .alert-box {
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .alert-success { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-error { background: #fff0f2; color: #a22d3e; border: 1px solid #ffd6dc; }

        .orders-table-card {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 18px;
            overflow: hidden;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .order-table th {
            padding: 14px 20px;
            text-align: left;
            color: #8b968f;
            background: #faf5f5;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .order-table td {
            padding: 16px 20px;
            border-top: 1px solid #fbf2f2;
            vertical-align: middle;
        }
        .order-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }
        .status-selesai { background: #fee2e2; color: #991b1b; }
        .status-diproses { background: #e0e7ff; color: #3730a3; }
        .status-menunggu { background: #fff8e6; color: #b8860b; }
        .status-dibatalkan { background: #fdf2f2; color: #c94e5c; }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: .2s;
        }
        .btn-pdf {
            background: #dc2626;
            color: white;
        }
        .btn-pdf:hover {
            background: #991b1b;
            transform: translateY(-1px);
        }
        .btn-wa {
            background: #25d366;
            color: white;
        }
        .btn-wa:hover {
            background: #1da851;
        }
        .status-select {
            padding: 6px 10px;
            border: 1px solid #d9e2dc;
            border-radius: 7px;
            font-size: 11px;
            outline: none;
            background: #fbfcfb;
            font-family: inherit;
        }

        .items-preview {
            max-width: 250px;
            font-size: 11px;
            color: #68746d;
            line-height: 1.4;
        }

        .empty-state {
            padding: 70px 20px;
            text-align: center;
            color: #8b968f;
        }
        .empty-icon { font-size: 50px; margin-bottom: 12px; }

        @media (max-width: 800px) {
            .sidebar { display: none; }
            .dashboard-main { width: 100%; margin-left: 0; }
            .dashboard-content { padding: 20px; }
            .order-table { min-width: 800px; }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- SIDEBAR -->
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
            <a href="pesanan.php" class="active"><span class="menu-icon">🛒</span>Pesanan Masuk</a>
            <a href="ulasan.php"><span class="menu-icon">⭐</span>Rating & Ulasan</a>
            <a href="masukan.php"><span class="menu-icon">✉️</span>Masukan Pembeli</a>
            <div class="menu-title">Pengaturan</div>
            <a href="profil.php"><span class="menu-icon">👤</span>Profil & No. WhatsApp</a>
        </div>

        <div class="sidebar-bottom">
            <div class="profile-mini">
                <div class="profile-avatar">
                    <?= strtoupper(substr($_SESSION['nama'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Penjual') ?></strong>
                    <span>Penjual</span>
                </div>
            </div>
            <a href="../auth/logout.php" style="display:block;text-align:center;color:#d45555;font-size:11px;margin-top:12px;font-weight:600;">
                Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="dashboard-main">
        <header class="dashboard-top">
            <div>
                <h1>Pesanan Masuk</h1>
                <p>Kelola barang pesanan dari pembeli & cetak struk PDF transaksi.</p>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="page-heading">
                <div>
                    <h2>Daftar Pesanan</h2>
                    <p>Semua transaksi pembelian untuk produk toko Anda.</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert-box alert-success">
                    ✓ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error">
                    ⚠ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="filter-tabs">
                <a href="pesanan.php" class="filter-tab <?= empty($statusFilter) ? 'active' : '' ?>">Semua</a>
                <a href="pesanan.php?status=Selesai" class="filter-tab <?= $statusFilter === 'Selesai' ? 'active' : '' ?>">Selesai</a>
                <a href="pesanan.php?status=Diproses" class="filter-tab <?= $statusFilter === 'Diproses' ? 'active' : '' ?>">Diproses</a>
                <a href="pesanan.php?status=Menunggu Pembayaran" class="filter-tab <?= $statusFilter === 'Menunggu Pembayaran' ? 'active' : '' ?>">Menunggu Pembayaran</a>
                <a href="pesanan.php?status=Dibatalkan" class="filter-tab <?= $statusFilter === 'Dibatalkan' ? 'active' : '' ?>">Dibatalkan</a>
            </div>

            <div class="orders-table-card">
                <?php if (mysqli_num_rows($query) > 0): ?>
                    <div class="table-responsive">
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Pembeli & Kontak</th>
                                    <th>Rincian Produk</th>
                                    <th>Total Tagihan</th>
                                    <th>Metode & Status</th>
                                    <th>Aksi / Cetak Struk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($query)):
                                    $oId = $order['id'];
                                    $detailQ = mysqli_query($conn, "SELECT * FROM detail_pesanan WHERE pesanan_id = $oId");
                                    $items = [];
                                    while ($det = mysqli_fetch_assoc($detailQ)) {
                                        $items[] = htmlspecialchars($det['nama_produk']) . ' (' . $det['jumlah'] . 'x)';
                                    }

                                    $statusClass = 'status-selesai';
                                    if ($order['status'] === 'Diproses') $statusClass = 'status-diproses';
                                    elseif ($order['status'] === 'Menunggu Pembayaran') $statusClass = 'status-menunggu';
                                    elseif ($order['status'] === 'Dibatalkan') $statusClass = 'status-dibatalkan';

                                    $pembeliWa = preg_replace('/[^0-9]/', '', $order['no_wa_penerima'] ?: $order['no_wa_pembeli'] ?: '');
                                    if (!empty($pembeliWa) && substr($pembeliWa, 0, 1) === '0') {
                                        $pembeliWa = '62' . substr($pembeliWa, 1);
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#dc2626;">#KPD<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></strong>
                                            <div style="font-size:11px;color:#8a958f;margin-top:4px;">
                                                <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($order['nama_penerima'] ?: $order['nama_pembeli']) ?></strong>
                                            <?php if (!empty($pembeliWa)): ?>
                                                <div style="margin-top:5px;">
                                                    <a href="https://wa.me/<?= $pembeliWa ?>" target="_blank" class="btn-action btn-wa" style="padding:4px 8px;font-size:10px;">
                                                        💬 Hubungi WA
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($order['alamat_pengiriman'])): ?>
                                                <div style="font-size:11px;color:#78847d;margin-top:4px;max-width:200px;">
                                                    📍 <?= htmlspecialchars($order['alamat_pengiriman']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="items-preview">
                                                <?= implode('<br>• ', array_map(function($i){ return '• ' . $i; }, $items)) ?: '-' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="font-size:14px;color:#dc2626;">
                                                Rp <?= number_format($order['total_harga'], 0, ',', '.') ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="order-badge <?= $statusClass ?>">
                                                <?= htmlspecialchars($order['status']) ?>
                                            </span>
                                            <div style="font-size:11px;color:#89948e;margin-top:4px;">
                                                <?= htmlspecialchars($order['metode_pembayaran'] ?? 'Tunai') ?>
                                            </div>
                                            <form method="POST" style="margin-top:8px;">
                                                <input type="hidden" name="pesanan_id" value="<?= $order['id'] ?>">
                                                <select name="status" class="status-select" onchange="this.form.submit()">
                                                    <option value="Selesai" <?= $order['status'] === 'Selesai' ? 'selected' : '' ?>>Ubah: Selesai</option>
                                                    <option value="Diproses" <?= $order['status'] === 'Diproses' ? 'selected' : '' ?>>Ubah: Diproses</option>
                                                    <option value="Menunggu Pembayaran" <?= $order['status'] === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Ubah: Menunggu</option>
                                                    <option value="Dibatalkan" <?= $order['status'] === 'Dibatalkan' ? 'selected' : '' ?>>Ubah: Dibatalkan</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td>
                                            <a href="struk.php?id=<?= $order['id'] ?>" target="_blank" class="btn-action btn-pdf">
                                                🖨️ Cetak Struk (PDF)
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🧾</div>
                        <h3>Belum Ada Pesanan</h3>
                        <p>Pesanan yang dibeli oleh pelanggan akan ditampilkan di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>

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

// PROSES BALAS MASUKAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['balas_masukan'])) {
    $fbId = intval($_POST['masukan_id'] ?? 0);
    $balasan = trim($_POST['balasan'] ?? '');

    if ($fbId > 0 && !empty($balasan)) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE masukan_penjual SET balasan = ?, status = 'dibalas' WHERE id = ? AND penjual_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "sii", $balasan, $fbId, $userId);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Balasan berhasil dikirimkan ke pembeli!";
        } else {
            $error = "Gagal mengirim balasan.";
        }
    }
}

// PROSES TANDAI SUDAH DIBACA
if (isset($_GET['baca']) && intval($_GET['baca']) > 0) {
    $bacaId = intval($_GET['baca']);
    mysqli_query($conn, "UPDATE masukan_penjual SET status = 'dibaca' WHERE id = $bacaId AND penjual_id = $userId AND status = 'belum_dibaca'");
    header("Location: masukan.php");
    exit;
}

// Filter status
$statusFilter = trim($_GET['status'] ?? '');
$sql = "
    SELECT
        masukan_penjual.*,
        pembeli.nama AS nama_pembeli,
        pembeli.email AS email_pembeli,
        pembeli.no_wa AS no_wa_pembeli
    FROM masukan_penjual
    INNER JOIN users pembeli ON masukan_penjual.pembeli_id = pembeli.id
    WHERE masukan_penjual.penjual_id = $userId
";

if (!empty($statusFilter)) {
    $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
    $sql .= " AND masukan_penjual.status = '$safeStatus' ";
}

$sql .= " ORDER BY masukan_penjual.created_at DESC";
$query = mysqli_query($conn, $sql);

$selectedId = intval($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masukan Pembeli — Niagora</title>
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

        .fb-card {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
        }

        .btn-reply {
            padding: 8px 16px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-reply:hover {
            background: #991b1b;
        }

        .btn-mark {
            padding: 8px 14px;
            background: #faf5f5;
            color: #716668;
            border: 1px solid #f2e6e6;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        .filter-tab {
            padding: 8px 15px;
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #6c7871;
            text-decoration: none;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
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
                <a href="ulasan.php"><span class="menu-icon">⭐</span>Rating & Ulasan</a>
                <a href="masukan.php" class="active"><span class="menu-icon">✉️</span>Masukan Pembeli</a>
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
                    <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:18px;margin:0 0 2px;">Masukan & Saran Pembeli</h1>
                    <p style="color:#8a958f;font-size:11px;margin:0;">Kelola tanggapan, saran produk, dan masukan dari pelanggan Anda.</p>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (!empty($success)): ?>
                    <div style="padding:12px 16px;background:#fee2e2;color:#991b1b;border-radius:10px;margin-bottom:20px;font-size:13px;font-weight:600;">
                        ✓ <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
                    <a href="masukan.php" class="filter-tab <?= empty($statusFilter) ? 'active' : '' ?>">Semua Masukan</a>
                    <a href="masukan.php?status=belum_dibaca" class="filter-tab <?= $statusFilter === 'belum_dibaca' ? 'active' : '' ?>">Belum Dibaca</a>
                    <a href="masukan.php?status=dibaca" class="filter-tab <?= $statusFilter === 'dibaca' ? 'active' : '' ?>">Sudah Dibaca</a>
                    <a href="masukan.php?status=dibalas" class="filter-tab <?= $statusFilter === 'dibalas' ? 'active' : '' ?>">Sudah Dibalas</a>
                </div>

                <?php if (mysqli_num_rows($query) > 0): ?>
                    <div>
                        <?php while ($fb = mysqli_fetch_assoc($query)):
                            $statusClass = 'status-unread';
                            $statusLabel = 'Baru / Belum Dibaca';
                            if ($fb['status'] === 'dibaca') {
                                $statusClass = 'status-read';
                                $statusLabel = 'Sudah Dibaca';
                            } elseif ($fb['status'] === 'dibalas') {
                                $statusClass = 'status-replied';
                                $statusLabel = 'Telah Dibalas';
                            }
                        ?>
                            <div class="fb-card" style="<?= $selectedId == $fb['id'] ? 'border-color:#dc2626;box-shadow:0 0 0 2px #fee2e2;' : '' ?>">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;border-bottom:1px solid #f2e6e6;padding-bottom:12px;flex-wrap:wrap;gap:8px;">
                                    <div>
                                        <h3 style="font-size:16px;font-weight:700;margin:0 0 4px;color:#1e1315;">
                                            <?= htmlspecialchars($fb['subjek']) ?>
                                        </h3>
                                        <div style="font-size:11px;color:#736769;">
                                            Dari: <strong><?= htmlspecialchars($fb['nama_pembeli']) ?></strong>
                                            <?php if (!empty($fb['no_wa_pembeli'])): ?>
                                                • WA: <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $fb['no_wa_pembeli']) ?>" target="_blank" style="color:#dc2626;font-weight:700;"><?= htmlspecialchars($fb['no_wa_pembeli']) ?></a>
                                            <?php endif; ?>
                                            • <?= date('d M Y, H:i', strtotime($fb['created_at'])) ?> WIB
                                        </div>
                                    </div>
                                    <span class="feedback-badge <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>

                                <p style="font-size:13px;color:#716668;line-height:1.6;margin:0 0 16px;">
                                    <?= nl2br(htmlspecialchars($fb['pesan'])) ?>
                                </p>

                                <?php if (!empty($fb['balasan'])): ?>
                                    <div style="padding:14px;background:#fef2f2;border-left:4px solid #dc2626;border-radius:8px;font-size:13px;margin-bottom:14px;">
                                        <strong style="color:#dc2626;display:block;margin-bottom:4px;">Balasan Anda:</strong>
                                        <div style="color:#1e1315;line-height:1.5;"><?= nl2br(htmlspecialchars($fb['balasan'])) ?></div>
                                    </div>
                                <?php endif; ?>

                                <details style="margin-top:10px;">
                                    <summary style="font-size:12px;font-weight:700;color:#dc2626;cursor:pointer;">
                                        💬 <?= !empty($fb['balasan']) ? 'Ubah Balasan Pesan' : 'Balas Masukan Ini' ?>
                                    </summary>
                                    <form method="POST" style="margin-top:12px;">
                                        <input type="hidden" name="masukan_id" value="<?= $fb['id'] ?>">
                                        <textarea name="balasan" rows="3" required placeholder="Tuliskan balasan Anda kepada pembeli..." style="width:100%;padding:10px 12px;border:1px solid #dce4df;border-radius:8px;font-size:12px;box-sizing:border-box;font-family:inherit;outline:none;"><?= htmlspecialchars($fb['balasan'] ?? '') ?></textarea>
                                        <div style="display:flex;gap:8px;margin-top:8px;">
                                            <button type="submit" name="balas_masukan" class="btn-reply">
                                                Kirim Balasan
                                            </button>
                                            <?php if ($fb['status'] === 'belum_dibaca'): ?>
                                                <a href="masukan.php?baca=<?= $fb['id'] ?>" class="btn-mark">
                                                    Tandai Sudah Dibaca
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </details>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="background:white;border:1px solid #f2e6e6;border-radius:18px;padding:70px 20px;text-align:center;">
                        <div style="font-size:50px;margin-bottom:10px;">✉️</div>
                        <h3 style="font-size:16px;margin-bottom:6px;">Belum Ada Masukan</h3>
                        <p style="color:#8a958f;font-size:12px;max-width:350px;margin:0 auto;">
                            Masukan dan saran dari pembeli akan ditampilkan di sini.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>
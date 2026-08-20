<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$buyerId = $_SESSION['user_id'];

// Ambil riwayat masukan pembeli
$query = mysqli_query($conn, "
    SELECT 
        masukan_penjual.*,
        penjual.nama AS nama_penjual,
        penjual.no_wa AS no_wa_penjual
    FROM masukan_penjual
    INNER JOIN users penjual ON masukan_penjual.penjual_id = penjual.id
    WHERE masukan_penjual.pembeli_id = $buyerId
    ORDER BY masukan_penjual.created_at DESC
");

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
    <title>Masukan Saya — Niagora</title>
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
            color: #736769;
            text-decoration: none;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
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
        }

        .page-container {
            width: min(960px, calc(100% - 40px));
            margin: 35px auto 70px;
        }

        .feedback-card {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            transition: .2s;
        }

        .feedback-card:hover {
            border-color: #fca5a5;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-belum {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-dibaca {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-dibalas {
            background: #dcfce7;
            color: #166534;
        }

        .reply-box {
            margin-top: 15px;
            padding: 14px;
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <nav class="buyer-navbar">
        <a href="dashboard.php" class="nav-brand">
            <div class="logo-icon">N</div>
            <span>Niagora</span>
        </a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">Etalase</a>
            <a href="pesanan.php" class="nav-link">Pesanan Saya</a>
            <a href="masukan_saya.php" class="nav-link active">Masukan Saya</a>
            <a href="keranjang.php" class="btn-cart">
                🛒 Keranjang
                <?php if ($cartCount > 0): ?>
                    <span style="background:white;color:#dc2626;padding:2px 6px;border-radius:20px;font-size:11px;"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <a href="../auth/logout.php" class="nav-link" style="color:#d45555;font-weight:700;">Keluar</a>
        </div>
    </nav>

    <div class="page-container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;margin:0 0 6px;">
                    Masukan Saya ke Penjual ✉️
                </h1>
                <p style="color:#736769;font-size:13px;margin:0;">
                    Daftar semua masukan dan saran yang telah Anda kirimkan ke para penjual.
                </p>
            </div>
            <a href="kirim_masukan.php" style="padding:10px 18px;background:#dc2626;color:white;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                + Tulis Masukan Baru
            </a>
        </div>

        <?php if (isset($_GET['sent'])): ?>
            <div style="padding:14px;background:#fee2e2;color:#991b1b;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:600;">
                ✓ Masukan Anda berhasil dikirimkan ke penjual!
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($query) > 0): ?>
            <div>
                <?php while ($m = mysqli_fetch_assoc($query)):
                    $statusClass = 'status-belum';
                    $statusLabel = 'Menunggu Dibaca';
                    if ($m['status'] === 'dibaca') {
                        $statusClass = 'status-dibaca';
                        $statusLabel = 'Sudah Dibaca';
                    } elseif ($m['status'] === 'dibalas') {
                        $statusClass = 'status-dibalas';
                        $statusLabel = 'Telah Dibalas Penjual';
                    }
                ?>
                    <div class="feedback-card">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;border-bottom:1px solid #f2e6e6;padding-bottom:12px;">
                            <div>
                                <div style="font-size:12px;color:#736769;">
                                    Kepada Toko: <strong style="color:#1e1315;"><?= htmlspecialchars($m['nama_penjual']) ?></strong>
                                </div>
                                <div style="font-size:11px;color:#9aa8a0;margin-top:2px;">
                                    Dikirim: <?= date('d M Y, H:i', strtotime($m['created_at'])) ?> WIB
                                </div>
                            </div>
                            <span class="status-pill <?= $statusClass ?>">
                                <?= $statusLabel ?>
                            </span>
                        </div>

                        <h3 style="font-size:16px;font-weight:700;margin:0 0 8px;color:#1e1315;">
                            <?= htmlspecialchars($m['subjek']) ?>
                        </h3>
                        <p style="font-size:13px;color:#716668;line-height:1.6;margin:0;">
                            <?= nl2br(htmlspecialchars($m['pesan'])) ?>
                        </p>

                        <?php if (!empty($m['balasan'])): ?>
                            <div class="reply-box">
                                <strong style="color:#dc2626;display:block;margin-bottom:4px;">
                                    💬 Balasan dari <?= htmlspecialchars($m['nama_penjual']) ?>:
                                </strong>
                                <div style="color:#1e1315;line-height:1.5;">
                                    <?= nl2br(htmlspecialchars($m['balasan'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="background:white;border:1px solid #f2e6e6;border-radius:20px;padding:70px 20px;text-align:center;">
                <div style="font-size:50px;margin-bottom:10px;">✉️</div>
                <h3 style="font-size:18px;margin-bottom:6px;">Belum Ada Masukan yang Dikirim</h3>
                <p style="color:#736769;font-size:13px;max-width:400px;margin:0 auto 20px;">
                    Punya saran, masukan kualitas, atau pertanyaan kepada toko penjual? Kirim masukan sekarang.
                </p>
                <a href="kirim_masukan.php" style="display:inline-block;padding:11px 22px;background:#dc2626;color:white;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                    + Kirim Masukan Pertama
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
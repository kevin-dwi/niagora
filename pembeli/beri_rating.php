<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$buyerId = $_SESSION['user_id'];
$orderId = intval($_GET['pesanan_id'] ?? 0);
$error = "";
$success = "";

// Cek data pesanan
$orderQuery = mysqli_query($conn, "
    SELECT 
        pesanan.*,
        penjual.nama AS nama_penjual
    FROM pesanan
    INNER JOIN users penjual ON pesanan.penjual_id = penjual.id
    WHERE pesanan.id = $orderId
    AND pesanan.pembeli_id = $buyerId
    LIMIT 1
");

$order = mysqli_fetch_assoc($orderQuery);
if (!$order) {
    header("Location: pesanan.php");
    exit;
}

$sellerId = $order['penjual_id'];

// Ambil produk dalam pesanan
$detailQuery = mysqli_query($conn, "
    SELECT *
    FROM detail_pesanan
    WHERE pesanan_id = $orderId
");

$items = [];
while ($d = mysqli_fetch_assoc($detailQuery)) {
    $items[] = $d;
}

// PROSES SUBMIT RATING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_rating'])) {
    // 1. Rating Toko
    $storeRating = intval($_POST['rating_toko'] ?? 5);
    $storeReview = trim($_POST['ulasan_toko'] ?? '');

    if ($storeRating >= 1 && $storeRating <= 5) {
        $stmtStore = mysqli_prepare(
            $conn,
            "INSERT INTO rating_toko (penjual_id, pembeli_id, pesanan_id, rating, ulasan)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), ulasan = VALUES(ulasan)"
        );
        mysqli_stmt_bind_param($stmtStore, "iiiis", $sellerId, $buyerId, $orderId, $storeRating, $storeReview);
        mysqli_stmt_execute($stmtStore);
    }

    // 2. Rating Produk-Produk
    if (isset($_POST['rating_produk']) && is_array($_POST['rating_produk'])) {
        $stmtProd = mysqli_prepare(
            $conn,
            "INSERT INTO rating_produk (produk_id, pembeli_id, pesanan_id, rating, ulasan)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), ulasan = VALUES(ulasan)"
        );

        foreach ($_POST['rating_produk'] as $pId => $pRatingVal) {
            $pId = intval($pId);
            $pRatingVal = intval($pRatingVal);
            $pReview = trim($_POST['ulasan_produk'][$pId] ?? '');

            if ($pRatingVal >= 1 && $pRatingVal <= 5 && $pId > 0) {
                mysqli_stmt_bind_param($stmtProd, "iiiis", $pId, $buyerId, $orderId, $pRatingVal, $pReview);
                mysqli_stmt_execute($stmtProd);
            }
        }
    }

    header("Location: pesanan.php?rated=1");
    exit;
}

// Ambil rating toko saat ini jika sudah pernah diisi
$existingStoreRatingQ = mysqli_query($conn, "SELECT * FROM rating_toko WHERE pembeli_id = $buyerId AND penjual_id = $sellerId LIMIT 1");
$existingStore = mysqli_fetch_assoc($existingStoreRatingQ);
$currentStoreScore = $existingStore['rating'] ?? 5;
$currentStoreText = $existingStore['ulasan'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Rating & Ulasan — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
            font-family: 'DM Sans', sans-serif;
            color: #1e1315;
            margin: 0;
        }

        .page-box {
            width: min(720px, calc(100% - 30px));
            margin: 35px auto 60px;
        }

        .rating-card {
            background: white;
            border: 1px solid #f2e6e6;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .star-radio-group {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 8px;
            margin: 8px 0;
        }

        .star-radio-group input {
            display: none;
        }

        .star-radio-group label {
            font-size: 32px;
            color: #d1d5db;
            cursor: pointer;
            transition: .15s;
        }

        .star-radio-group label:hover,
        .star-radio-group label:hover~label,
        .star-radio-group input:checked~label {
            color: #f59e0b;
            transform: scale(1.15);
        }

        .product-rate-item {
            padding: 18px 0;
            border-top: 1px dashed #f2e6e6;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: .2s;
        }

        .btn-submit:hover {
            background: #991b1b;
        }
    </style>
</head>

<body>
    <div class="page-box">
        <a href="pesanan.php" style="display:inline-block;margin-bottom:15px;color:#736769;font-size:13px;text-decoration:none;">
            ← Kembali ke Pesanan Saya
        </a>

        <div class="rating-card">
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;margin:0 0 6px;color:#dc2626;">
                ⭐ Beri Rating & Ulasan
            </h1>
            <p style="color:#736769;font-size:13px;margin:0 0 20px;">
                Pesanan <strong>#KPD<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></strong> • Toko <strong><?= htmlspecialchars($order['nama_penjual']) ?></strong>
            </p>

            <form method="POST">
                <!-- 1. RATING TOKO -->
                <div style="background:#fdf8f8;border:1px solid #fee2e2;border-radius:16px;padding:20px;margin-bottom:24px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 4px;color:#991b1b;">
                        🏪 1. Rating Toko / Penjual: <?= htmlspecialchars($order['nama_penjual']) ?>
                    </h3>
                    <p style="font-size:12px;color:#736769;margin:0 0 10px;">
                        Bagaimana pelayanan, keramahan, dan kecepatan penjual ini?
                    </p>

                    <div class="star-radio-group">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" id="store_star_<?= $s ?>" name="rating_toko" value="<?= $s ?>" <?= $currentStoreScore == $s ? 'checked' : '' ?>>
                            <label for="store_star_<?= $s ?>" title="<?= $s ?> Bintang">★</label>
                        <?php endfor; ?>
                    </div>

                    <div style="margin-top:10px;">
                        <textarea name="ulasan_toko" rows="2" placeholder="Tuliskan ulasan untuk toko penjual (opsional)..." style="width:100%;padding:10px 12px;border:1px solid #fca5a5;border-radius:9px;font-size:12px;box-sizing:border-box;font-family:inherit;"><?= htmlspecialchars($currentStoreText) ?></textarea>
                    </div>
                </div>

                <!-- 2. RATING PRODUK / BARANG -->
                <div style="margin-bottom:25px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 12px;color:#1e1315;">
                        📦 2. Rating Barang / Produk yang Dibeli
                    </h3>

                    <?php foreach ($items as $idx => $it):
                        $pId = $it['produk_id'];
                        $curProdRatingQ = mysqli_query($conn, "SELECT * FROM rating_produk WHERE pembeli_id = $buyerId AND produk_id = $pId LIMIT 1");
                        $curProdR = mysqli_fetch_assoc($curProdRatingQ);
                        $curPScore = $curProdR['rating'] ?? 5;
                        $curPText = $curProdR['ulasan'] ?? '';
                    ?>
                        <div class="product-rate-item">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <strong style="font-size:14px;color:#1e1315;">
                                    <?= htmlspecialchars($it['nama_produk']) ?> (<?= $it['jumlah'] ?>x)
                                </strong>
                                <span style="font-size:12px;color:#dc2626;font-weight:700;">
                                    Rp <?= number_format($it['harga'], 0, ',', '.') ?>
                                </span>
                            </div>

                            <div class="star-radio-group">
                                <?php for ($s = 5; $s >= 1; $s--): ?>
                                    <input type="radio" id="prod_star_<?= $pId ?>_<?= $s ?>" name="rating_produk[<?= $pId ?>]" value="<?= $s ?>" <?= $curPScore == $s ? 'checked' : '' ?>>
                                    <label for="prod_star_<?= $pId ?>_<?= $s ?>" title="<?= $s ?> Bintang">★</label>
                                <?php endfor; ?>
                            </div>

                            <div style="margin-top:8px;">
                                <textarea name="ulasan_produk[<?= $pId ?>]" rows="2" placeholder="Tuliskan ulasan tentang kualitas produk ini..." style="width:100%;padding:10px 12px;border:1px solid #e5ece8;border-radius:9px;font-size:12px;box-sizing:border-box;font-family:inherit;"><?= htmlspecialchars($curPText) ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" name="simpan_rating" class="btn-submit">
                    ⭐ Simpan Rating & Ulasan
                </button>
            </form>
        </div>
    </div>
</body>

</html>
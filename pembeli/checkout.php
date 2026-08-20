<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: keranjang.php");
    exit;
}

$pembeliId = $_SESSION['user_id'];
$buyerQuery = mysqli_query($conn, "SELECT * FROM users WHERE id = $pembeliId LIMIT 1");
$buyer = mysqli_fetch_assoc($buyerQuery);

// Ambil data produk di keranjang
$ids = array_map('intval', array_keys($cart));
$idString = implode(',', $ids);

$productQuery = mysqli_query($conn, "
    SELECT
        produk.*,
        users.nama AS nama_penjual,
        users.no_wa AS no_wa_penjual
    FROM produk
    INNER JOIN users ON produk.penjual_id = users.id
    WHERE produk.id IN ($idString)
");

$cartItems = [];
$totalHarga = 0;
$sellerGroups = [];

while ($row = mysqli_fetch_assoc($productQuery)) {
    $qty = intval($cart[$row['id']] ?? 0);
    if ($qty > 0) {
        $subtotal = $row['harga'] * $qty;
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $totalHarga += $subtotal;
        $cartItems[] = $row;

        $sellerId = $row['penjual_id'];
        if (!isset($sellerGroups[$sellerId])) {
            $sellerGroups[$sellerId] = [
                'nama_penjual' => $row['nama_penjual'],
                'no_wa_penjual' => $row['no_wa_penjual'],
                'items' => [],
                'total' => 0
            ];
        }
        $sellerGroups[$sellerId]['items'][] = $row;
        $sellerGroups[$sellerId]['total'] += $subtotal;
    }
}

$error = "";

// PROSES CHECKOUT
if (isset($_POST['proses_transaksi'])) {
    $nama_penerima = trim($_POST['nama_penerima']);
    $no_wa_penerima = trim($_POST['no_wa_penerima']);
    $alamat_pengiriman = trim($_POST['alamat_pengiriman']);
    $metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'Tunai / COD');
    $catatan = trim($_POST['catatan'] ?? '');

    if (empty($nama_penerima) || empty($no_wa_penerima)) {
        $error = "Nama penerima dan Nomor WhatsApp wajib diisi.";
    } else {
        $conn->begin_transaction();
        try {
            $createdOrderIds = [];

            // Buat pesanan per penjual
            foreach ($sellerGroups as $sellerId => $sGroup) {
                $orderTotal = $sGroup['total'];

                $orderStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO pesanan (pembeli_id, penjual_id, total_harga, status, metode_pembayaran, nama_penerima, no_wa_penerima, alamat_pengiriman, catatan)
                     VALUES (?, ?, ?, 'Selesai', ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $orderStmt,
                    "iidsssss",
                    $pembeliId,
                    $sellerId,
                    $orderTotal,
                    $metode_pembayaran,
                    $nama_penerima,
                    $no_wa_penerima,
                    $alamat_pengiriman,
                    $catatan
                );
                mysqli_stmt_execute($orderStmt);
                $newOrderId = mysqli_insert_id($conn);
                $createdOrderIds[] = $newOrderId;

                // Insert details and decrement stock
                $detailStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stockStmt = mysqli_prepare(
                    $conn,
                    "UPDATE produk SET stok = GREATEST(0, stok - ?), terjual = terjual + ? WHERE id = ?"
                );

                foreach ($sGroup['items'] as $item) {
                    $pId = $item['id'];
                    $pName = $item['nama_produk'];
                    $pPrice = $item['harga'];
                    $pQty = $item['qty'];
                    $pSubtotal = $item['subtotal'];

                    mysqli_stmt_bind_param($detailStmt, "iisdid", $newOrderId, $pId, $pName, $pPrice, $pQty, $pSubtotal);
                    mysqli_stmt_execute($detailStmt);

                    mysqli_stmt_bind_param($stockStmt, "iii", $pQty, $pQty, $pId);
                    mysqli_stmt_execute($stockStmt);
                }
            }

            $conn->commit();

            // Kosongkan keranjang
            $_SESSION['cart'] = [];

            // Arahkan ke struk pesanan terbaru
            $lastOrderId = end($createdOrderIds);
            header("Location: struk.php?id=" . $lastOrderId . "&success=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan saat memproses transaksi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selesaikan Pesanan & Transaksi — Niagora</title>
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
        .nav-link:hover { color: #dc2626; background: #fee2e2; }

        .checkout-page {
            width: min(1080px, calc(100% - 40px));
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

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 25px;
            align-items: start;
        }

        .card-box {
            background: white;
            border: 1px solid #e5ece8;
            border-radius: 18px;
            padding: 26px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }
        .card-box h3 {
            margin: 0 0 18px;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #17251e;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            margin-bottom: 16px;
        }
        .form-row label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #2b3a32;
        }
        .form-row input, .form-row select, .form-row textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dce4df;
            border-radius: 10px;
            outline: none;
            background: #fbfcfb;
            font-family: inherit;
            font-size: 13px;
            box-sizing: border-box;
            transition: .2s;
        }
        .form-row input:focus, .form-row select:focus, .form-row textarea:focus {
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220,38,38,.1);
        }

        .payment-options {
            display: grid;
            gap: 10px;
        }
        .payment-label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #dce4df;
            border-radius: 12px;
            cursor: pointer;
            transition: .2s;
            background: #fdfefe;
        }
        .payment-label:hover {
            border-color: #dc2626;
            background: #fdf8f8;
        }
        .payment-label input[type="radio"] {
            accent-color: #dc2626;
            width: 17px;
            height: 17px;
        }
        .payment-info strong {
            display: block;
            font-size: 13px;
            color: #17251e;
        }
        .payment-info span {
            font-size: 11px;
            color: #7b8881;
        }

        /* ORDER REVIEW ITEMS */
        .order-summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f4f1;
            font-size: 13px;
        }
        .order-summary-item:last-child {
            border-bottom: none;
        }
        .order-summary-item .item-txt strong {
            display: block;
            color: #17251e;
            font-size: 13px;
        }
        .order-summary-item .item-txt span {
            color: #7b8881;
            font-size: 11px;
        }

        .total-breakdown {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #d5ded8;
        }
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6a7870;
            margin-bottom: 8px;
        }
        .breakdown-row.final {
            font-size: 18px;
            font-weight: 800;
            color: #17251e;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #edf2ee;
        }
        .breakdown-row.final span:last-child {
            color: #dc2626;
        }

        .btn-confirm {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-sizing: border-box;
            transition: .25s;
        }
        .btn-confirm:hover {
            background: #991b1b;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.25);
        }

        .alert-box {
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            background: #fff0f2;
            color: #a22d3e;
            border: 1px solid #ffd6dc;
        }

        @media (max-width: 850px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="buyer-navbar">
    <a href="dashboard.php" class="nav-brand">
        <div class="logo-icon">N</div>
        <span>Niagora</span>
    </a>

    <div class="nav-links">
        <a href="keranjang.php" class="nav-link">← Kembali ke Keranjang</a>
    </div>
</nav>

<div class="checkout-page">
    <div class="page-header">
        <h1>Selesaikan Pesanan & Transaksi 💳</h1>
        <p>Lengkapi informasi pengiriman dan metode pembayaran untuk mendapatkan struk digital Anda.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-box">
            ⚠ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="checkout-grid">
            <!-- LEFT FORM -->
            <div>
                <!-- FORM PENERIMA -->
                <div class="card-box">
                    <h3>📍 Informasi Pengiriman & Penerima</h3>

                    <div class="form-row">
                        <label>Nama Lengkap Penerima</label>
                        <input type="text" name="nama_penerima" value="<?= htmlspecialchars($buyer['nama'] ?? '') ?>" required placeholder="Nama penerima pesanan">
                    </div>

                    <div class="form-row">
                        <label>Nomor WhatsApp Penerima (Aktif)</label>
                        <input type="text" name="no_wa_penerima" value="<?= htmlspecialchars($buyer['no_wa'] ?? '') ?>" required placeholder="Contoh: 081234567890">
                        <small style="color:#7b8881;font-size:11px;display:block;margin-top:4px;">
                            Nomor ini digunakan penjual untuk konfirmasi barang & pengiriman.
                        </small>
                    </div>

                    <div class="form-row">
                        <label>Alamat / Lokasi Pengantaran</label>
                        <textarea name="alamat_pengiriman" rows="3" placeholder="Contoh: RT 03 RW 01, Rumah Pagar Hijau, Desa Sukamaju"><?= htmlspecialchars($buyer['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <label>Catatan Tambahan untuk Penjual (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Contoh: Tolong bungkus rapat ya kak, terima kasih.">
                    </div>
                </div>

                <!-- METODE PEMBAYARAN -->
                <div class="card-box">
                    <h3>💵 Metode Pembayaran</h3>

                    <div class="payment-options">
                        <label class="payment-label">
                            <input type="radio" name="metode_pembayaran" value="Tunai / COD" checked>
                            <div class="payment-info">
                                <strong>💵 Tunai / Bayar di Tempat (COD)</strong>
                                <span>Bayar langsung ke penjual atau kurir saat barang diterima.</span>
                            </div>
                        </label>

                        <label class="payment-label">
                            <input type="radio" name="metode_pembayaran" value="QRIS / Dompet Digital">
                            <div class="payment-info">
                                <strong>📱 QRIS / E-Wallet (DANA, GoPay, OVO, ShopeePay)</strong>
                                <span>Scan QRIS atau transfer dompet digital instan.</span>
                            </div>
                        </label>

                        <label class="payment-label">
                            <input type="radio" name="metode_pembayaran" value="Transfer Bank">
                            <div class="payment-info">
                                <strong>🏦 Transfer Bank (BRI, BNI, BCA, Mandiri)</strong>
                                <span>Transfer langsung ke rekening penjual.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- RIGHT REVIEW -->
            <div>
                <div class="card-box">
                    <h3>🛍️ Rincian Belanja</h3>

                    <?php foreach ($cartItems as $cItem): ?>
                        <div class="order-summary-item">
                            <div class="item-txt">
                                <strong><?= htmlspecialchars($cItem['nama_produk']) ?></strong>
                                <span><?= $cItem['qty'] ?> pcs × Rp <?= number_format($cItem['harga'], 0, ',', '.') ?> (Penjual: <?= htmlspecialchars($cItem['nama_penjual']) ?>)</span>
                            </div>
                            <div style="font-weight:700;color:#1c2b23;">
                                Rp <?= number_format($cItem['subtotal'], 0, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-breakdown">
                        <div class="breakdown-row">
                            <span>Total Harga Produk</span>
                            <strong>Rp <?= number_format($totalHarga, 0, ',', '.') ?></strong>
                        </div>
                        <div class="breakdown-row">
                            <span>Biaya Layanan Niagora</span>
                            <strong style="color:#dc2626;">GRATIS</strong>
                        </div>
                        <div class="breakdown-row final">
                            <span>Total Tagihan</span>
                            <span>Rp <?= number_format($totalHarga, 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <button type="submit" name="proses_transaksi" class="btn-confirm">
                        ✓ Konfirmasi Pesanan & Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

</body>
</html>

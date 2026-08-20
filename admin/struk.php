<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$orderId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

// Ambil data pesanan milik penjual ini
$query = mysqli_query($conn, "
    SELECT
        pesanan.*,
        pembeli.nama AS nama_pembeli,
        pembeli.email AS email_pembeli,
        pembeli.no_wa AS no_wa_pembeli,
        penjual.nama AS nama_toko,
        penjual.email AS email_toko,
        penjual.no_wa AS no_wa_toko,
        penjual.alamat AS alamat_toko
    FROM pesanan
    INNER JOIN users pembeli ON pesanan.pembeli_id = pembeli.id
    INNER JOIN users penjual ON pesanan.penjual_id = penjual.id
    WHERE pesanan.id = $orderId
    AND pesanan.penjual_id = $userId
");

$order = mysqli_fetch_assoc($query);

if (!$order) {
    echo "<script>alert('Pesanan tidak ditemukan atau bukan milik Anda.'); window.location='pesanan.php';</script>";
    exit;
}

$details = mysqli_query($conn, "
    SELECT *
    FROM detail_pesanan
    WHERE pesanan_id = $orderId
    ORDER BY id ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Penjualan #KPD<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?> — Niagora</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fdf8f8;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #1e1315;
            padding: 30px 15px;
            display: flex;
            justify-content: center;
        }

        .receipt-card {
            width: 100%;
            max-width: 520px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 32px;
            position: relative;
        }

        .header-top {
            text-align: center;
            border-bottom: 2px dashed #f2e6e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 22px;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 6px;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: #dc2626;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .store-name {
            font-size: 15px;
            font-weight: 700;
            color: #1e1315;
        }

        .store-info {
            font-size: 12px;
            color: #716668;
            margin-top: 4px;
        }

        .receipt-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 14px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            font-size: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #d5ded8;
        }

        .meta-item strong {
            display: block;
            color: #8b9991;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .meta-item span {
            font-weight: 600;
            color: #1c2b23;
        }

        .table-items {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .table-items th {
            text-align: left;
            padding: 8px 0;
            color: #8b9991;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #e2eae5;
        }

        .table-items td {
            padding: 10px 0;
            border-bottom: 1px solid #f0f4f1;
            vertical-align: top;
        }

        .table-items td.text-right, .table-items th.text-right {
            text-align: right;
        }

        .item-title {
            font-weight: 700;
            color: #1c2b23;
            display: block;
        }

        .item-unit {
            font-size: 11px;
            color: #7a8a81;
        }

        .summary-box {
            border-top: 2px dashed #d5ded8;
            padding-top: 15px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 8px;
            color: #55635c;
        }

        .summary-row.grand-total {
            font-size: 17px;
            font-weight: 800;
            color: #dc2626;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2eae5;
        }

        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #8b9991;
            line-height: 1.5;
            margin-top: 20px;
        }

        .action-bar {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: .2s;
        }

        .btn-print {
            background: #dc2626;
            color: white;
        }
        .btn-print:hover {
            background: #991b1b;
        }

        .btn-back {
            background: #e4ece7;
            color: #4b5952;
        }
        .btn-back:hover {
            background: #d4e0d9;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                max-width: 100%;
                border-radius: 0;
                padding: 20px;
            }
            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="header-top">
        <div class="brand-logo">
            <div class="brand-icon">N</div>
            Niagora
        </div>
        <div class="store-name"><?= htmlspecialchars($order['nama_toko'] ?? 'Toko Penjual') ?></div>
        <?php if (!empty($order['no_wa_toko'])): ?>
            <div class="store-info">WhatsApp: <?= htmlspecialchars($order['no_wa_toko']) ?></div>
        <?php endif; ?>
        <?php if (!empty($order['alamat_toko'])): ?>
            <div class="store-info"><?= htmlspecialchars($order['alamat_toko']) ?></div>
        <?php endif; ?>
        <span class="receipt-badge">Struk Transaksi Penjualan</span>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <strong>No. Pesanan</strong>
            <span>#KPD<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="meta-item">
            <strong>Waktu Transaksi</strong>
            <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <div class="meta-item">
            <strong>Nama Pembeli</strong>
            <span><?= htmlspecialchars($order['nama_penerima'] ?: $order['nama_pembeli']) ?></span>
        </div>
        <div class="meta-item">
            <strong>WhatsApp Pembeli</strong>
            <span><?= htmlspecialchars($order['no_wa_penerima'] ?: $order['no_wa_pembeli'] ?: '-') ?></span>
        </div>
        <div class="meta-item">
            <strong>Metode Pembayaran</strong>
            <span><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Tunai') ?></span>
        </div>
        <div class="meta-item">
            <strong>Status Pesanan</strong>
            <span style="color: #dc2626;"><?= htmlspecialchars($order['status']) ?></span>
        </div>
    </div>

    <?php if (!empty($order['alamat_pengiriman'])): ?>
        <div style="font-size:11px;color:#55635c;margin-bottom:15px;background:#f9fbf9;padding:10px;border-radius:8px;">
            <strong>Alamat / Lokasi Pengiriman:</strong><br>
            <?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?>
        </div>
    <?php endif; ?>

    <table class="table-items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $subtotalSum = 0;
            while ($item = mysqli_fetch_assoc($details)):
                $subtotalSum += $item['subtotal'];
            ?>
                <tr>
                    <td>
                        <span class="item-title"><?= htmlspecialchars($item['nama_produk']) ?></span>
                    </td>
                    <td class="text-right"><?= $item['jumlah'] ?>x</td>
                    <td class="text-right">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                    <td class="text-right"><strong>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></strong></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Tagihan Produk</span>
            <strong>Rp <?= number_format($subtotalSum, 0, ',', '.') ?></strong>
        </div>
        <div class="summary-row grand-total">
            <span>TOTAL PEMBAYARAN</span>
            <span>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="footer-note">
        Dokumen struk resmi dicetak melalui platform <strong>Niagora</strong>.<br>
        Terima kasih atas transaksi Anda!
    </div>

    <div class="action-bar">
        <a href="pesanan.php" class="btn btn-back">
            ← Kembali
        </a>
        <button onclick="window.print()" class="btn btn-print">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>
</div>

</body>
</html>

<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$orderId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$isSuccess = isset($_GET['success']) ? true : false;

$orderQuery = mysqli_query(
    $conn,
    "SELECT
        pesanan.*,
        pembeli.nama AS nama_pembeli,
        pembeli.email AS email_pembeli,
        pembeli.no_wa AS no_wa_pembeli,
        penjual.nama AS nama_penjual,
        penjual.no_wa AS no_wa_penjual,
        penjual.alamat AS alamat_penjual
     FROM pesanan
     INNER JOIN users pembeli ON pesanan.pembeli_id = pembeli.id
     INNER JOIN users penjual ON pesanan.penjual_id = penjual.id
     WHERE pesanan.id = $orderId
     AND pesanan.pembeli_id = $userId"
);

$order = mysqli_fetch_assoc($orderQuery);

if (!$order) {
    header("Location: pesanan.php");
    exit;
}

$details = mysqli_query(
    $conn,
    "SELECT *
     FROM detail_pesanan
     WHERE pesanan_id = $orderId
     ORDER BY id ASC"
);

$itemsList = [];
$totalCalc = 0;
while ($row = mysqli_fetch_assoc($details)) {
    $itemsList[] = $row;
    $totalCalc += $row['subtotal'];
}

// Format No WhatsApp Penjual
$waPenjual = preg_replace('/[^0-9]/', '', $order['no_wa_penjual'] ?? '');
if (!empty($waPenjual) && substr($waPenjual, 0, 1) === '0') {
    $waPenjual = '62' . substr($waPenjual, 1);
}
$orderCode = "#KPD" . str_pad($orderId, 5, '0', STR_PAD_LEFT);
$waMessage = "Halo " . $order['nama_penjual'] . ", saya " . ($order['nama_penerima'] ?: $order['nama_pembeli']) . " ingin konfirmasi pesanan " . $orderCode . " sebesar Rp " . number_format($order['total_harga'], 0, ',', '.') . " di Niagora.";
$waUrl = !empty($waPenjual) ? "https://wa.me/" . $waPenjual . "?text=" . urlencode($waMessage) : "#";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Digital <?= $orderCode ?> — Niagora</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fdf8f8;
            font-family: 'DM Sans', Arial, sans-serif;
            color: #1e1315;
            padding: 35px 15px 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .success-banner {
            width: 100%;
            max-width: 460px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);
        }

        .receipt-container {
            width: 100%;
            max-width: 460px;
            background: white;
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #f2e6e6;
            padding-bottom: 22px;
            margin-bottom: 20px;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #dc2626;
            margin-bottom: 4px;
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

        .receipt-tagline {
            font-size: 12px;
            color: #7b8a81;
        }

        .stamp-badge {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 14px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .meta-list {
            font-size: 12px;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px dashed #d5ded8;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .meta-row span:first-child {
            color: #7b8a81;
        }
        .meta-row span:last-child {
            font-weight: 600;
            color: #1c2b23;
            text-align: right;
        }

        .seller-badge-box {
            background: #f7faf8;
            border: 1px solid #e3ede6;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .seller-badge-box strong {
            display: block;
            color: #1c2b23;
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .items-table th {
            text-align: left;
            padding: 8px 0;
            color: #8b9991;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #e5ece7;
        }
        .items-table td {
            padding: 10px 0;
            border-bottom: 1px solid #f2f6f3;
            vertical-align: top;
        }
        .items-table .text-right {
            text-align: right;
        }
        .item-name {
            font-weight: 700;
            color: #17251e;
        }
        .item-sub {
            font-size: 11px;
            color: #7b8a81;
        }

        .total-section {
            border-top: 2px dashed #d5ded8;
            padding-top: 15px;
            margin-bottom: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #617067;
            margin-bottom: 8px;
        }
        .total-row.grand {
            font-size: 17px;
            font-weight: 800;
            color: #dc2626;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5ece7;
        }

        .receipt-footer {
            text-align: center;
            font-size: 11px;
            color: #8b9991;
            line-height: 1.5;
            margin-top: 15px;
        }

        /* ACTIONS */
        .actions-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 25px;
        }
        .btn-act {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            cursor: pointer;
            border: none;
            box-sizing: border-box;
            transition: .2s;
        }
        .btn-print-pdf {
            background: #dc2626;
            color: white;
        }
        .btn-print-pdf:hover {
            background: #991b1b;
            transform: translateY(-2px);
        }
        .btn-chat-seller {
            background: #25d366;
            color: white;
        }
        .btn-chat-seller:hover {
            background: #1fa851;
            transform: translateY(-2px);
        }
        .btn-nav-history {
            background: #e6ede8;
            color: #4b5952;
        }
        .btn-nav-history:hover {
            background: #d8e2dc;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .success-banner, .actions-stack {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                border-radius: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<?php if ($isSuccess): ?>
    <div class="success-banner">
        🎉 <strong>Transaksi Berhasil!</strong> Pesanan Anda telah tercatat di sistem Niagora.
    </div>
<?php endif; ?>

<div class="receipt-container">
    <div class="receipt-header">
        <div class="brand-logo">
            <div class="brand-icon">N</div>
            Niagora
        </div>
        <div class="receipt-tagline">Struk Digital Pembelian Resmi</div>
        <span class="stamp-badge">✓ TRANSAKSI SUKSES</span>
    </div>

    <!-- META ORDER -->
    <div class="meta-list">
        <div class="meta-row">
            <span>No. Pesanan</span>
            <strong><?= $orderCode ?></strong>
        </div>
        <div class="meta-row">
            <span>Tanggal Transaksi</span>
            <span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?> WIB</span>
        </div>
        <div class="meta-row">
            <span>Nama Pembeli</span>
            <span><?= htmlspecialchars($order['nama_penerima'] ?: $order['nama_pembeli']) ?></span>
        </div>
        <div class="meta-row">
            <span>WhatsApp Pembeli</span>
            <span><?= htmlspecialchars($order['no_wa_penerima'] ?: $order['no_wa_pembeli'] ?: '-') ?></span>
        </div>
        <div class="meta-row">
            <span>Metode Pembayaran</span>
            <span><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Tunai / COD') ?></span>
        </div>
        <div class="meta-row">
            <span>Status</span>
            <span style="color:#dc2626;font-weight:700;"><?= htmlspecialchars($order['status']) ?></span>
        </div>
    </div>

    <!-- SELLER BOX -->
    <div class="seller-badge-box">
        <strong>🏪 Toko Penjual: <?= htmlspecialchars($order['nama_penjual']) ?></strong>
        <?php if (!empty($order['no_wa_penjual'])): ?>
            <div style="color:#5f7066;margin-top:2px;">No. WhatsApp: <?= htmlspecialchars($order['no_wa_penjual']) ?></div>
        <?php endif; ?>
        <?php if (!empty($order['alamat_penjual'])): ?>
            <div style="color:#5f7066;margin-top:2px;">Lokasi: <?= htmlspecialchars($order['alamat_penjual']) ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($order['alamat_pengiriman'])): ?>
        <div style="font-size:11px;color:#5a6860;margin-bottom:15px;background:#f9fbf9;padding:10px;border-radius:10px;">
            <strong>📍 Alamat / Catatan Pengiriman:</strong><br>
            <?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?>
        </div>
    <?php endif; ?>

    <!-- TABLE ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemsList as $it): ?>
                <tr>
                    <td>
                        <div class="item-name"><?= htmlspecialchars($it['nama_produk']) ?></div>
                    </td>
                    <td class="text-right"><?= $it['jumlah'] ?>x</td>
                    <td class="text-right">Rp <?= number_format($it['harga'], 0, ',', '.') ?></td>
                    <td class="text-right"><strong>Rp <?= number_format($it['subtotal'], 0, ',', '.') ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTAL -->
    <div class="total-section">
        <div class="total-row">
            <span>Total Tagihan Barang</span>
            <strong>Rp <?= number_format($totalCalc, 0, ',', '.') ?></strong>
        </div>
        <div class="total-row">
            <span>Biaya Layanan</span>
            <strong style="color:#dc2626;">Rp 0 (GRATIS)</strong>
        </div>
        <div class="total-row grand">
            <span>TOTAL PEMBAYARAN</span>
            <span>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="receipt-footer">
        Terima kasih telah berbelanja dan mendukung usaha lokal bersama <strong>Niagora</strong>.<br>
        Simpan struk ini sebagai bukti transaksi sah Anda.
    </div>

    <!-- ACTIONS -->
    <div class="actions-stack">
        <a href="beri_rating.php?pesanan_id=<?= $orderId ?>" class="btn-act" style="background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;">
            ⭐ Beri Rating & Ulasan (Toko & Produk)
        </a>

        <a href="kirim_masukan.php?penjual_id=<?= $order['penjual_id'] ?>" class="btn-act" style="background:#fee2e2;color:#991b1b;">
            ✉️ Kirim Masukan / Saran ke Penjual
        </a>

        <button onclick="window.print()" class="btn-act btn-print-pdf">
            🖨️ Cetak / Simpan Struk PDF
        </button>

        <?php if (!empty($waPenjual)): ?>
            <a href="<?= $waUrl ?>" target="_blank" class="btn-act btn-chat-seller">
                💬 Hubungi Penjual via WhatsApp
            </a>
        <?php endif; ?>

        <a href="pesanan.php" class="btn-act btn-nav-history">
            📦 Lihat Riwayat Pesanan Saya
        </a>

        <a href="dashboard.php" style="text-align:center;color:#dc2626;font-size:12px;font-weight:700;text-decoration:none;margin-top:5px;">
            ← Lanjut Belanja di Etalase
        </a>
    </div>
</div>

</body>
</html>

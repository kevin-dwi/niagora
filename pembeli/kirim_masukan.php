<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$buyerId = $_SESSION['user_id'];
$error = "";
$success = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $penjualId = intval($_POST['penjual_id'] ?? 0);
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    $redirect = trim($_POST['redirect'] ?? '');

    if ($penjualId <= 0 || empty($subjek) || empty($pesan)) {
        $error = "Semua field masukan wajib diisi.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO masukan_penjual (penjual_id, pembeli_id, subjek, pesan, status)
             VALUES (?, ?, ?, ?, 'belum_dibaca')"
        );
        mysqli_stmt_bind_param($stmt, "iiss", $penjualId, $buyerId, $subjek, $pesan);

        if (mysqli_stmt_execute($stmt)) {
            if ($redirect === 'dashboard') {
                header("Location: dashboard.php?feedback_sent=1");
                exit;
            } elseif ($redirect === 'pesanan') {
                header("Location: pesanan.php?feedback_sent=1");
                exit;
            } else {
                header("Location: masukan_saya.php?sent=1");
                exit;
            }
        } else {
            $error = "Gagal mengirimkan masukan: " . mysqli_error($conn);
        }
    }
}

$targetSellerId = intval($_GET['penjual_id'] ?? 0);
$seller = null;
if ($targetSellerId > 0) {
    $sq = mysqli_query($conn, "SELECT id, nama, email, no_wa FROM users WHERE id = $targetSellerId AND role = 'penjual' LIMIT 1");
    $seller = mysqli_fetch_assoc($sq);
}

// Ambil daftar penjual jika tidak spesifik
$allSellers = mysqli_query($conn, "SELECT id, nama FROM users WHERE role = 'penjual' ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Masukan ke Penjual — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
        }

        .page-box {
            width: min(650px, calc(100% - 30px));
            margin: 40px auto;
        }

        .form-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--shadow);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 10px;
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
        <a href="dashboard.php" style="display:inline-block;margin-bottom:15px;color:#736769;font-size:13px;text-decoration:none;">
            ← Kembali ke Etalase
        </a>

        <div class="form-card">
            <h1 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;margin:0 0 6px;color:#dc2626;">
                ✉️ Beri Masukan / Saran ke Penjual
            </h1>
            <p style="color:#736769;font-size:13px;margin:0 0 22px;">
                Sampaikan masukan, saran, apresiasi atau pertanyaan Anda langsung kepada toko penjual.
            </p>

            <?php if (!empty($error)): ?>
                <div style="padding:12px;background:#fee2e2;color:#991b1b;border-radius:10px;font-size:13px;margin-bottom:18px;">
                    ⚠ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;">Pilih Toko / Penjual</label>
                    <?php if ($seller): ?>
                        <input type="hidden" name="penjual_id" value="<?= $seller['id'] ?>">
                        <input type="text" value="<?= htmlspecialchars($seller['nama']) ?>" readonly style="width:100%;padding:13px;background:#faf5f5;border:1px solid #f2e6e6;border-radius:10px;font-size:13px;box-sizing:border-box;color:#1e1315;font-weight:700;">
                    <?php else: ?>
                        <select name="penjual_id" required style="width:100%;padding:13px;background:#fbfcfb;border:1px solid #dce4df;border-radius:10px;font-size:13px;box-sizing:border-box;">
                            <option value="">-- Pilih Toko Penjual --</option>
                            <?php while ($s = mysqli_fetch_assoc($allSellers)): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;">Subjek / Topik</label>
                    <input type="text" name="subjek" required placeholder="Contoh: Kualitas Kemasan / Waktu Pengiriman / Tanya Varian Produk" style="width:100%;padding:13px;border:1px solid #dce4df;border-radius:10px;font-size:13px;box-sizing:border-box;outline:none;">
                </div>

                <div style="margin-bottom:22px;">
                    <label style="display:block;font-size:12px;font-weight:700;margin-bottom:6px;">Isi Masukan / Saran Anda</label>
                    <textarea name="pesan" rows="5" required placeholder="Tuliskan pesan masukan Anda secara jelas dan sopan..." style="width:100%;padding:13px;border:1px solid #dce4df;border-radius:10px;font-size:13px;box-sizing:border-box;outline:none;font-family:inherit;resize:vertical;"></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    ✉️ Kirim Masukan ke Penjual
                </button>
            </form>
        </div>
    </div>
</body>

</html>
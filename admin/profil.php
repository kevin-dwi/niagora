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

if (isset($_POST['simpan_profil'])) {
    $nama = trim($_POST['nama']);
    $no_wa = trim($_POST['no_wa']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama)) {
        $error = "Nama toko / penjual wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, no_wa = ?, alamat = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $nama, $no_wa, $alamat, $userId);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['nama'] = $nama;
            $success = "Profil toko dan nomor WhatsApp berhasil diperbarui!";
        } else {
            $error = "Gagal menyimpan perubahan profil.";
        }
    }
}

$query = mysqli_query($conn, "SELECT * FROM users WHERE id = $userId LIMIT 1");
$user = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil & WhatsApp Penjual — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
        .dashboard-content { padding: 35px; max-width: 800px; }

        .profile-card {
            background: white;
            border: 1px solid #e6ebe8;
            border-radius: 18px;
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2c3e35;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e1e7e3;
            border-radius: 10px;
            outline: none;
            background: #fbfcfb;
            font-family: inherit;
            font-size: 13px;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220,38,38,.1);
        }
        .form-group input[readonly] {
            background: #f1f4f2;
            color: #718077;
            cursor: not-allowed;
        }
        .wa-preview {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #fee2e2;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 12px;
            color: #991b1b;
        }
        .btn-save {
            padding: 13px 25px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: .2s;
        }
        .btn-save:hover {
            background: #991b1b;
            transform: translateY(-2px);
        }
        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .alert-success { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-error { background: #fff0f2; color: #a22d3e; border: 1px solid #ffd6dc; }

        @media (max-width: 800px) {
            .sidebar { display: none; }
            .dashboard-main { width: 100%; margin-left: 0; }
            .dashboard-content { padding: 20px; }
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
            <a href="pesanan.php"><span class="menu-icon">🛒</span>Pesanan Masuk</a>
            <a href="ulasan.php"><span class="menu-icon">⭐</span>Rating & Ulasan</a>
            <a href="masukan.php"><span class="menu-icon">✉️</span>Masukan Pembeli</a>
            <div class="menu-title">Pengaturan</div>
            <a href="profil.php" class="active"><span class="menu-icon">👤</span>Profil & No. WhatsApp</a>
        </div>

        <div class="sidebar-bottom">
            <div class="profile-mini">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['nama'] ?? 'P', 0, 1)) ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars($user['nama'] ?? 'Penjual') ?></strong>
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
                <h1>Profil Toko & WhatsApp</h1>
                <p>Kelola data toko dan nomor WhatsApp untuk kontak pembeli.</p>
            </div>
        </header>

        <div class="dashboard-content">
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

            <div class="profile-card">
                <form method="POST">
                    <div class="form-group">
                        <label>Email Akun (Login)</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        <small style="color:#8a958f;font-size:11px;">Email akun tidak dapat diubah.</small>
                    </div>

                    <div class="form-group">
                        <label>Nama Toko / Penjual</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required placeholder="Contoh: Toko Berkah Tani">
                    </div>

                    <div class="form-group">
                        <label>Nomor WhatsApp (Aktif)</label>
                        <input type="text" name="no_wa" value="<?= htmlspecialchars($user['no_wa'] ?? '') ?>" placeholder="Contoh: 081234567890 atau 6281234567890">
                        <small style="color:#718077;font-size:11px;display:block;margin-top:4px;">
                            Tombol <strong>"Chat WhatsApp"</strong> pada etalase pembeli akan otomatis menghubungkan pembeli ke nomor ini.
                        </small>
                        <?php if (!empty($user['no_wa'])): ?>
                            <div class="wa-preview">
                                💬 Nomor WA aktif sekarang: <strong><?= htmlspecialchars($user['no_wa']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Alamat Toko / Lokasi Pengambilan</label>
                        <textarea name="alamat" rows="3" placeholder="Contoh: Dusun Krajan RT 01 RW 02, Desa Sukamaju"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="simpan_profil" class="btn-save">
                        💾 Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>

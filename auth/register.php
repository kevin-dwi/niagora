<?php

session_start();

require_once "../config/database.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_wa = trim($_POST['no_wa'] ?? '');
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];
    $role = $_POST['role'];

    if (
        empty($nama) ||
        empty($email) ||
        empty($password) ||
        empty($konfirmasi)
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Format email tidak valid.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak sama.";

    } else {

        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $cek,
            "s",
            $email
        );

        mysqli_stmt_execute($cek);

        $result = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($result) > 0) {

            $error = "Email sudah terdaftar.";

        } else {

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (nama, email, password, role, no_wa)
                VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $nama,
                $email,
                $passwordHash,
                $role,
                $no_wa
            );

            if (mysqli_stmt_execute($stmt)) {

                $success =
                    "Akun berhasil dibuat. Silakan masuk.";

            } else {

                $error =
                    "Terjadi kesalahan saat membuat akun.";

            }

        }

    }

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Daftar — Niagora</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        body {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                radial-gradient(
                    circle at top right,
                    #fee2e2,
                    transparent 35%
                ),
                #fdf8f8;
        }

        .auth-wrapper {
            width: min(1050px, calc(100% - 30px));

            display: grid;

            grid-template-columns: 1fr 1fr;

            overflow: hidden;

            background: white;

            border-radius: 28px;

            box-shadow:
                0 25px 80px rgba(60, 20, 25, .12);
        }

        .auth-banner {
            padding: 50px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            min-height: 650px;

            color: white;

            background:
                linear-gradient(
                    145deg,
                    #7f1d1d,
                    #dc2626
                );
        }

        .auth-banner h1 {
            max-width: 400px;

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 42px;

            line-height: 1.15;

            margin-top: 80px;
        }

        .auth-banner h1 span {
            color: #fca5a5;
        }

        .auth-banner p {
            max-width: 390px;

            color: #fecaca;

            font-size: 14px;

            margin-top: 18px;
        }

        .auth-features {
            display: grid;

            gap: 12px;
        }

        .auth-feature {
            display: flex;
            align-items: center;

            gap: 12px;

            color: #fee2e2;

            font-size: 13px;
        }

        .auth-check {
            width: 28px;
            height: 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                rgba(255,255,255,.12);

            border-radius: 8px;
        }

        .auth-form {
            padding: 50px;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-logo {
            margin-bottom: 35px;
        }

        .auth-form h2 {
            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 28px;

            margin-bottom: 7px;
        }

        .auth-subtitle {
            color: #78837d;

            font-size: 13px;

            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;

            font-size: 12px;
            font-weight: 700;

            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {

            width: 100%;

            padding: 13px 14px;

            border: 1px solid #e1e7e3;

            border-radius: 10px;

            outline: none;

            background: #fbfcfb;

            font-size: 13px;

            transition: .2s;

        }

        .form-group input:focus,
        .form-group select:focus {

            border-color:
                #dc2626;

            background: white;

            box-shadow:
                0 0 0 3px
                rgba(220,38,38,.1);

        }

        .role-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

        }

        .role-option {

            position: relative;
        }

        .role-option input {
            position: absolute;

            opacity: 0;
        }

        .role-option label {

            display: block;

            padding: 13px;

            text-align: center;

            border: 1px solid #e1e7e3;

            border-radius: 10px;

            cursor: pointer;

            font-size: 12px;

            transition: .2s;

        }

        .role-option input:checked + label {

            color: #dc2626;

            background: #fee2e2;

            border-color: #dc2626;

        }

        .auth-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 11px;

            color: white;

            background: #dc2626;

            cursor: pointer;

            font-size: 13px;

            font-weight: 700;

            margin-top: 8px;

            transition: .25s;

        }

        .auth-button:hover {

            background: #991b1b;

            transform: translateY(-2px);

        }

        .alert {

            padding: 11px 13px;

            border-radius: 9px;

            font-size: 12px;

            margin-bottom: 18px;

        }

        .alert-error {

            color: #a22d3e;

            background: #fff0f2;

        }

        .alert-success {

            color: #991b1b;

            background: #fee2e2;

        }

        .auth-footer {

            text-align: center;

            color: #78837d;

            font-size: 12px;

            margin-top: 22px;

        }

        .auth-footer a {

            color: #dc2626;

            font-weight: 700;

        }

        @media (max-width: 750px) {

            .auth-wrapper {

                grid-template-columns: 1fr;
            }

            .auth-banner {

                display: none;
            }

            .auth-form {

                padding: 35px 25px;
            }

        }

    </style>

</head>

<body>

<div class="auth-wrapper">

    <div class="auth-banner">

        <div>

            <div class="logo">

                <div class="logo-icon">
                    N
                </div>

                <span>
                    Niagora
                </span>

            </div>

            <h1>

                Mulai perjalanan
                <span class="gradient-text">usahamu.</span>

            </h1>


            <p>

                Bergabung dengan Niagora dan
                kelola usaha sekaligus menjangkau
                lebih banyak pembeli secara digital.

            </p>

        </div>


        <div class="auth-features">

            <div class="auth-feature">

                <div class="auth-check">
                    ✓
                </div>

                Kelola produk dan stok

            </div>


            <div class="auth-feature">

                <div class="auth-check">
                    ✓
                </div>

                Terima pesanan online

            </div>


            <div class="auth-feature">

                <div class="auth-check">
                    ✓
                </div>

                Pantau penjualan

            </div>

        </div>

    </div>


    <div class="auth-form">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;color:#716668;font-size:12px;font-weight:700;text-decoration:none;padding:6px 12px;background:#faf5f5;border:1px solid #f2e6e6;border-radius:8px;transition:.2s;">
                ← Kembali ke Beranda
            </a>
            <button class="theme-toggle" type="button" title="Ganti mode siang/malam">🌙</button>
        </div>

        <div class="auth-logo">

            <div class="logo">

                <div class="logo-icon">
                    N
                </div>

                <span>
                    Niagora
                </span>

            </div>

        </div>


        <h2>
            Buat akun baru
        </h2>

        <p class="auth-subtitle">
            Daftar dan mulai menggunakan Niagora.
        </p>


        <?php if ($error): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >

            <div class="form-group">

                <label>
                    Nama lengkap
                </label>

                <input
                    type="text"
                    name="nama"
                    placeholder="Masukkan nama kamu"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="nama@email.com"
                    required
                >

            </div>


            <div class="form-group">

                <label id="waLabel">
                    Nomor WhatsApp (Aktif)
                </label>

                <input
                    type="text"
                    name="no_wa"
                    id="no_wa"
                    placeholder="Contoh: 081234567890"
                    required
                >
                <small style="display:block;color:#78837d;font-size:11px;margin-top:4px;" id="waHint">
                    Nomor WhatsApp memudahkan pembeli menghubungi penjual mengenai produk & pesanan.
                </small>

            </div>


            <div class="form-group">

                <label>
                    Daftar sebagai
                </label>


                <div class="role-grid">

                    <div class="role-option">

                        <input
                            type="radio"
                            name="role"
                            value="pembeli"
                            id="pembeli"
                            checked
                        >

                        <label for="pembeli">
                            🛍️ Pembeli
                        </label>

                    </div>


                    <div class="role-option">

                        <input
                            type="radio"
                            name="role"
                            value="penjual"
                            id="penjual"
                        >

                        <label for="penjual">
                            🏪 Penjual
                        </label>

                    </div>

                </div>

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Minimal 6 karakter"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Konfirmasi password
                </label>

                <input
                    type="password"
                    name="konfirmasi"
                    placeholder="Ulangi password"
                    required
                >

            </div>


            <button
                type="submit"
                name="register"
                class="auth-button btn-shimmer"
            >

                Buat akun

            </button>

        </form>


        <div class="auth-footer">

            Sudah punya akun?

            <a href="login.php">
                Masuk sekarang
            </a>

            <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #f2e6e6;">
                <a href="../index.php" style="color:#716668;font-weight:600;font-size:12px;text-decoration:none;">
                    🏠 Kembali ke Halaman Utama
                </a>
            </div>

        </div>

    </div>

</div>

</body>

</html>
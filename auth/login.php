<?php

session_start();

require_once "../config/database.php";

$error = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {

        $error = "Email dan password wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);

        $user =
            mysqli_fetch_assoc($result);


        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {

            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'penjual') {

                header(
                    "Location: ../admin/dashboard.php"
                );

            } else {

                header(
                    "Location: ../pembeli/dashboard.php"
                );

            }

            exit;

        } else {

            $error =
                "Email atau password salah.";

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

    <title>Masuk — Niagora</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            background:

                radial-gradient(
                    circle at top left,
                    #fee2e2,
                    transparent 35%
                ),

                #fdf8f8;

        }


        .login-wrapper {

            width: min(
                900px,
                calc(100% - 30px)
            );

            display: grid;

            grid-template-columns:
                .9fr 1.1fr;

            overflow: hidden;

            background: white;

            border-radius: 28px;

            box-shadow:
                0 25px 80px
                rgba(60,20,25,.12);

        }


        .login-visual {

            min-height: 580px;

            padding: 45px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            color: white;

            background:
                linear-gradient(
                    145deg,
                    #7f1d1d,
                    #dc2626
                );

        }


        .login-visual h1 {

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 38px;

            line-height: 1.2;

            margin-top: 70px;

        }


        .login-visual h1 span {

            color: #fca5a5;

        }


        .login-visual p {

            color: #fecaca;

            font-size: 13px;

            margin-top: 18px;

        }


        .login-icon {

            margin-top: 40px;

            font-size: 100px;

            opacity: .9;

        }


        .login-form {

            padding: 50px;

            display: flex;

            flex-direction: column;

            justify-content: center;

        }


        .login-form h2 {

            font-family:
                "Plus Jakarta Sans",
                sans-serif;

            font-size: 28px;

            margin-top: 35px;

        }


        .login-form > p {

            color: #78837d;

            font-size: 13px;

            margin: 7px 0 25px;

        }


        .form-group {

            margin-bottom: 18px;

        }


        .form-group label {

            display: block;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 7px;

        }


        .form-group input {

            width: 100%;

            padding: 14px;

            border: 1px solid #e1e7e3;

            border-radius: 10px;

            outline: none;

            background: #fbfcfb;

            font-size: 13px;

        }


        .form-group input:focus {

            border-color: #dc2626;

            background: white;

            box-shadow:
                0 0 0 3px
                rgba(220,38,38,.1);

        }


        .login-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 10px;

            color: white;

            background: #dc2626;

            font-weight: 700;

            cursor: pointer;

            transition: .25s;

        }


        .login-button:hover {

            background: #991b1b;

            transform: translateY(-2px);

        }


        .alert {

            padding: 11px;

            margin-bottom: 17px;

            color: #a22d3e;

            background: #fff0f2;

            border-radius: 9px;

            font-size: 12px;

        }


        .login-footer {

            text-align: center;

            margin-top: 22px;

            color: #78837d;

            font-size: 12px;

        }


        .login-footer a {

            color: #dc2626;

            font-weight: 700;

        }


        @media (max-width: 700px) {

            .login-wrapper {

                grid-template-columns: 1fr;

            }

            .login-visual {

                display: none;

            }

            .login-form {

                padding: 35px 25px;

            }

        }

    </style>

</head>

<body>


<div class="login-wrapper">


    <div class="login-visual">

        <div class="logo">

            <div class="logo-icon">
                N
            </div>

            <span>
                Niagora
            </span>

        </div>

        <h1>

            Selamat datang
            <span class="gradient-text">kembali.</span>

        </h1>


        <p>

            Kelola usaha atau temukan
            kebutuhanmu dengan lebih mudah
            melalui Niagora.

        </p>


        <div class="login-icon">
            🛍️
        </div>

    </div>


    <div class="login-form">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;color:#716668;font-size:12px;font-weight:700;text-decoration:none;padding:6px 12px;background:#faf5f5;border:1px solid #f2e6e6;border-radius:8px;transition:.2s;">
                ← Kembali ke Beranda
            </a>
            <button class="theme-toggle" type="button" title="Ganti mode siang/malam">🌙</button>
        </div>

        <div class="logo">

            <div class="logo-icon">
                N
            </div>

            <span>
                Niagora
            </span>

        </div>


        <h2>
            Masuk ke akun
        </h2>


        <p>
            Masukkan akun Niagora kamu.
        </p>


        <?php if ($error): ?>

            <div class="alert">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
        >


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

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >

            </div>


            <button
                type="submit"
                name="login"
                class="login-button btn-shimmer"
            >

                Masuk
            </button>


        </form>


        <div class="login-footer">

            Belum punya akun?

            <a href="register.php">
                Daftar sekarang
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
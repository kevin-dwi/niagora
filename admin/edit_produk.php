<?php

session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'penjual'
) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


/* Ambil produk */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM produk
     WHERE id = ?
     AND penjual_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id,
    $userId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$produk =
    mysqli_fetch_assoc($result);


if (!$produk) {

    header("Location: produk.php");

    exit;

}


$kategoriQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM kategori
     ORDER BY nama_kategori"
);


$error = "";


/* ========================================
   UPDATE
======================================== */

if (isset($_POST['update'])) {

    $nama =
        trim($_POST['nama_produk']);

    $kategori =
        intval($_POST['kategori_id']);

    $harga =
        floatval($_POST['harga']);

    $stok =
        intval($_POST['stok']);

    $deskripsi =
        trim($_POST['deskripsi']);

    $gambar =
        $produk['gambar'];


    if (empty($nama)) {

        $error =
            "Nama produk wajib diisi.";

    } else {


        /* Upload gambar baru */

        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] === 0
        ) {

            $allowed =
                ['jpg', 'jpeg', 'png', 'webp'];

            $extension =
                strtolower(
                    pathinfo(
                        $_FILES['gambar']['name'],
                        PATHINFO_EXTENSION
                    )
                );


            if (!in_array($extension, $allowed)) {

                $error =
                    "Format gambar tidak didukung.";

            } else {

                $newImage =
                    uniqid("produk_")
                    . "."
                    . $extension;

                $destination =
                    "../assets/img/"
                    . $newImage;


                if (
                    move_uploaded_file(
                        $_FILES['gambar']['tmp_name'],
                        $destination
                    )
                ) {

                    /* Hapus gambar lama */

                    if (!empty($gambar)) {

                        $old =
                            "../assets/img/"
                            . $gambar;

                        if (file_exists($old)) {
                            unlink($old);
                        }

                    }


                    $gambar =
                        $newImage;

                }

            }

        }


        if (empty($error)) {

            $update = mysqli_prepare(
                $conn,
                "UPDATE produk SET
                    kategori_id = ?,
                    nama_produk = ?,
                    deskripsi = ?,
                    harga = ?,
                    stok = ?,
                    gambar = ?
                 WHERE id = ?
                 AND penjual_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "issdisii",
                $kategori,
                $nama,
                $deskripsi,
                $harga,
                $stok,
                $gambar,
                $id,
                $userId
            );


            if (
                mysqli_stmt_execute($update)
            ) {

                header(
                    "Location: produk.php"
                );

                exit;

            } else {

                $error =
                    "Gagal mengubah produk.";

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

    <title>Edit Produk — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
        }

        .edit-page {
            width: min(850px, calc(100% - 40px));
            margin: 50px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #718078;
            font-size: 12px;
        }

        .edit-card {
            padding: 30px;
            background: white;
            border: 1px solid #e6ebe8;
            border-radius: 20px;
        }

        .edit-header {
            margin-bottom: 25px;
        }

        .edit-header h1 {
            font-family:
                "Plus Jakarta Sans",
                sans-serif;
            font-size: 25px;
        }

        .edit-header p {
            color: #7d8982;
            font-size: 12px;
        }

        .current-image {
            width: 180px;
            height: 180px;
            overflow: hidden;
            margin-bottom: 10px;
            background: #fee2e2;
            border-radius: 15px;
        }

        .current-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #e1e7e3;
            border-radius: 10px;
            outline: none;
            background: #fbfcfb;
            font-family: inherit;
            font-size: 12px;
        }

        .form-group textarea {
            height: 110px;
            resize: vertical;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .error {
            padding: 12px;
            margin-bottom: 18px;
            color: #a22d3e;
            background: #fff0f2;
            border-radius: 9px;
            font-size: 12px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #edf1ef;
        }

        .cancel,
        .update {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .cancel {
            color: #68746d;
            border: 1px solid #e1e7e3;
        }

        .update {
            color: white;
            background: #dc2626;
            border: none;
            cursor: pointer;
        }

        .update:hover {
            background: #991b1b;
        }

        @media (max-width: 600px) {

            .edit-page {
                width: calc(100% - 25px);
                margin: 25px auto;
            }

            .two-column {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<div class="edit-page">

    <a
        href="produk.php"
        class="back"
    >
        ← Kembali ke produk
    </a>


    <div class="edit-card">

        <div class="edit-header">

            <h1>
                Edit produk
            </h1>

            <p>
                Perbarui informasi produk kamu.
            </p>

        </div>


        <?php if ($error): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="form-group">

                <label>
                    Gambar saat ini
                </label>

                <?php if (!empty($produk['gambar'])): ?>

                    <div class="current-image">

                        <img
                            src="../assets/img/<?= htmlspecialchars(
                                $produk['gambar']
                            ) ?>"
                        >

                    </div>

                <?php endif; ?>


                <input
                    type="file"
                    name="gambar"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <div class="form-group">

                <label>
                    Nama produk
                </label>

                <input
                    type="text"
                    name="nama_produk"
                    value="<?= htmlspecialchars(
                        $produk['nama_produk']
                    ) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Kategori
                </label>

                <select
                    name="kategori_id"
                    required
                >

                    <?php while (
                        $kategori =
                        mysqli_fetch_assoc(
                            $kategoriQuery
                        )
                    ): ?>

                        <option
                            value="<?= $kategori['id'] ?>"
                            <?= $kategori['id']
                                == $produk['kategori_id']
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars(
                                $kategori['nama_kategori']
                            ) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="two-column">

                <div class="form-group">

                    <label>
                        Harga
                    </label>

                    <input
                        type="number"
                        name="harga"
                        min="0"
                        value="<?= $produk['harga'] ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Stok
                    </label>

                    <input
                        type="number"
                        name="stok"
                        min="0"
                        value="<?= $produk['stok'] ?>"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label>
                    Deskripsi
                </label>

                <textarea
                    name="deskripsi"
                ><?= htmlspecialchars(
                    $produk['deskripsi']
                ) ?></textarea>

            </div>


            <div class="actions">

                <a
                    href="produk.php"
                    class="cancel"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    name="update"
                    class="update"
                >
                    Simpan perubahan
                </button>

            </div>


        </form>

    </div>

</div>


</body>
</html>
<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$error = "";

$kategoriQuery = mysqli_query(
    $conn,
    "SELECT * FROM kategori ORDER BY nama_kategori"
);


if (isset($_POST['simpan'])) {

    $nama = trim($_POST['nama_produk']);
    $kategori = intval($_POST['kategori_id']);
    $harga = floatval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $deskripsi = trim($_POST['deskripsi']);

    $gambar = "";


    if (
        empty($nama) ||
        $harga < 0 ||
        $stok < 0
    ) {

        $error =
            "Data produk belum lengkap.";

    } else {


        /* ================================
           UPLOAD GAMBAR
        ================================= */

        if (
            isset($_FILES['gambar']) &&
            $_FILES['gambar']['error'] === 0
        ) {

            $allowed =
                ['jpg', 'jpeg', 'png', 'webp'];

            $filename =
                $_FILES['gambar']['name'];

            $extension =
                strtolower(
                    pathinfo(
                        $filename,
                        PATHINFO_EXTENSION
                    )
                );


            if (!in_array($extension, $allowed)) {

                $error =
                    "Format gambar harus JPG, JPEG, PNG, atau WEBP.";

            } elseif (
                $_FILES['gambar']['size'] >
                3 * 1024 * 1024
            ) {

                $error =
                    "Ukuran gambar maksimal 3MB.";

            } else {

                $gambar =
                    uniqid("produk_")
                    . "."
                    . $extension;

                $destination =
                    "../assets/img/"
                    . $gambar;


                move_uploaded_file(
                    $_FILES['gambar']['tmp_name'],
                    $destination
                );

            }

        }


        if (empty($error)) {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO produk
                (
                    penjual_id,
                    kategori_id,
                    nama_produk,
                    deskripsi,
                    harga,
                    stok,
                    gambar
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iissdis",
                $userId,
                $kategori,
                $nama,
                $deskripsi,
                $harga,
                $stok,
                $gambar
            );


            if (mysqli_stmt_execute($stmt)) {

                header(
                    "Location: produk.php"
                );

                exit;

            } else {

                $error =
                    "Gagal menyimpan produk.";

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

    <title>Tambah Produk — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
        }

        .form-page {
            width: min(850px, calc(100% - 40px));
            margin: 50px auto;
        }

        .back-link {
            display: inline-block;
            color: #718078;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .form-card {
            background: white;
            border: 1px solid #e6ebe8;
            border-radius: 20px;
            padding: 30px;
        }

        .form-header {
            margin-bottom: 30px;
        }

        .form-header h1 {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 25px;
        }

        .form-header p {
            color: #7d8982;
            font-size: 12px;
            margin-top: 5px;
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }

        .image-upload {
            height: 300px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f4f7f5;
            border: 2px dashed #cbd8d0;
            border-radius: 16px;
            cursor: pointer;
        }

        .image-upload:hover {
            border-color: #dc2626;
            background: #fdf5f5;
        }

        .image-upload-content {
            text-align: center;
            pointer-events: none;
        }

        .upload-icon {
            font-size: 45px;
            margin-bottom: 8px;
        }

        .image-upload strong {
            display: block;
            font-size: 12px;
        }

        .image-upload span {
            color: #8a958f;
            font-size: 10px;
        }

        #preview {
            width: 100%;
            height: 100%;
            display: none;
            object-fit: cover;
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
            height: 105px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #dc2626;
            background: white;
            box-shadow:
                0 0 0 3px
                rgba(220,38,38,.1);
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .error {
            padding: 12px;
            color: #a22d3e;
            background: #fff0f2;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #edf1ef;
        }

        .cancel-button {
            padding: 12px 18px;
            color: #68746d;
            border: 1px solid #e1e7e3;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .save-button {
            padding: 12px 20px;
            color: white;
            background: #dc2626;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .save-button:hover {
            background: #991b1b;
        }

        @media (max-width: 700px) {

            .form-layout {
                grid-template-columns: 1fr;
            }

            .form-page {
                width: min(100% - 25px, 850px);
                margin: 25px auto;
            }

        }

    </style>

</head>

<body>


<div class="form-page">

    <a
        href="produk.php"
        class="back-link"
    >
        ← Kembali ke produk
    </a>


    <div class="form-card">

        <div class="form-header">

            <h1>
                Tambah produk
            </h1>

            <p>
                Tambahkan produk baru ke etalase Niagora.
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

            <div class="form-layout">


                <div>

                    <label
                        for="gambar"
                        class="image-upload"
                    >

                        <div
                            class="image-upload-content"
                            id="uploadContent"
                        >

                            <div class="upload-icon">
                                🖼️
                            </div>

                            <strong>
                                Upload gambar produk
                            </strong>

                            <span>
                                JPG, PNG, WEBP • Maks. 3MB
                            </span>

                        </div>


                        <img
                            id="preview"
                            alt="Preview"
                        >

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            accept=".jpg,.jpeg,.png,.webp"
                            hidden
                        >

                    </label>

                </div>


                <div>


                    <div class="form-group">

                        <label>
                            Nama produk
                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            placeholder="Contoh: Beras Premium 5kg"
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

                            <option value="">
                                Pilih kategori
                            </option>

                            <?php while (
                                $kategori =
                                mysqli_fetch_assoc(
                                    $kategoriQuery
                                )
                            ): ?>

                                <option
                                    value="<?= $kategori['id'] ?>"
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
                                placeholder="0"
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
                                placeholder="0"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label>
                            Deskripsi produk
                        </label>

                        <textarea
                            name="deskripsi"
                            placeholder="Jelaskan produk kamu..."
                        ></textarea>

                    </div>


                </div>

            </div>


            <div class="form-actions">

                <a
                    href="produk.php"
                    class="cancel-button"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    name="simpan"
                    class="save-button"
                >
                    Simpan produk
                </button>

            </div>


        </form>

    </div>

</div>


<script>

const input =
    document.getElementById("gambar");

const preview =
    document.getElementById("preview");

const content =
    document.getElementById("uploadContent");


input.addEventListener(
    "change",
    function () {

        const file = this.files[0];

        if (!file) return;

        const reader =
            new FileReader();

        reader.onload =
            function (event) {

                preview.src =
                    event.target.result;

                preview.style.display =
                    "block";

                content.style.display =
                    "none";

            };

        reader.readAsDataURL(file);

    }
);

</script>


</body>
</html>
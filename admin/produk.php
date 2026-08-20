<?php

session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

$query = mysqli_query(
    $conn,
    "SELECT
        produk.*,
        kategori.nama_kategori
     FROM produk
     LEFT JOIN kategori
        ON produk.kategori_id = kategori.id
     WHERE produk.penjual_id = $userId
     ORDER BY produk.created_at DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Produk — Niagora</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #fdf8f8;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

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

        .sidebar-logo {
            padding: 0 10px 25px;
        }

        .sidebar-menu {
            display: grid;
            gap: 6px;
        }

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

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #dc2626;
            background: #fee2e2;
        }

        .menu-icon {
            width: 25px;
            text-align: center;
            font-size: 16px;
        }

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

        .dashboard-top h1 {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 18px;
        }

        .dashboard-top p {
            color: #8a958f;
            font-size: 11px;
        }

        .dashboard-content {
            padding: 35px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .page-heading h2 {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 25px;
        }

        .page-heading p {
            color: #7a857f;
            font-size: 13px;
            margin-top: 4px;
        }

        .add-button {
            padding: 12px 18px;
            color: white;
            background: #dc2626;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            transition: .2s;
        }

        .add-button:hover {
            background: #991b1b;
            transform: translateY(-2px);
        }

        .product-panel {
            background: white;
            border: 1px solid #e6ebe8;
            border-radius: 18px;
            overflow: hidden;
        }

        .table-top {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #edf1ef;
        }

        .table-top h3 {
            font-size: 14px;
        }

        .search-box {
            width: 240px;
            padding: 10px 13px;
            border: 1px solid #e1e7e3;
            border-radius: 9px;
            outline: none;
            font-size: 12px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table th {
            padding: 14px 20px;
            text-align: left;
            color: #8b968f;
            background: #fafbfa;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .product-table td {
            padding: 15px 20px;
            border-top: 1px solid #f0f2f1;
            font-size: 12px;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-thumb {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fee2e2;
            border-radius: 10px;
            font-size: 22px;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info strong {
            display: block;
            font-size: 12px;
        }

        .product-info span {
            color: #8b968f;
            font-size: 10px;
        }

        .category-badge {
            padding: 5px 9px;
            color: #dc2626;
            background: #fee2e2;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .stock {
            font-weight: 700;
        }

        .stock.low {
            color: #d78a24;
        }

        .stock.safe {
            color: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
        }

        .action-button {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 13px;
        }

        .edit-button {
            color: #2779a8;
            background: #eaf5fb;
        }

        .delete-button {
            color: #c94e5c;
            background: #fff0f2;
        }

        .empty-product {
            padding: 70px 20px;
            text-align: center;
        }

        .empty-product .icon {
            font-size: 50px;
            margin-bottom: 12px;
        }

        .empty-product h3 {
            font-size: 15px;
            margin-bottom: 5px;
        }

        .empty-product p {
            color: #8b968f;
            font-size: 12px;
        }

        @media (max-width: 800px) {

            .sidebar {
                display: none;
            }

            .dashboard-main {
                width: 100%;
                margin-left: 0;
            }

            .dashboard-content {
                padding: 20px;
            }

            .table-top {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .search-box {
                width: 100%;
            }

            .product-panel {
                overflow-x: auto;
            }

            .product-table {
                min-width: 750px;
            }

        }

    </style>

</head>

<body>

<div class="dashboard">

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
            <a href="produk.php" class="active"><span class="menu-icon">📦</span>Produk</a>
            <a href="pesanan.php"><span class="menu-icon">🛒</span>Pesanan Masuk</a>
            <a href="ulasan.php"><span class="menu-icon">⭐</span>Rating & Ulasan</a>
            <a href="masukan.php"><span class="menu-icon">✉️</span>Masukan Pembeli</a>
            <div class="menu-title">Pengaturan</div>
            <a href="profil.php"><span class="menu-icon">👤</span>Profil & No. WhatsApp</a>
        </div>

        <div class="sidebar-bottom">

            <div class="profile-mini">

                <div class="profile-avatar">
                    <?= strtoupper(
                        substr($_SESSION['nama'], 0, 1)
                    ) ?>
                </div>

                <div>

                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION['nama']
                        ) ?>
                    </strong>

                    <span>Penjual</span>

                </div>

            </div>

            <a
                href="../auth/logout.php"
                style="
                    display:block;
                    text-align:center;
                    color:#d45555;
                    font-size:11px;
                    margin-top:12px;
                "
            >
                Keluar
            </a>

        </div>

    </aside>


    <main class="dashboard-main">

        <header class="dashboard-top">

            <div>

                <h1>
                    Produk
                </h1>

                <p>
                    Kelola semua produk yang kamu jual.
                </p>

            </div>

        </header>


        <div class="dashboard-content">

            <div class="page-heading">

                <div>

                    <h2>
                        Produk kamu
                    </h2>

                    <p>
                        Tambahkan dan kelola produk
                        untuk ditampilkan di etalase.
                    </p>

                </div>

                <a
                    href="tambah_produk.php"
                    class="add-button"
                >
                    + Tambah produk
                </a>

            </div>


            <div class="product-panel">

                <div class="table-top">

                    <h3>
                        Daftar produk
                    </h3>

                    <input
                        type="text"
                        id="searchProduct"
                        class="search-box"
                        placeholder="🔍 Cari produk..."
                    >

                </div>


                <?php if (mysqli_num_rows($query) > 0): ?>

                    <table
                        class="product-table"
                        id="productTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Produk
                                </th>

                                <th>
                                    Kategori
                                </th>

                                <th>
                                    Rating
                                </th>

                                <th>
                                    Harga
                                </th>

                                <th>
                                    Stok
                                </th>

                                <th>
                                    Terjual
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while (
                                $item =
                                mysqli_fetch_assoc($query)
                            ): ?>

                                <tr>

                                    <td>

                                        <div class="product-info">

                                            <div class="product-thumb">

                                                <?php if (
                                                    !empty(
                                                        $item['gambar']
                                                    )
                                                ): ?>

                                                    <img
                                                        src="../assets/img/<?= htmlspecialchars(
                                                            $item['gambar']
                                                        ) ?>"
                                                    >

                                                <?php else: ?>

                                                    📦

                                                <?php endif; ?>

                                            </div>

                                            <div>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $item['nama_produk']
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    ID #<?= $item['id'] ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <span
                                            class="category-badge"
                                        >

                                            <?= htmlspecialchars(
                                                $item['nama_kategori']
                                                ?? 'Tanpa kategori'
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>
                                        <?php
                                        $pRating = get_product_rating($conn, $item['id']);
                                        echo render_rating_stars($pRating['rating'], $pRating['count'], 'sm');
                                        ?>
                                    </td>


                                    <td>

                                        <strong>

                                            Rp <?= number_format(
                                                $item['harga'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <span
                                            class="stock
                                            <?= $item['stok'] <= 5
                                                ? 'low'
                                                : 'safe' ?>"
                                        >

                                            <?= $item['stok'] ?>

                                            <?= $item['stok'] <= 5
                                                ? ' • menipis'
                                                : '' ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= $item['terjual'] ?>

                                    </td>


                                    <td>

                                        <div
                                            class="action-buttons"
                                        >

                                            <a
                                                href="edit_produk.php?id=<?= $item['id'] ?>"
                                                class="action-button edit-button"
                                                title="Edit"
                                            >
                                                ✏️
                                            </a>

                                            <a
                                                href="hapus_produk.php?id=<?= $item['id'] ?>"
                                                class="action-button delete-button"
                                                title="Hapus"
                                                onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                            >
                                                🗑️
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty-product">

                        <div class="icon">
                            📦
                        </div>

                        <h3>
                            Belum ada produk
                        </h3>

                        <p>
                            Tambahkan produk pertamamu
                            untuk mulai berjualan.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

</div>


<script>

const search =
    document.getElementById("searchProduct");

if (search) {

    search.addEventListener("input", function () {

        const keyword =
            this.value.toLowerCase();

        const rows =
            document.querySelectorAll(
                "#productTable tbody tr"
            );

        rows.forEach(function (row) {

            const text =
                row.innerText.toLowerCase();

            row.style.display =
                text.includes(keyword)
                    ? ""
                    : "none";

        });

    });

}

</script>

</body>

</html>
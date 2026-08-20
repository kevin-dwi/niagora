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


/* Cari produk milik penjual */

$stmt = mysqli_prepare(
    $conn,
    "SELECT gambar
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


if ($produk) {

    /* Hapus file gambar */

    if (!empty($produk['gambar'])) {

        $file =
            "../assets/img/"
            . $produk['gambar'];

        if (file_exists($file)) {
            unlink($file);
        }

    }


    /* Hapus database */

    $delete = mysqli_prepare(
        $conn,
        "DELETE FROM produk
         WHERE id = ?
         AND penjual_id = ?"
    );

    mysqli_stmt_bind_param(
        $delete,
        "ii",
        $id,
        $userId
    );

    mysqli_stmt_execute($delete);

}

header("Location: produk.php");

exit;
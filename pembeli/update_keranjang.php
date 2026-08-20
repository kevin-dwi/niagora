<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$aksi = trim($_GET['aksi'] ?? '');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($aksi === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: keranjang.php");
    exit;
}

if ($id > 0) {
    $query = mysqli_query($conn, "SELECT stok FROM produk WHERE id = $id");
    $product = mysqli_fetch_assoc($query);

    if (!$product) {
        unset($_SESSION['cart'][$id]);
        header("Location: keranjang.php");
        exit;
    }

    if ($aksi === 'tambah') {
        $current = $_SESSION['cart'][$id] ?? 0;
        if ($current < $product['stok']) {
            $_SESSION['cart'][$id] = $current + 1;
        }
    } elseif ($aksi === 'kurang') {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]--;
            if ($_SESSION['cart'][$id] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['cart'][$id]);
    }
}

header("Location: keranjang.php");
exit;

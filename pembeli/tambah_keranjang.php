<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$redirectTo = trim($_GET['redirect'] ?? 'dashboard');

$query = mysqli_query(
    $conn,
    "SELECT id, penjual_id, stok, nama_produk
     FROM produk
     WHERE id = $id
     AND stok > 0"
);

$product = mysqli_fetch_assoc($query);

if (!$product) {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek batas stok
$currentQty = $_SESSION['cart'][$id] ?? 0;
if ($currentQty < $product['stok']) {
    $_SESSION['cart'][$id] = $currentQty + 1;
}

if ($redirectTo === 'keranjang') {
    header("Location: keranjang.php");
} elseif ($redirectTo === 'index') {
    header("Location: ../index.php?added=1#produk");
} else {
    header("Location: dashboard.php?added=1");
}
exit;

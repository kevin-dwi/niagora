<?php
session_start();
require_once "config/database.php";

$isLoggedIn = isset($_SESSION['login']) && $_SESSION['login'] === true;
$userRole = $_SESSION['role'] ?? '';
$userName = $_SESSION['nama'] ?? '';

// Parameters Filter
$selectedCategory = intval($_GET['kategori'] ?? 0);
$searchKeyword = trim($_GET['search'] ?? '');

// 1. Ambil Kategori Dinamis dari Database beserta Total Produk
$kategoriQuery = mysqli_query($conn, "
    SELECT
        kategori.*,
        COUNT(produk.id) AS total_produk
    FROM kategori
    LEFT JOIN produk ON kategori.id = produk.kategori_id AND produk.stok > 0
    GROUP BY kategori.id
    ORDER BY kategori.id ASC
");

$categoriesList = [];
$activeCategoryName = '';
while ($k = mysqli_fetch_assoc($kategoriQuery)) {
    $categoriesList[] = $k;
    if ($selectedCategory > 0 && $k['id'] == $selectedCategory) {
        $activeCategoryName = $k['nama_kategori'];
    }
}

// 2. Query Produk sesuai Kategori & Pencarian
$sql = "
    SELECT
        produk.*,
        kategori.nama_kategori,
        kategori.icon AS kategori_icon
    FROM produk
    LEFT JOIN kategori
        ON produk.kategori_id = kategori.id
    WHERE produk.stok > 0
";

if ($selectedCategory > 0) {
    $sql .= " AND produk.kategori_id = $selectedCategory ";
}

if (!empty($searchKeyword)) {
    $safeKeyword = mysqli_real_escape_string($conn, $searchKeyword);
    $sql .= " AND (produk.nama_produk LIKE '%$safeKeyword%' OR produk.deskripsi LIKE '%$safeKeyword%') ";
}

$sql .= " ORDER BY produk.terjual DESC, produk.created_at DESC ";

if ($selectedCategory == 0 && empty($searchKeyword)) {
    $sql .= " LIMIT 12 ";
}

$query = mysqli_query($conn, $sql);

$produk = [];
while ($row = mysqli_fetch_assoc($query)) {
    $produk[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Niagora — Niaga Gerbang Online Rakyat</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

<!-- ================= NAVBAR ================= -->
<header class="navbar">
    <div class="nav-container">
        <a href="index.php" class="logo">
            <div class="logo-icon">N</div>
            <span>Niagora</span>
        </a>

        <nav class="nav-menu">
            <a href="index.php" class="active">Beranda</a>
            <a href="#produk">Produk</a>
            <a href="#kategori">Kategori</a>
            <a href="#tentang">Tentang</a>
        </nav>

        <div class="nav-actions">
            <button class="theme-toggle" type="button" title="Ganti mode siang/malam">🌙</button>

            <?php if ($isLoggedIn): ?>
                <?php if ($userRole === 'penjual'): ?>
                    <a href="admin/dashboard.php" class="btn-register">
                        ▦ Dashboard Penjual (<?= htmlspecialchars($userName) ?>)
                    </a>
                <?php else: ?>
                    <a href="pembeli/dashboard.php" class="btn-register">
                        🛍️ Etalase Pembeli (<?= htmlspecialchars($userName) ?>)
                    </a>
                <?php endif; ?>
                <a href="auth/logout.php" class="btn-login btn-logout">
                    Keluar
                </a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-login">Masuk</a>
                <a href="auth/register.php" class="btn-register">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>


<!-- ================= HERO ================= -->
<section class="hero">
    <!-- Decorative 3D animated background -->
    <div class="hero-bg-deco">
        <div class="grid-pattern"></div>
        <div class="ring-3d ring-1" data-depth="6"></div>
        <div class="ring-3d ring-2" data-depth="-4"></div>
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
    </div>

    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                Platform ekonomi digital lokal
            </div>

            <h1>
                Belanja mudah,
                <span class="gradient-text">dukung usaha lokal.</span>
            </h1>

            <p>
                Temukan berbagai kebutuhan dari usaha
                lokal di sekitarmu. Lebih mudah untuk
                pembeli, lebih berkembang untuk penjual.
            </p>

            <form action="index.php#produk" method="GET" class="hero-search">
                <div class="search-icon">🔍</div>
                <input type="text" name="search" id="searchProduct" placeholder="Cari produk yang kamu butuhkan..." value="<?= htmlspecialchars($searchKeyword) ?>">
                <?php if ($selectedCategory > 0): ?>
                    <input type="hidden" name="kategori" value="<?= $selectedCategory ?>">
                <?php endif; ?>
                <button type="submit">Cari</button>
            </form>

            <div class="hero-info">
                <div class="info-item">
                    <strong data-count="100" data-suffix="+">100+</strong>
                    <span>Produk lokal</span>
                </div>
                <div class="info-divider"></div>
                <div class="info-item">
                    <strong data-count="50" data-suffix="+">50+</strong>
                    <span>Pelaku usaha</span>
                </div>
                <div class="info-divider"></div>
                <div class="info-item">
                    <strong>Mudah</strong>
                    <span>Digunakan</span>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <!-- 3D Rotating cube -->
            <div class="cube-3d">
                <div class="cube-face cube-front">🛍️</div>
                <div class="cube-face cube-back">🏪</div>
                <div class="cube-face cube-right">📦</div>
                <div class="cube-face cube-left">💰</div>
                <div class="cube-face cube-top">⭐</div>
                <div class="cube-face cube-bottom">🚀</div>
            </div>

            <!-- Main hero card with new arrangement -->
            <div class="hero-card main-card tilt-3d" data-depth="10">
                <div class="floating-label">Produk populer</div>
                <div class="hero-product-image">🛍️</div>
                <div class="hero-product-info">
                    <div>
                        <span class="product-category">Pilihan hari ini</span>
                        <h3>Belanja kebutuhanmu</h3>
                    </div>
                    <div class="hero-arrow">→</div>
                </div>
            </div>

            <!-- Floating cards around -->
            <div class="floating-card card-one tilt-3d" data-depth="-18">
                <div class="floating-icon">✓</div>
                <div>
                    <strong>Pesanan selesai</strong>
                    <span>Baru saja</span>
                </div>
            </div>

            <div class="floating-card card-two tilt-3d" data-depth="18">
                <div class="floating-icon cart">🛒</div>
                <div>
                    <strong>+3 produk</strong>
                    <span>Dalam keranjang</span>
                </div>
            </div>

            <div class="floating-card card-three tilt-3d" data-depth="-12">
                <div class="floating-icon star">⭐</div>
                <div>
                    <strong>4.9 rating</strong>
                    <span>Dari 200+ ulasan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="scroll-indicator">
        <span>Scroll</span>
        <div class="mouse"><div class="wheel"></div></div>
    </div>
</section>


<!-- ================= CATEGORY ================= -->
<section class="category-section" id="kategori">
    <div class="section-container">
        <div class="section-heading reveal">
            <div>
                <span class="section-label">Jelajahi</span>
                <h2>Cari berdasarkan kategori</h2>
            </div>
            <?php if ($selectedCategory > 0 || !empty($searchKeyword)): ?>
                <a href="index.php#produk" style="color:#dc2626;font-weight:700;">✕ Tampilkan Semua Kategori</a>
            <?php else: ?>
                <a href="#produk">Lihat semua →</a>
            <?php endif; ?>
        </div>

        <div class="category-grid">
            <?php foreach ($categoriesList as $kat):
                $isActive = ($selectedCategory == $kat['id']);
                $icon = !empty($kat['icon']) ? $kat['icon'] : '📦';
                $slug = !empty($kat['slug']) ? $kat['slug'] : 'makanan';
            ?>
                <a
                    href="index.php?kategori=<?= $kat['id'] ?>#produk"
                    class="category-card tilt-3d reveal <?= $isActive ? 'active' : '' ?>"
                    data-kategori-id="<?= $kat['id'] ?>"
                    style="<?= $isActive ? 'border-color:#dc2626;background:#fef2f2;transform:translateY(-6px);box-shadow:var(--shadow);' : '' ?>"
                >
                    <div class="category-icon <?= htmlspecialchars($slug) ?>" style="<?= $isActive ? 'background:#fee2e2;' : '' ?>">
                        <?= $icon ?>
                    </div>
                    <span><?= htmlspecialchars($kat['nama_kategori']) ?></span>
                    <small><?= $kat['total_produk'] ?> produk</small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ================= PRODUCTS ================= -->
<section class="products-section" id="produk">
    <div class="section-container">
        <div class="section-heading reveal" style="align-items:center;flex-wrap:wrap;gap:15px;">
            <div>
                <span class="section-label">
                    <?= $selectedCategory > 0 ? 'Kategori Terpilih' : (!empty($searchKeyword) ? 'Hasil Pencarian' : 'Pilihan terbaik') ?>
                </span>
                <h2>
                    <?php if ($selectedCategory > 0): ?>
                        Kategori: <span class="gradient-text"><?= htmlspecialchars($activeCategoryName) ?></span>
                    <?php elseif (!empty($searchKeyword)): ?>
                        Pencarian: "<span class="gradient-text"><?= htmlspecialchars($searchKeyword) ?></span>"
                    <?php else: ?>
                        Produk populer
                    <?php endif; ?>
                </h2>
                <small style="color:var(--text-muted);font-size:12px;display:block;margin-top:2px;">
                    Menampilkan <?= count($produk) ?> produk siap dipesan
                </small>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <?php if ($selectedCategory > 0 || !empty($searchKeyword)): ?>
                    <a href="index.php#produk" style="padding:8px 16px;background:#fee2e2;color:#dc2626;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;transition:.2s;">
                        ✕ Reset Filter
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn && $userRole === 'pembeli'): ?>
                    <a href="pembeli/dashboard.php<?= $selectedCategory > 0 ? '?kategori=' . $selectedCategory : '' ?>" class="btn-register" style="padding:9px 18px;font-size:12px;">
                        🛍️ Buka di Etalase Lengkap →
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-grid" id="productGrid">
            <?php if (count($produk) > 0): ?>
                <?php foreach ($produk as $item): ?>
                    <div class="product-card tilt-3d reveal" data-name="<?= strtolower(htmlspecialchars($item['nama_produk'])) ?>" data-kategori-id="<?= $item['kategori_id'] ?>">
                        <div class="product-image">
                            <?php if (!empty($item['gambar'])): ?>
                                <img src="assets/img/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                            <?php else: ?>
                                <div class="no-image">📦</div>
                            <?php endif; ?>
                            <button class="wishlist">♡</button>
                        </div>
                        <div class="product-content">
                            <div class="product-top-row">
                                <a href="index.php?kategori=<?= $item['kategori_id'] ?>#produk" class="product-category" style="text-decoration:none;">
                                    <?= htmlspecialchars($item['nama_kategori'] ?? 'Produk') ?>
                                </a>
                                <?php
                                $pRating = get_product_rating($conn, $item['id']);
                                echo render_rating_stars($pRating['rating'], $pRating['count'], 'sm');
                                ?>
                            </div>
                            <h3><?= htmlspecialchars($item['nama_produk']) ?></h3>
                            <div class="product-bottom">
                                <div>
                                    <strong>Rp <?= number_format($item['harga'], 0, ',', '.') ?></strong>
                                    <span><?= $item['stok'] ?> stok</span>
                                </div>
                                <?php if ($isLoggedIn && $userRole === 'pembeli'): ?>
                                    <a href="pembeli/tambah_keranjang.php?id=<?= $item['id'] ?>&redirect=index" class="add-cart" title="Tambah ke keranjang">+</a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="add-cart" title="Masuk untuk membeli">+</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; background:white; border:1px solid var(--border); border-radius:20px; padding:60px 20px; text-align:center;">
                    <div style="font-size:45px; margin-bottom:10px;">📦</div>
                    <h3 style="font-size:18px; margin-bottom:6px;">Tidak Ada Produk di Kategori Ini</h3>
                    <p style="color:var(--text-muted); font-size:13px; max-width:380px; margin:0 auto 18px;">
                        Belum ada produk yang terdaftar untuk kategori <strong><?= htmlspecialchars($activeCategoryName ?: 'ini') ?></strong>.
                    </p>
                    <a href="index.php#produk" style="display:inline-block; padding:10px 20px; background:#dc2626; color:white; border-radius:10px; font-weight:700; font-size:13px; text-decoration:none;">
                        Lihat Semua Produk
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ================= BUSINESS CTA ================= -->
<section class="business-section" id="tentang">
    <div class="section-container">
        <div class="business-box reveal">
            <div class="business-content">
                <span class="section-label light">Punya usaha?</span>
                <h2>Kembangkan usahamu bersama <span class="gradient-text">Niagora</span>.</h2>
                <p>Kelola produk, stok, pesanan, dan penjualan dari satu tempat. Buat usahamu lebih mudah ditemukan oleh masyarakat.</p>
                <a href="auth/register.php" class="business-button">Mulai berjualan →</a>
            </div>
            <div class="business-decoration">
                <div class="circle circle-one"></div>
                <div class="circle circle-two"></div>
                <div class="business-icon">📈</div>
            </div>
        </div>
    </div>
</section>


<!-- ================= FOOTER ================= -->
<footer class="footer">
    <div class="section-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="logo footer-logo">
                    <div class="logo-icon">N</div>
                    <span>Niagora</span>
                </div>
                <p>Etalase digital untuk menghubungkan usaha lokal dengan masyarakat.</p>
            </div>
            <div>
                <h4>Niagora</h4>
                <a href="#">Tentang kami</a>
                <a href="#">Cara kerja</a>
                <a href="#">Kontak</a>
            </div>
            <div>
                <h4>Bantuan</h4>
                <a href="#">FAQ</a>
                <a href="#">Panduan</a>
                <a href="#">Kebijakan</a>
            </div>
            <div>
                <h4>Ikuti kami</h4>
                <div class="socials">
                    <a href="#">IG</a>
                    <a href="#">FB</a>
                    <a href="#">WA</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Niagora — Niaga Gerbang Online Rakyat.</span>
            <span>Etalase & Ekonomi Digital</span>
        </div>
    </div>
</footer>

<?php if (isset($_GET['added'])): ?>
    <div class="toast-notify" id="toastMsg" style="position:fixed;bottom:25px;right:25px;background:#7f1d1d;color:white;padding:14px 22px;border-radius:12px;box-shadow:0 15px 35px rgba(0,0,0,0.2);font-size:13px;font-weight:600;z-index:1000;display:flex;align-items:center;gap:10px;">
        🛒 Produk berhasil dimasukkan ke keranjang belanja!
    </div>
    <script>
        setTimeout(function(){
            const t = document.getElementById('toastMsg');
            if (t) t.style.display = 'none';
        }, 3500);
    </script>
<?php endif; ?>

<!-- Custom cursor -->
<div class="cursor-dot" id="cursorDot"></div>
<div class="cursor-ring" id="cursorRing"></div>

<script src="assets/js/script.js"></script>
</body>
</html>
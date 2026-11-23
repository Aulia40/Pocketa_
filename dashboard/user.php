<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["role"] !== "user") {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];
$inisial = strtoupper(substr($nama, 0, 1));

// Ambil 4 produk terbaru untuk katalog pilihan
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
$latest_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function rupiah($harga) {
    return "Rp" . number_format((float)$harga, 0, ",", ".");
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>POCKETA | My Dashboard</title>

<link rel="stylesheet" href="../style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="/POCKETA/assets/css/user-navbar.css?v=2">
<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    display: block;
    min-height: 100vh;
    padding: 0;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    font-family: 'DM Sans', sans-serif;
    color: #5C4033;
}
/* MAIN */

.container {
    width: 88%;
    max-width: 1150px;
    margin: 45px auto;
}

/* BANNER SLIDER */

.slider-container {
    position: relative;
    background: #FFF9F6;
    border-radius: 25px;
    box-shadow: 0 12px 35px rgba(92,64,51,.08);
    overflow: hidden;
    margin-bottom: 35px;
}

.slide {
    display: none;
    padding: 42px;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    position: relative;
    animation: fadeIn 0.6s ease-in-out;
}

.slide.active {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}

.slide::after {
    content: "♡";
    position: absolute;
    right: 45px;
    top: -45px;
    font-size: 190px;
    color: rgba(216,154,154,.13);
    z-index: 0;
}

.slide-content {
    position: relative;
    z-index: 1;
}

.slide-label {
    display: inline-block;
    background: #FBE9E7;
    color: #B47C7C;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 17px;
}

.slide h1 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    margin-bottom: 12px;
    color: #5C4033;
}

.slide p {
    font-size: 14px;
    line-height: 1.8;
    color: #927B6E;
    max-width: 480px;
    margin-bottom: 20px;
}

.slide-btn {
    display: inline-block;
    background: #5C4033;
    color: white;
    text-decoration: none;
    padding: 10px 22px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    transition: .3s;
}

.slide-btn:hover {
    background: #D89A9A;
}

.slide-icon {
    position: relative;
    z-index: 1;
    width: 115px;
    height: 115px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F4C7C3, #EAD6CC);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    flex-shrink: 0;
    color: #5C4033;
}

/* Kontrol Slider */
.slider-controls {
    position: absolute;
    bottom: 15px;
    right: 35px;
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10;
}

.slider-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #EAD6CC;
    cursor: pointer;
    transition: 0.3s;
}

.slider-dot.active {
    background: #5C4033;
    width: 24px;
    border-radius: 10px;
}

.slider-arrow {
    background: rgba(92,64,51,0.08);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    color: #5C4033;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
}

.slider-arrow:hover {
    background: #5C4033;
    color: white;
}

/* SECTION TITLE */

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    margin-bottom: 20px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    margin-bottom: 5px;
}

.section-heading p {
    font-size: 12px;
    color: #927B6E;
}

.view-all {
    font-size: 12px;
    font-weight: 700;
    color: #B47C7C;
    text-decoration: none;
    transition: .3s;
}

.view-all:hover {
    color: #5C4033;
}

/* MENU CARDS */

.menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.menu-card {
    background: #FFF9F6;
    padding: 28px;
    border-radius: 22px;
    text-decoration: none;
    color: #5C4033;
    border: 1px solid rgba(255,255,255,.8);
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
    transition: .3s ease;
    position: relative;
    overflow: hidden;
}

.menu-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 35px rgba(92,64,51,.13);
    border-color: #D89A9A;
}

.menu-icon {
    width: 58px;
    height: 58px;
    background: #FBE9E7;
    border-radius: 17px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 23px;
    color: #B47C7C;
    margin-bottom: 22px;
}

.menu-card:nth-child(2) .menu-icon {
    background: #F3E8DF;
    color: #99745F;
}

.menu-card:nth-child(3) .menu-icon {
    background: #F8E4E5;
    color: #C17E86;
}

.menu-card h3 {
    font-size: 17px;
    margin-bottom: 9px;
}

.menu-card p {
    color: #927B6E;
    font-size: 12px;
    line-height: 1.7;
    min-height: 40px;
}

.card-arrow {
    display: flex;
    justify-content: flex-end;
    color: #D89A9A;
    margin-top: 20px;
}

/* CATALOG / PRODUCT PREVIEW SECTION */

.catalog-section {
    margin-bottom: 38px;
}

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.product-card {
    background: #FFF9F6;
    border-radius: 20px;
    overflow: hidden;
    text-decoration: none;
    color: #5C4033;
    border: 1px solid rgba(255,255,255,.8);
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
    transition: .3s ease;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 35px rgba(92,64,51,.13);
    border-color: #D89A9A;
}

.product-img {
    width: 100%;
    height: 160px;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #B47C7C;
    font-size: 40px;
    position: relative;
    overflow: hidden;
}

.product-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255, 249, 246, 0.9);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    color: #B47C7C;
    z-index: 2;
}

.product-info {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-info h3 {
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 700;
}

.product-price {
    font-size: 13px;
    color: #C17E86;
    font-weight: 800;
    margin-bottom: 12px;
}

.product-btn {
    margin-top: auto;
    background: #FBE9E7;
    color: #5C4033;
    text-align: center;
    padding: 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    transition: .3s;
}

.product-card:hover .product-btn {
    background: #5C4033;
    color: white;
}

/* ARTICLE / BLOG SECTION */

.article-section {
    margin-top: 38px;
}

.article-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.article-card {
    background: #FFF9F6;
    border-radius: 22px;
    overflow: hidden;
    text-decoration: none;
    color: #5C4033;
    border: 1px solid rgba(255,255,255,.8);
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
    transition: .3s ease;
    display: flex;
    flex-direction: column;
}

.article-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 35px rgba(92,64,51,.13);
    border-color: #D89A9A;
}

.article-img {
    width: 100%;
    height: 150px;
    background: linear-gradient(135deg, #F4C7C3, #D89A9A);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFF9F6;
    font-size: 32px;
    overflow: hidden;
}

.article-content {
    padding: 22px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.article-date {
    font-size: 11px;
    color: #B47C7C;
    font-weight: 700;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.article-card h3 {
    font-size: 15px;
    margin-bottom: 8px;
    line-height: 1.4;
}

.article-card p {
    color: #927B6E;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 15px;
    flex-grow: 1;
}

.article-link {
    font-size: 12px;
    font-weight: 700;
    color: #5C4033;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* ACCOUNT */

.account-section {
    margin-top: 38px;
}

.account-card {
    background: #FFF9F6;
    padding: 30px;
    border-radius: 22px;
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
}

.account-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 23px;
    border-bottom: 1px solid #F0DED7;
    margin-bottom: 23px;
}

.avatar {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #F4C7C3, #D89A9A);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-size: 22px;
    font-weight: 700;
}

.account-header h3 {
    font-size: 16px;
    margin-bottom: 4px;
}

.account-header p {
    font-size: 12px;
    color: #A58B7D;
}

.account-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-item {
    background: #FDF2EF;
    padding: 17px 20px;
    border-radius: 14px;
}

.detail-item span {
    font-size: 11px;
    color: #A58B7D;
    display: block;
    margin-bottom: 7px;
}

.detail-item strong {
    font-size: 13px;
    overflow-wrap: anywhere;
}

/* MODERN FOOTER STYLING */
.site-footer {
    background: #FFF9F6;
    border-radius: 25px 25px 0 0;
    padding: 50px 7% 30px 7%;
    margin-top: 60px;
    box-shadow: 0 -10px 30px rgba(92, 64, 51, 0.04);
    border-top: 1px solid rgba(234, 214, 204, 0.6);
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #F0DED7;
}

.footer-col h3 {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    margin-bottom: 15px;
    color: #5C4033;
}

.footer-col p {
    font-size: 13px;
    line-height: 1.8;
    color: #927B6E;
    margin-bottom: 15px;
}

.footer-col ul {
    list-style: none;
}

.footer-col ul li {
    margin-bottom: 10px;
}

.footer-col ul li a {
    text-decoration: none;
    color: #927B6E;
    font-size: 13px;
    transition: 0.3s;
}

.footer-col ul li a:hover {
    color: #5C4033;
    padding-left: 5px;
}

.footer-socials {
    display: flex;
    gap: 12px;
    margin-top: 15px;
}

.social-icon {
    width: 36px;
    height: 36px;
    background: #FBE9E7;
    color: #B47C7C;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

.social-icon:hover {
    background: #5C4033;
    color: white;
    transform: translateY(-3px);
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #A58B7D;
}

.footer-bottom span {
    color: #C17E86;
}

@media(max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    .footer-bottom {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}

/* RESPONSIVE */

@media(max-width: 950px) {
    .catalog-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 800px) {
    .menu-grid, .article-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .container {
        width: 92%;
    }
}

@media(max-width: 600px) {


    .container {
        margin: 28px auto;
    }

    .slide {
        padding: 28px 23px;
        flex-direction: column;
        text-align: center;
    }

    .slide h1 {
        font-size: 24px;
    }

    .slide p {
        max-width: 100%;
    }

    .slide-icon {
        display: none;
    }

    .slider-controls {
        bottom: 10px;
        right: 50%;
        transform: translateX(50%);
    }

    .menu-grid, .catalog-grid, .article-grid {
        grid-template-columns: 1fr;
    }

    .account-details {
        grid-template-columns: 1fr;
    }

    .section-heading h2 {
        font-size: 22px;
    }
    
}

</style>

</head>

<body>

<!-- NAVBAR -->

<?php
$activePage = "home";
include "../includes/user-navbar.php";
?>

<!-- MAIN CONTENT -->

<main class="container">

    <!-- BANNER SLIDER -->

    <div class="slider-container">

        <!-- Slide 1: Welcome -->
        <div class="slide active">
            <div class="slide-content">
                <span class="slide-label">
                    <i class="fa-solid fa-heart"></i> WELCOME TO POCKETA
                </span>
                <h1>Halo, <?= htmlspecialchars($nama) ?>!</h1>
                <p>
                    Senang melihatmu kembali. Temukan berbagai produk handmade yang unik, 
                    lucu, dan dibuat dengan penuh kreativitas untuk menemani harimu.
                </p>
                <a href="products.php" class="slide-btn">Jelajahi Katalog <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="slide-icon">
                <i class="fa-solid fa-gift"></i>
            </div>
        </div>

        <!-- Slide 2: Promo Diskon -->
        <div class="slide">
            <div class="slide-content">
                <span class="slide-label" style="background: #F8E4E5; color: #C17E86;">
                    <i class="fa-solid fa-tag"></i> PROMO SPESIAL MINGGU INI
                </span>
                <h1>Diskon Spesial 10% untuk Pembelian Pertama!</h1>
                <p>
                    Gunakan kode voucher <strong style="color: #5C4033;">POCKETANEW</strong> saat checkout 
                    dan nikmati potongan harga khusus untuk semua koleksi dompet kain & tassel.
                </p>
                <a href="products.php" class="slide-btn">Gunakan Voucher <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="slide-icon" style="background: linear-gradient(135deg, #F8E4E5, #F4C7C3); color: #C17E86;">
                <i class="fa-solid fa-percent"></i>
            </div>
        </div>

        <!-- Slide 3: Info Custom / Handmade -->
        <div class="slide">
            <div class="slide-content">
                <span class="slide-label" style="background: #F3E8DF; color: #99745F;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> 100% HANDMADE
                </span>
                <h1>Punya Ide Desain atau Request Ukuran Sendiri?</h1>
                <p>
                    POCKETA menerima pesanan khusus sesuai keinginanmu. Hubungi tim admin kami 
                    untuk konsultasi motif kain perca dan model aksesoris pilihanmu.
                </p>
                <a href="profile.php" class="slide-btn">Hubungi Kami <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="slide-icon" style="background: linear-gradient(135deg, #F3E8DF, #EAD6CC); color: #99745F;">
                <i class="fa-solid fa-scissors"></i>
            </div>
        </div>

        <!-- Tombol Kontrol Slider -->
        <div class="slider-controls">
            <button class="slider-arrow" onclick="prevSlide()"><i class="fa-solid fa-chevron-left"></i></button>
            <div style="display: flex; gap: 6px; align-items: center;">
                <span class="slider-dot active" onclick="currentSlide(0)"></span>
                <span class="slider-dot" onclick="currentSlide(1)"></span>
                <span class="slider-dot" onclick="currentSlide(2)"></span>
            </div>
            <button class="slider-arrow" onclick="nextSlide()"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

    </div>


    <!-- SCRIPT JAVASCRIPT UNTUK SLIDER -->
    <script>
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');

        function showSlide(n) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            slideIndex = (n + slides.length) % slides.length;
            
            slides[slideIndex].classList.add('active');
            dots[slideIndex].classList.add('active');
        }

        function nextSlide() {
            showSlide(slideIndex + 1);
        }

        function prevSlide() {
            showSlide(slideIndex - 1);
        }

        function currentSlide(n) {
            showSlide(n);
        }

        // Auto slide setiap 5 detik
        setInterval(() => {
            nextSlide();
        }, 5000);
    </script>


    <!-- KATALOG PRODUK PILIHAN -->

    <section class="catalog-section">

        <div class="section-heading">

            <div>
                <h2>Katalog Produk Pilihan</h2>
                <p>Beberapa koleksi handmade favorit yang siap jadi milikmu.</p>
            </div>

            <a href="products.php" class="view-all">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>

        </div>

        <?php if (count($latest_products) > 0): ?>
            <div class="catalog-grid">
                <?php foreach ($latest_products as $product): ?>
                    <a href="product-detail.php?id=<?= (int)$product["id"] ?>" class="product-card">
                        <div class="product-img">
                            <span class="product-badge"><?= e($product["kategori"] ?? 'Produk') ?></span>
                            <?php if (!empty($product["gambar"])): ?>
                                <img 
                                    src="../images/<?= e(basename($product["gambar"])) ?>" 
                                    alt="<?= e($product["nama_produk"]) ?>"
                                    onerror="this.style.display='none'; this.parentElement.innerHTML='👜';"
                                >
                            <?php else: ?>
                                👜
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?= e($product["nama_produk"]) ?></h3>
                            <div class="product-price"><?= rupiah($product["harga"]) ?></div>
                            <div class="product-btn">Lihat Detail</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: #FFF9F6; border-radius: 20px; color: #927B6E; font-size: 13px;">
                Belum ada produk yang tersedia saat ini.
            </div>
        <?php endif; ?>

    </section>


    <!-- MENU / EXPLORE -->

    <section>

        <div class="section-heading">

            <div>
                <h2>Explore POCKETA</h2>
                <p>Apa yang ingin kamu lakukan hari ini?</p>
            </div>

        </div>


        <div class="menu-grid">

            <a href="products.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>

                <h3>Produk</h3>

                <p>
                    Jelajahi koleksi dompet kain,
                    tassel, dan produk handmade lainnya.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>


            <a href="order-history.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <h3>Pesanan Saya</h3>

                <p>
                    Pantau status pesanan dan lihat
                    riwayat pembelian produkmu.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>


            <a href="profile.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-user-pen"></i>
                </div>

                <h3>Profil</h3>

                <p>
                    Lihat dan kelola informasi
                    akun POCKETA milikmu.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>

        </div>

    </section>


    <!-- ARTIKEL / KABAR RESMI POCKETA -->

    <section class="article-section" id="artikel">

        <div class="section-heading">
            <div>
                <h2>Kabar & Cerita POCKETA</h2>
                <p>Informasi terbaru, tips merawat produk, dan cerita di balik layar koleksi kami.</p>
            </div>
        </div>

       <?php
        $stmtArt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3");
        $db_articles = $stmtArt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="article-grid">
            <?php if (count($db_articles) > 0): ?>
                <?php foreach ($db_articles as $art): ?>
                    <?php 
                        // Cek apakah artikel memiliki link luar khusus atau menggunakan halaman internal
                        $has_external_url = (!empty($art["url"]) && $art["url"] !== "#");
                        $target_link = $has_external_url ? e($art["url"]) : "article-detail.php?id=" . (int)$art["id"];
                    ?>
                    <a href="<?= $target_link ?>" <?= $has_external_url ? 'target="_blank"' : '' ?> class="article-card">
                        <div class="article-img">
                            <?php if (!empty($art["gambar"])): ?>
                                <img src="../images/<?= e($art["gambar"]) ?>" alt="<?= e($art["title"]) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <?php endif; ?>
                        </div>
                        <div class="article-content">
                            <span class="article-date"><?= e($art["published_date"]) ?></span>
                            <h3><?= e($art["title"]) ?></h3>
                            <p><?= e($art["summary"]) ?></p>
                            <span class="article-link">Baca Selengkapnya <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: span 3; text-align: center; padding: 30px; background: #FFF9F6; border-radius: 20px; color: #927B6E;">
                    Belum ada kabar atau artikel resmi yang dipublikasikan.
                </div>
            <?php endif; ?>
        </div>

    </section>


    <!-- ACCOUNT INFORMATION -->

    <section class="account-section">

        <div class="section-heading">

            <div>
                <h2>Informasi Akun</h2>
                <p>Detail akun yang sedang kamu gunakan.</p>
            </div>

        </div>


        <div class="account-card">

            <div class="account-header">

                <div class="avatar">
                    <?= htmlspecialchars($inisial) ?>
                </div>

                <div>

                    <h3>
                        <?= htmlspecialchars($nama) ?>
                    </h3>

                    <p>POCKETA Member</p>

                </div>

            </div>


            <div class="account-details">

                <div class="detail-item">

                    <span>
                        <i class="fa-regular fa-user"></i>
                        Nama Lengkap
                    </span>

                    <strong>
                        <?= htmlspecialchars($nama) ?>
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        <i class="fa-regular fa-envelope"></i>
                        Email Address
                    </span>

                    <strong>
                        <?= htmlspecialchars($email) ?>
                    </strong>

                </div>

            </div>

        </div>

    </section>


</main>


<!-- MODERN FOOTER -->
<footer class="site-footer">
    <div class="footer-grid">
        
        <!-- Kolom 1: Tentang Brand -->
        <div class="footer-col">
            <div class="logo" style="margin-bottom: 12px; font-size: 24px;">
                POCKE<span>TA.</span>
            </div>
            <p>
                Platform kerajinan tangan lokal berkelanjutan. Menghadirkan produk dompet kain perca, 
                tassel, dan aksesoris penuh kreativitas untuk menemani gaya hidup harianmu.
            </p>
            <div class="footer-socials">
                <a href="#" class="social-icon"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="#" class="social-icon"><i class="fa-solid fa-envelope"></i></a>
            </div>
        </div>

        <!-- Kolom 2: Navigasi Cepat -->
        <div class="footer-col">
            <h3>Navigasi</h3>
            <ul>
                <li><a href="products.php">Katalog Produk</a></li>
                <li><a href="order-history.php">Pesanan Saya</a></li>
                <li><a href="profile.php">Profil Akun</a></li>
            </ul>
        </div>

        <!-- Kolom 3: Bantuan & Info -->
        <!-- Kolom 3: Bantuan & Info di Footer user.php -->
        <div class="footer-col">
            <h3>Bantuan</h3>
            <ul>
                <li><a href="help.php#cara-pemesanan">Cara Pemesanan</a></li>
                <li><a href="help.php#kebijakan-pengiriman">Kebijakan Pengiriman</a></li>
                <li><a href="help.php#kontak-admin">Kontak Admin</a></li>
            </ul>
        </div>

    </div>

    <!-- Copyright & Credit -->
    <div class="footer-bottom">
        <p>© 2026 <strong>POCKETA</strong>. All rights reserved.</p>
        <p>Made with <span>♥</span> for handmade lovers in Indonesia.</p>
    </div>
</footer>

</body>
</html>
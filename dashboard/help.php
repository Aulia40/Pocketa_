<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$nama = $_SESSION["nama"] ?? "Pelanggan";

function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan & Informasi | POCKETA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #FBE9E7; color: #5C4033; }
        header { background: #FFF9F6; padding: 20px 7%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(92, 64, 51, 0.08); }
        .logo { font-size: 25px; font-weight: bold; letter-spacing: 2px; }
        nav a { text-decoration: none; color: #8B6F60; margin-left: 22px; font-size: 14px; }
        nav a:hover { color: #A66A6A; }
        .container { max-width: 900px; width: 90%; margin: 40px auto; }
        .back { display: inline-block; text-decoration: none; color: #8B6F60; margin-bottom: 25px; font-weight: bold; }
        .card { background: #FFF9F6; padding: 40px; border-radius: 20px; box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08); margin-bottom: 30px; }
        h1 { font-size: 28px; margin-bottom: 10px; color: #5C4033; }
        .subtitle { font-size: 14px; color: #8B6F60; margin-bottom: 30px; line-height: 1.6; }
        h2 { font-size: 20px; color: #5C4033; margin-top: 30px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        h2 i { color: #A66A6A; }
        p, li { font-size: 14px; color: #6D5245; line-height: 1.8; margin-bottom: 12px; }
        ul { padding-left: 20px; margin-bottom: 20px; }
        .contact-box { 
            background: #FDF2EF; 
            padding: 25px; 
            border-radius: 15px; 
            border: 1px solid #EAD6CC; 
            margin-top: 20px; 
            display: flex; 
            align-items: center; 
            gap: 20px; 
        }
        .whatsapp-icon-wrap {
            min-width: 55px;
            height: 55px;
            background: #25D366;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25);
        }
        .btn-whatsapp { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            background: #25D366; 
            color: white; 
            padding: 10px 18px; 
            border-radius: 9px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 13px; 
            margin-top: 12px; 
            transition: 0.2s; 
        }
        .btn-whatsapp:hover { 
            background: #1EBE5D; 
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<header>
    <div class="logo">POCKETA</div>
    <nav>
        <a href="user.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="profile.php">Profil</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="container">
    <a href="user.php" class="back">← Kembali ke Dashboard</a>

    <div class="card">
        <h1>Pusat Bantuan & Informasi POCKETA</h1>
        <p class="subtitle">Halo, <strong><?= e($nama) ?></strong>! Temukan panduan lengkap seputar cara pemesanan produk, kebijakan pengiriman, hingga cara menghubungi tim admin kami di halaman ini.</p>

        <!-- BAGIAN 1: CARA PEMESANAN -->
        <section id="cara-pemesanan">
            <h2><i class="fa-solid fa-bag-shopping"></i> 1. Cara Pemesanan</h2>
            <p>Berbelanja produk kerajinan tangan lokal di POCKETA sangat mudah dan praktis. Ikuti langkah-langkah berikut:</p>
            <ul>
                <li>Buka menu <strong>Katalog Produk</strong> untuk melihat berbagai pilihan dompet kain, tassel, dan aksesoris menarik.</li>
                <li>Klik pada produk yang kamu inginkan untuk melihat detail foto, deskripsi, serta ketersediaan stok.</li>
                <li>Tentukan jumlah pembelian, lalu klik tombol <strong>"Tambah ke Keranjang"</strong>.</li>
                <li>Buka halaman Keranjang untuk memeriksa kembali daftar belanjaanmu.</li>
                <li>Lanjutkan ke proses *checkout* pesanan dan ikuti instruksi pembayaran yang tersedia.</li>
            </ul>
        </section>

        <hr style="border: none; border-top: 1px solid #EAD6CC; margin: 30px 0;">

        <!-- BAGIAN 2: KEBIJAKAN PENGIRIMAN -->
        <section id="kebijakan-pengiriman">
            <h2><i class="fa-solid fa-truck-fast"></i> 2. Kebijakan Pengiriman</h2>
            <p>Kami berkomitmen untuk mengemas dan mengirimkan pesananmu dengan aman dan cepat:</p>
            <ul>
                <li><strong>Waktu Proses:</strong> Pesanan yang sudah dikonfirmasi akan diproses dan dikemas dalam waktu 1x24 jam hari kerja.</li>
                <li><strong>Mitra Kurir:</strong> Kami bekerja sama dengan layanan ekspedisi terpercaya di Indonesia untuk memastikan paket tiba dengan aman di alamat tujuan.</li>
                <li><strong>Pengemasan (Packaging):</strong> Setiap produk POCKETA dikemas menggunakan material yang ramah lingkungan dan aman untuk melindungi bentuk serta detail kerajinan tangan di dalamnya.</li>
            </ul>
        </section>

        <hr style="border: none; border-top: 1px solid #EAD6CC; margin: 30px 0;">

      <!-- BAGIAN 3: KONTAK ADMIN -->
        <section id="kontak-admin">
            <h2><i class="fa-solid fa-headset"></i> 3. Kontak Admin & Layanan Pelanggan</h2>
            <p>Punya pertanyaan seputar produk kustom, kendala pesanan, atau kerja sama? Tim kami siap membantumu!</p>
            
            <div class="contact-box">
                <div class="whatsapp-icon-wrap">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div>
                    <strong style="font-size: 16px; display: block; margin-bottom: 4px; color: #5C4033;">WhatsApp Admin POCKETA</strong>
                    <p style="margin: 0; font-size: 13px; color: #8B6F60;">Hubungi kami melalui pesan WhatsApp untuk respons yang cepat dan ramah setiap hari pukul 09.00 - 17.00 WITA.</p>
                    <a href="https://wa.me/6281234567890" target="_blank" class="btn-whatsapp">
                        <i class="fa-brands fa-whatsapp" style="font-size: 16px;"></i> Chat Admin Sekarang
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

</body>
</html>
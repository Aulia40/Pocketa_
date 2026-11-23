<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

$nama = $_SESSION["nama"] ?? "Tamu";
$inisial = strtoupper(substr($nama, 0, 1));

// Ambil ID artikel dari URL secara aman
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

// Ambil data artikel berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika artikel tidak ditemukan di database, alihkan kembali ke user.php
if (!$article) {
    header("Location: user.php?error=notfound");
    exit;
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
<title><?= e($article["title"]) ?> | POCKETA</title>

<link rel="stylesheet" href="../style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    color: #5C4033;
    min-height: 100vh;
}
.navbar {
    width: 100%;
    padding: 18px 7%;
    background: rgba(255,249,246,0.92);
    backdrop-filter: blur(15px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 20px rgba(92,64,51,0.06);
}
.logo { font-size: 27px; font-weight: 800; letter-spacing: 2px; color: #5C4033; text-decoration: none; }
.logo span { color: #D89A9A; }
.container {
    width: 88%;
    max-width: 850px;
    margin: 45px auto;
}
.article-container {
    background: #FFF9F6;
    padding: 45px;
    border-radius: 25px;
    box-shadow: 0 12px 35px rgba(92,64,51,.08);
}
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #B47C7C;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 25px;
    transition: .3s;
}
.back-btn:hover { color: #5C4033; }
.article-category {
    display: inline-block;
    background: #FBE9E7;
    color: #B47C7C;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}
h1 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    line-height: 1.3;
    margin-bottom: 15px;
    color: #5C4033;
}
.article-meta {
    font-size: 12px;
    color: #927B6E;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #F0DED7;
}
.article-featured-img {
    width: 100%;
    max-height: 400px;
    border-radius: 18px;
    object-fit: cover;
    margin-bottom: 30px;
    background: #EAD6CC;
}
.article-body {
    font-size: 15px;
    line-height: 1.9;
    color: #5C4033;
}
.article-body p {
    margin-bottom: 20px;
}
.footer {
    text-align: center;
    padding: 30px 15px;
    color: #927B6E;
    font-size: 11px;
    margin-top: 35px;
}
.footer span { color: #C17E86; }

@media(max-width: 600px) {
    .article-container { padding: 25px 20px; }
    h1 { font-size: 24px; }
}
</style>
</head>
<body>

<nav class="navbar">
    <a href="user.php" class="logo">POCKE<span>TA.</span></a>
    <a href="user.php" style="text-decoration: none; color: #8B6F60; font-weight: 600; font-size: 13px;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>
</nav>

<main class="container">
    <div class="article-container">
        
        <a href="user.php" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i> Beranda Utama
        </a>

        <span class="article-category"><?= e($article["category"]) ?></span>

        <h1><?= e($article["title"]) ?></h1>

        <div class="article-meta">
            <i class="fa-regular fa-calendar"></i> Dipublikasikan pada <?= e($article["published_date"]) ?>
        </div>

        <?php if (!empty($article["gambar"])): ?>
            <img src="../images/<?= e($article["gambar"]) ?>" alt="<?= e($article["title"]) ?>" class="article-featured-img">
        <?php endif; ?>

        <div class="article-body">
            <!-- Tampilkan ringkasan sebagai pengantar -->
            <p style="font-size: 16px; color: #79584d; margin-bottom: 25px;"><strong><?= e($article["summary"]) ?></strong></p>
            
            <!-- Tampilkan isi lengkap artikel dari database dengan format paragraf -->
            <div>
                <?= nl2br(e($article["content"] ?? "")) ?>
            </div>
        </div>

    </div>
</main>

<footer class="footer">
    <p>© 2026 <strong>POCKETA</strong>. Made with <span>♥</span> for handmade lovers.</p>
</footer>

</body>
</html>
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

// Ambil kategori yang dipilih
$kategori = $_GET["kategori"] ?? "Semua";

// Ambil daftar kategori dari database
$stmtKategori = $pdo->query(
    "SELECT DISTINCT kategori FROM products ORDER BY kategori ASC"
);
$daftarKategori = $stmtKategori->fetchAll(PDO::FETCH_COLUMN);

// Ambil produk berdasarkan kategori
if ($kategori === "Semua") {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare(
        "SELECT * FROM products WHERE kategori = ? ORDER BY created_at DESC"
    );
    $stmt->execute([$kategori]);
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format rupiah
function rupiah($harga) {
    return "Rp" . number_format($harga, 0, ",", ".");
}

// Escape output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Produk | POCKETA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
            color: #5C4033;
            min-height: 100vh;
        }

        header {
            background: rgba(255, 249, 246, 0.92);
            backdrop-filter: blur(15px);
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(92, 64, 51, 0.06);
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            color: #5C4033;
            letter-spacing: 2px;
        }

        .logo span {
            color: #D89A9A;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        nav a {
            text-decoration: none;
            color: #8B6F60;
            font-size: 14px;
            font-weight: 600;
            transition: .3s;
        }

        nav a:hover {
            color: #D89A9A;
        }

        .container {
            width: 88%;
            max-width: 1150px;
            margin: 45px auto;
        }

        .heading {
            text-align: center;
            margin-bottom: 35px;
        }

        .heading h1 {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            margin-bottom: 10px;
            color: #5C4033;
        }

        .heading p {
            color: #927B6E;
            font-size: 14px;
        }

        .categories {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 35px;
        }

        .categories a {
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 30px;
            background: #FFF9F6;
            color: #8B6F60;
            border: 1px solid rgba(255, 255, 255, .8);
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(92, 64, 51, 0.04);
            transition: .3s ease;
        }

        .categories a.active,
        .categories a:hover {
            background: #5C4033;
            color: white;
            border-color: #5C4033;
            transform: translateY(-2px);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: #FFF9F6;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(92, 64, 51, .07);
            border: 1px solid rgba(255, 255, 255, .8);
            transition: 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(92, 64, 51, .13);
            border-color: #D89A9A;
        }

        .product-image {
            height: 220px;
            background: linear-gradient(135deg, #F4C7C3, #EAD6CC);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 55px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            padding: 22px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-category {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #B47C7C;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .product-info h3 {
            font-size: 17px;
            margin-bottom: 8px;
            color: #5C4033;
        }

        .price {
            font-weight: 700;
            color: #C17E86;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .stock {
            font-size: 12px;
            color: #927B6E;
            margin-bottom: 20px;
            margin-top: auto;
        }

        .detail-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #5C4033;
            color: white;
            padding: 11px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            transition: .3s;
        }

        .detail-btn:hover {
            background: #D89A9A;
            transform: translateY(-2px);
        }

        .empty {
            text-align: center;
            padding: 50px 20px;
            background: #FFF9F6;
            border-radius: 22px;
            box-shadow: 0 8px 25px rgba(92, 64, 51, .07);
        }

        .empty h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 8px;
            color: #5C4033;
        }

        .empty p {
            color: #927B6E;
            font-size: 13px;
        }

        footer {
            text-align: center;
            padding: 30px 15px;
            color: #927B6E;
            font-size: 11px;
            margin-top: 35px;
        }

        footer span {
            color: #C17E86;
        }

        @media (max-width: 600px) {
            header {
                padding: 16px 5%;
                flex-direction: column;
                gap: 15px;
            }

            nav {
                gap: 12px;
            }

            .container {
                width: 92%;
                margin: 28px auto;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">POCKE<span>TA.</span></div>

    <nav>
        <a href="user.php">Dashboard</a>
        <a href="products.php" style="color: #D89A9A;">Produk</a>
        <a href="cart.php">Keranjang</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <div class="heading">
        <h1>Katalog Produk</h1>
        <p>Temukan produk handmade favoritmu di POCKETA.</p>
    </div>

    <div class="categories">
        <a href="products.php" class="<?= $kategori === 'Semua' ? 'active' : '' ?>">
            Semua
        </a>

        <?php foreach ($daftarKategori as $kat): ?>
            <a href="products.php?kategori=<?= urlencode($kat) ?>" class="<?= $kategori === $kat ? 'active' : '' ?>">
                <?= e($kat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($products) > 0): ?>

        <div class="product-grid">

            <?php foreach ($products as $product): ?>

                <div class="product-card">

                    <div class="product-image">
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

                        <div class="product-category">
                            <?= e($product["kategori"]) ?>
                        </div>

                        <h3><?= e($product["nama_produk"]) ?></h3>

                        <div class="price">
                            <?= rupiah($product["harga"]) ?>
                        </div>

                        <div class="stock">
                            <?= e($product["status"]) ?> &bull; Stok: <?= (int)$product["stok"] ?>
                        </div>

                        <a class="detail-btn" href="product-detail.php?id=<?= (int)$product["id"] ?>">
                            Lihat Detail
                        </a>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <h3>Produk belum tersedia</h3>
            <p>Belum ada produk dalam kategori ini.</p>
        </div>

    <?php endif; ?>

</main>

<footer>
    <p>
        &copy; 2026 <strong>POCKETA</strong>. Made with <span>♥</span> for handmade lovers.
    </p>
</footer>

</body>
</html>
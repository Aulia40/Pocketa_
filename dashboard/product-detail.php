
<?php
session_start();

require_once "../config/database.php";

// Ambil ID produk dari URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: products.php");
    exit;
}

// Ambil data produk dari database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika produk tidak ditemukan
if (!$product) {
    http_response_code(404);
    die("Produk tidak ditemukan. <a href='products.php'>Kembali ke katalog</a>");
}

// Format harga
function rupiah($harga) {
    return "Rp" . number_format($harga, 0, ",", ".");
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
    <title><?= e($product["nama_produk"]) ?> | POCKETA</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #FBE9E7;
            color: #5C4033;
        }

        header {
            background: #FFF9F6;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(92, 64, 51, 0.08);
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        nav a {
            text-decoration: none;
            color: #8B6F60;
            margin-left: 22px;
            font-size: 14px;
        }

        nav a:hover {
            color: #A66A6A;
        }

        .container {
            max-width: 1000px;
            width: 90%;
            margin: 50px auto;
        }

        .back {
            display: inline-block;
            text-decoration: none;
            color: #8B6F60;
            margin-bottom: 25px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: #FFF9F6;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08);
        }

        .product-image {
            min-height: 350px;
            background: #F4C7C3;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 100px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            min-height: 350px;
            object-fit: cover;
        }

        .category {
            color: #A66A6A;
            font-size: 14px;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .price {
            font-size: 25px;
            font-weight: bold;
            color: #A66A6A;
            margin-bottom: 20px;
        }

        .description {
            color: #8B6F60;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .stock {
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input[type="number"] {
            width: 90px;
            padding: 12px;
            border: 1px solid #EAD6CC;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 20px;
        }

        button {
            display: block;
            width: 100%;
            padding: 14px;
            background: #5C4033;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #A66A6A;
        }

        button:disabled {
            background: #BCAAA4;
            cursor: not-allowed;
        }

        .notice {
            color: #A66A6A;
            margin-top: 15px;
            font-size: 14px;
        }

        @media (max-width: 700px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin: 0 8px;
            }

            .product-detail {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .product-image {
                min-height: 250px;
            }

            .product-image img {
                min-height: 250px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">POCKETA</div>

    <nav>
        <a href="user.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="cart.php">Keranjang</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <a href="products.php" class="back">← Kembali ke Katalog</a>

    <div class="product-detail">

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

            <div class="category">
                <?= e($product["kategori"]) ?>
            </div>

            <h1><?= e($product["nama_produk"]) ?></h1>

            <div class="price">
                <?= rupiah($product["harga"]) ?>
            </div>

            <div class="description">
                <?= nl2br(e($product["deskripsi"] ?: "Produk handmade dari POCKETA.")) ?>
            </div>

            <div class="stock">
                Status: <strong><?= e($product["status"]) ?></strong>
                <br>
                Stok tersedia: <strong><?= (int)$product["stok"] ?></strong>
            </div>

            <?php if ((int)$product["stok"] > 0): ?>

                <form action="add-to-cart.php" method="POST">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$product["id"] ?>"
                    >

                    <label for="jumlah">Jumlah Pembelian</label>

                    <input
                        type="number"
                        id="jumlah"
                        name="jumlah"
                        value="1"
                        min="1"
                        max="<?= (int)$product["stok"] ?>"
                        required
                    >

                    <button type="submit">
                        Tambah ke Keranjang
                    </button>

                </form>

            <?php else: ?>

                <button type="button" disabled>
                    Stok Habis
                </button>

                <p class="notice">
                    Maaf, produk ini sedang tidak tersedia.
                </p>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>

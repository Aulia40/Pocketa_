
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Proses perubahan jumlah
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";
    $product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

    if ($product_id && isset($_SESSION["cart"][$product_id])) {

        if ($action === "remove") {
            unset($_SESSION["cart"][$product_id]);

        } elseif ($action === "update") {

            $jumlah = filter_input(INPUT_POST, "jumlah", FILTER_VALIDATE_INT);

            $stmt = $pdo->prepare("SELECT stok FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product && $jumlah >= 1 && $jumlah <= $product["stok"]) {
                $_SESSION["cart"][$product_id]["jumlah"] = $jumlah;
            }
        }
    }

    header("Location: cart.php");
    exit;
}

// Ambil data terbaru dari database
$cart_items = [];
$total = 0;

foreach ($_SESSION["cart"] as $product_id => $item) {

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Hapus produk yang sudah tidak tersedia
    if (!$product) {
        unset($_SESSION["cart"][$product_id]);
        continue;
    }

    $jumlah = (int)$item["jumlah"];

    // Sesuaikan jumlah jika stok berubah
    if ($jumlah > $product["stok"]) {
        $jumlah = (int)$product["stok"];
        $_SESSION["cart"][$product_id]["jumlah"] = $jumlah;
    }

    if ($jumlah < 1) {
        unset($_SESSION["cart"][$product_id]);
        continue;
    }

    $subtotal = $product["harga"] * $jumlah;
    $total += $subtotal;

    $cart_items[] = [
        "id" => $product["id"],
        "nama" => $product["nama_produk"],
        "harga" => $product["harga"],
        "jumlah" => $jumlah,
        "stok" => $product["stok"],
        "gambar" => $product["gambar"],
        "subtotal" => $subtotal
    ];
}

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
    <title>Keranjang Belanja | POCKETA</title>

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
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        nav a {
            text-decoration: none;
            color: #8B6F60;
            margin-left: 20px;
            font-size: 14px;
        }

        .container {
            max-width: 1000px;
            width: 90%;
            margin: 45px auto;
        }

        h1 {
            margin-bottom: 25px;
        }

        .cart-box {
            background: #FFF9F6;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(92, 64, 51, 0.08);
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #EAD6CC;
        }

        .product-image {
            width: 100px;
            height: 100px;
            background: #F4C7C3;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info {
            flex: 1;
        }

        .product-info h3 {
            margin-bottom: 8px;
        }

        .price {
            color: #A66A6A;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        input[type="number"] {
            width: 65px;
            padding: 8px;
            border: 1px solid #EAD6CC;
            border-radius: 6px;
        }

        button, .checkout-btn, .continue-btn {
            border: none;
            padding: 10px 15px;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .update-btn {
            background: #EAD6CC;
            color: #5C4033;
        }

        .remove-btn {
            background: #F4C7C3;
            color: #5C4033;
        }

        .summary {
            text-align: right;
            padding-top: 25px;
        }

        .summary h2 {
            margin-bottom: 20px;
        }

        .checkout-btn {
            display: inline-block;
            background: #5C4033;
            color: white;
        }

        .continue-btn {
            display: inline-block;
            background: #F4C7C3;
            color: #5C4033;
            margin-right: 8px;
        }

        .empty {
            text-align: center;
            padding: 45px 15px;
        }

        .empty p {
            margin: 12px 0 22px;
            color: #8B6F60;
        }

        @media(max-width: 600px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin: 0 7px;
            }

            .cart-item {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .product-image {
                width: 75px;
                height: 75px;
            }

            .summary {
                text-align: left;
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

    <h1>Keranjang Belanja</h1>

    <div class="cart-box">

        <?php if (count($cart_items) > 0): ?>

            <?php foreach ($cart_items as $item): ?>

                <div class="cart-item">

                    <div class="product-image">
                        <?php if (!empty($item["gambar"])): ?>
                            <img
                                src="../images/<?= e(basename($item["gambar"])) ?>"
                                alt="<?= e($item["nama"]) ?>"
                                onerror="this.style.display='none'; this.parentElement.innerHTML='👜';"
                            >
                        <?php else: ?>
                            👜
                        <?php endif; ?>
                    </div>

                    <div class="product-info">

                        <h3><?= e($item["nama"]) ?></h3>

                        <div class="price">
                            <?= rupiah($item["harga"]) ?>
                        </div>

                        <p style="margin-bottom: 12px;">
                            Subtotal: <strong><?= rupiah($item["subtotal"]) ?></strong>
                        </p>

                        <div class="quantity-form">

                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id"
                                       value="<?= (int)$item["id"] ?>">

                                <input
                                    type="number"
                                    name="jumlah"
                                    min="1"
                                    max="<?= (int)$item["stok"] ?>"
                                    value="<?= (int)$item["jumlah"] ?>"
                                    required
                                >

                                <button class="update-btn" type="submit">
                                    Ubah
                                </button>
                            </form>

                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id"
                                       value="<?= (int)$item["id"] ?>">

                                <button class="remove-btn" type="submit">
                                    Hapus
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <div class="summary">

                <h2>Total: <?= rupiah($total) ?></h2>

                <a href="products.php" class="continue-btn">
                    Lanjut Belanja
                </a>

                <a href="checkout.php" class="checkout-btn">
                    Checkout
                </a>

            </div>

        <?php else: ?>

            <div class="empty">
                <h2>Keranjang Masih Kosong 🛍️</h2>
                <p>Yuk, temukan produk handmade favoritmu!</p>

                <a href="products.php" class="checkout-btn">
                    Mulai Belanja
                </a>
            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>

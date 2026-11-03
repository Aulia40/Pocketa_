
<?php
session_start();

require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

// Ambil riwayat pesanan milik pengguna yang sedang login
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$_SESSION["user_id"]]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function rupiah($harga) {
    return "Rp" . number_format((float)$harga, 0, ",", ".");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pesanan | POCKETA</title>

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
            box-shadow: 0 3px 15px rgba(92,64,51,.08);
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .logo span {
            color: #D89A9A;
        }

        nav a {
            text-decoration: none;
            color: #8B6F60;
            margin-left: 20px;
            font-size: 14px;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 45px auto;
        }

        h1 {
            margin-bottom: 10px;
        }

        .subtitle {
            color: #8B6F60;
            margin-bottom: 30px;
        }

        .order-card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(92,64,51,.05);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            padding-bottom: 15px;
            border-bottom: 1px solid #EAD6CC;
        }

        .order-number {
            font-weight: bold;
            color: #A66A6A;
            margin-bottom: 7px;
        }

        .date {
            font-size: 13px;
            color: #8B6F60;
        }

        .status {
            display: inline-block;
            background: #F4C7C3;
            color: #5C4033;
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 12px;
            height: fit-content;
        }

        .order-info {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            padding: 20px 0;
        }

        .info-label {
            font-size: 12px;
            color: #8B6F60;
            margin-bottom: 7px;
        }

        .info-value {
            font-weight: bold;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border-top: 1px solid #EAD6CC;
            padding-top: 17px;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            color: #A66A6A;
        }

        .detail-btn {
            text-decoration: none;
            background: #5C4033;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            font-size: 13px;
        }

        .detail-btn:hover {
            background: #A66A6A;
        }

        .empty {
            text-align: center;
            background: #FFF9F6;
            border-radius: 15px;
            padding: 50px 20px;
        }

        .empty p {
            color: #8B6F60;
            margin: 12px 0 22px;
        }

        .shop-btn {
            display: inline-block;
            text-decoration: none;
            background: #5C4033;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
        }

        @media(max-width: 600px) {
            header {
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin: 0 8px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="logo">POCKE<span>TA</span></div>

    <nav>
        <a href="user.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="cart.php">Keranjang</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h1>Riwayat Pesanan 🛍️</h1>

    <p class="subtitle">
        Lihat dan pantau semua transaksi belanjamu di POCKETA.
    </p>

    <?php if (count($orders) > 0): ?>

        <?php foreach ($orders as $order): ?>

            <div class="order-card">

                <div class="order-header">

                    <div>
                        <div class="order-number">
                            <?= e($order["order_number"]) ?>
                        </div>

                        <div class="date">
                            <?= date("d M Y, H:i", strtotime($order["created_at"])) ?>
                        </div>
                    </div>

                    <span class="status">
                        <?= e($order["status_pesanan"]) ?>
                    </span>

                </div>

                <div class="order-info">

                    <div>
                        <div class="info-label">Nama Penerima</div>
                        <div class="info-value">
                            <?= e($order["nama_penerima"]) ?>
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Metode Pembayaran</div>
                        <div class="info-value">
                            <?= e($order["metode_pembayaran"]) ?>
                        </div>
                    </div>

                    <div>
                        <div class="info-label">Total Pembayaran</div>
                        <div class="total">
                            <?= rupiah($order["total_harga"]) ?>
                        </div>
                    </div>

                </div>

                <div class="order-footer">

                    <span class="date">
                        Detail transaksi dapat dilihat di bawah.
                    </span>

                    <a
                        href="order-detail.php?id=<?= (int)$order["id"] ?>"
                        class="detail-btn"
                    >
                        Lihat Detail →
                    </a>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <h2>Belum Ada Pesanan 🩷</h2>

            <p>
                Kamu belum melakukan transaksi di POCKETA.
                Yuk, temukan produk handmade favoritmu!
            </p>

            <a href="products.php" class="shop-btn">
                Mulai Belanja
            </a>
        </div>

    <?php endif; ?>

</main>

</body>
</html>

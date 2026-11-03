
<?php
session_start();

// Pastikan ada data pesanan
if (!isset($_SESSION["last_order"])) {
    header("Location: user.php");
    exit;
}

$order = $_SESSION["last_order"];

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

    <title>Pesanan Berhasil | POCKETA</title>

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
            min-height: 100vh;
        }

        .navbar {
            background: #FFF9F6;
            padding: 20px 7%;
            box-shadow: 0 3px 15px rgba(92,64,51,.08);
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .logo span {
            color: #D89A9A;
        }

        .container {
            width: 90%;
            max-width: 700px;
            margin: 45px auto;
        }

        .success-card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 8px 25px rgba(92,64,51,.06);
        }

        .success-icon {
            text-align: center;
            font-size: 65px;
            margin-bottom: 15px;
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 27px;
        }

        .subtitle {
            text-align: center;
            color: #8B6F60;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-number {
            background: #FBE9E7;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            margin-bottom: 25px;
        }

        .order-number p {
            color: #8B6F60;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .order-number strong {
            font-size: 20px;
            color: #A66A6A;
        }

        .section {
            margin-top: 25px;
        }

        .section h2 {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
        }

        .detail-row span:first-child {
            color: #8B6F60;
        }

        .detail-row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .product-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
        }

        .product-row span:last-child {
            font-weight: bold;
            white-space: nowrap;
        }

        .total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 19px;
            font-weight: bold;
            color: #A66A6A;
        }

        .status {
            display: inline-block;
            background: #F4C7C3;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            margin-top: 10px;
        }

        .home-btn {
            display: block;
            text-align: center;
            text-decoration: none;
            background: #5C4033;
            color: white;
            padding: 14px;
            border-radius: 10px;
            margin-top: 30px;
            transition: .3s;
        }

        .home-btn:hover {
            background: #A66A6A;
        }

        @media(max-width: 500px) {
            .success-card {
                padding: 22px;
            }

            .detail-row,
            .product-row {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">
        POCKE<span>TA</span>
    </div>
</nav>

<main class="container">

    <div class="success-card">

        <div class="success-icon">🩷</div>

        <h1>Pesanan Berhasil!</h1>

        <p class="subtitle">
            Terima kasih telah berbelanja di POCKETA.
            Pesananmu berhasil kami terima.
        </p>

        <div class="order-number">
            <p>Nomor Pesanan</p>
            <strong><?= e($order["order_number"] ?? "-") ?></strong>
        </div>

        <div class="section">

            <h2>Informasi Pembeli</h2>

            <div class="detail-row">
                <span>Nama Penerima</span>
                <span><?= e($order["nama_penerima"] ?? "-") ?></span>
            </div>

            <div class="detail-row">
                <span>Email</span>
                <span><?= e($order["email"] ?? "-") ?></span>
            </div>

            <div class="detail-row">
                <span>Nomor Telepon</span>
                <span><?= e($order["telepon"] ?? "-") ?></span>
            </div>

            <div class="detail-row">
                <span>Alamat</span>
                <span><?= e($order["alamat"] ?? "-") ?></span>
            </div>

            <div class="detail-row">
                <span>Metode Pembayaran</span>
                <span><?= e($order["pembayaran"] ?? "-") ?></span>
            </div>

            <div class="detail-row">
                <span>Tanggal Pesanan</span>
                <span><?= e($order["tanggal"] ?? "-") ?></span>
            </div>

        </div>

        <div class="section">

            <h2>Ringkasan Produk</h2>

            <?php foreach (($order["produk"] ?? []) as $item): ?>

                <div class="product-row">

                    <span>
                        <?= e($item["nama"] ?? "Produk") ?>
                        × <?= (int)($item["jumlah"] ?? 0) ?>
                    </span>

                    <span>
                        <?= rupiah($item["subtotal"] ?? 0) ?>
                    </span>

                </div>

            <?php endforeach; ?>

            <div class="total">
                <span>Total Pembayaran</span>
                <span><?= rupiah($order["total"] ?? 0) ?></span>
            </div>

        </div>

        <div class="section">

            <h2>Status Pesanan</h2>

            <span class="status">
                <?= e($order["status"] ?? "Menunggu Pembayaran") ?>
            </span>

        </div>

        <a href="user.php" class="home-btn">
            Kembali ke Dashboard
        </a>

    </div>

</main>

</body>
</html>

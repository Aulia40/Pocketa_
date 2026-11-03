
<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: order-history.php");
    exit;
}

// Ambil pesanan berdasarkan ID dan pemiliknya
$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
");

$stmt->execute([$id, $_SESSION["user_id"]]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    die("Pesanan tidak ditemukan.");
}

// Ambil detail produk pesanan
$stmtItems = $pdo->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
");

$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

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

    <title>Detail Pesanan | POCKETA</title>

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
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        header span {
            color: #D89A9A;
        }

        .container {
            max-width: 750px;
            width: 90%;
            margin: 40px auto;
        }

        .card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(92,64,51,.05);
        }

        h1 {
            margin-bottom: 10px;
        }

        h2 {
            font-size: 18px;
            margin-bottom: 18px;
        }

        .subtitle {
            color: #8B6F60;
            margin-bottom: 25px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
        }

        .row span:first-child {
            color: #8B6F60;
        }

        .row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .product {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
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
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            background: #5C4033;
            color: white;
            padding: 13px 20px;
            border-radius: 8px;
        }

        .back-btn:hover {
            background: #A66A6A;
        }
    </style>
</head>

<body>

<header>POCKE<span>TA</span></header>

<main class="container">

    <h1>Detail Pesanan</h1>

    <p class="subtitle">
        Informasi lengkap transaksi POCKETA kamu.
    </p>

    <section class="card">

        <h2>Informasi Pesanan</h2>

        <div class="row">
            <span>Nomor Pesanan</span>
            <strong><?= e($order["order_number"]) ?></strong>
        </div>

        <div class="row">
            <span>Tanggal</span>
            <span><?= date("d M Y, H:i", strtotime($order["created_at"])) ?></span>
        </div>

        <div class="row">
            <span>Status</span>
            <span class="status"><?= e($order["status_pesanan"]) ?></span>
        </div>

        <div class="row">
            <span>Metode Pembayaran</span>
            <span><?= e($order["metode_pembayaran"]) ?></span>
        </div>

    </section>

    <section class="card">

        <h2>Informasi Pengiriman</h2>

        <div class="row">
            <span>Nama Penerima</span>
            <span><?= e($order["nama_penerima"]) ?></span>
        </div>

        <div class="row">
            <span>Email</span>
            <span><?= e($order["email"]) ?></span>
        </div>

        <div class="row">
            <span>Telepon</span>
            <span><?= e($order["telepon"]) ?></span>
        </div>

        <div class="row">
            <span>Alamat</span>
            <span><?= nl2br(e($order["alamat"])) ?></span>
        </div>

        <div class="row">
            <span>Catatan</span>
            <span><?= e($order["catatan"] ?: "-") ?></span>
        </div>

    </section>

    <section class="card">

        <h2>Produk yang Dibeli</h2>

        <?php foreach ($items as $item): ?>

            <div class="product">

                <span>
                    <?= e($item["nama_produk"]) ?>
                    × <?= (int)$item["jumlah"] ?>
                </span>

                <strong>
                    <?= rupiah($item["subtotal"]) ?>
                </strong>

            </div>

        <?php endforeach; ?>

        <div class="total">
            <span>Total Pembayaran</span>
            <span><?= rupiah($order["total_harga"]) ?></span>
        </div>

    </section>

    <a href="order-history.php" class="back-btn">
        ← Kembali ke Riwayat Pesanan
    </a>

</main>

</body>
</html>

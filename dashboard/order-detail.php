
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

// Ambil produk dalam pesanan
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

function statusClass($status) {
    $status = strtolower(trim((string)$status));

    if (in_array($status, ["selesai", "completed"])) {
        return "success";
    }

    if (in_array($status, ["dibatalkan", "cancelled"])) {
        return "danger";
    }

    if (in_array($status, [
        "dikirim",
        "dalam pengiriman",
        "diproses",
        "dikemas"
    ])) {
        return "info";
    }

    return "pending";
}

$statusPesanan = $order["status_pesanan"] ?? "Menunggu Konfirmasi";
$statusPembayaran = $order["status_pembayaran"] ?? "Belum Dikonfirmasi";
$buktiPembayaran = $order["bukti_pembayaran"] ?? "";
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
            background: linear-gradient(
                135deg,
                #FBE9E7,
                #F4C7C3,
                #EAD6CC
            );
            color: #5C4033;
            min-height: 100vh;
        }

        header {
            background: #FFF9F6;
            padding: 20px 7%;
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 2px;
            box-shadow: 0 3px 12px rgba(92, 64, 51, .06);
        }

        header span {
            color: #D89A9A;
        }

        .container {
            max-width: 850px;
            width: 90%;
            margin: 40px auto;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #8B6F60;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(92, 64, 51, .05);
        }

        .card h2 {
            font-size: 18px;
            margin-bottom: 18px;
            color: #5C4033;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .order-number {
            font-size: 17px;
            font-weight: bold;
            color: #A66A6A;
        }

        .status {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #FFF0CE;
            color: #94651D;
        }

        .success {
            background: #E1F2E5;
            color: #357348;
        }

        .info {
            background: #E3EFF8;
            color: #38678B;
        }

        .danger {
            background: #FBE0E0;
            color: #A33D3D;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 13px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
            line-height: 1.6;
        }

        .row:last-child {
            border-bottom: none;
        }

        .row span:first-child {
            color: #8B6F60;
            flex-shrink: 0;
        }

        .row span:last-child,
        .row strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 14px;
            line-height: 1.6;
        }

        .product:last-of-type {
            border-bottom: none;
        }

        .product-name {
            font-weight: bold;
        }

        .product-qty {
            color: #8B6F60;
            font-size: 13px;
        }

        .product-price {
            font-weight: bold;
            white-space: nowrap;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            border-top: 1px solid #EAD6CC;
            padding-top: 20px;
            margin-top: 10px;
            font-size: 19px;
            font-weight: bold;
            color: #A66A6A;
        }

        .payment-proof {
            margin-top: 18px;
        }

        .payment-proof img {
            display: block;
            width: 100%;
            max-width: 300px;
            max-height: 350px;
            object-fit: contain;
            border: 1px solid #EAD6CC;
            border-radius: 10px;
            margin-top: 12px;
        }

        .empty-items {
            color: #8B6F60;
            font-size: 14px;
            padding: 15px 0;
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .back-btn,
        .shop-btn {
            display: inline-block;
            text-decoration: none;
            padding: 13px 20px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: bold;
            transition: .2s;
        }

        .back-btn {
            background: #5C4033;
            color: white;
        }

        .back-btn:hover {
            background: #A66A6A;
        }

        .shop-btn {
            background: #F4C7C3;
            color: #5C4033;
        }

        .shop-btn:hover {
            background: #E8B1AD;
        }

        @media (max-width: 600px) {
            .container {
                margin: 25px auto;
            }

            .card {
                padding: 20px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .row {
                font-size: 13px;
            }

            .total {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

<header>POCKE<span>TA</span></header>

<main class="container">

    <div class="page-heading">
        <h1>Detail Pesanan</h1>
        <p class="subtitle">
            Lihat informasi lengkap transaksi dan produk yang kamu pesan di POCKETA.
        </p>
    </div>

    <!-- INFORMASI PESANAN -->
    <section class="card">

        <div class="order-header">
            <div>
                <h2>Informasi Pesanan</h2>
                <div class="order-number">
                    <?= e($order["order_number"] ?? "-") ?>
                </div>
            </div>

            <span class="status <?= statusClass($statusPesanan) ?>">
                <?= e($statusPesanan) ?>
            </span>
        </div>

        <div class="row">
            <span>Tanggal Pesanan</span>
            <strong>
                <?= !empty($order["created_at"])
                    ? date("d M Y, H:i", strtotime($order["created_at"]))
                    : "-" ?>
            </strong>
        </div>

        <div class="row">
            <span>Metode Pembayaran</span>
            <span><?= e($order["metode_pembayaran"] ?? "-") ?></span>
        </div>

        <div class="row">
            <span>Status Pembayaran</span>
            <span class="status <?= statusClass($statusPembayaran) ?>">
                <?= e($statusPembayaran) ?>
            </span>
        </div>

    </section>

    <!-- INFORMASI PENGIRIMAN -->
    <section class="card">

        <h2>Informasi Pengiriman</h2>

        <div class="row">
            <span>Nama Penerima</span>
            <strong><?= e($order["nama_penerima"] ?? "-") ?></strong>
        </div>

        <div class="row">
            <span>Email</span>
            <span><?= e($order["email"] ?? "-") ?></span>
        </div>

        <div class="row">
            <span>Nomor Telepon</span>
            <span><?= e($order["telepon"] ?? "-") ?></span>
        </div>

        <div class="row">
            <span>Alamat</span>
            <span><?= nl2br(e($order["alamat"] ?? "-")) ?></span>
        </div>

        <div class="row">
            <span>Catatan</span>
            <span><?= e($order["catatan"] ?: "-") ?></span>
        </div>

    </section>

    <!-- PRODUK YANG DIBELI -->
    <section class="card">

        <h2>Produk yang Dibeli</h2>

        <?php if (!empty($items)): ?>

            <?php foreach ($items as $item): ?>

                <div class="product">

                    <div>
                        <div class="product-name">
                            <?= e($item["nama_produk"] ?? "Produk POCKETA") ?>
                        </div>

                        <div class="product-qty">
                            Jumlah: <?= (int)($item["jumlah"] ?? 0) ?>
                        </div>
                    </div>

                    <div class="product-price">
                        <?= rupiah($item["subtotal"] ?? 0) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty-items">
                Detail produk belum tersedia.
            </p>

        <?php endif; ?>

        <div class="total">
            <span>Total Pembayaran</span>
            <span><?= rupiah($order["total_harga"] ?? 0) ?></span>
        </div>

    </section>

    <!-- BUKTI PEMBAYARAN -->
    <?php if (!empty($buktiPembayaran)): ?>

        <section class="card">

            <h2>Bukti Pembayaran</h2>

            <p class="subtitle">
                Bukti pembayaran yang telah kamu unggah.
            </p>

            <div class="payment-proof">
                <img
                    src="../<?= e(ltrim($buktiPembayaran, "/")) ?>"
                    alt="Bukti Pembayaran"
                >
            </div>

        </section>

    <?php endif; ?>

    <div class="button-group">

        <a href="order-history.php" class="back-btn">
            ← Kembali ke Riwayat
        </a>

        <a href="products.php" class="shop-btn">
            Belanja Lagi
        </a>

    </div>

</main>

</body>
</html>


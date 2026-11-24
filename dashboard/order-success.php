
<?php
session_start();

if (!isset($_SESSION["last_order"])) {
    header("Location: user.php");
    exit;
}

$order = $_SESSION["last_order"];

function e($value): string
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

function rupiah($harga): string
{
    return "Rp" . number_format((float)$harga, 0, ",", ".");
}

$orderNumber = $order["order_number"] ?? "-";
$status = $order["status"] ?? "Menunggu Konfirmasi";
$statusPembayaran = $order["status_pembayaran"] ?? "Menunggu Pembayaran";
$payment = $order["pembayaran"] ?? "-";

$buktiPath = trim((string)($order["bukti_pembayaran"] ?? ""));
$buktiSrc = "";

if ($buktiPath !== "") {
    $buktiSrc = str_starts_with($buktiPath, "../") ||
                str_starts_with($buktiPath, "http")
        ? $buktiPath
        : "../" . ltrim($buktiPath, "/");
}

// Progress pesanan
$progressSteps = [
    "Pesanan Diterima",
    "Sedang Diproses",
    "Sedang Dikemas",
    "Dalam Pengiriman",
    "Pesanan Selesai"
];

// Status yang sudah tercapai
$statusMap = [
    "Menunggu Konfirmasi" => 0,
    "Menunggu Pembayaran" => 0,
    "Diproses" => 1,
    "Sedang Diproses" => 1,
    "Dikemas" => 2,
    "Sedang Dikemas" => 2,
    "Dikirim" => 3,
    "Dalam Pengiriman" => 3,
    "Selesai" => 4
];

$currentStep = $statusMap[$status] ?? 0;

// COD dan pembayaran digital
$isCOD = stripos($payment, "cod") !== false;
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
            background: #F8EFEC;
            color: #5C4033;
            min-height: 100vh;
        }

        .navbar {
            background: #FFF9F6;
            padding: 17px 7%;
            box-shadow: 0 3px 15px rgba(92,64,51,.07);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .logo span {
            color: #D89A9A;
        }

        .nav-text {
            font-size: 13px;
            color: #8B6F60;
        }

        .container {
            width: 92%;
            max-width: 850px;
            margin: 30px auto 60px;
        }

        .success-header {
            background: linear-gradient(135deg, #EBC2BE, #D99E9C);
            padding: 35px 25px;
            border-radius: 18px 18px 0 0;
            text-align: center;
            color: white;
        }

        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 15px;
            background: white;
            color: #C27E80;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 42px;
            font-weight: bold;
        }

        .success-header h1 {
            font-size: 25px;
            margin-bottom: 9px;
        }

        .success-header p {
            font-size: 13px;
            line-height: 1.7;
            opacity: .95;
        }

        .main-card {
            background: #FFF9F6;
            border-radius: 0 0 18px 18px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(92,64,51,.06);
        }

        .order-number {
            background: #FFF0ED;
            border: 1px solid #F0D3CD;
            padding: 18px;
            border-radius: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
        }

        .order-number small {
            display: block;
            color: #9B8174;
            font-size: 12px;
            margin-bottom: 7px;
        }

        .order-number strong {
            font-size: 17px;
            color: #A66A6A;
            overflow-wrap: anywhere;
        }

        .copy-btn {
            border: 1px solid #D89A9A;
            background: white;
            color: #A66A6A;
            padding: 9px 13px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            flex-shrink: 0;
        }

        .copy-btn:hover {
            background: #FCEDEC;
        }

        .section {
            margin-top: 28px;
        }

        .section-title {
            font-size: 17px;
            margin-bottom: 18px;
            color: #5C4033;
        }

        /* PROGRESS PESANAN */

        .progress {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0 15px;
        }

        .progress::before {
            content: "";
            position: absolute;
            top: 17px;
            left: 8%;
            right: 8%;
            height: 3px;
            background: #EAD6CC;
            z-index: 0;
        }

        .progress-fill {
            position: absolute;
            top: 17px;
            left: 8%;
            height: 3px;
            background: #D89A9A;
            z-index: 1;
            transition: .3s;
        }

        .progress-item {
            width: 20%;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .progress-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #EAD6CC;
            color: #8B6F60;
            margin: 0 auto 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            border: 3px solid #FFF9F6;
        }

        .progress-item.done .progress-circle {
            background: #D89A9A;
            color: white;
        }

        .progress-item span {
            font-size: 10px;
            color: #9B8174;
            display: block;
            line-height: 1.4;
        }

        .progress-item.done span {
            color: #A66A6A;
            font-weight: bold;
        }

        /* DETAIL PESANAN */

        .detail-box {
            border-top: 1px solid #EAD6CC;
            padding-top: 17px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
            font-size: 13px;
        }

        .detail-row span:first-child {
            color: #9B8174;
            flex-shrink: 0;
        }

        .detail-row span:last-child {
            text-align: right;
            overflow-wrap: anywhere;
            color: #5C4033;
        }

        /* PRODUK */

        .product-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 13px;
        }

        .product-name {
            font-weight: bold;
            color: #5C4033;
        }

        .product-qty {
            color: #9B8174;
            margin-top: 5px;
            font-size: 12px;
        }

        .product-price {
            font-weight: bold;
            white-space: nowrap;
        }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            font-size: 16px;
            font-weight: bold;
        }

        .total-box strong {
            color: #C27E80;
            font-size: 20px;
        }

        /* STATUS PEMBAYARAN */

        .payment-status {
            background: #FFF0ED;
            border: 1px solid #EAD6CC;
            padding: 17px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.8;
        }

        .payment-status strong {
            color: #A66A6A;
        }

        .status-badge {
            display: inline-block;
            background: #F4C7C3;
            color: #754C43;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 8px;
        }

        .proof-box {
            margin-top: 15px;
            text-align: center;
        }

        .proof-box img {
            max-width: 230px;
            max-height: 250px;
            border-radius: 12px;
            border: 1px solid #EAD6CC;
            object-fit: contain;
        }

        .proof-box p {
            font-size: 12px;
            margin-bottom: 10px;
            color: #8B6F60;
        }

        /* FOOTER BUTTON */

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: bold;
            transition: .2s;
        }

        .btn-primary {
            background: #8B6F60;
            color: white;
        }

        .btn-primary:hover {
            background: #5C4033;
        }

        .btn-secondary {
            border: 1px solid #D89A9A;
            color: #A66A6A;
            background: white;
        }

        .btn-secondary:hover {
            background: #FFF0ED;
        }

        .thank-you {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #A68C7D;
        }

        @media(max-width: 550px) {

            .navbar {
                padding: 15px 5%;
            }

            .nav-text {
                font-size: 11px;
            }

            .container {
                width: 94%;
                margin-top: 20px;
            }

            .success-header {
                padding: 28px 18px;
            }

            .success-header h1 {
                font-size: 21px;
            }

            .main-card {
                padding: 18px;
            }

            .progress-item span {
                font-size: 9px;
            }

            .progress-circle {
                width: 31px;
                height: 31px;
                font-size: 12px;
            }

            .progress::before,
            .progress-fill {
                top: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .order-number strong {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">

    <div class="logo">
        POCKE<span>TA</span>
    </div>

    <div class="nav-text">
        Pesanan Saya
    </div>

</nav>

<main class="container">

    <!-- HEADER BERHASIL -->

    <div class="success-header">

        <div class="success-icon">✓</div>

        <h1>Pesanan Berhasil!</h1>

        <p>
            Terima kasih telah berbelanja di POCKETA 🩷
            <br>
            Pesananmu berhasil kami terima.
        </p>

    </div>

    <div class="main-card">

        <!-- NOMOR PESANAN -->

        <div class="order-number">

            <div>
                <small>Nomor Pesanan</small>
                <strong id="orderNumber"><?= e($orderNumber) ?></strong>
            </div>

            <button
                type="button"
                class="copy-btn"
                onclick="copyOrderNumber()"
            >
                Salin
            </button>

        </div>

        <!-- PROGRESS PESANAN -->

        <div class="section">

            <h2 class="section-title">Perjalanan Pesanan</h2>

            <div class="progress">

                <div
                    class="progress-fill"
                    style="width: <?= ($currentStep / 4) * 84 ?>%;"
                ></div>

                <?php foreach ($progressSteps as $index => $step): ?>

                    <div class="progress-item <?= $index <= $currentStep ? 'done' : '' ?>">

                        <div class="progress-circle">
                            <?= $index < $currentStep ? '✓' : ($index + 1) ?>
                        </div>

                        <span><?= e($step) ?></span>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- INFORMASI PENERIMA -->

        <div class="section">

            <h2 class="section-title">Alamat Pengiriman</h2>

            <div class="detail-box">

                <div class="detail-row">
                    <span>Nama Penerima</span>
                    <span><?= e($order["nama_penerima"] ?? "-") ?></span>
                </div>

                <div class="detail-row">
                    <span>Nomor Telepon</span>
                    <span><?= e($order["telepon"] ?? "-") ?></span>
                </div>

                <div class="detail-row">
                    <span>Email</span>
                    <span><?= e($order["email"] ?? "-") ?></span>
                </div>

                <div class="detail-row">
                    <span>Alamat</span>
                    <span><?= e($order["alamat"] ?? "-") ?></span>
                </div>

                <?php if (!empty($order["catatan"])): ?>

                    <div class="detail-row">
                        <span>Catatan</span>
                        <span><?= e($order["catatan"]) ?></span>
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <!-- RINGKASAN PRODUK -->

        <div class="section">

            <h2 class="section-title">Rincian Pesanan</h2>

            <div class="detail-box">

                <?php foreach (($order["produk"] ?? []) as $item): ?>

                    <div class="product-row">

                        <div>
                            <div class="product-name">
                                <?= e($item["nama"] ?? "Produk") ?>
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

            </div>

            <div class="total-box">
                <span>Total Pembayaran</span>
                <strong><?= rupiah($order["total"] ?? 0) ?></strong>
            </div>

        </div>

        <!-- INFORMASI PEMBAYARAN -->

        <div class="section">

            <h2 class="section-title">Informasi Pembayaran</h2>

            <div class="payment-status">

                Metode Pembayaran:
                <strong><?= e($payment) ?></strong>

                <br>

                Status Pembayaran:

                <br>

                <span class="status-badge">
                    <?= e($statusPembayaran) ?>
                </span>

                <br><br>

                <?php if ($isCOD): ?>

                    Silakan siapkan pembayaran sesuai total pesanan
                    dan bayarkan langsung kepada kurir ketika pesanan tiba.

                <?php else: ?>

                    <?php if ($buktiSrc !== ""): ?>

                        Bukti pembayaran berhasil diunggah.
                        Pembayaran akan diperiksa oleh admin POCKETA.

                    <?php else: ?>

                        Pesanan diterima. Silakan ikuti instruksi pembayaran
                        yang tersedia dan tunggu konfirmasi dari admin.

                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <?php if ($buktiSrc !== ""): ?>

                <div class="proof-box">

                    <p>Bukti Pembayaran</p>

                    <img
                        src="<?= e($buktiSrc) ?>"
                        alt="Bukti Pembayaran"
                    >

                </div>

            <?php endif; ?>

        </div>

        <!-- TANGGAL -->

        <div class="section">

            <div class="detail-box">

                <div class="detail-row">
                    <span>Tanggal Pesanan</span>
                    <span><?= e($order["tanggal"] ?? "-") ?></span>
                </div>

                <div class="detail-row">
                    <span>Status Pesanan</span>
                    <span>
                        <strong><?= e($status) ?></strong>
                    </span>
                </div>

            </div>

        </div>

        <!-- TOMBOL -->

        <div class="action-buttons">

            <a href="user.php" class="btn btn-primary">
                Kembali ke Beranda
            </a>

            <a href="products.php" class="btn btn-secondary">
                Belanja Lagi
            </a>

        </div>

        <p class="thank-you">
            Terima kasih sudah memilih produk handmade POCKETA 🩷
        </p>

    </div>

</main>

<script>

function copyOrderNumber() {

    const orderNumber = document.getElementById("orderNumber").innerText;

    if (navigator.clipboard && window.isSecureContext) {

        navigator.clipboard.writeText(orderNumber).then(function () {
            alert("Nomor pesanan berhasil disalin!");
        });

    } else {

        const textarea = document.createElement("textarea");
        textarea.value = orderNumber;

        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        textarea.remove();

        alert("Nomor pesanan berhasil disalin!");

    }

}

</script>

</body>
</html>


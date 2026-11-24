
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$cart = $_SESSION["cart"] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

$total = 0;

foreach ($cart as $item) {
    $total += (int)($item["harga"] ?? 0) * (int)($item["jumlah"] ?? 0);
}

$nama = $_SESSION["nama"] ?? "";
$email = $_SESSION["email"] ?? "";

$transferChannels = ["BCA", "BRI", "BNI", "Mandiri", "BSI", "SeaBank"];
$ewalletChannels = ["DANA", "GoPay", "OVO", "ShopeePay", "QRIS"];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POCKETA - Checkout</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="/assets/css/user-navbar.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            display: block;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
            color: #5C4033;
        }

        .checkout-container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto 60px;
        }

        .checkout-heading {
            margin-bottom: 28px;
        }

        .checkout-heading h1 {
            font-size: 30px;
            color: #5C4033;
            margin-bottom: 8px;
        }

        .checkout-heading p {
            color: #8B6F60;
            font-size: 14px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .checkout-card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(92, 64, 51, .07);
        }

        .checkout-card h2 {
            font-size: 19px;
            margin-bottom: 22px;
            color: #5C4033;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #5C4033;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 1.5px solid #EAD6CC;
            border-radius: 11px;
            background: #FFFFFF;
            color: #5C4033;
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #D89A9A;
            box-shadow: 0 0 0 3px rgba(216, 154, 154, .15);
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        /* Kategori pembayaran */

        .payment-category {
            margin-bottom: 24px;
        }

        .category-title {
            font-size: 14px;
            font-weight: 800;
            color: #8B6F60;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 1.5px solid #EAD6CC;
            border-radius: 13px;
            margin-bottom: 10px;
            background: #FFFFFF;
            cursor: pointer;
            transition: .2s;
        }

        .payment-option:hover {
            border-color: #D89A9A;
            background: #FFF5F3;
        }

        .payment-option:has(input:checked) {
            border-color: #B77C7C;
            background: #FCEDEC;
            box-shadow: 0 0 0 2px rgba(183, 124, 124, .1);
        }

        .payment-option input {
            width: 17px;
            height: 17px;
            accent-color: #A66A6A;
            flex-shrink: 0;
        }

        .payment-option span {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #5C4033;
        }

        .payment-option small {
            display: block;
            color: #9B8174;
            font-size: 11px;
            margin-top: 4px;
        }

        .cod-category {
            padding: 16px;
            background: #F8F0E9;
            border: 1px solid #EAD6CC;
            border-radius: 15px;
        }

        .cod-category .payment-option {
            margin-bottom: 0;
        }

        /* Detail pembayaran */

        .payment-box {
            display: none;
            margin-top: 14px;
            padding: 18px;
            border: 1.5px dashed #DCC2B5;
            border-radius: 14px;
            background: #FFFFFF;
        }

        .payment-box.active {
            display: block;
            animation: fadeIn .25s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .rekening-box {
            margin-top: 14px;
            padding: 16px;
            border-radius: 12px;
            background: #FFF4F1;
            border: 1px solid #EAD6CC;
            color: #5C4033;
            font-size: 13px;
            line-height: 1.8;
        }

        .rekening-box strong {
            font-size: 20px;
            letter-spacing: 1px;
        }

        .qr-box {
            display: none;
            margin-top: 14px;
            padding: 15px;
            border-radius: 12px;
            background: #FFF4F1;
            border: 1px solid #EAD6CC;
            text-align: center;
        }

        .info-note {
            font-size: 12px;
            color: #8B6F60;
            margin-top: 9px;
            line-height: 1.6;
        }

        .cod-info {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 12px;
            padding: 16px;
            color: #8B6F60;
            font-size: 13px;
            line-height: 1.7;
        }

        .cod-info strong {
            color: #5C4033;
        }

        /* Ringkasan pesanan */

        .summary-card {
            position: sticky;
            top: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 13px;
            color: #8B6F60;
        }

        .order-item span {
            line-height: 1.5;
        }

        .order-item strong {
            color: #5C4033;
            white-space: nowrap;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1.5px solid #EAD6CC;
            color: #5C4033;
            font-weight: 800;
            font-size: 17px;
        }

        .summary-total span:last-child {
            color: #A66A6A;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: #8B6F60;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .25s;
            margin-top: 24px;
        }

        .submit-btn:hover {
            background: #5C4033;
            transform: translateY(-2px);
        }

        .secure-note {
            text-align: center;
            color: #9B8174;
            font-size: 11px;
            margin-top: 13px;
        }

        @media(max-width: 750px) {
            .checkout-container {
                width: 92%;
                margin-top: 28px;
            }

            .checkout-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .checkout-card {
                padding: 20px;
            }

            .checkout-heading h1 {
                font-size: 25px;
            }

            .summary-card {
                position: static;
            }
        }
    </style>
</head>

<body>

<?php
$activePage = "home";
include "../includes/user-navbar.php";
?>

<main class="checkout-container">

    <div class="checkout-heading">
        <h1>Checkout</h1>
        <p>Lengkapi informasi pengiriman dan pilih metode pembayaran.</p>
    </div>

    <form action="process-order.php" method="POST" enctype="multipart/form-data">

        <div class="checkout-grid">

            <div>

                <!-- ALAMAT PENGIRIMAN -->

                <section class="checkout-card">

                    <h2>Alamat Pengiriman</h2>

                    <div class="form-group">
                        <label>Nama Penerima</label>
                        <input
                            type="text"
                            name="nama_penerima"
                            value="<?= htmlspecialchars($nama) ?>"
                            placeholder="Masukkan nama penerima"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="Masukkan email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input
                            type="tel"
                            name="telepon"
                            placeholder="08xxxxxxxxxx"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Alamat Lengkap</label>
                        <textarea
                            name="alamat"
                            placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan, kota..."
                            required
                        ></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Catatan (Opsional)</label>
                        <textarea
                            name="catatan"
                            placeholder="Catatan tambahan untuk pesanan..."
                        ></textarea>
                    </div>

                </section>

                <!-- METODE PEMBAYARAN -->

                <section class="checkout-card">

                    <h2>Metode Pembayaran</h2>

                    <div class="payment-category">

                        <div class="category-title">
                            PEMBAYARAN DIGITAL
                        </div>

                        <label class="payment-option">
                            <input
                                type="radio"
                                name="kategori_pembayaran"
                                value="transfer"
                                required
                            >
                            <div>
                                <span>Transfer Bank</span>
                                <small>BCA, BRI, BNI, Mandiri, BSI, SeaBank</small>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input
                                type="radio"
                                name="kategori_pembayaran"
                                value="e-wallet"
                            >
                            <div>
                                <span>E-Wallet / QRIS</span>
                                <small>DANA, GoPay, OVO, ShopeePay, QRIS</small>
                            </div>
                        </label>

                        <!-- DETAIL TRANSFER BANK -->

                        <div id="box-transfer" class="payment-box">

                            <div class="form-group">
                                <label for="channel-transfer">Pilih Bank</label>

                                <select id="channel-transfer" name="channel_transfer">
                                    <option value="">-- Pilih Bank --</option>

                                    <?php foreach ($transferChannels as $bank): ?>
                                        <option value="<?= htmlspecialchars($bank) ?>">
                                            <?= htmlspecialchars($bank) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <div class="rekening-box">
                                <strong>901503934487</strong><br>
                                A/N Pocketa Official<br>
                                SeaBank
                            </div>

                            <p class="info-note">
                                Silakan transfer sesuai total pesanan ke rekening di atas.
                                Setelah itu, unggah bukti pembayaran.
                            </p>

                        </div>

                        <!-- DETAIL E-WALLET -->

                        <div id="box-ewallet" class="payment-box">

                            <div class="form-group">
                                <label for="channel-ewallet">Pilih E-Wallet / QRIS</label>

                                <select id="channel-ewallet" name="channel_ewallet">
                                    <option value="">-- Pilih E-Wallet / QRIS --</option>

                                    <?php foreach ($ewalletChannels as $wallet): ?>
                                        <option value="<?= htmlspecialchars($wallet) ?>">
                                            <?= htmlspecialchars($wallet) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- NOMOR E-WALLET POCKETA -->

                            <div id="ewallet-number-container" class="rekening-box" style="display: none;">

                                <strong style="font-size: 15px;">
                                    Nomor E-Wallet POCKETA
                                </strong>

                                <br>

                                <strong>+62 821-9476-0155</strong>

                                <br>
                                A/N POCKETA Official

                                <p class="info-note">
                                    Silakan transfer sesuai total pesanan ke nomor e-wallet POCKETA di atas.
                                    Setelah itu, unggah bukti pembayaran.
                                </p>

                            </div>

                            <!-- QRIS -->

                            <div id="qr-qris-container" class="qr-box">

                                <p style="color: #5C4033; margin-bottom: 12px;">
                                    <strong>Scan QRIS POCKETA</strong>
                                </p>

                                <img
                                    src="/images/qris-pocketa.png"
                                    alt="QRIS POCKETA"
                                    style="width: 220px; max-width: 100%; height: auto; border-radius: 10px;"
                                >

                                <p class="info-note">
                                    Scan kode QR menggunakan aplikasi pembayaran yang mendukung QRIS.
                                </p>

                            </div>

                        </div>

                        <!-- BUKTI PEMBAYARAN -->

                        <div id="box-bukti" class="payment-box">

                            <div class="form-group" style="margin-bottom: 0;">

                                <label for="bukti_pembayaran">
                                    Upload Bukti Pembayaran
                                </label>

                                <input
                                    type="file"
                                    id="bukti_pembayaran"
                                    name="bukti_pembayaran"
                                    accept="image/jpeg,image/png,image/webp"
                                >

                                <p class="info-note">
                                    Format JPG, PNG, atau WEBP. Ukuran maksimal 5 MB.
                                </p>

                            </div>

                        </div>

                    </div>

                    <!-- PEMBAYARAN COD -->

                    <div class="cod-category">

                        <div class="category-title">
                            PEMBAYARAN DI TEMPAT
                        </div>

                        <label class="payment-option">
                            <input
                                type="radio"
                                name="kategori_pembayaran"
                                value="cod"
                            >
                            <div>
                                <span>Cash on Delivery (COD)</span>
                                <small>Bayar langsung kepada kurir saat pesanan tiba</small>
                            </div>
                        </label>

                        <div id="box-cod" class="payment-box">

                            <div class="cod-info">
                                <strong>Informasi Pembayaran COD</strong><br><br>

                                Kamu tidak perlu melakukan pembayaran sekarang.
                                Siapkan uang sesuai total pesanan dan lakukan
                                pembayaran langsung kepada kurir ketika barang tiba.

                                <p class="info-note">
                                    Tidak perlu memilih bank, e-wallet, atau mengunggah
                                    bukti pembayaran.
                                </p>
                            </div>

                        </div>

                    </div>

                </section>

            </div>

            <!-- RINGKASAN PESANAN -->

            <div>

                <section class="checkout-card summary-card">

                    <h2>Ringkasan Pesanan</h2>

                    <?php foreach ($cart as $item): ?>

                        <div class="order-item">

                            <span>
                                <?= htmlspecialchars((string)($item["nama"] ?? "Produk")) ?>
                                × <?= (int)($item["jumlah"] ?? 0) ?>
                            </span>

                            <strong>
                                Rp<?= number_format(
                                    (int)($item["harga"] ?? 0) * (int)($item["jumlah"] ?? 0),
                                    0,
                                    ",",
                                    "."
                                ) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                    <div class="summary-total">
                        <span>Total Pembayaran</span>

                        <span>
                            Rp<?= number_format($total, 0, ",", ".") ?>
                        </span>
                    </div>

                    <button type="submit" class="submit-btn">
                        Konfirmasi Pesanan
                    </button>

                    <p class="secure-note">
                        Terima kasih telah berbelanja di POCKETA 🩷
                    </p>

                </section>

            </div>

        </div>

    </form>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Pilihan metode pembayaran
    const radios = document.querySelectorAll(
        'input[name="kategori_pembayaran"]'
    );

    // Kotak pembayaran
    const boxTransfer = document.getElementById("box-transfer");
    const boxEwallet = document.getElementById("box-ewallet");
    const boxBukti = document.getElementById("box-bukti");
    const boxCod = document.getElementById("box-cod");

    // Detail e-wallet
    const ewalletNumberContainer = document.getElementById(
        "ewallet-number-container"
    );

    const qrQrisContainer = document.getElementById(
        "qr-qris-container"
    );

    // Input pembayaran
    const selectTransfer = document.getElementById("channel-transfer");
    const selectEwallet = document.getElementById("channel-ewallet");
    const inputBukti = document.getElementById("bukti_pembayaran");


    // Mengatur tampilan metode pembayaran
    function updatePaymentUI() {

        const selected = document.querySelector(
            'input[name="kategori_pembayaran"]:checked'
        );

        const value = selected ? selected.value : "";

        // Sembunyikan seluruh detail pembayaran
        boxTransfer.classList.remove("active");
        boxEwallet.classList.remove("active");
        boxBukti.classList.remove("active");
        boxCod.classList.remove("active");

        ewalletNumberContainer.style.display = "none";
        qrQrisContainer.style.display = "none";

        // Reset input wajib
        selectTransfer.required = false;
        selectEwallet.required = false;
        inputBukti.required = false;

        // Transfer Bank
        if (value === "transfer") {

            boxTransfer.classList.add("active");
            boxBukti.classList.add("active");

            selectTransfer.required = true;
            inputBukti.required = true;

        }

        // E-Wallet
        else if (value === "e-wallet") {

            boxEwallet.classList.add("active");
            boxBukti.classList.add("active");

            selectEwallet.required = true;
            inputBukti.required = true;

            updateEwalletDetails();

        }

        // COD
        else if (value === "cod") {

            boxCod.classList.add("active");

        }

    }


    // Mengatur detail berdasarkan pilihan e-wallet
    function updateEwalletDetails() {

        ewalletNumberContainer.style.display = "none";
        qrQrisContainer.style.display = "none";

        const selectedWallet = selectEwallet.value;

        // DANA, GoPay, OVO, ShopeePay
        if (
            selectedWallet === "DANA" ||
            selectedWallet === "GoPay" ||
            selectedWallet === "OVO" ||
            selectedWallet === "ShopeePay"
        ) {

            ewalletNumberContainer.style.display = "block";

        }

        // QRIS
        else if (selectedWallet === "QRIS") {

            qrQrisContainer.style.display = "block";

        }

    }


    // Event perubahan metode pembayaran
    radios.forEach(function (radio) {

        radio.addEventListener("change", updatePaymentUI);

    });


    // Event perubahan e-wallet
    selectEwallet.addEventListener("change", updateEwalletDetails);


    // Jalankan saat halaman pertama kali dibuka
    updatePaymentUI();

});
</script>

</body>
</html>

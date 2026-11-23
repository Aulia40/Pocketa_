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

$nama  = $_SESSION["nama"] ?? "";
$email = $_SESSION["email"] ?? "";

$transferChannels = ["BCA", "BRI", "BNI", "Mandiri", "BSI", "SeaBank"];
$ewalletChannels  = ["DANA", "GoPay", "OVO", "ShopeePay", "QRIS"];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>POCKETA - Checkout</title>

    <link rel="stylesheet" href="../style.css">

    <style>
        body {
            display: block;
            padding: 0;
            min-height: 100vh;
        }

        .navbar {
            background: #FFF9F6;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 15px rgba(92,64,51,.08);
        }

        .logo {
            font-size: 25px;
            font-weight: 800;
            color: #5C4033;
        }

        .logo span {
            color: #D89A9A;
        }

        .back-btn {
            text-decoration: none;
            color: #8B6F60;
            font-size: 13px;
        }

        .checkout-container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .checkout-container h1 {
            margin-bottom: 25px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 22px;
        }

        .checkout-card {
            background: #FFF9F6;
            border: 1.5px solid #EAD6CC;
            border-radius: 18px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(92,64,51,.06);
        }

        .checkout-card h2 {
            font-size: 19px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #5C4033;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #EAD6CC;
            border-radius: 10px;
            background: white;
            color: #5C4033;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #D89A9A;
        }

        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }

        .payment-option {
            display: block;
            padding: 13px;
            border: 1px solid #EAD6CC;
            border-radius: 10px;
            margin-bottom: 10px;
            color: #8B6F60;
            font-size: 14px;
            cursor: pointer;
        }

        .payment-option input {
            margin-right: 8px;
            accent-color: #A66A6A;
        }

        .payment-box {
            display: none;
            margin-top: 14px;
            padding: 16px;
            border: 1.5px dashed #EAD6CC;
            border-radius: 12px;
            background: #FFFFFF;
        }

        .payment-box.active {
            display: block;
        }

        .rekening-box {
            margin-top: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            color: #5C4033;
            font-size: 13px;
            line-height: 1.6;
        }

        .qr-box {
            display: none;
            text-align: center;
            margin: 15px 0;
            padding: 14px;
            border-radius: 12px;
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
        }

        .info-note {
            font-size: 12px;
            color: #8B6F60;
            margin-top: 6px;
            line-height: 1.5;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #EAD6CC;
            font-size: 13px;
            color: #8B6F60;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            color: #5C4033;
            font-weight: 700;
            font-size: 17px;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 11px;
            background: #8B6F60;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #5C4033;
        }

        @media(max-width: 700px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .checkout-container {
                width: 92%;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">
        POCKE<span>TA</span>
    </div>
    <a href="cart.php" class="back-btn">
        ← Kembali ke Keranjang
    </a>
</nav>

<main class="checkout-container">

    <h1>Checkout 🩷</h1>

    <form action="process-order.php" method="POST" enctype="multipart/form-data">

        <div class="checkout-grid">

            <div>

                <section class="checkout-card">

                    <h2>📍 Alamat Pengiriman</h2>

                    <div class="form-group">
                        <label>Nama Penerima</label>
                        <input
                            type="text"
                            name="nama_penerima"
                            value="<?= htmlspecialchars($nama) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
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

                    <div class="form-group">
                        <label>Catatan (Opsional)</label>
                        <textarea
                            name="catatan"
                            placeholder="Catatan tambahan untuk pesanan..."
                        ></textarea>
                    </div>

                </section>

                <section class="checkout-card">

                    <h2>💳 Metode Pembayaran</h2>

                    <label class="payment-option">
                        <input
                            type="radio"
                            name="kategori_pembayaran"
                            value="transfer"
                            required
                        >
                        Transfer Bank
                    </label>

                    <label class="payment-option">
                        <input
                            type="radio"
                            name="kategori_pembayaran"
                            value="e-wallet"
                        >
                        E-Wallet / QRIS
                    </label>

                    <label class="payment-option">
                        <input
                            type="radio"
                            name="kategori_pembayaran"
                            value="cod"
                        >
                        Cash on Delivery (COD)
                    </label>

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
                            A/N Pocketa official<br>
                            SeaBank
                        </div>

                        <p class="info-note">
                            Silakan transfer sesuai total pesanan lalu unggah bukti pembayaran di bawah.
                        </p>
                    </div>

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

                        <div id="qr-dana-container" class="qr-box">
                            <p class="info-note" style="margin-top: 0; color: #5C4033;">
                                <strong>Pocketa official</strong><br>
                                +62 821-9476-0155
                            </p>
                        </div>

                        <p class="info-note">
                            Setelah melakukan pembayaran, unggah screenshot bukti transaksi di bawah.
                        </p>
                    </div>

                    <div id="box-bukti" class="payment-box">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="bukti_pembayaran">Upload Bukti Pembayaran</label>
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

                    <div id="box-cod" class="payment-box">
                        <p class="info-note" style="margin-top: 0;">
                            Pembayaran dilakukan langsung kepada kurir saat pesanan tiba. Tidak perlu upload bukti pembayaran.
                        </p>
                    </div>

                </section>

            </div>

            <div>

                <section class="checkout-card">

                    <h2>🛍️ Ringkasan Pesanan</h2>

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
                        <span>Total</span>
                        <span>
                            Rp<?= number_format(
                                $total,
                                0,
                                ",",
                                "."
                            ) ?>
                        </span>
                    </div>

                    <button type="submit" class="submit-btn">
                        Konfirmasi Pesanan
                    </button>

                </section>

            </div>

        </div>

    </form>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const radios = document.querySelectorAll('input[name="kategori_pembayaran"]');
    const boxTransfer = document.getElementById("box-transfer");
    const boxEwallet = document.getElementById("box-ewallet");
    const boxBukti = document.getElementById("box-bukti");
    const boxCod = document.getElementById("box-cod");
    const qrDanaContainer = document.getElementById("qr-dana-container");

    const selectTransfer = document.getElementById("channel-transfer");
    const selectEwallet = document.getElementById("channel-ewallet");
    const inputBukti = document.getElementById("bukti_pembayaran");

    function updatePaymentUI() {
        const selected = document.querySelector('input[name="kategori_pembayaran"]:checked');
        const val = selected ? selected.value : "";

        boxTransfer.classList.remove("active");
        boxEwallet.classList.remove("active");
        boxBukti.classList.remove("active");
        boxCod.classList.remove("active");
        qrDanaContainer.style.display = "none";

        if (selectTransfer) selectTransfer.required = false;
        if (selectEwallet) selectEwallet.required = false;
        if (inputBukti) inputBukti.required = false;

        if (val === "transfer") {
            boxTransfer.classList.add("active");
            boxBukti.classList.add("active");
            if (selectTransfer) selectTransfer.required = true;
            if (inputBukti) inputBukti.required = true;
        } else if (val === "e-wallet") {
            boxEwallet.classList.add("active");
            boxBukti.classList.add("active");
            qrDanaContainer.style.display = "block";
            if (selectEwallet) selectEwallet.required = true;
            if (inputBukti) inputBukti.required = true;
        } else if (val === "cod") {
            boxCod.classList.add("active");
        }
    }

    radios.forEach((radio) => {
        radio.addEventListener("change", updatePaymentUI);
    });

    if (selectTransfer) selectTransfer.addEventListener("change", updatePaymentUI);
    if (selectEwallet) selectEwallet.addEventListener("change", updatePaymentUI);

    updatePaymentUI();
});
</script>

</body>
</html>
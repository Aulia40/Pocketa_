
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
    $total += $item["harga"] * $item["jumlah"];
}

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];

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
        .form-group textarea {
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
        .form-group textarea:focus {
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

    <form action="process-order.php" method="POST">

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
                            name="pembayaran"
                            value="Transfer Bank"
                            required
                        >
                        Transfer Bank
                    </label>

                    <label class="payment-option">
                        <input
                            type="radio"
                            name="pembayaran"
                            value="E-Wallet"
                        >
                        E-Wallet
                    </label>

                    <label class="payment-option">
                        <input
                            type="radio"
                            name="pembayaran"
                            value="COD"
                        >
                        Cash on Delivery (COD)
                    </label>

                </section>

            </div>

            <div>

                <section class="checkout-card">

                    <h2>🛍️ Ringkasan Pesanan</h2>

                    <?php foreach ($cart as $item): ?>

                        <div class="order-item">

                            <span>
                                <?= htmlspecialchars($item["nama"]) ?>
                                × <?= (int)$item["jumlah"] ?>
                            </span>

                            <strong>
                                Rp<?= number_format(
                                    $item["harga"] * $item["jumlah"],
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

</body>
</html>

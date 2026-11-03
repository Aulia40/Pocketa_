
<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../dashboard/user.php");
    exit;
}

require_once "../config/database.php";

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];

/* =========================
   STATISTIK DASHBOARD
========================= */

// Total produk
$totalProduk = $pdo->query(
    "SELECT COUNT(*) FROM products"
)->fetchColumn();

// Total pesanan
$totalPesanan = $pdo->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

// Total pengguna
$totalPengguna = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'user'"
)->fetchColumn();

// Pesanan menunggu pembayaran
$pesananPending = $pdo->query(
    "SELECT COUNT(*) FROM orders
     WHERE status_pesanan = 'Menunggu Pembayaran'"
)->fetchColumn();

// Total pendapatan dari pesanan yang sudah selesai
$totalPendapatan = $pdo->query(
    "SELECT COALESCE(SUM(total_harga), 0)
     FROM orders
     WHERE status_pesanan = 'Selesai'"
)->fetchColumn();

/* =========================
   PESANAN TERBARU
========================= */

$stmt = $pdo->query(
    "SELECT
        id,
        order_number,
        nama_penerima,
        total_harga,
        status_pesanan,
        created_at
     FROM orders
     ORDER BY created_at DESC
     LIMIT 5"
);

$pesananTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>POCKETA | Admin Dashboard</title>

<link rel="stylesheet" href="../style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    display: block;
    padding: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    font-family: 'DM Sans', sans-serif;
    color: #5C4033;
}

/* NAVBAR */

.navbar {
    background: #FFF9F6;
    padding: 18px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(92,64,51,.07);
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 2px;
}

.logo span {
    color: #D89A9A;
}

.admin-label {
    font-size: 10px;
    background: #FBE9E7;
    color: #B47C7C;
    padding: 6px 12px;
    border-radius: 20px;
    margin-left: 8px;
    font-weight: 700;
}

.logout {
    background: #5C4033;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 25px;
    font-size: 12px;
    transition: .3s;
}

.logout:hover {
    background: #D89A9A;
}

/* MAIN */

.container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
}

/* WELCOME */

.welcome {
    background: #FFF9F6;
    padding: 35px;
    border-radius: 23px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(92,64,51,.07);
}

.welcome span {
    font-size: 11px;
    color: #B47C7C;
    font-weight: 700;
    letter-spacing: 1px;
}

.welcome h1 {
    font-family: 'Playfair Display', serif;
    font-size: 30px;
    margin: 12px 0;
}

.welcome p {
    font-size: 13px;
    color: #927B6E;
    line-height: 1.8;
}

/* STATISTICS */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 22px;
}

.stat-card {
    background: #FFF9F6;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(92,64,51,.06);
    border: 1px solid #EAD6CC;
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FBE9E7;
    color: #B47C7C;
    font-size: 20px;
}

.stat-card:nth-child(2) .stat-icon {
    background: #F3E8DF;
    color: #99745F;
}

.stat-card:nth-child(3) .stat-icon {
    background: #F8E4E5;
    color: #C17E86;
}

.stat-card h3 {
    font-size: 12px;
    color: #927B6E;
    font-weight: 600;
}

.stat-number {
    font-size: 29px;
    font-weight: 800;
    margin-top: 5px;
}

.stat-extra {
    margin-top: 8px;
    font-size: 11px;
    color: #A58B7D;
}

/* SECONDARY STATS */

.secondary-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 35px;
}

.secondary-card {
    background: #FFF9F6;
    border-radius: 18px;
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 18px;
    border: 1px solid #EAD6CC;
}

.secondary-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    background: #FBE9E7;
    color: #B47C7C;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.secondary-card h3 {
    font-size: 12px;
    color: #927B6E;
}

.secondary-card strong {
    font-size: 20px;
    display: block;
    margin-top: 5px;
}

/* SECTION */

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 23px;
}

.section-heading p {
    color: #927B6E;
    font-size: 12px;
    margin-top: 5px;
}

/* ORDERS */

.order-container {
    background: #FFF9F6;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(92,64,51,.06);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th {
    text-align: left;
    font-size: 11px;
    color: #A58B7D;
    padding: 15px 12px;
    border-bottom: 1px solid #EAD6CC;
}

td {
    padding: 17px 12px;
    font-size: 12px;
    border-bottom: 1px solid #F3E8E2;
}

.order-number {
    font-weight: 700;
    color: #5C4033;
}

.customer {
    font-weight: 600;
}

.price {
    font-weight: 700;
    color: #8B6F60;
}

.status {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-pending {
    background: #FFF0D8;
    color: #A87932;
}

.status-processing {
    background: #E8EFFB;
    color: #5676A6;
}

.status-shipping {
    background: #E9E5F8;
    color: #7564A5;
}

.status-completed {
    background: #E2F2E5;
    color: #56805D;
}

.status-cancelled {
    background: #FBE4E4;
    color: #B65F5F;
}

.empty {
    text-align: center;
    padding: 40px 15px;
    color: #A58B7D;
}

.empty i {
    font-size: 35px;
    color: #D89A9A;
    margin-bottom: 15px;
}

/* BUTTON */

.view-all {
    display: inline-block;
    text-decoration: none;
    background: #5C4033;
    color: white;
    padding: 10px 18px;
    border-radius: 25px;
    font-size: 11px;
    font-weight: 600;
    transition: .3s;
}

.view-all:hover {
    background: #D89A9A;
}

/* ACCOUNT */

.account {
    background: #FFF9F6;
    padding: 25px;
    border-radius: 20px;
    margin-top: 30px;
    border: 1px solid #EAD6CC;
}

.account h2 {
    font-family: 'Playfair Display', serif;
    font-size: 21px;
    margin-bottom: 18px;
}

.account p {
    font-size: 13px;
    margin-bottom: 10px;
    color: #927B6E;
}

.account strong {
    color: #5C4033;
}

/* FOOTER */

footer {
    text-align: center;
    padding: 30px;
    color: #927B6E;
    font-size: 11px;
}

footer span {
    color: #C17E86;
}

/* MOBILE */

@media(max-width: 750px) {

    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .secondary-stats {
        grid-template-columns: 1fr;
    }

}

@media(max-width: 500px) {

    .navbar {
        padding: 16px 5%;
    }

    .logo {
        font-size: 21px;
    }

    .admin-label {
        font-size: 9px;
    }

    .container {
        width: 92%;
        margin: 25px auto;
    }

    .welcome {
        padding: 25px;
    }

    .welcome h1 {
        font-size: 24px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .section-heading {
        align-items: flex-start;
        gap: 12px;
    }

    .section-heading h2 {
        font-size: 20px;
    }

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <div>
        <span class="logo">POCKE<span>TA.</span></span>
        <span class="admin-label">ADMIN</span>
    </div>

    <a href="../auth/logout.php" class="logout">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
    </a>

</nav>


<main class="container">

    <!-- WELCOME -->

    <section class="welcome">

        <span>
            <i class="fa-solid fa-chart-line"></i>
            ADMIN CONTROL PANEL
        </span>

        <h1>Halo, <?= htmlspecialchars($nama) ?>!</h1>

        <p>
            Selamat datang di dashboard admin POCKETA.
            Pantau aktivitas toko, kelola produk, dan
            periksa pesanan pelanggan di sini.
        </p>

    </section>


    <!-- STATISTIK -->

    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>

            <h3>Total Produk</h3>

            <div class="stat-number">
                <?= number_format($totalProduk) ?>
            </div>

            <div class="stat-extra">
                Produk dalam database
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>

            <h3>Total Pesanan</h3>

            <div class="stat-number">
                <?= number_format($totalPesanan) ?>
            </div>

            <div class="stat-extra">
                <?= number_format($pesananPending) ?> pesanan menunggu pembayaran
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <h3>Total Pengguna</h3>

            <div class="stat-number">
                <?= number_format($totalPengguna) ?>
            </div>

            <div class="stat-extra">
                Pelanggan terdaftar
            </div>

        </div>

    </section>


    <!-- PENDAPATAN -->

    <section class="secondary-stats">

        <div class="secondary-card">

            <div class="secondary-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <h3>Menunggu Pembayaran</h3>
                <strong><?= number_format($pesananPending) ?></strong>
            </div>

        </div>


        <div class="secondary-card">

            <div class="secondary-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>

            <div>
                <h3>Total Pendapatan Pesanan Selesai</h3>
                <strong>
                    Rp<?= number_format((float)$totalPendapatan, 0, ',', '.') ?>
                </strong>
            </div>

        </div>

    </section>


    <!-- PESANAN TERBARU -->

    <section>

        <div class="section-heading">

            <div>
                <h2>Pesanan Terbaru</h2>
                <p>Pesanan yang masuk dari pelanggan POCKETA.</p>
            </div>

            <a href="orders.php" class="view-all">
                Lihat Semua <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>


        <div class="order-container">

            <?php if (count($pesananTerbaru) > 0): ?>

            <table>

                <thead>

                    <tr>
                        <th>Nomor Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($pesananTerbaru as $order): ?>

                    <?php

                    $status = $order["status_pesanan"];

                    $statusClass = "status-pending";

                    if ($status === "Diproses") {
                        $statusClass = "status-processing";
                    } elseif ($status === "Dikirim") {
                        $statusClass = "status-shipping";
                    } elseif ($status === "Selesai") {
                        $statusClass = "status-completed";
                    } elseif ($status === "Dibatalkan") {
                        $statusClass = "status-cancelled";
                    }

                    ?>

                    <tr>

                        <td class="order-number">
                            <?= htmlspecialchars($order["order_number"]) ?>
                        </td>

                        <td class="customer">
                            <?= htmlspecialchars($order["nama_penerima"]) ?>
                        </td>

                        <td>
                            <?= date("d/m/Y", strtotime($order["created_at"])) ?>
                        </td>

                        <td class="price">
                            Rp<?= number_format((float)$order["total_harga"], 0, ',', '.') ?>
                        </td>

                        <td>
                            <span class="status <?= $statusClass ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-box-open"></i>

                <p>Belum ada pesanan yang masuk.</p>

                <small>
                    Pesanan pelanggan akan muncul di sini setelah checkout berhasil.
                </small>

            </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- ACCOUNT -->

    <section class="account">

        <h2>Informasi Admin</h2>

        <p>
            <strong>Nama:</strong>
            <?= htmlspecialchars($nama) ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?= htmlspecialchars($email) ?>
        </p>

        <p>
            <strong>Role:</strong> Administrator
        </p>

    </section>

</main>


<footer>

    © 2026 <strong>POCKETA</strong>.
    Made with <span>♥</span> for handmade lovers.

</footer>

</body>
</html>
```
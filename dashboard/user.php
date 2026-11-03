<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SESSION["role"] !== "user") {
    header("Location: ../login.html");
    exit;
}

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];

$inisial = strtoupper(substr($nama, 0, 1));

?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>POCKETA | My Dashboard</title>

<link rel="stylesheet" href="../style.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* DIPERBAIKI: Mengubah - menjadi * untuk CSS Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    display: block;
    min-height: 100vh;
    padding: 0;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    font-family: 'DM Sans', sans-serif;
    color: #5C4033;
}

/* NAVBAR */

.navbar {
    width: 100%;
    padding: 18px 7%;
    background: rgba(255,249,246,0.92);
    backdrop-filter: blur(15px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 20px rgba(92,64,51,0.06);
}

.logo {
    font-size: 27px;
    font-weight: 800;
    letter-spacing: 2px;
    color: #5C4033;
}

.logo span {
    color: #D89A9A;
}

.nav-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.nav-user {
    font-size: 13px;
    color: #8B6F60;
    font-weight: 600;
}

.logout {
    text-decoration: none;
    padding: 10px 20px;
    background: #5C4033;
    color: white;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    transition: .3s;
}

.logout:hover {
    background: #D89A9A;
    transform: translateY(-2px);
}

/* MAIN */

.container {
    width: 88%;
    max-width: 1150px;
    margin: 45px auto;
}

/* WELCOME */

.welcome {
    background: #FFF9F6;
    border-radius: 25px;
    padding: 42px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    box-shadow: 0 12px 35px rgba(92,64,51,.08);
    position: relative;
    overflow: hidden;
    margin-bottom: 35px;
}

.welcome::after {
    content: "♡";
    position: absolute;
    right: 45px;
    top: -45px;
    font-size: 190px;
    color: rgba(216,154,154,.13);
}

.welcome-content {
    position: relative;
    z-index: 1;
}

.welcome-label {
    display: inline-block;
    background: #FBE9E7;
    color: #B47C7C;
    padding: 8px 15px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 17px;
}

.welcome h1 {
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    margin-bottom: 12px;
    color: #5C4033;
}

.welcome p {
    font-size: 14px;
    line-height: 1.8;
    color: #927B6E;
    max-width: 470px;
}

.welcome-icon {
    position: relative;
    z-index: 1;
    width: 115px;
    height: 115px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F4C7C3, #EAD6CC);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    flex-shrink: 0;
}

/* SECTION TITLE */

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    margin-bottom: 20px;
}

.section-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 25px;
    margin-bottom: 5px;
}

.section-heading p {
    font-size: 12px;
    color: #927B6E;
}

/* MENU CARDS */

.menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.menu-card {
    background: #FFF9F6;
    padding: 28px;
    border-radius: 22px;
    text-decoration: none;
    color: #5C4033;
    border: 1px solid rgba(255,255,255,.8);
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
    transition: .3s ease;
    position: relative;
    overflow: hidden;
}

.menu-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 35px rgba(92,64,51,.13);
    border-color: #D89A9A;
}

.menu-icon {
    width: 58px;
    height: 58px;
    background: #FBE9E7;
    border-radius: 17px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 23px;
    color: #B47C7C;
    margin-bottom: 22px;
}

.menu-card:nth-child(2) .menu-icon {
    background: #F3E8DF;
    color: #99745F;
}

.menu-card:nth-child(3) .menu-icon {
    background: #F8E4E5;
    color: #C17E86;
}

.menu-card h3 {
    font-size: 17px;
    margin-bottom: 9px;
}

.menu-card p {
    color: #927B6E;
    font-size: 12px;
    line-height: 1.7;
    min-height: 40px;
}

.card-arrow {
    display: flex;
    justify-content: flex-end;
    color: #D89A9A;
    margin-top: 20px;
}

/* ACCOUNT */

.account-section {
    margin-top: 38px;
}

.account-card {
    background: #FFF9F6;
    padding: 30px;
    border-radius: 22px;
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
}

.account-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 23px;
    border-bottom: 1px solid #F0DED7;
    margin-bottom: 23px;
}

.avatar {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #F4C7C3, #D89A9A);
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-size: 22px;
    font-weight: 700;
}

.account-header h3 {
    font-size: 16px;
    margin-bottom: 4px;
}

.account-header p {
    font-size: 12px;
    color: #A58B7D;
}

.account-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.detail-item {
    background: #FDF2EF;
    padding: 17px 20px;
    border-radius: 14px;
}

.detail-item span {
    font-size: 11px;
    color: #A58B7D;
    display: block;
    margin-bottom: 7px;
}

.detail-item strong {
    font-size: 13px;
    overflow-wrap: anywhere;
}

/* FOOTER */

.footer {
    text-align: center;
    padding: 30px 15px;
    color: #927B6E;
    font-size: 11px;
    margin-top: 35px;
}

.footer span {
    color: #C17E86;
}

/* RESPONSIVE */

@media(max-width: 800px) {

    .menu-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .container {
        width: 92%;
    }

}

@media(max-width: 600px) {

    .navbar {
        padding: 16px 5%;
    }

    .logo {
        font-size: 22px;
    }

    .nav-user {
        display: none;
    }

    .container {
        margin: 28px auto;
    }

    .welcome {
        padding: 28px 23px;
    }

    .welcome h1 {
        font-size: 26px;
    }

    .welcome-icon {
        display: none;
    }

    .menu-grid {
        grid-template-columns: 1fr;
    }

    .account-details {
        grid-template-columns: 1fr;
    }

    .section-heading h2 {
        font-size: 22px;
    }

}

</style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <div class="logo">
        POCKE<span>TA.</span>
    </div>

    <div class="nav-right">

        <span class="nav-user">
            <i class="fa-regular fa-user"></i>
            <?= htmlspecialchars($nama) ?>
        </span>

        <a href="../auth/logout.php" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Logout
        </a>

    </div>

</nav>


<!-- MAIN CONTENT -->

<main class="container">

    <!-- WELCOME -->

    <section class="welcome">

        <div class="welcome-content">

            <span class="welcome-label">
                <i class="fa-solid fa-heart"></i>
                WELCOME TO POCKETA
            </span>

            <h1>
                Halo, <?= htmlspecialchars($nama) ?>!
            </h1>

            <p>
                Senang melihatmu kembali.
                Temukan berbagai produk handmade yang unik,
                lucu, dan dibuat dengan penuh kreativitas.
                Yuk, jelajahi koleksi favoritmu!
            </p>

        </div>

        <div class="welcome-icon">
            <i class="fa-solid fa-gift"></i>
        </div>

    </section>


    <!-- MENU -->

    <section>

        <div class="section-heading">

            <div>
                <h2>Explore POCKETA</h2>
                <p>Apa yang ingin kamu lakukan hari ini?</p>
            </div>

        </div>


        <div class="menu-grid">

            <a href="products.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>

                <h3>Produk</h3>

                <p>
                    Jelajahi koleksi dompet kain,
                    tassel, dan produk handmade lainnya.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>


            <a href="order-history.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <h3>Pesanan Saya</h3>

                <p>
                    Pantau status pesanan dan lihat
                    riwayat pembelian produkmu.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>


            <a href="profile.php" class="menu-card">

                <div class="menu-icon">
                    <i class="fa-solid fa-user-pen"></i>
                </div>

                <h3>Profil</h3>

                <p>
                    Lihat dan kelola informasi
                    akun POCKETA milikmu.
                </p>

                <div class="card-arrow">
                    <i class="fa-solid fa-arrow-right"></i>
                </div>

            </a>

        </div>

    </section>


    <!-- ACCOUNT INFORMATION -->

    <section class="account-section">

        <div class="section-heading">

            <div>
                <h2>Informasi Akun</h2>
                <p>Detail akun yang sedang kamu gunakan.</p>
            </div>

        </div>


        <div class="account-card">

            <div class="account-header">

                <div class="avatar">
                    <?= htmlspecialchars($inisial) ?>
                </div>

                <div>

                    <h3>
                        <?= htmlspecialchars($nama) ?>
                    </h3>

                    <p>POCKETA Member</p>

                </div>

            </div>


            <div class="account-details">

                <div class="detail-item">

                    <span>
                        <i class="fa-regular fa-user"></i>
                        Nama Lengkap
                    </span>

                    <strong>
                        <?= htmlspecialchars($nama) ?>
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        <i class="fa-regular fa-envelope"></i>
                        Email Address
                    </span>

                    <strong>
                        <?= htmlspecialchars($email) ?>
                    </strong>

                </div>

            </div>

        </div>

    </section>


</main>


<!-- FOOTER -->

<footer class="footer">

    <p>
        © 2026 <strong>POCKETA</strong>.
        Made with <span>♥</span> for handmade lovers.
    </p>

</footer>

</body>
</html>
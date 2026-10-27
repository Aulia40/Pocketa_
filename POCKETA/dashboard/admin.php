<?php

session_start();

/* =========================
   CEK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

/* =========================
   CEK ROLE ADMIN
========================= */

if ($_SESSION["role"] !== "admin") {
    header("Location: ../dashboard/user.php");
    exit;
}

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>POCKETA - Admin Dashboard</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        /* =========================
           ADMIN DASHBOARD
        ========================= */

        body {
            display: block;
            padding: 0;
            min-height: 100vh;
        }

        /* NAVBAR */

        .admin-navbar {
            width: 100%;
            background: #FFF9F6;
            padding: 18px 6%;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow:
                0 3px 15px
                rgba(92, 64, 51, 0.08);
        }

        .admin-logo {
            font-size: 25px;
            font-weight: 800;
            color: #5C4033;
            letter-spacing: 1px;
        }

        .admin-logo span {
            color: #D89A9A;
        }

        .admin-label {
            font-size: 12px;
            color: #A66A6A;
            margin-left: 8px;
            font-weight: 600;
        }

        .logout-btn {
            text-decoration: none;
            padding: 9px 18px;

            border-radius: 10px;
            border: 1.5px solid #EAD6CC;

            color: #8B6F60;
            font-size: 13px;
            font-weight: 600;

            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #F4C7C3;
            color: #5C4033;
            border-color: #D89A9A;
        }

        /* MAIN */

        .admin-dashboard {
            width: 90%;
            max-width: 1100px;
            margin: 45px auto;
        }

        /* WELCOME */

        .admin-welcome {
            background: #FFF9F6;
            padding: 35px;

            border-radius: 22px;

            box-shadow:
                0 12px 35px
                rgba(92, 64, 51, 0.10);

            margin-bottom: 30px;
        }

        .admin-welcome h1 {
            color: #5C4033;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .admin-welcome p {
            margin-bottom: 0;
        }

        /* STATISTIC */

        .admin-stats {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #FFF9F6;

            padding: 25px;

            border-radius: 18px;

            border: 1.5px solid #EAD6CC;

            box-shadow:
                0 8px 25px
                rgba(92, 64, 51, 0.07);
        }

        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #8B6F60;
            margin-bottom: 7px;
        }

        .stat-number {
            font-size: 27px;
            font-weight: 700;
            color: #5C4033;
        }

        /* MENU */

        .admin-menu {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
        }

        .admin-card {
            background: #FFF9F6;

            padding: 28px;

            border-radius: 20px;

            border: 1.5px solid #EAD6CC;

            text-decoration: none;

            color: #5C4033;

            box-shadow:
                0 8px 25px
                rgba(92, 64, 51, 0.07);

            transition: 0.3s;
        }

        .admin-card:hover {
            transform: translateY(-4px);

            border-color: #D89A9A;

            box-shadow:
                0 12px 30px
                rgba(92, 64, 51, 0.12);
        }

        .admin-card-icon {
            font-size: 32px;
            margin-bottom: 14px;
        }

        .admin-card h3 {
            color: #5C4033;
            margin-bottom: 8px;
        }

        .admin-card p {
            font-size: 13px;
            margin-bottom: 0;
        }

        /* ACCOUNT */

        .admin-account {
            margin-top: 25px;

            background: #FFF9F6;

            padding: 28px;

            border-radius: 20px;

            border: 1.5px solid #EAD6CC;

            box-shadow:
                0 8px 25px
                rgba(92, 64, 51, 0.07);
        }

        .admin-account h2 {
            margin-bottom: 18px;
        }

        .admin-account p {
            margin-bottom: 8px;
        }

        /* MOBILE */

        @media (max-width: 700px) {

            .admin-navbar {
                padding: 16px 5%;
            }

            .admin-dashboard {
                width: 92%;
                margin: 30px auto;
            }

            .admin-stats,
            .admin-menu {
                grid-template-columns: 1fr;
            }

            .admin-welcome {
                padding: 28px 23px;
            }

            .admin-welcome h1 {
                font-size: 24px;
            }

        }

    </style>

</head>

<body>

    <!-- NAVBAR -->

    <nav class="admin-navbar">

        <div>

            <span class="admin-logo">
                POCKE<span>TA</span>
            </span>

            <span class="admin-label">
                ADMIN
            </span>

        </div>

        <a
            href="../auth/logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </nav>


    <!-- DASHBOARD -->

    <main class="admin-dashboard">

        <!-- WELCOME -->

        <section class="admin-welcome">

            <h1>
                Halo, <?= htmlspecialchars($nama) ?>! 
            </h1>

            <p>
                Selamat datang di halaman admin POCKETA.
                Kelola produk, pesanan, dan pengguna dari sini.
            </p>

        </section>


        <!-- STATISTIK -->

        <section class="admin-stats">

            <div class="stat-card">

                <div class="stat-icon">
                    
                </div>

                <h3>
                    Total Produk
                </h3>

                <div class="stat-number">
                    0
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    
                </div>

                <h3>
                    Total Pesanan
                </h3>

                <div class="stat-number">
                    0
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    
                </div>

                <h3>
                    Total Pengguna
                </h3>

                <div class="stat-number">
                    0
                </div>

            </div>

        </section>


        <!-- MENU ADMIN -->

        <section class="admin-menu">

            <a
                href="#"
                class="admin-card"
            >

                <div class="admin-card-icon">
                    
                </div>

                <h3>
                    Kelola Produk
                </h3>

                <p>
                    Tambah, edit, dan hapus
                    produk POCKETA.
                </p>

            </a>


            <a
                href="#"
                class="admin-card"
            >

                <div class="admin-card-icon">
                    
                </div>

                <h3>
                    Kelola Pesanan
                </h3>

                <p>
                    Lihat dan kelola pesanan
                    dari pelanggan.
                </p>

            </a>


            <a
                href="#"
                class="admin-card"
            >

                <div class="admin-card-icon">
                    
                </div>

                <h3>
                    Data Pengguna
                </h3>

                <p>
                    Lihat data pengguna
                    yang terdaftar.
                </p>

            </a>

        </section>


        <!-- ACCOUNT -->

        <section class="admin-account">

            <h2>
                Informasi Admin
            </h2>

            <p>
                <strong>Nama:</strong>
                <?= htmlspecialchars($nama) ?>
            </p>

            <p>
                <strong>Email:</strong>
                <?= htmlspecialchars($email) ?>
            </p>

            <p>
                <strong>Role:</strong>
                Admin
            </p>

        </section>

    </main>

</body>

</html>
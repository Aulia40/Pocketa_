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

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>POCKETA - Dashboard</title>

    <link rel="stylesheet" href="../style.css">

    <style>

        /* =========================
           USER DASHBOARD
        ========================= */

        body {
            display: block;
            padding: 0;
            min-height: 100vh;
        }

        .dashboard-navbar {
            width: 100%;
            background: #FFF9F6;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 3px 15px rgba(92, 64, 51, 0.08);
        }

        .dashboard-logo {
            font-size: 25px;
            font-weight: 800;
            color: #5C4033;
            letter-spacing: 1px;
        }

        .dashboard-logo span {
            color: #D89A9A;
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

        .dashboard {
            width: 90%;
            max-width: 1100px;
            margin: 45px auto;
        }

        .welcome-card {
            background: #FFF9F6;
            padding: 35px;
            border-radius: 22px;
            box-shadow:
                0 12px 35px
                rgba(92, 64, 51, 0.10);
            margin-bottom: 30px;
        }

        .welcome-card h1 {
            color: #5C4033;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .welcome-card p {
            margin-bottom: 0;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 20px;
        }

        .dashboard-card {
            background: #FFF9F6;
            padding: 30px;
            border-radius: 20px;
            text-decoration: none;
            color: #5C4033;
            box-shadow:
                0 8px 25px
                rgba(92, 64, 51, 0.08);
            border: 1.5px solid #EAD6CC;
            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            border-color: #D89A9A;
            box-shadow:
                0 12px 30px
                rgba(92, 64, 51, 0.12);
        }

        .card-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .dashboard-card h3 {
            color: #5C4033;
            margin-bottom: 8px;
        }

        .dashboard-card p {
            margin-bottom: 0;
            font-size: 13px;
        }

        .account-card {
            margin-top: 25px;
            background: #FFF9F6;
            padding: 28px;
            border-radius: 20px;
            border: 1.5px solid #EAD6CC;
            box-shadow:
                0 8px 25px
                rgba(92, 64, 51, 0.07);
        }

        .account-card h2 {
            margin-bottom: 18px;
        }

        .account-info {
            display: grid;
            gap: 10px;
        }

        .account-info p {
            margin-bottom: 0;
        }

        @media (max-width: 700px) {

            .dashboard-navbar {
                padding: 16px 5%;
            }

            .dashboard {
                width: 92%;
                margin: 30px auto;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .welcome-card {
                padding: 28px 23px;
            }

            .welcome-card h1 {
                font-size: 24px;
            }

        }

    </style>

</head>

<body>


    <!-- NAVBAR -->

    <nav class="dashboard-navbar">

        <div class="dashboard-logo">
            POCKE<span>TA</span>
        </div>

        <a
            href="../auth/logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </nav>


    <!-- DASHBOARD -->

    <main class="dashboard">


        <!-- WELCOME -->

        <section class="welcome-card">

            <h1>
                Halo, <?= htmlspecialchars($nama) ?>! 
            </h1>

            <p>
                Selamat datang kembali di POCKETA.
                Yuk, temukan produk handmade favoritmu!
            </p>

        </section>


        <!-- MENU -->

        <section class="dashboard-cards">


            <a href="#" class="dashboard-card">

                <div class="card-icon">
                    
                </div>

                <h3>
                    Produk
                </h3>

                <p>
                    Lihat dan jelajahi produk
                    handmade POCKETA.
                </p>

            </a>


            <a href="#" class="dashboard-card">

                <div class="card-icon">
                    
                </div>

                <h3>
                    Pesanan Saya
                </h3>

                <p>
                    Lihat status dan riwayat
                    pesanan kamu.
                </p>

            </a>


            <a href="#" class="dashboard-card">

                <div class="card-icon">
                </div>

                <h3>
                    Profil
                </h3>

                <p>
                    Kelola informasi akun kamu.
                </p>

            </a>


        </section>


        <!-- ACCOUNT -->

        <section class="account-card">

            <h2>
                Informasi Akun
            </h2>

            <div class="account-info">

                <p>
                    <strong>Nama:</strong>
                    <?= htmlspecialchars($nama) ?>
                </p>

                <p>
                    <strong>Email:</strong>
                    <?= htmlspecialchars($email) ?>
                </p>

            </div>

        </section>


    </main>

</body>

</html>
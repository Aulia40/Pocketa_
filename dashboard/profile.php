<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$user_id = $_SESSION["user_id"];

// Ambil data terbaru pengguna dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.html");
    exit;
}

$nama = $user["nama"] ?? "Pengguna POCKETA";
$email = $user["email"] ?? "-";
$role = $user["role"] ?? "customer";
$inisial = strtoupper(substr($nama, 0, 1));

function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | POCKETA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #FBE9E7; color: #5C4033; }
        header { background: #FFF9F6; padding: 20px 7%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(92, 64, 51, 0.08); }
        .logo { font-size: 25px; font-weight: bold; letter-spacing: 2px; }
        nav a { text-decoration: none; color: #8B6F60; margin-left: 22px; font-size: 14px; }
        nav a:hover { color: #A66A6A; }
        .container { max-width: 800px; width: 90%; margin: 50px auto; }
        .back { display: inline-block; text-decoration: none; color: #8B6F60; margin-bottom: 25px; }
        .profile-card { background: #FFF9F6; padding: 40px; border-radius: 20px; box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08); text-align: center; }
        .avatar-circle { width: 90px; height: 90px; background: #F4C7C3; color: #5C4033; font-size: 36px; font-weight: bold; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 0 4px 15px rgba(92,64,51,0.1); }
        h1 { font-size: 26px; margin-bottom: 5px; color: #5C4033; }
        .badge { display: inline-block; background: #FBE9E7; color: #A66A6A; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 25px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: left; background: #FFFDFB; padding: 25px; border-radius: 15px; border: 1px solid #EAD6CC; margin-bottom: 30px; }
        .info-item label { font-size: 12px; color: #8B6F60; display: block; margin-bottom: 5px; font-weight: bold; }
        .info-item span { font-size: 15px; color: #5C4033; font-weight: 500; }
        .btn-group { display: flex; justify-content: center; gap: 15px; }
        .btn { padding: 12px 24px; border-radius: 9px; font-size: 14px; font-weight: bold; text-decoration: none; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: #5C4033; color: white; border: none; }
        .btn-primary:hover { background: #A66A6A; }
        .btn-outline { background: transparent; color: #5C4033; border: 1px solid #5C4033; }
        .btn-outline:hover { background: #FBE9E7; }
        @media(max-width: 600px) { .info-grid { grid-template-columns: 1fr; } .btn-group { flex-direction: column; } }
    </style>
</head>
<body>

<header>
    <div class="logo">POCKETA</div>
    <nav>
        <a href="user.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="cart.php">Keranjang</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="container">
    <a href="user.php" class="back">← Kembali ke Dashboard</a>

    <div class="profile-card">
        <div class="avatar-circle">
            <?= e($inisial) ?>
        </div>

        <h1><?= e($nama) ?></h1>
        <div class="badge"><?= e($role) === 'admin' ? 'Administrator' : 'Pelanggan Setia' ?></div>

        <div class="info-grid">
            <div class="info-item">
                <label>Nama Lengkap</label>
                <span><?= e($nama) ?></span>
            </div>
            <div class="info-item">
                <label>Alamat Email</label>
                <span><?= e($email) ?></span>
            </div>
            <div class="info-item">
                <label>Status Akun</label>
                <span style="color: #2E7D32; font-weight: bold;">● Aktif</span>
            </div>
            <div class="info-item">
                <label>Bergabung Sejak</label>
                <span><?= isset($user["created_at"]) ? date("d M Y", strtotime($user["created_at"])) : "Terdaftar" ?></span>
            </div>
        </div>

        <div class="btn-group">
            <a href="settings.php" class="btn btn-primary"><i class="fa-solid fa-gear"></i> Pengaturan Akun</a>
            <a href="user.php" class="btn btn-outline"><i class="fa-solid fa-house"></i> Halaman Utama</a>
        </div>
    </div>
</main>

</body>
</html>
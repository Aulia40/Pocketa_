
<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

$nama = $_SESSION["nama"];
$email = $_SESSION["email"];
$user_id = $_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$success = "";
$error = "";

/* =========================
   PROSES UPDATE SETTINGS
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Permintaan tidak valid. Silakan refresh halaman.";
    } else {

        $action = $_POST["action"] ?? "";

        /* UPDATE PROFIL */

        if ($action === "profile") {

            $namaBaru = trim($_POST["nama"] ?? "");
            $emailBaru = trim($_POST["email"] ?? "");

            if ($namaBaru === "" || $emailBaru === "") {
                $error = "Nama dan email wajib diisi.";

            } elseif (!filter_var($emailBaru, FILTER_VALIDATE_EMAIL)) {
                $error = "Format email tidak valid.";

            } else {

                $check = $pdo->prepare(
                    "SELECT id FROM users
                     WHERE email = :email AND id != :id"
                );

                $check->execute([
                    ":email" => $emailBaru,
                    ":id" => $user_id
                ]);

                if ($check->fetch()) {

                    $error = "Email sudah digunakan akun lain.";

                } else {

                    $update = $pdo->prepare(
                        "UPDATE users
                         SET nama = :nama, email = :email
                         WHERE id = :id"
                    );

                    $update->execute([
                        ":nama" => $namaBaru,
                        ":email" => $emailBaru,
                        ":id" => $user_id
                    ]);

                    $_SESSION["nama"] = $namaBaru;
                    $_SESSION["email"] = $emailBaru;

                    $nama = $namaBaru;
                    $email = $emailBaru;

                    $success = "Informasi profil berhasil diperbarui.";
                }
            }
        }

        /* UPDATE PASSWORD */

        elseif ($action === "password") {

            $passwordLama = $_POST["password_lama"] ?? "";
            $passwordBaru = $_POST["password_baru"] ?? "";
            $konfirmasi = $_POST["konfirmasi_password"] ?? "";

            $stmt = $pdo->prepare(
                "SELECT password FROM users WHERE id = :id"
            );

            $stmt->execute([":id" => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($passwordLama, $user["password"])) {

                $error = "Password lama tidak sesuai.";

            } elseif (strlen($passwordBaru) < 8) {

                $error = "Password baru minimal 8 karakter.";

            } elseif ($passwordBaru !== $konfirmasi) {

                $error = "Konfirmasi password tidak cocok.";

            } else {

                $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

                $update = $pdo->prepare(
                    "UPDATE users SET password = :password WHERE id = :id"
                );

                $update->execute([
                    ":password" => $hash,
                    ":id" => $user_id
                ]);

                $success = "Password berhasil diperbarui.";
            }
        }

        else {
            $error = "Jenis pengaturan tidak dikenal.";
        }
    }
}

$inisial = strtoupper(substr($nama, 0, 1));

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>POCKETA | Pengaturan Akun</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/user-navbar.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    font-family: 'DM Sans', sans-serif;
    color: #5C4033;
}

.settings-container {
    width: 88%;
    max-width: 1050px;
    margin: 45px auto 70px;
}

.settings-heading {
    margin-bottom: 30px;
}

.settings-heading h1 {
    font-family: 'Playfair Display', serif;
    font-size: 30px;
    margin-bottom: 8px;
}

.settings-heading p {
    font-size: 13px;
    color: #927B6E;
    line-height: 1.7;
}

.settings-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 25px;
    align-items: start;
}

.settings-sidebar,
.settings-card {
    background: #FFF9F6;
    border-radius: 22px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(92,64,51,.07);
}

.settings-user {
    text-align: center;
    padding-bottom: 22px;
    border-bottom: 1px solid #F0DED7;
    margin-bottom: 20px;
}

.settings-avatar {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F4C7C3, #D89A9A);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 29px;
    font-weight: 800;
    margin: 0 auto 12px;
}

.settings-user h3 {
    font-size: 15px;
    margin-bottom: 5px;
}

.settings-user p {
    font-size: 11px;
    color: #A58B7D;
}

.settings-sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #927B6E;
    padding: 13px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 12px;
    margin-bottom: 6px;
    transition: .3s;
}

.settings-sidebar a:hover,
.settings-sidebar a.active {
    background: #FBE9E7;
    color: #5C4033;
}

.settings-sidebar a i {
    width: 18px;
    text-align: center;
}

.settings-card {
    margin-bottom: 22px;
}

.settings-card h2 {
    font-size: 17px;
    margin-bottom: 7px;
}

.settings-card .description {
    font-size: 12px;
    color: #A58B7D;
    margin-bottom: 25px;
    line-height: 1.7;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 9px;
}

.form-group input {
    width: 100%;
    padding: 13px 15px;
    background: #FDF2EF;
    border: 1px solid #F0DED7;
    border-radius: 12px;
    outline: none;
    font-family: inherit;
    font-size: 13px;
    color: #5C4033;
    transition: .3s;
}

.form-group input:focus {
    border-color: #D89A9A;
    background: white;
    box-shadow: 0 0 0 3px rgba(216,154,154,.12);
}

.form-group input::placeholder {
    color: #BBA79D;
}

.save-btn {
    padding: 12px 22px;
    background: #5C4033;
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: .3s;
}

.save-btn:hover {
    background: #D89A9A;
    transform: translateY(-2px);
}

.alert {
    padding: 15px 18px;
    border-radius: 12px;
    font-size: 12px;
    margin-bottom: 20px;
}

.alert.success {
    background: #EAF5EA;
    color: #477447;
}

.alert.error {
    background: #FCE8E8;
    color: #A34C4C;
}

.account-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #FDF2EF;
    padding: 16px;
    border-radius: 13px;
    font-size: 12px;
    margin-top: 15px;
}

.account-status span {
    color: #927B6E;
}

.account-status strong {
    color: #5C4033;
}

@media(max-width: 750px) {

    .settings-layout {
        grid-template-columns: 1fr;
    }

    .settings-container {
        width: 92%;
        margin-top: 30px;
    }

    .settings-heading h1 {
        font-size: 25px;
    }

    .settings-sidebar {
        padding: 20px;
    }
}

</style>

</head>

<body>

<?php
$activePage = "";
include "../includes/user-navbar.php";
?>

<main class="settings-container">

    <div class="settings-heading">

        <h1>Pengaturan Akun</h1>

        <p>
            Kelola informasi pribadi dan keamanan akun POCKETA milikmu.
        </p>

    </div>

    <?php if ($success): ?>

        <div class="alert success">
            <i class="fa-solid fa-circle-check"></i>
            <?= e($success) ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <div class="settings-layout">

        <!-- SIDEBAR -->

        <aside class="settings-sidebar">

            <div class="settings-user">

                <div class="settings-avatar">
                    <?= e($inisial) ?>
                </div>

                <h3><?= e($nama) ?></h3>

                <p>POCKETA Member</p>

            </div>

            <a href="profile.php">
                <i class="fa-regular fa-user"></i>
                Profil Saya
            </a>

            <a href="#profil" class="active">
                <i class="fa-solid fa-user-gear"></i>
                Informasi Akun
            </a>

            <a href="#keamanan">
                <i class="fa-solid fa-lock"></i>
                Keamanan
            </a>

            <a href="../auth/logout.php"
               onclick="return confirm('Yakin ingin logout?')">

                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout

            </a>

        </aside>

        <!-- MAIN SETTINGS -->

        <div class="settings-content">

            <!-- PROFIL -->

            <section class="settings-card" id="profil">

                <h2>
                    <i class="fa-regular fa-user"></i>
                    Informasi Profil
                </h2>

                <p class="description">
                    Perbarui nama dan email yang digunakan pada akun POCKETA.
                </p>

                <form method="POST">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">

                    <input type="hidden" name="action" value="profile">

                    <div class="form-group">

                        <label>Nama Lengkap</label>

                        <input type="text"
                               name="nama"
                               value="<?= e($nama) ?>"
                               maxlength="100"
                               required>

                    </div>

                    <div class="form-group">

                        <label>Alamat Email</label>

                        <input type="email"
                               name="email"
                               value="<?= e($email) ?>"
                               maxlength="150"
                               required>

                    </div>

                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Perubahan
                    </button>

                </form>

            </section>

            <!-- PASSWORD -->

            <section class="settings-card" id="keamanan">

                <h2>
                    <i class="fa-solid fa-shield-halved"></i>
                    Keamanan Akun
                </h2>

                <p class="description">
                    Gunakan password yang kuat untuk menjaga keamanan akunmu.
                </p>

                <form method="POST">

                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">

                    <input type="hidden" name="action" value="password">

                    <div class="form-group">

                        <label>Password Lama</label>

                        <input type="password"
                               name="password_lama"
                               placeholder="Masukkan password lama"
                               autocomplete="current-password"
                               required>

                    </div>

                    <div class="form-group">

                        <label>Password Baru</label>

                        <input type="password"
                               name="password_baru"
                               placeholder="Minimal 8 karakter"
                               minlength="8"
                               autocomplete="new-password"
                               required>

                    </div>

                    <div class="form-group">

                        <label>Konfirmasi Password Baru</label>

                        <input type="password"
                               name="konfirmasi_password"
                               placeholder="Ulangi password baru"
                               minlength="8"
                               autocomplete="new-password"
                               required>

                    </div>

                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-lock"></i>
                        Perbarui Password
                    </button>

                </form>

            </section>

            <!-- STATUS AKUN -->

            <section class="settings-card">

                <h2>
                    <i class="fa-solid fa-circle-info"></i>
                    Informasi Akun
                </h2>

                <p class="description">
                    Informasi dasar akun yang sedang digunakan.
                </p>

                <div class="account-status">

                    <span>Status Akun</span>

                    <strong>Member POCKETA</strong>

                </div>

                <div class="account-status">

                    <span>Email Terdaftar</span>

                    <strong><?= e($email) ?></strong>

                </div>

            </section>

        </div>

    </div>

</main>

<script src="../assets/js/user-navbar.js"></script>

</body>
</html>

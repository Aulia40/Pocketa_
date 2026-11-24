
<?php

header("Content-Type: application/json; charset=UTF-8");

ini_set("display_errors", "0");
error_reporting(E_ALL);

function sendResponse($success, $message, $status = 200, $extra = []) {
    http_response_code($status);

    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {
    // 1. Periksa metode request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        sendResponse(false, "Metode request tidak diizinkan.", 405);
    }

    // 2. Koneksi database
    require_once __DIR__ . "/../config/database.php";

    // 3. Mulai session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 4. Ambil data login
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        sendResponse(false, "Email dan password wajib diisi.", 422);
    }

    // 5. Cari akun berdasarkan email
    $stmt = $pdo->prepare(
        "SELECT id, nama, email, password, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 6. Validasi akun dan password
    if (!$user || !password_verify($password, $user["password"])) {
        sendResponse(false, "Email atau password salah.", 401);
    }

    // 7. Buat session
    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["nama"] = $user["nama"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];

    // 8. Kirim respons berhasil
    sendResponse(
        true,
        "Login berhasil!",
        200,
        [
            "role" => $user["role"],
            "nama" => $user["nama"]
        ]
    );

} catch (Throwable $e) {

    error_log("POCKETA Login Error: " . $e->getMessage());

    sendResponse(
        false,
        "Terjadi kesalahan pada server. Silakan coba kembali.",
        500
    );
}

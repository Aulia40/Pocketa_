
<?php

header("Content-Type: application/json; charset=UTF-8");

ini_set("display_errors", "0");
error_reporting(E_ALL);

function sendResponse($success, $message, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    // 1. Periksa metode request
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        sendResponse(false, "Metode request tidak diizinkan.", 405);
    }

    // 2. Koneksi database
    require_once __DIR__ . "/../config/database.php";

    // 3. Ambil data
    $nama = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // 4. Validasi
    if ($nama === "") {
        sendResponse(false, "Nama wajib diisi.", 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Email tidak valid.", 422);
    }

    if (strlen($password) < 8) {
        sendResponse(false, "Password minimal 8 karakter.", 422);
    }

    // 5. Periksa email
    $check = $pdo->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    $check->execute([$email]);

    if ($check->fetch()) {
        sendResponse(false, "Email sudah terdaftar.", 409);
    }

    // 6. Hash password
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // 7. Simpan pengguna
    $stmt = $pdo->prepare(
        "INSERT INTO users (nama, email, password, role)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $nama,
        $email,
        $hashedPassword,
        "user"
    ]);

    // 8. Respons berhasil
    sendResponse(true, "Registrasi berhasil!", 201);

} catch (Throwable $e) {

    error_log(
        "POCKETA Register Error: " . $e->getMessage()
    );

    sendResponse(
        false,
        "Terjadi kesalahan pada server. Silakan coba kembali.",
        500
    );
}

<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Metode request tidak diizinkan"
    ]);

    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Email dan password wajib diisi"
    ]);

    exit;
}

try {

    $stmt = $pdo->prepare(
        "SELECT id, nama, email, password, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Email atau password salah"
        ]);

        exit;
    }

    if (!password_verify($password, $user["password"])) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Email atau password salah"
        ]);

        exit;
    }

    // Buat session
    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["nama"] = $user["nama"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];

    // Kirim hasil login ke JavaScript
    echo json_encode([
        "success" => true,
        "message" => "Login berhasil!",
        "role" => $user["role"]
    ]);

    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan pada database"
    ]);

    exit;
}
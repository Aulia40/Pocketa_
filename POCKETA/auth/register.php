<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Metode request harus POST"
    ]);

    exit;
}

$nama = trim($_POST["nama"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($nama === "") {
    echo json_encode([
        "success" => false,
        "message" => "Nama kosong"
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Email tidak valid"
    ]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password minimal 8 karakter"
    ]);
    exit;
}

try {

    // Cek apakah email sudah ada
    $check = $pdo->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $check->execute([$email]);

    if ($check->fetch()) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "Email sudah terdaftar"
        ]);

        exit;
    }

    // Hash password
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Simpan user
    $stmt = $pdo->prepare(
        "INSERT INTO users
        (nama, email, password, role)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->execute([
        $nama,
        $email,
        $hashedPassword,
        "user"
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Registrasi berhasil!"
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "error" => $e->getMessage()
    ]);
}
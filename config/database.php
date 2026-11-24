
<?php

$host = "sql101.infinityfree.com";
$port = 3306;

$dbname = "if0_43517600_Pocketa_db";
$username = "if0_43517600";
$password = "I0lvACKcl9";

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log("POCKETA Database Error: " . $e->getMessage());

    throw new RuntimeException("Koneksi database gagal.");

}

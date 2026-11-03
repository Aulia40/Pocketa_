
<?php
session_start();

require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

// Hanya menerima POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: products.php");
    exit;
}

$product_id = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
$jumlah = filter_input(INPUT_POST, "jumlah", FILTER_VALIDATE_INT);

if (!$product_id || !$jumlah || $jumlah < 1) {
    die("Data produk atau jumlah pembelian tidak valid.");
}

// Ambil produk langsung dari database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Produk tidak ditemukan.");
}

// Periksa stok
if ($product["stok"] < 1) {
    die("Maaf, stok produk sedang habis.");
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Hitung jumlah setelah ditambahkan
$jumlah_sebelumnya = $_SESSION["cart"][$product_id]["jumlah"] ?? 0;
$total_jumlah = $jumlah_sebelumnya + $jumlah;

// Pastikan tidak melebihi stok
if ($total_jumlah > $product["stok"]) {
    die("Jumlah pembelian melebihi stok yang tersedia.");
}

// Simpan data produk dari database ke session
$_SESSION["cart"][$product_id] = [
    "product_id" => $product["id"],
    "nama" => $product["nama_produk"],
    "harga" => $product["harga"],
    "jumlah" => $total_jumlah,
    "gambar" => $product["gambar"],
    "stok" => $product["stok"]
];

// Kembali ke keranjang
header("Location: cart.php");
exit;

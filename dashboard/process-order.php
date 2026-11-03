
<?php
session_start();

require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

// Pastikan request berasal dari form checkout
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: checkout.php");
    exit;
}

// Periksa keranjang
$cart = $_SESSION["cart"] ?? [];

if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// Ambil data dari form
$nama_penerima = trim($_POST["nama_penerima"] ?? "");
$email = trim($_POST["email"] ?? "");
$telepon = trim($_POST["telepon"] ?? "");
$alamat = trim($_POST["alamat"] ?? "");
$catatan = trim($_POST["catatan"] ?? "");
$pembayaran = $_POST["pembayaran"] ?? "";

// Validasi data
if (
    $nama_penerima === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $telepon === "" ||
    $alamat === "" ||
    !in_array($pembayaran, ["Transfer Bank", "E-Wallet", "COD"], true)
) {
    die("Data checkout tidak valid. Silakan kembali dan periksa form.");
}

try {

    // Mulai transaksi database
    $pdo->beginTransaction();

    $total = 0;
    $order_products = [];

    // Ambil ulang harga dan stok dari database
    foreach ($cart as $product_id => $item) {

        $product_id = (int)$product_id;
        $jumlah = (int)$item["jumlah"];

        if ($product_id < 1 || $jumlah < 1) {
            throw new Exception("Data produk tidak valid.");
        }

        // Kunci baris produk selama transaksi berlangsung
        $stmt = $pdo->prepare(
            "SELECT * FROM products WHERE id = ? FOR UPDATE"
        );

        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Salah satu produk tidak ditemukan.");
        }

        if ($jumlah > (int)$product["stok"]) {
            throw new Exception(
                "Stok produk " . $product["nama_produk"] .
                " tidak mencukupi. Silakan perbarui keranjang."
            );
        }

        $subtotal = $product["harga"] * $jumlah;
        $total += $subtotal;

        $order_products[] = [
            "product_id" => $product["id"],
            "nama" => $product["nama_produk"],
            "harga" => $product["harga"],
            "jumlah" => $jumlah,
            "subtotal" => $subtotal
        ];
    }

    // Buat nomor pesanan
    $order_number = "POC-" . date("Ymd") . "-" .
                    strtoupper(bin2hex(random_bytes(3)));

    // Simpan informasi utama pesanan
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            order_number,
            nama_penerima,
            email,
            telepon,
            alamat,
            catatan,
            metode_pembayaran,
            total_harga,
            status_pesanan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtOrder->execute([
        $_SESSION["user_id"],
        $order_number,
        $nama_penerima,
        $email,
        $telepon,
        $alamat,
        $catatan,
        $pembayaran,
        $total,
        "Menunggu Pembayaran"
    ]);

    // Ambil ID pesanan yang baru dibuat
    $order_id = $pdo->lastInsertId();

    // Simpan rincian setiap produk
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            nama_produk,
            harga,
            jumlah,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Kurangi stok produk yang sudah dipesan
    $stmtStock = $pdo->prepare("
        UPDATE products
        SET stok = stok - ?
        WHERE id = ? AND stok >= ?
    ");

    foreach ($order_products as $product) {

        $stmtItem->execute([
            $order_id,
            $product["product_id"],
            $product["nama"],
            $product["harga"],
            $product["jumlah"],
            $product["subtotal"]
        ]);

        $stmtStock->execute([
            $product["jumlah"],
            $product["product_id"],
            $product["jumlah"]
        ]);

        if ($stmtStock->rowCount() !== 1) {
            throw new Exception("Gagal memperbarui stok produk.");
        }
    }

    // Simpan informasi untuk halaman konfirmasi
    $_SESSION["last_order"] = [
        "order_id" => $order_id,
        "order_number" => $order_number,
        "nama_penerima" => $nama_penerima,
        "email" => $email,
        "telepon" => $telepon,
        "alamat" => $alamat,
        "catatan" => $catatan,
        "pembayaran" => $pembayaran,
        "produk" => $order_products,
        "total" => $total,
        "tanggal" => date("d-m-Y H:i:s"),
        "status" => "Menunggu Pembayaran"
    ];

    // Selesaikan transaksi
    $pdo->commit();

    // Kosongkan keranjang
    unset($_SESSION["cart"]);

    // Arahkan ke halaman sukses
    header("Location: order-success.php");
    exit;

} catch (Throwable $e) {

    // Batalkan semua perubahan jika terjadi kesalahan
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo "<h2>Pesanan belum berhasil diproses</h2>";
    echo "<p>" . htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        "UTF-8"
    ) . "</p>";
    echo "<a href='checkout.php'>Kembali ke Checkout</a>";
    exit;
}

<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/../config/database.php";

function gagalProses(string $pesan, int $statusCode = 400, ?string $filePath = null): void
{
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);
    }

    http_response_code($statusCode);
    echo "<div style='font-family:Arial,sans-serif;padding:30px'>";
    echo "<h2>Pesanan belum berhasil diproses</h2>";
    echo "<p>" . htmlspecialchars($pesan, ENT_QUOTES, "UTF-8") . "</p>";
    echo "<a href='checkout.php'>Kembali ke Checkout</a>";
    echo "</div>";
    exit;
}

// 1. VALIDASI LOGIN & REQUEST
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: checkout.php");
    exit;
}

$cart = $_SESSION["cart"] ?? [];
if (empty($cart)) {
    header("Location: cart.php");
    exit;
}

// 2. AMBIL & VALIDASI DATA FORM
$nama_penerima = trim((string)($_POST["nama_penerima"] ?? ""));
$email         = trim((string)($_POST["email"] ?? ""));
$telepon       = trim((string)($_POST["telepon"] ?? ""));
$alamat        = trim((string)($_POST["alamat"] ?? ""));
$catatan       = trim((string)($_POST["catatan"] ?? ""));
$kategori      = trim((string)($_POST["kategori_pembayaran"] ?? ""));

$metode_transfer = ["BCA", "BRI", "BNI", "Mandiri", "BSI", "SeaBank"];
$metode_ewallet  = ["DANA", "GoPay", "OVO", "ShopeePay", "QRIS"];

if ($kategori === "transfer") {
    $pembayaran = trim((string)($_POST["channel_transfer"] ?? ""));
    if (!in_array($pembayaran, $metode_transfer, true)) {
        gagalProses("Silakan pilih bank transfer yang valid.", 422);
    }
} elseif ($kategori === "e-wallet") {
    $pembayaran = trim((string)($_POST["channel_ewallet"] ?? ""));
    if (!in_array($pembayaran, $metode_ewallet, true)) {
        gagalProses("Silakan pilih e-wallet atau QRIS yang valid.", 422);
    }
} elseif ($kategori === "cod") {
    $pembayaran = "COD";
} else {
    gagalProses("Metode pembayaran tidak valid.", 422);
}

if (
    $nama_penerima === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $telepon === "" ||
    $alamat === ""
) {
    gagalProses("Data checkout tidak valid. Silakan kembali dan periksa form.", 422);
}

// 3. UPLOAD BUKTI PEMBAYARAN (JIKA TRANSFER / E-WALLET)
$bukti_pembayaran = null;
$file_berhasil_disimpan = null;

if ($kategori === "transfer" || $kategori === "e-wallet") {
    if (
        !isset($_FILES["bukti_pembayaran"]) ||
        $_FILES["bukti_pembayaran"]["error"] !== UPLOAD_ERR_OK
    ) {
        gagalProses("Bukti pembayaran wajib diupload.", 422);
    }

    $file = $_FILES["bukti_pembayaran"];

    if ($file["size"] > 5 * 1024 * 1024) {
        gagalProses("Ukuran foto maksimal 5MB.", 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file["tmp_name"]);

    $format_valid = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
    ];

    if (!isset($format_valid[$mime])) {
        gagalProses("Format file harus JPG, PNG, atau WEBP.", 422);
    }

    $upload_dir = __DIR__ . "/../uploads/payment_proofs/";
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        gagalProses("Folder upload gagal dibuat.", 500);
    }

    $nama_file   = bin2hex(random_bytes(16)) . "." . $format_valid[$mime];
    $target_file = $upload_dir . $nama_file;

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        gagalProses("Bukti pembayaran gagal disimpan.", 500);
    }

    $bukti_pembayaran = "uploads/payment_proofs/" . $nama_file;
    $file_berhasil_disimpan = $target_file;
}

// 4. TENTUKAN STATUS PESANAN & PEMBAYARAN
if ($kategori === "cod") {
    $status_pesanan    = "Pesanan Diproses";
    $status_pembayaran = "Bayar Saat Barang Diterima";
} elseif ($kategori === "transfer") {
    $status_pesanan    = "Menunggu Verifikasi";
    $status_pembayaran = "Menunggu Verifikasi Transfer (" . $pembayaran . ")";
} else {
    $status_pesanan    = "Menunggu Verifikasi";
    $status_pembayaran = "Menunggu Verifikasi E-Wallet (" . $pembayaran . ")";
}

// 5. SIMPAN ORDER, DETAIL ITEM, DAN KURANGI STOK
try {
    $pdo->beginTransaction();

    $total = 0;
    $order_products = [];

    $stmt = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    foreach ($cart as $product_id => $item) {
        $product_id = (int)$product_id;
        $jumlah     = (int)($item["jumlah"] ?? 0);

        if ($product_id < 1 || $jumlah < 1) {
            throw new Exception("Data produk tidak valid.");
        }

        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Produk tidak ditemukan.");
        }

        if ($jumlah > (int)$product["stok"]) {
            throw new Exception("Stok " . $product["nama_produk"] . " tidak mencukupi.");
        }

        $harga    = (int)$product["harga"];
        $subtotal = $harga * $jumlah;
        $total   += $subtotal;

        $order_products[] = [
            "product_id" => $product["id"],
            "nama"       => $product["nama_produk"],
            "harga"      => $harga,
            "jumlah"     => $jumlah,
            "subtotal"   => $subtotal,
        ];
    }

    $order_number = "POC-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));

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
        $status_pesanan,
    ]);

    $order_id = $pdo->lastInsertId();

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
            $product["subtotal"],
        ]);

        $stmtStock->execute([
            $product["jumlah"],
            $product["product_id"],
            $product["jumlah"],
        ]);

        if ($stmtStock->rowCount() !== 1) {
            throw new Exception("Gagal mengurangi stok produk.");
        }
    }

    $_SESSION["last_order"] = [
        "order_id"          => $order_id,
        "order_number"      => $order_number,
        "nama_penerima"     => $nama_penerima,
        "email"             => $email,
        "telepon"           => $telepon,
        "alamat"            => $alamat,
        "catatan"           => $catatan,
        "pembayaran"        => $pembayaran,
        "bukti_pembayaran"  => $bukti_pembayaran,
        "produk"            => $order_products,
        "total"             => $total,
        "tanggal"           => date("d-m-Y H:i:s"),
        "status"            => $status_pesanan,
        "status_pembayaran" => $status_pembayaran,
    ];

    $pdo->commit();
    unset($_SESSION["cart"]);

    header("Location: order-success.php");
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    gagalProses($e->getMessage(), 400, $file_berhasil_disimpan);
}
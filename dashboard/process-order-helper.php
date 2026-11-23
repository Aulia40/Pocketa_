
<?php
// process-order-helper.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

function prosesPesanan($jenis_pembayaran)
{
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

    $nama_penerima = trim($_POST["nama_penerima"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $telepon       = trim($_POST["telepon"] ?? "");
    $alamat        = trim($_POST["alamat"] ?? "");
    $catatan       = trim($_POST["catatan"] ?? "");
    $pembayaran    = trim($_POST["pembayaran"] ?? "");

    // Metode pembayaran yang diizinkan
    $transfer_valid = [
        "BCA", "BRI", "BNI", "Mandiri", "BSI", "SeaBank"
    ];

    $ewallet_valid = [
        "DANA", "GoPay", "OVO", "ShopeePay"
    ];

    // Validasi jenis pembayaran sesuai file yang dipanggil
    if ($jenis_pembayaran === "transfer") {
        if (!in_array($pembayaran, $transfer_valid, true)) {
            die("Metode transfer bank tidak valid.");
        }
    } elseif ($jenis_pembayaran === "ewallet") {
        if (!in_array($pembayaran, $ewallet_valid, true)) {
            die("Metode e-wallet tidak valid.");
        }
    } elseif ($jenis_pembayaran === "cod") {
        if ($pembayaran !== "COD") {
            die("Metode COD tidak valid.");
        }
    } else {
        die("Jenis pembayaran tidak valid.");
    }

    // Validasi data penerima
    if (
        $nama_penerima === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telepon === "" ||
        $alamat === ""
    ) {
        die("Data penerima tidak lengkap atau tidak valid.");
    }

    // =================================================
    // UPLOAD BUKTI PEMBAYARAN
    // =================================================

    $bukti_pembayaran = null;
    $file_berhasil_disimpan = null;

    // COD tidak membutuhkan bukti pembayaran
    if ($jenis_pembayaran !== "cod") {

        if (
            !isset($_FILES["bukti_pembayaran"]) ||
            $_FILES["bukti_pembayaran"]["error"] !== UPLOAD_ERR_OK
        ) {
            die("Silakan upload bukti pembayaran.");
        }

        $file = $_FILES["bukti_pembayaran"];

        if ($file["size"] > 5 * 1024 * 1024) {
            die("Ukuran file maksimal 5 MB.");
        }

        if (!is_uploaded_file($file["tmp_name"])) {
            die("File upload tidak valid.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file["tmp_name"]);

        $format_valid = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        if (!isset($format_valid[$mime])) {
            die("File harus JPG, PNG, atau WEBP.");
        }

        $upload_dir = "../uploads/payment_proofs/";

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                die("Folder bukti pembayaran gagal dibuat.");
            }
        }

        $nama_file = bin2hex(random_bytes(16))
                   . "." . $format_valid[$mime];

        $target_file = $upload_dir . $nama_file;

        if (!move_uploaded_file(
            $file["tmp_name"],
            $target_file
        )) {
            die("Bukti pembayaran gagal disimpan.");
        }

        $bukti_pembayaran = "uploads/payment_proofs/" . $nama_file;
        $file_berhasil_disimpan = $target_file;
    }

    // =================================================
    // STATUS ORDER
    // =================================================

    if ($jenis_pembayaran === "cod") {
        $status_pesanan = "Pesanan Diproses";
        $status_pembayaran = "Bayar Saat Barang Diterima";
    } else {
        $status_pesanan = "Menunggu Verifikasi";
        $status_pembayaran = "Menunggu Verifikasi Pembayaran";
    }

    global $pdo;

    try {

        $pdo->beginTransaction();

        $total = 0;
        $order_products = [];

        // =================================================
        // VALIDASI PRODUK DAN STOK
        // =================================================

        foreach ($cart as $product_id => $item) {

            $product_id = (int)$product_id;
            $jumlah = (int)($item["jumlah"] ?? 0);

            if ($product_id < 1 || $jumlah < 1) {
                throw new Exception("Data produk tidak valid.");
            }

            $stmt = $pdo->prepare("
                SELECT *
                FROM products
                WHERE id = ?
                FOR UPDATE
            ");

            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Produk tidak ditemukan.");
            }

            if ($jumlah > (int)$product["stok"]) {
                throw new Exception(
                    "Stok " . $product["nama_produk"]
                    . " tidak mencukupi."
                );
            }

            $harga = (int)$product["harga"];
            $subtotal = $harga * $jumlah;

            $total += $subtotal;

            $order_products[] = [
                "product_id" => $product["id"],
                "nama"       => $product["nama_produk"],
                "harga"      => $harga,
                "jumlah"     => $jumlah,
                "subtotal"   => $subtotal
            ];
        }

        // =================================================
        // NOMOR ORDER
        // =================================================

        $order_number = "POC-" . date("Ymd") . "-"
                      . strtoupper(bin2hex(random_bytes(3)));

        // =================================================
        // SIMPAN ORDER
        // =================================================

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
                status_pesanan,
                bukti_pembayaran,
                status_pembayaran
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $bukti_pembayaran,
            $status_pembayaran
        ]);

        $order_id = $pdo->lastInsertId();

        // =================================================
        // SIMPAN ORDER ITEMS
        // =================================================

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

        // =================================================
        // KURANGI STOK
        // =================================================

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
                throw new Exception(
                    "Gagal mengurangi stok produk."
                );
            }
        }

        // =================================================
        // SESSION ORDER
        // =================================================

        $_SESSION["last_order"] = [
            "order_id" => $order_id,
            "order_number" => $order_number,
            "nama_penerima" => $nama_penerima,
            "email" => $email,
            "telepon" => $telepon,
            "alamat" => $alamat,
            "catatan" => $catatan,
            "pembayaran" => $pembayaran,
            "bukti_pembayaran" => $bukti_pembayaran,
            "produk" => $order_products,
            "total" => $total,
            "tanggal" => date("d-m-Y H:i:s"),
            "status" => $status_pesanan,
            "status_pembayaran" => $status_pembayaran
        ];

        // =================================================
        // COMMIT
        // =================================================

        $pdo->commit();

        unset($_SESSION["cart"]);

        header("Location: order-success.php");
        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if (
            $file_berhasil_disimpan &&
            file_exists($file_berhasil_disimpan)
        ) {
            unlink($file_berhasil_disimpan);
        }

        http_response_code(400);

        echo "<div style='font-family:Arial;padding:30px'>";
        echo "<h2>Pesanan gagal diproses</h2>";

        echo "<p>" . htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            "UTF-8"
        ) . "</p>";

        echo "<a href='checkout.php'>Kembali ke Checkout</a>";
        echo "</div>";

        exit;
    }
}
?>

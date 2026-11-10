
<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pesan = "";
$editProduk = null;

// Ambil data produk untuk diedit
if (isset($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET["edit"]]);
    $editProduk = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Simpan produk
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf_token"],
        $_POST["csrf_token"] ?? ""
    )) {
        die("Permintaan tidak valid.");
    }

    $aksi = $_POST["aksi"] ?? "";

    // HAPUS PRODUK
    if ($aksi === "hapus") {
        $id = (int)($_POST["id"] ?? 0);

        try {
            $stmt = $pdo->prepare(
                "DELETE FROM products WHERE id = ?"
            );
            $stmt->execute([$id]);

            header("Location: manage-products.php?pesan=hapus");
            exit;

        } catch (PDOException $e) {
            $pesan = "Produk tidak dapat dihapus karena sudah digunakan dalam transaksi.";
        }
    }

    // TAMBAH DAN EDIT PRODUK
    if ($aksi === "simpan") {

        $id = (int)($_POST["id"] ?? 0);
        $nama = trim($_POST["nama_produk"] ?? "");
        $kategori = trim($_POST["kategori"] ?? "");
        $harga = (float)($_POST["harga"] ?? 0);
        $deskripsi = trim($_POST["deskripsi"] ?? "");
        $stok = (int)($_POST["stok"] ?? 0);
        $status = $_POST["status"] ?? "Ready";

        if ($nama === "" || $kategori === "" ||
            $harga <= 0 || $stok < 0 ||
            !in_array($status, ["Ready", "Pre-Order"])) {

            $pesan = "Periksa kembali data produk.";

        } else {

            $gambar = $_POST["gambar_lama"] ?? "";

            // Upload gambar baru
            if (!empty($_FILES["gambar"]["name"])) {

                $tmp = $_FILES["gambar"]["tmp_name"];
                $mime = mime_content_type($tmp);

                $allowed = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowed[$mime]) ||
                    !is_uploaded_file($tmp) ||
                    $_FILES["gambar"]["size"] > 2 * 1024 * 1024) {

                    $pesan = "Gambar harus JPG, PNG, atau WEBP maksimal 2 MB.";

                } else {

                    $folder = __DIR__ . "/../images/";

                    if (!is_dir($folder)) {
                        mkdir($folder, 0755, true);
                    }

                    $namaFile = "produk_" . bin2hex(random_bytes(8))
                        . "." . $allowed[$mime];

                    if (move_uploaded_file(
                        $tmp,
                        $folder . $namaFile
                    )) {
                        $gambar = $namaFile;
                    } else {
                        $pesan = "Gagal mengunggah gambar.";
                    }
                }
            }

            if ($pesan === "") {

                if ($id > 0) {

                    $stmt = $pdo->prepare(
                        "UPDATE products SET
                            nama_produk = ?,
                            kategori = ?,
                            harga = ?,
                            deskripsi = ?,
                            gambar = ?,
                            stok = ?,
                            status = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $nama, $kategori, $harga,
                        $deskripsi, $gambar, $stok,
                        $status, $id
                    ]);

                } else {

                    $stmt = $pdo->prepare(
                        "INSERT INTO products
                        (nama_produk, kategori, harga,
                         deskripsi, gambar, stok, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $nama, $kategori, $harga,
                        $deskripsi, $gambar, $stok, $status
                    ]);
                }

                header("Location: manage-products.php?pesan=sukses");
                exit;
            }
        }
    }
}

// Ambil semua produk
$produk = $pdo->query(
    "SELECT * FROM products ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET["pesan"])) {
    if ($_GET["pesan"] === "sukses") {
        $pesan = "Produk berhasil disimpan!";
    } elseif ($_GET["pesan"] === "hapus") {
        $pesan = "Produk berhasil dihapus!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Produk | POCKETA</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 5%;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg,#FBE9E7,#F4C7C3,#EAD6CC);
    color: #5C4033;
}

.container {
    max-width: 1150px;
    margin: auto;
}

h1, h2 {
    color: #5C4033;
}

.panel {
    background: #FFF9F6;
    padding: 25px;
    border-radius: 18px;
    margin: 20px 0;
    box-shadow: 0 5px 20px #5c403310;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    margin: 8px 0 15px;
    border: 1px solid #EAD6CC;
    border-radius: 9px;
    font: inherit;
}

button, .back {
    border: none;
    padding: 11px 18px;
    border-radius: 20px;
    cursor: pointer;
    background: #5C4033;
    color: white;
    text-decoration: none;
    display: inline-block;
}

.cancel {
    background: #D89A9A;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 13px;
    border-bottom: 1px solid #EAD6CC;
    text-align: left;
    font-size: 13px;
}

.table-wrap {
    overflow-x: auto;
}

.product-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
}

.alert {
    background: #FBE9E7;
    padding: 12px;
    border-radius: 10px;
    margin: 15px 0;
}

.actions {
    display: flex;
    gap: 7px;
    align-items: center;
}

.edit {
    background: #B47C7C;
}

.delete {
    background: #A84F4F;
}

@media(max-width:600px) {
    body { padding: 20px 3%; }
    .panel { padding: 16px; }
}
</style>
</head>

<body>
<div class="container">

    <h1>Kelola Katalog POCKETA</h1>
    <p>Tambah, ubah, dan perbarui produk toko.</p>

    <a href="admin.php" class="back">
        <i class="fa fa-arrow-left"></i> Dashboard
    </a>

    <?php if ($pesan): ?>
        <div class="alert">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>
            <?= $editProduk ? "Edit Produk" : "Tambah Produk Baru" ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="aksi" value="simpan">

            <input type="hidden" name="id"
                value="<?= (int)($editProduk["id"] ?? 0) ?>">

            <input type="hidden" name="gambar_lama"
                value="<?= htmlspecialchars($editProduk["gambar"] ?? "") ?>">

            <label>Nama Produk</label>
            <input type="text" name="nama_produk" required
                value="<?= htmlspecialchars($editProduk["nama_produk"] ?? "") ?>">

            <label>Kategori</label>
            <select name="kategori" required>
                <?php
                $kategoriList = ["Dompet", "Tassel", "Gift Set", "Custom"];
                foreach ($kategoriList as $kategori):
                ?>
                    <option value="<?= $kategori ?>"
                        <?= ($editProduk["kategori"] ?? "") === $kategori ? "selected" : "" ?>>
                        <?= $kategori ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Harga (Rp)</label>
            <input type="number" name="harga" min="1" required
                value="<?= htmlspecialchars($editProduk["harga"] ?? "") ?>">

            <label>Deskripsi Produk</label>
            <textarea name="deskripsi" rows="4"><?= htmlspecialchars($editProduk["deskripsi"] ?? "") ?></textarea>

            <label>Gambar Produk</label>
            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">

            <?php if (!empty($editProduk["gambar"])): ?>
                <p>Gambar saat ini:</p>
                <img class="product-img"
                    src="../images/<?= htmlspecialchars($editProduk["gambar"]) ?>">
            <?php endif; ?>

            <label>Stok</label>
            <input type="number" name="stok" min="0" required
                value="<?= htmlspecialchars($editProduk["stok"] ?? "0") ?>">

            <label>Status Produk</label>
            <select name="status">
                <option value="Ready"
                    <?= ($editProduk["status"] ?? "") === "Ready" ? "selected" : "" ?>>
                    Ready
                </option>
                <option value="Pre-Order"
                    <?= ($editProduk["status"] ?? "") === "Pre-Order" ? "selected" : "" ?>>
                    Pre-Order
                </option>
            </select>

            <button type="submit">
                <i class="fa fa-save"></i>
                Simpan Produk
            </button>

            <?php if ($editProduk): ?>
                <a href="manage-products.php" class="back cancel">
                    Batal
                </a>
            <?php endif; ?>

        </form>
    </section>

    <section class="panel">
        <h2>Daftar Katalog Produk</h2>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($produk as $p): ?>
                <tr>
                    <td>
                        <?php if ($p["gambar"]): ?>
                            <img class="product-img"
                                src="../images/<?= htmlspecialchars($p["gambar"]) ?>">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td><?= htmlspecialchars($p["nama_produk"]) ?></td>
                    <td><?= htmlspecialchars($p["kategori"]) ?></td>
                    <td>Rp<?= number_format($p["harga"],0,',','.') ?></td>
                    <td><?= (int)$p["stok"] ?></td>
                    <td><?= htmlspecialchars($p["status"]) ?></td>

                    <td>
                        <div class="actions">
                            <a class="back edit"
                                href="?edit=<?= (int)$p["id"] ?>">
                                Edit
                            </a>

                            <form method="POST"
                                onsubmit="return confirm('Hapus produk ini?')">

                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden" name="aksi" value="hapus">

                                <input type="hidden" name="id"
                                    value="<?= (int)$p["id"] ?>">

                                <button type="submit" class="delete">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>

</div>
</body>
</html>

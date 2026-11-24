<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

$uploadRoot = __DIR__ . "/../assets/images/custom/";

$folders = [
    "warna"    => "colors/",
    "telinga"  => "ears/",
    "mata"     => "eyes/",
    "motif"    => "patterns/",
    "aksesori" => "accessories/"
];

$kategoriNama = [
    "warna"    => "Warna",
    "telinga"  => "Telinga",
    "mata"     => "Mata",
    "motif"    => "Motif",
    "aksesori" => "Aksesori"
];

/* Buat folder jika belum tersedia */
foreach ($folders as $folder) {
    $path = $uploadRoot . $folder;

    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

if (!is_dir($uploadRoot . "base/")) {
    mkdir($uploadRoot . "base/", 0755, true);
}

$message = "";
$error = "";
$editData = null;


/* =========================================================
   FUNGSI GAMBAR
========================================================= */

function getLocalPath($publicPath)
{
    if (!$publicPath) {
        return null;
    }

    return __DIR__ . "/.." . $publicPath;
}


function uploadImage($file, $destination, $oldPath = null)
{
    if (
        !$file ||
        !isset($file["error"]) ||
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return $oldPath;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload gambar gagal.");
    }

    if ($file["size"] > 3 * 1024 * 1024) {
        throw new RuntimeException("Ukuran gambar maksimal 3 MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    if ($mime !== "image/png") {
        throw new RuntimeException("Gambar harus berformat PNG.");
    }

    $image = getimagesize($file["tmp_name"]);

    if (!$image || $image["mime"] !== "image/png") {
        throw new RuntimeException("File gambar tidak valid.");
    }

    $filename = bin2hex(random_bytes(12)) . ".png";
    $target = $destination . $filename;

    if (!move_uploaded_file($file["tmp_name"], $target)) {
        throw new RuntimeException("Gagal menyimpan gambar.");
    }

    return str_replace(
        __DIR__ . "/..",
        "",
        $target
    );
}


function deleteImageFile($path)
{
    if (!$path) {
        return;
    }

    $file = getLocalPath($path);

    if ($file && is_file($file)) {
        unlink($file);
    }
}


/* =========================================================
   PROSES FORM
========================================================= */

try {

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $action = $_POST["action"] ?? "";


        /* -------------------------------------------------
           SIMPAN KARAKTER DASAR
        ------------------------------------------------- */

        if ($action === "save_base") {

            $nama = trim($_POST["nama_base"] ?? "");

            if ($nama === "") {
                throw new RuntimeException(
                    "Nama karakter harus diisi."
                );
            }

            $old = $pdo->query("
                SELECT *
                FROM custom_base
                ORDER BY id ASC
                LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);

            $oldImage = $old["image_path"] ?? null;

            $imagePath = uploadImage(
                $_FILES["gambar_base"] ?? null,
                $uploadRoot . "base/",
                $oldImage
            );

            if (!$imagePath) {
                throw new RuntimeException(
                    "Silakan upload gambar karakter dasar."
                );
            }

            if ($old) {

                $stmt = $pdo->prepare("
                    UPDATE custom_base
                    SET nama = ?, image_path = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nama,
                    $imagePath,
                    $old["id"]
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO custom_base (nama, image_path)
                    VALUES (?, ?)
                ");

                $stmt->execute([
                    $nama,
                    $imagePath
                ]);
            }

            if ($oldImage && $oldImage !== $imagePath) {
                deleteImageFile($oldImage);
            }

            $message = "Karakter dasar berhasil disimpan.";
        }


        /* -------------------------------------------------
           TAMBAH ATAU EDIT PILIHAN
        ------------------------------------------------- */

        elseif ($action === "save_option") {

            $id = (int)($_POST["id"] ?? 0);
            $kategori = $_POST["kategori"] ?? "";
            $nama = trim($_POST["nama"] ?? "");
            $warna = trim($_POST["kode_warna"] ?? "");

            $stok = filter_var(
                $_POST["stok"] ?? null,
                FILTER_VALIDATE_INT
            );

            $harga = filter_var(
                $_POST["harga_tambahan"] ?? null,
                FILTER_VALIDATE_FLOAT
            );

            $status = $_POST["status"] ?? "aktif";

            if (!isset($folders[$kategori])) {
                throw new RuntimeException(
                    "Kategori tidak valid."
                );
            }

            if (
                $nama === "" ||
                $stok === false ||
                $stok === null ||
                $stok < 0 ||
                $harga === false ||
                $harga === null ||
                $harga < 0
            ) {
                throw new RuntimeException(
                    "Periksa kembali nama, stok, dan harga."
                );
            }

            if (!in_array($status, ["aktif", "nonaktif"], true)) {
                throw new RuntimeException(
                    "Status tidak valid."
                );
            }

            if (
                $kategori === "warna" &&
                !preg_match('/^#[0-9A-Fa-f]{6}$/', $warna)
            ) {
                throw new RuntimeException(
                    "Kode warna harus menggunakan format HEX, misalnya #F4C7C3."
                );
            }

            /* Ambil data lama jika sedang edit */
            $old = null;

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM custom_options
                    WHERE id = ?
                ");

                $stmt->execute([$id]);
                $old = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$old) {
                    throw new RuntimeException(
                        "Data pilihan tidak ditemukan."
                    );
                }
            }

            $oldImage = $old["image_path"] ?? null;
            $imagePath = $oldImage;

            if ($kategori !== "warna") {

                $imagePath = uploadImage(
                    $_FILES["gambar"] ?? null,
                    $uploadRoot . $folders[$kategori],
                    $oldImage
                );

                if (!$imagePath) {
                    throw new RuntimeException(
                        "Silakan upload gambar PNG."
                    );
                }

            } else {

                $imagePath = null;
            }


            /* Update pilihan */
            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE custom_options
                    SET
                        kategori = ?,
                        nama = ?,
                        image_path = ?,
                        kode_warna = ?,
                        stok = ?,
                        harga_tambahan = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $kategori,
                    $nama,
                    $imagePath,
                    $kategori === "warna" ? $warna : null,
                    $stok,
                    $harga,
                    $status,
                    $id
                ]);

                $message = "Pilihan berhasil diperbarui.";

            } else {

                /* Tambah pilihan baru */
                $stmt = $pdo->prepare("
                    INSERT INTO custom_options
                    (
                        kategori,
                        nama,
                        image_path,
                        kode_warna,
                        stok,
                        harga_tambahan,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $kategori,
                    $nama,
                    $imagePath,
                    $kategori === "warna" ? $warna : null,
                    $stok,
                    $harga,
                    $status
                ]);

                $message = "Pilihan berhasil ditambahkan.";
            }

            /* Hapus gambar lama setelah database berhasil diperbarui */
            if (
                $oldImage &&
                $oldImage !== $imagePath
            ) {
                deleteImageFile($oldImage);
            }
        }


        /* -------------------------------------------------
           HAPUS PILIHAN
        ------------------------------------------------- */

        elseif ($action === "delete_option") {

            $id = (int)($_POST["id"] ?? 0);

            if ($id < 1) {
                throw new RuntimeException(
                    "ID pilihan tidak valid."
                );
            }

            $stmt = $pdo->prepare("
                SELECT image_path
                FROM custom_options
                WHERE id = ?
            ");

            $stmt->execute([$id]);
            $old = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$old) {
                throw new RuntimeException(
                    "Pilihan yang ingin dihapus tidak ditemukan."
                );
            }

            $stmt = $pdo->prepare("
                DELETE FROM custom_options
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            deleteImageFile($old["image_path"]);

            $message = "Pilihan berhasil dihapus.";
        }
    }

} catch (Throwable $e) {

    $error = $e->getMessage();
}


/* =========================================================
   AMBIL DATA DATABASE
========================================================= */

/* Karakter dasar */
$base = $pdo->query("
    SELECT *
    FROM custom_base
    ORDER BY id ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);


/* Semua pilihan */
$options = $pdo->query("
    SELECT *
    FROM custom_options
    ORDER BY kategori ASC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);


/* Pilihan yang dapat digunakan untuk preview */
$previewOptions = [];

foreach ($options as $option) {

    if (
        $option["status"] === "aktif" &&
        (int)$option["stok"] > 0 &&
        $option["kategori"] !== "warna" &&
        !empty($option["image_path"])
    ) {
        $previewOptions[$option["kategori"]][] = $option;
    }
}


/* Data edit */
if (isset($_GET["edit"])) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM custom_options
        WHERE id = ?
    ");

    $stmt->execute([
        (int)$_GET["edit"]
    ]);

    $editData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editData) {
        $error = "Data pilihan yang ingin diedit tidak ditemukan.";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>POCKETA | Kelola Kustomisasi</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 5%;
    background: linear-gradient(
        135deg,
        #FBE9E7,
        #F4C7C3,
        #EAD6CC
    );
    color: #5C4033;
    font-family: Arial, sans-serif;
}

.container {
    max-width: 1150px;
    margin: auto;
}

h1 {
    font-size: 28px;
    margin: 25px 0 8px;
}

h2 {
    font-size: 20px;
    margin-top: 0;
}

p {
    color: #927B6E;
    line-height: 1.6;
}

.card {
    background: #FFF9F6;
    padding: 25px;
    border-radius: 18px;
    margin-bottom: 23px;
    box-shadow: 0 5px 20px rgba(92,64,51,.07);
}

label {
    display: block;
    margin: 15px 0 7px;
    font-size: 13px;
    font-weight: bold;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #EAD6CC;
    border-radius: 10px;
    background: #fff;
    color: #5C4033;
    font-size: 14px;
    outline: none;
}

input:focus,
select:focus {
    border-color: #B47C7C;
    box-shadow: 0 0 0 3px rgba(180,124,124,.12);
}

button,
.btn {
    display: inline-block;
    border: none;
    background: #5C4033;
    color: white;
    padding: 11px 18px;
    border-radius: 20px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
    margin-top: 15px;
}

button:hover,
.btn:hover {
    opacity: .88;
}

.btn-secondary {
    background: #B47C7C;
}

.btn-danger {
    background: #B65F5F;
}

.message {
    padding: 13px 16px;
    background: #E2F2E5;
    color: #35633C;
    border-radius: 10px;
    margin-bottom: 18px;
}

.error {
    padding: 13px 16px;
    background: #FBE4E4;
    color: #9B4545;
    border-radius: 10px;
    margin-bottom: 18px;
}

.base-preview {
    width: 170px;
    height: 170px;
    object-fit: contain;
    padding: 10px;
    border-radius: 15px;
    background: #FBE9E7;
    border: 1px solid #EAD6CC;
}

.upload-area {
    border: 2px dashed #D8B5A9;
    background: #FFF9F6;
    padding: 18px;
    border-radius: 15px;
    margin-top: 8px;
}

.upload-area input {
    background: transparent;
    border: none;
    padding: 8px 0;
}

.image-preview {
    min-height: 210px;
    background-color: #FBE9E7;
    background-image:
        linear-gradient(45deg, #f3d9d4 25%, transparent 25%),
        linear-gradient(-45deg, #f3d9d4 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f3d9d4 75%),
        linear-gradient(-45deg, transparent 75%, #f3d9d4 75%);
    background-size: 20px 20px;
    background-position:
        0 0,
        0 10px,
        10px -10px,
        -10px 0;
    border-radius: 12px;
    margin-top: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

#preview-gambar,
#preview-base {
    max-width: 100%;
    max-height: 260px;
    object-fit: contain;
}

#preview-placeholder,
#placeholder-base {
    text-align: center;
    color: #A58B7D;
    padding: 20px;
}

.upload-info {
    display: block;
    margin-top: 9px;
    color: #927B6E;
    font-size: 12px;
}

.color-input-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-input-row input[type="color"] {
    width: 55px;
    height: 44px;
    padding: 3px;
    cursor: pointer;
}

.current-image {
    margin-top: 12px;
}

.current-image img {
    width: 100px;
    height: 100px;
    object-fit: contain;
    padding: 8px;
    background: #FBE9E7;
    border-radius: 12px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th,
td {
    text-align: left;
    padding: 13px 10px;
    border-bottom: 1px solid #EAD6CC;
    font-size: 12px;
    vertical-align: middle;
}

th {
    color: #927B6E;
}

.table-image {
    width: 65px;
    height: 65px;
    object-fit: contain;
    background: #FBE9E7;
    border-radius: 10px;
    padding: 5px;
}

.color-swatch {
    display: inline-block;
    width: 35px;
    height: 35px;
    border: 1px solid #ddd;
    border-radius: 9px;
    vertical-align: middle;
}

.actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.actions form {
    margin: 0;
}

.actions button,
.actions .btn {
    margin-top: 0;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
}

.status-aktif {
    background: #E2F2E5;
    color: #35633C;
}

.status-nonaktif {
    background: #FBE4E4;
    color: #9B4545;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-full {
    grid-column: 1 / -1;
}


/* =========================================================
   LAYOUT PREVIEW AVATAR: KONTROL KIRI, PREVIEW KANAN
========================================================= */

.avatar-preview-container {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 25px;
    align-items: start;
    width: 100%;
    margin-top: 20px;
}

.avatar-controls {
    display: flex;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.avatar-controls label {
    margin-top: 12px;
}

.avatar-preview-side {
    width: 100%;
    position: sticky;
    top: 20px;
}

.avatar-stage {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border-radius: 16px;
    border: 1px solid #EAD6CC;
    background-color: #FBE9E7;
    background-image:
        linear-gradient(45deg, #f3d9d4 25%, transparent 25%),
        linear-gradient(-45deg, #f3d9d4 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f3d9d4 75%),
        linear-gradient(-45deg, transparent 75%, #f3d9d4 75%);
    background-size: 20px 20px;
    background-position:
        0 0,
        0 10px,
        10px -10px,
        -10px 0;
}

.avatar-layer {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
    pointer-events: none;
}

.layer-base {
    z-index: 1;
}

.layer-motif {
    z-index: 2;
}

.layer-telinga {
    z-index: 3;
}

.layer-mata {
    z-index: 4;
}

.layer-aksesori {
    z-index: 5;
}

.avatar-caption {
    text-align: center;
    font-size: 12px;
    margin-top: 10px;
}

#reset-avatar {
    width: fit-content;
    margin-top: 20px;
}


/* RESPONSIVE */

@media(max-width: 700px) {

    body {
        padding: 20px 4%;
    }

    .card {
        padding: 18px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-full {
        grid-column: auto;
    }

    h1 {
        font-size: 23px;
    }

    .avatar-preview-container {
        grid-template-columns: 1fr;
    }

    .avatar-preview-side {
        position: relative;
        top: auto;
        width: 100%;
        max-width: 250px;
        margin: 0 auto;
        grid-row: 1;
    }

    .avatar-controls {
        grid-row: 2;
    }
}
</style>
</head>

<body>

<div class="container">

    <a href="admin.php" class="btn btn-secondary">
        ← Kembali ke Dashboard
    </a>

    <h1>Kelola Kustomisasi Avatar</h1>

    <p>
        Atur karakter dasar dan pilihan desain gantungan hewan POCKETA.
        Gambar PNG transparan dapat digunakan sebagai bagian avatar.
    </p>


    <?php if ($message): ?>
        <div class="message">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>


    <!-- =====================================================
         1. KARAKTER DASAR
    ====================================================== -->

    <section class="card">

        <h2>1. Karakter Dasar</h2>

        <p>
            Upload satu gambar utama yang akan menjadi fondasi avatar.
        </p>

        <?php if ($base): ?>

            <div class="current-image">
                <img
                    src="<?= htmlspecialchars($base["image_path"]) ?>"
                    alt="Karakter dasar"
                >

                <p>
                    <?= htmlspecialchars($base["nama"]) ?>
                </p>
            </div>

        <?php else: ?>

            <p>Belum ada karakter dasar.</p>

        <?php endif; ?>


        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="action" value="save_base">

            <label>Nama karakter</label>

            <input
                type="text"
                name="nama_base"
                value="<?= htmlspecialchars($base["nama"] ?? "Karakter POCKETA") ?>"
                required
            >

            <label>Gambar karakter dasar (PNG)</label>

            <div class="upload-area">

                <input
                    type="file"
                    name="gambar_base"
                    id="gambar-base"
                    accept="image/png"
                    <?= !$base ? "required" : "" ?>
                >

                <div class="image-preview">

                    <img
                        id="preview-base"
                        src="<?= htmlspecialchars($base["image_path"] ?? "") ?>"
                        alt="Preview karakter dasar"
                        style="<?= $base ? "" : "display:none;" ?>"
                    >

                    <div
                        id="placeholder-base"
                        style="<?= $base ? "display:none;" : "" ?>"
                    >
                        <p><strong>Preview karakter dasar</strong></p>
                        <small>Gambar akan muncul di sini.</small>
                    </div>

                </div>

            </div>

            <small class="upload-info">
                Format PNG transparan, maksimal 3 MB.
            </small>

            <button type="submit">
                Simpan Karakter Dasar
            </button>

        </form>

    </section>


    <!-- =====================================================
         2. LIVE PREVIEW AVATAR
    ====================================================== -->

    <section class="card">

        <h2>2. Preview Avatar POCKETA</h2>

        <p>
            Pilih bagian avatar di sebelah kiri dan lihat hasilnya
            secara langsung di sebelah kanan.
        </p>

        <div class="avatar-preview-container">

            <!-- KONTROL DI KIRI -->

            <div class="avatar-controls">

                <label for="pilih-telinga">Telinga</label>

                <select id="pilih-telinga">
                    <option value="">Tanpa tambahan telinga</option>

                    <?php foreach ($previewOptions["telinga"] ?? [] as $item): ?>
                        <option value="<?= htmlspecialchars($item["image_path"]) ?>">
                            <?= htmlspecialchars($item["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>


                <label for="pilih-mata">Mata</label>

                <select id="pilih-mata">
                    <option value="">Mata bawaan karakter</option>

                    <?php foreach ($previewOptions["mata"] ?? [] as $item): ?>
                        <option value="<?= htmlspecialchars($item["image_path"]) ?>">
                            <?= htmlspecialchars($item["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>


                <label for="pilih-motif">Motif</label>

                <select id="pilih-motif">
                    <option value="">Tanpa motif tambahan</option>

                    <?php foreach ($previewOptions["motif"] ?? [] as $item): ?>
                        <option value="<?= htmlspecialchars($item["image_path"]) ?>">
                            <?= htmlspecialchars($item["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>


                <label for="pilih-aksesori">Aksesori</label>

                <select id="pilih-aksesori">
                    <option value="">Tanpa aksesori</option>

                    <?php foreach ($previewOptions["aksesori"] ?? [] as $item): ?>
                        <option value="<?= htmlspecialchars($item["image_path"]) ?>">
                            <?= htmlspecialchars($item["nama"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>


                <button type="button" id="reset-avatar">
                    Reset Preview
                </button>

            </div>


            <!-- PREVIEW DI KANAN -->

            <div class="avatar-preview-side">

                <div class="avatar-stage">

                    <?php if ($base): ?>

                        <img
                            class="avatar-layer layer-base"
                            src="<?= htmlspecialchars($base["image_path"]) ?>"
                            alt="Karakter dasar"
                        >

                        <img
                            class="avatar-layer layer-motif"
                            id="preview-motif"
                            style="display:none;"
                            alt="Motif"
                        >

                        <img
                            class="avatar-layer layer-telinga"
                            id="preview-telinga"
                            style="display:none;"
                            alt="Telinga"
                        >

                        <img
                            class="avatar-layer layer-mata"
                            id="preview-mata"
                            style="display:none;"
                            alt="Mata"
                        >

                        <img
                            class="avatar-layer layer-aksesori"
                            id="preview-aksesori"
                            style="display:none;"
                            alt="Aksesori"
                        >

                    <?php else: ?>

                        <p style="padding:20px;text-align:center;">
                            Upload karakter dasar terlebih dahulu.
                        </p>

                    <?php endif; ?>

                </div>

                <p class="avatar-caption">
                    Preview hasil kustomisasi
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         3. FORM TAMBAH / EDIT PILIHAN
    ====================================================== -->

    <section class="card">

        <h2>
            3. <?= $editData ? "Edit Pilihan Kustomisasi" : "Tambah Pilihan Kustomisasi" ?>
        </h2>

        <p>
            Tambahkan pilihan warna, telinga, mata, motif, atau aksesori.
        </p>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="action" value="save_option">

            <input
                type="hidden"
                name="id"
                value="<?= (int)($editData["id"] ?? 0) ?>"
            >

            <div class="form-grid">

                <div>

                    <label for="kategori">Kategori</label>

                    <select name="kategori" id="kategori" required>

                        <?php foreach ($kategoriNama as $key => $label): ?>

                            <option
                                value="<?= htmlspecialchars($key) ?>"
                                <?= ($editData["kategori"] ?? "") === $key ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label>Nama pilihan</label>

                    <input
                        type="text"
                        name="nama"
                        value="<?= htmlspecialchars($editData["nama"] ?? "") ?>"
                        placeholder="Contoh: Telinga Bulat, Pita, Cream"
                        required
                    >

                </div>


                <!-- INPUT WARNA -->

                <div id="warna-field" class="form-full">

                    <label>Kode warna HEX</label>

                    <div class="color-input-row">

                        <input
                            type="color"
                            id="color-picker"
                            value="<?= htmlspecialchars($editData["kode_warna"] ?? "#F4C7C3") ?>"
                        >

                        <input
                            type="text"
                            name="kode_warna"
                            id="kode-warna"
                            value="<?= htmlspecialchars($editData["kode_warna"] ?? "") ?>"
                            placeholder="#F4C7C3"
                        >

                    </div>

                    <small class="upload-info">
                        Pilih warna yang sesuai dengan bahan yang tersedia.
                    </small>

                </div>


                <!-- INPUT GAMBAR -->

                <div id="gambar-field" class="form-full">

                    <label>Upload Gambar PNG</label>

                    <div class="upload-area">

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            accept="image/png"
                            data-existing="<?= !empty($editData["image_path"]) ? "1" : "0" ?>"
                        >

                        <?php if (!empty($editData["image_path"])): ?>
                            <div class="current-image">
                                <small>Gambar yang sedang digunakan:</small>
                                <br>
                                <img
                                    src="<?= htmlspecialchars($editData["image_path"]) ?>"
                                    alt="Gambar pilihan saat ini"
                                >
                            </div>
                        <?php endif; ?>

                        <div class="image-preview">

                            <img
                                id="preview-gambar"
                                src=""
                                alt="Preview pilihan"
                                style="display:none;"
                            >

                            <div id="preview-placeholder">
                                <p><strong>Preview gambar pilihan</strong></p>
                                <small>Gambar PNG baru akan muncul di sini.</small>
                            </div>

                        </div>

                    </div>

                    <small class="upload-info">
                        Format PNG transparan, maksimal 3 MB.
                        Jika sedang mengedit, gambar lama tetap digunakan
                        apabila tidak mengunggah gambar baru.
                    </small>

                </div>


                <div>

                    <label>Stok</label>

                    <input
                        type="number"
                        name="stok"
                        min="0"
                        value="<?= htmlspecialchars((string)($editData["stok"] ?? 0)) ?>"
                        required
                    >

                </div>


                <div>

                    <label>Harga tambahan (Rp)</label>

                    <input
                        type="number"
                        name="harga_tambahan"
                        min="0"
                        step="500"
                        value="<?= htmlspecialchars((string)($editData["harga_tambahan"] ?? 0)) ?>"
                        required
                    >

                </div>


                <div class="form-full">

                    <label>Status pilihan</label>

                    <select name="status">

                        <option
                            value="aktif"
                            <?= ($editData["status"] ?? "aktif") === "aktif" ? "selected" : "" ?>
                        >
                            Aktif
                        </option>

                        <option
                            value="nonaktif"
                            <?= ($editData["status"] ?? "") === "nonaktif" ? "selected" : "" ?>
                        >
                            Nonaktif
                        </option>

                    </select>

                </div>

            </div>


            <button type="submit">
                <?= $editData ? "Simpan Perubahan" : "Tambah Pilihan" ?>
            </button>


            <?php if ($editData): ?>

                <a
                    href="manage-customization.php"
                    class="btn btn-secondary"
                >
                    Batal Edit
                </a>

            <?php endif; ?>

        </form>

    </section>


    <!-- =====================================================
         4. DAFTAR PILIHAN
    ====================================================== -->

    <section class="card">

        <h2>4. Daftar Pilihan Kustomisasi</h2>

        <p>
            Semua pilihan yang sudah dimasukkan akan tampil di bawah ini.
        </p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Gambar / Warna</th>
                        <th>Kategori</th>
                        <th>Nama</th>
                        <th>Stok</th>
                        <th>Harga Tambahan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($options)): ?>

                    <tr>
                        <td colspan="7">
                            Belum ada pilihan kustomisasi.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($options as $option): ?>

                        <tr>

                            <td>

                                <?php if ($option["kategori"] === "warna"): ?>

                                    <span
                                        class="color-swatch"
                                        style="background:<?= htmlspecialchars($option["kode_warna"] ?? "#FFFFFF") ?>;"
                                    ></span>

                                <?php elseif (!empty($option["image_path"])): ?>

                                    <img
                                        class="table-image"
                                        src="<?= htmlspecialchars($option["image_path"]) ?>"
                                        alt="<?= htmlspecialchars($option["nama"]) ?>"
                                    >

                                <?php endif; ?>

                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $kategoriNama[$option["kategori"]] ?? $option["kategori"]
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($option["nama"]) ?>
                            </td>


                            <td>
                                <?= (int)$option["stok"] ?>
                            </td>


                            <td>
                                Rp<?= number_format(
                                    (float)$option["harga_tambahan"],
                                    0,
                                    ",",
                                    "."
                                ) ?>
                            </td>


                            <td>

                                <span class="status status-<?= htmlspecialchars($option["status"]) ?>">
                                    <?= htmlspecialchars(ucfirst($option["status"])) ?>
                                </span>

                            </td>


                            <td>

                                <div class="actions">

                                    <a
                                        class="btn btn-secondary"
                                        href="?edit=<?= (int)$option["id"] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus pilihan ini?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete_option"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$option["id"] ?>"
                                        >

                                        <button
                                            class="btn-danger"
                                            type="submit"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</div>


<script>

/* =========================================================
   PREVIEW GAMBAR UPLOAD
========================================================= */

function setupImagePreview(inputId, previewId, placeholderId) {

    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);

    if (!input || !preview || !placeholder) {
        return;
    }

    input.addEventListener("change", function () {

        const file = this.files[0];

        if (!file) {
            return;
        }

        if (file.type !== "image/png") {
            alert("Silakan pilih gambar PNG.");
            this.value = "";
            return;
        }

        if (file.size > 3 * 1024 * 1024) {
            alert("Ukuran gambar maksimal 3 MB.");
            this.value = "";
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            preview.src = event.target.result;
            preview.style.display = "block";
            placeholder.style.display = "none";
        };

        reader.readAsDataURL(file);
    });
}


setupImagePreview(
    "gambar",
    "preview-gambar",
    "preview-placeholder"
);


/* Preview karakter dasar */

const inputBase = document.getElementById("gambar-base");
const previewBase = document.getElementById("preview-base");
const placeholderBase = document.getElementById("placeholder-base");

if (inputBase && previewBase && placeholderBase) {

    inputBase.addEventListener("change", function () {

        const file = this.files[0];

        if (!file) return;

        if (file.type !== "image/png") {
            alert("Silakan pilih gambar PNG.");
            this.value = "";
            return;
        }

        if (file.size > 3 * 1024 * 1024) {
            alert("Ukuran gambar maksimal 3 MB.");
            this.value = "";
            return;
        }

        const reader = new FileReader();

        reader.onload = function (event) {
            previewBase.src = event.target.result;
            previewBase.style.display = "block";
            placeholderBase.style.display = "none";
        };

        reader.readAsDataURL(file);
    });
}


/* =========================================================
   TAMPILKAN INPUT SESUAI KATEGORI
========================================================= */

const kategori = document.getElementById("kategori");
const warnaField = document.getElementById("warna-field");
const gambarField = document.getElementById("gambar-field");

const kodeWarna = document.getElementById("kode-warna");
const colorPicker = document.getElementById("color-picker");
const inputGambar = document.getElementById("gambar");

function updateFields() {

    const isWarna = kategori.value === "warna";

    warnaField.style.display = isWarna ? "block" : "none";
    gambarField.style.display = isWarna ? "none" : "block";

    kodeWarna.required = isWarna;

    const hasExistingImage =
        inputGambar.dataset.existing === "1";

    inputGambar.required = !isWarna && !hasExistingImage;
}

kategori.addEventListener("change", updateFields);

updateFields();


/* =========================================================
   SINKRONISASI WARNA
========================================================= */

if (colorPicker && kodeWarna) {

    colorPicker.addEventListener("input", function () {
        kodeWarna.value = this.value.toUpperCase();
    });

    kodeWarna.addEventListener("input", function () {

        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            colorPicker.value = this.value;
        }

    });
}


/* =========================================================
   LIVE PREVIEW AVATAR
========================================================= */

const avatarSelections = [
    {
        select: "pilih-telinga",
        image: "preview-telinga"
    },
    {
        select: "pilih-mata",
        image: "preview-mata"
    },
    {
        select: "pilih-motif",
        image: "preview-motif"
    },
    {
        select: "pilih-aksesori",
        image: "preview-aksesori"
    }
];


avatarSelections.forEach(function (item) {

    const select = document.getElementById(item.select);
    const image = document.getElementById(item.image);

    if (!select || !image) {
        return;
    }

    select.addEventListener("change", function () {

        if (this.value) {
            image.src = this.value;
            image.style.display = "block";
        } else {
            image.removeAttribute("src");
            image.style.display = "none";
        }

    });

});


/* =========================================================
   RESET PREVIEW
========================================================= */

const resetButton = document.getElementById("reset-avatar");

if (resetButton) {

    resetButton.addEventListener("click", function () {

        avatarSelections.forEach(function (item) {

            const select = document.getElementById(item.select);
            const image = document.getElementById(item.image);

            if (select) {
                select.value = "";
            }

            if (image) {
                image.removeAttribute("src");
                image.style.display = "none";
            }

        });

    });

}

</script>

</body>
</html>
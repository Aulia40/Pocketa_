
<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../login.html");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$sukses = "";
$error = "";

$edit_mode = false;

$edit_data = [
    "id" => "",
    "title" => "",
    "slug" => "",
    "summary" => "",
    "content" => "",
    "url" => "",
    "category" => "",
    "published_date" => "",
    "gambar" => ""
];

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// =================================
// MODE EDIT
// =================================
if (isset($_GET["edit"])) {

    $edit_id = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);

    if ($edit_id && $edit_id > 0) {

        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$edit_id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $edit_mode = true;
            $edit_data = array_merge($edit_data, $result);
        } else {
            $error = "Artikel tidak ditemukan.";
        }
    }
}

// =================================
// PROSES TAMBAH DAN EDIT
// =================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_POST["id"] ?? "";
    $title = trim($_POST["title"] ?? "");
    $summary = trim($_POST["summary"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $url = trim($_POST["url"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $published_date = trim($_POST["published_date"] ?? "");
    $nama_gambar = $_POST["gambar_lama"] ?? "";

    $slug = strtolower(
        trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')
    );

    if ($url === "") {
        $url = "#";
    }

    if (
        $title === "" ||
        $summary === "" ||
        $content === "" ||
        $category === "" ||
        $published_date === ""
    ) {
        $error = "Mohon lengkapi semua kolom wajib.";
    }

    if (
        empty($error) &&
        $url !== "#" &&
        !filter_var($url, FILTER_VALIDATE_URL)
    ) {
        $error = "URL tidak valid. Gunakan alamat https:// atau http://.";
    }

    // Upload gambar
    $gambar_baru = null;

    if (
        empty($error) &&
        isset($_FILES["gambar"]) &&
        $_FILES["gambar"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["gambar"]["error"] !== UPLOAD_ERR_OK) {

            $error = "Upload gambar gagal. Periksa ukuran file.";

        } else {

            $tmp = $_FILES["gambar"]["tmp_name"];
            $ext = strtolower(
                pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION)
            );

            $allowed = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($ext, $allowed, true)) {

                $error = "Format gambar harus JPG, JPEG, PNG, atau WEBP.";

            } else {

                $uploadDir = __DIR__ . "/../images/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $gambar_baru = bin2hex(random_bytes(16)) . "." . $ext;

                if (!move_uploaded_file($tmp, $uploadDir . $gambar_baru)) {
                    $error = "Gagal menyimpan gambar ke folder images.";
                    $gambar_baru = null;
                } else {
                    $nama_gambar = $gambar_baru;
                }
            }
        }
    }

    // Simpan database
    if (empty($error)) {

        try {

            if (!empty($id)) {

                $stmt = $pdo->prepare("
                    UPDATE articles SET
                        title = ?,
                        slug = ?,
                        summary = ?,
                        content = ?,
                        url = ?,
                        category = ?,
                        published_date = ?,
                        gambar = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $summary,
                    $content,
                    $url,
                    $category,
                    $published_date,
                    $nama_gambar,
                    $id
                ]);

                $sukses = "Artikel berhasil diperbarui!";

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO articles
                    (title, slug, summary, content, url, category, published_date, gambar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $title,
                    $slug,
                    $summary,
                    $content,
                    $url,
                    $category,
                    $published_date,
                    $nama_gambar
                ]);

                $sukses = "Artikel berhasil ditambahkan!";
            }

            // Hapus gambar lama setelah database berhasil diperbarui
            if (
                $gambar_baru !== null &&
                !empty($_POST["gambar_lama"]) &&
                $_POST["gambar_lama"] !== $gambar_baru
            ) {
                $oldPath = __DIR__ . "/../images/" . basename($_POST["gambar_lama"]);

                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            $edit_mode = false;

            $edit_data = [
                "id" => "",
                "title" => "",
                "slug" => "",
                "summary" => "",
                "content" => "",
                "url" => "",
                "category" => "",
                "published_date" => "",
                "gambar" => ""
            ];

        } catch (PDOException $ex) {

            // Hapus gambar baru jika database gagal
            if ($gambar_baru !== null) {
                $newPath = __DIR__ . "/../images/" . $gambar_baru;

                if (is_file($newPath)) {
                    unlink($newPath);
                }
            }

            $error = "Gagal menyimpan artikel: " . $ex->getMessage();
        }
    }
}

// =================================
// PROSES HAPUS
// =================================
if (isset($_GET["hapus"])) {

    $id = filter_input(INPUT_GET, "hapus", FILTER_VALIDATE_INT);

    if ($id && $id > 0) {

        try {

            $stmt = $pdo->prepare("SELECT gambar FROM articles WHERE id = ?");
            $stmt->execute([$id]);

            $artikel = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($artikel) {

                $stmtDelete = $pdo->prepare("DELETE FROM articles WHERE id = ?");
                $stmtDelete->execute([$id]);

                if (!empty($artikel["gambar"])) {

                    $path = __DIR__ . "/../images/" . basename($artikel["gambar"]);

                    if (is_file($path)) {
                        unlink($path);
                    }
                }

                header("Location: manage-articles.php?status=deleted");
                exit;

            } else {
                $error = "Artikel tidak ditemukan.";
            }

        } catch (PDOException $ex) {
            $error = "Gagal menghapus artikel: " . $ex->getMessage();
        }
    }
}

if (($_GET["status"] ?? "") === "deleted") {
    $sukses = "Artikel berhasil dihapus!";
}

// =================================
// AMBIL DAFTAR ARTIKEL
// =================================
try {

    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {

    $articles = [];
    $error = "Gagal mengambil artikel: " . $ex->getMessage();

}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel | POCKETA</title>

    <link rel="stylesheet" href="../style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
            color: #5C4033;
            min-height: 100vh;
        }

        .navbar {
            background: #FFF9F6;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(92,64,51,.07);
        }

        .logo {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .logo span {
            color: #D89A9A;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .card {
            background: #FFF9F6;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(92,64,51,.06);
            margin-bottom: 30px;
            border: 1px solid #EAD6CC;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group.full {
            grid-column: span 2;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            color: #8B6F60;
        }

        input, textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: 1px solid #EAD6CC;
            background: #FDF2EF;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            color: #5C4033;
            outline: none;
        }

        input:focus, textarea:focus {
            border-color: #D89A9A;
            background: #FFF;
        }

        button, .btn {
            background: #5C4033;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        button:hover, .btn:hover {
            background: #D89A9A;
        }

        .btn-edit {
            background: #99745F;
        }

        .btn-danger {
            background: #C17E86;
        }

        .btn-cancel {
            background: #927B6E;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            text-align: left;
            font-size: 11px;
            color: #A58B7D;
            padding: 12px;
            border-bottom: 1px solid #EAD6CC;
        }

        td {
            padding: 15px 12px;
            font-size: 12px;
            border-bottom: 1px solid #F3E8E2;
            vertical-align: middle;
        }

        .thumb-preview {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background: #EAD6CC;
        }

        .alert-sukses {
            background: #E2F2E5;
            color: #56805D;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #FBE4E4;
            color: #B65F5F;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #5C4033;
            font-weight: 700;
            font-size: 13px;
        }

        @media(max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: span 1;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="logo">POCKE<span>TA.</span></div>

    <a href="/dashboard/admin.php" class="btn">
        <i class="fa-solid fa-arrow-left"></i>
        Dashboard Admin
    </a>
</nav>

<div class="container">

    <a href="/dashboard/admin.php" class="back-link">
        <i class="fa-solid fa-chevron-left"></i>
        Kembali ke Dashboard
    </a>

    <?php if ($sukses): ?>
        <div class="alert-sukses"><?= e($sukses) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">

        <h2>
            <?= $edit_mode ? "Edit Artikel" : "Tulis Artikel Baru POCKETA" ?>
        </h2>

        <form action="/dashboard/manage-articles.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= e($edit_data["id"]) ?>">

            <input type="hidden" name="gambar_lama" value="<?= e($edit_data["gambar"]) ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Judul Artikel</label>
                    <input
                        type="text"
                        name="title"
                        required
                        value="<?= e($edit_data["title"]) ?>"
                        placeholder="Contoh: Tips Merawat Dompet Kain POCKETA"
                    >
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <input
                        type="text"
                        name="category"
                        required
                        value="<?= e($edit_data["category"]) ?>"
                        placeholder="Contoh: Tips & Perawatan"
                    >
                </div>

                <div class="form-group">
                    <label>URL / Link Eksternal (Opsional)</label>
                    <input
                        type="url"
                        name="url"
                        value="<?= e($edit_data["url"] === "#" ? "" : $edit_data["url"]) ?>"
                        placeholder="https://contoh.com"
                    >
                </div>

                <div class="form-group">
                    <label>Tanggal Publikasi</label>
                    <input
                        type="text"
                        name="published_date"
                        required
                        value="<?= e($edit_data["published_date"]) ?>"
                        placeholder="Contoh: 17 Nov 2026"
                    >
                </div>

                <div class="form-group">
                    <label>Gambar Utama</label>
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">

                    <?php if ($edit_mode && !empty($edit_data["gambar"])): ?>
                        <p style="margin-top:10px;">Gambar saat ini:</p>
                        <img
                            src="/images/<?= e(rawurlencode($edit_data["gambar"])) ?>"
                            class="thumb-preview"
                            alt="Gambar artikel"
                        >
                    <?php endif; ?>
                </div>

                <div class="form-group full">
                    <label>Ringkasan Artikel</label>
                    <textarea name="summary" rows="3" required><?= e($edit_data["summary"]) ?></textarea>
                </div>

                <div class="form-group full">
                    <label>Isi Lengkap Artikel</label>
                    <textarea name="content" rows="8" required><?= e($edit_data["content"]) ?></textarea>
                </div>

                <div class="form-group full">

                    <button type="submit">
                        <i class="fa-solid fa-save"></i>
                        <?= $edit_mode ? "Perbarui Artikel" : "Publikasikan Artikel" ?>
                    </button>

                    <?php if ($edit_mode): ?>
                        <a href="/dashboard/manage-articles.php" class="btn btn-cancel">
                            Batal
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        </form>
    </div>

    <div class="card">

        <h2>Daftar Artikel Terbit</h2>

        <div style="overflow-x:auto;">

            <table>

                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($articles) > 0): ?>

                    <?php foreach ($articles as $art): ?>

                        <tr>

                            <td>
                                <?php if (!empty($art["gambar"])): ?>
                                    <img
                                        src="/images/<?= e(rawurlencode($art["gambar"])) ?>"
                                        class="thumb-preview"
                                        alt="Thumbnail"
                                    >
                                <?php else: ?>
                                    <i class="fa-solid fa-newspaper"></i>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?= e($art["title"]) ?></strong>
                            </td>

                            <td><?= e($art["category"]) ?></td>

                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">

                                    <a
                                        href="/dashboard/manage-articles.php?edit=<?= (int)$art["id"] ?>"
                                        class="btn btn-edit"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Edit
                                    </a>

                                    <a
                                        href="/dashboard/manage-articles.php?hapus=<?= (int)$art["id"] ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Yakin ingin menghapus artikel ini?')"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                        Hapus
                                    </a>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" style="text-align:center;padding:30px;">
                            Belum ada artikel tersimpan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>

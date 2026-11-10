<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

$nama = $_SESSION["nama"];
$sukses = "";
$error = "";

$edit_mode = false;
$edit_data = [
    "id" => "",
    "title" => "",
    "slug" => "",
    "summary" => "",
    "url" => "",
    "category" => "",
    "published_date" => "",
    "gambar" => ""
];

// Cek jika mode edit dipanggil
if (isset($_GET["edit"])) {
    $edit_id = (int)$_GET["edit"];
    $stmtEdit = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmtEdit->execute([$edit_id]);
    $resultEdit = $stmtEdit->fetch(PDO::FETCH_ASSOC);
    if ($resultEdit) {
        $edit_mode = true;
        $edit_data = $resultEdit;
    }
}

// Proses Simpan (Tambah atau Update)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"] ?? "";
    $title = trim($_POST["title"] ?? "");
    
    // Perbaikan: Ambil slug dari POST jika ada, jika tidak buat otomatis dari title
    $slug_input = trim($_POST["slug"] ?? "");
    if (!empty($slug_input)) {
        $slug = $slug_input;
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }

    $summary = trim($_POST["summary"] ?? "");
    $url = trim($_POST["url"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $published_date = trim($_POST["published_date"] ?? "");
    $nama_gambar = $_POST["gambar_lama"] ?? "";

    // Handle Upload Gambar Baru
    if (isset($_FILES["gambar"]) && $_FILES["gambar"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["gambar"]["tmp_name"];
        $fileName = $_FILES["gambar"]["name"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../images/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Hapus gambar lama jika ada
                if (!empty($nama_gambar) && file_exists("../images/" . $nama_gambar)) {
                    unlink("../images/" . $nama_gambar);
                }
                $nama_gambar = $newFileName;
            } else {
                $error = "Terjadi kesalahan saat mengunggah gambar.";
            }
        } else {
            $error = "Format gambar tidak valid. Hanya JPG, JPEG, PNG, dan WEBP.";
        }
    }

    if (empty($error)) {
        if (!empty($title) && !empty($url)) {
            if (!empty($id)) {
                // Update Artikel
                $stmt = $pdo->prepare("UPDATE articles SET title = ?, slug = ?, summary = ?, url = ?, category = ?, published_date = ?, gambar = ? WHERE id = ?");
                if ($stmt->execute([$title, $slug, $summary, $url, $category, $published_date, $nama_gambar, $id])) {
                    $sukses = "Artikel berhasil diperbarui!";
                    $edit_mode = false;
                    $edit_data = ["id" => "", "title" => "", "slug" => "", "summary" => "", "url" => "", "category" => "", "published_date" => "", "gambar" => ""];
                } else {
                    $error = "Gagal memperbarui artikel.";
                }
            } else {
                // Tambah Artikel Baru
                $stmt = $pdo->prepare("INSERT INTO articles (title, slug, summary, url, category, published_date, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$title, $slug, $summary, $url, $category, $published_date, $nama_gambar])) {
                    $sukses = "Artikel berhasil ditambahkan!";
                } else {
                    $error = "Gagal menyimpan artikel ke database.";
                }
            }
        } else {
            $error = "Judul dan URL artikel wajib diisi!";
        }
    }
}

// Proses Hapus Artikel
if (isset($_GET["hapus"])) {
    $id = (int)$_GET["hapus"];
    
    $stmtImg = $pdo->prepare("SELECT gambar FROM articles WHERE id = ?");
    $stmtImg->execute([$id]);
    $artData = $stmtImg->fetch(PDO::FETCH_ASSOC);
    
    if ($artData && !empty($artData["gambar"])) {
        $filePath = "../images/" . $artData["gambar"];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage-articles.php?status=deleted");
    exit;
}

// Ambil semua daftar artikel
$stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Artikel | Admin POCKETA</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC); color: #5C4033; min-height: 100vh; }
        .navbar { background: #FFF9F6; padding: 18px 6%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(92,64,51,.07); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 26px; font-weight: 800; letter-spacing: 2px; }
        .logo span { color: #D89A9A; }
        .container { width: 90%; max-width: 1100px; margin: 40px auto; }
        .card { background: #FFF9F6; padding: 30px; border-radius: 20px; box-shadow: 0 8px 25px rgba(92,64,51,.06); margin-bottom: 30px; border: 1px solid #EAD6CC; }
        h2 { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; color: #5C4033; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #8B6F60; }
        input, textarea { width: 100%; padding: 12px 15px; border-radius: 12px; border: 1px solid #EAD6CC; background: #FDF2EF; font-family: 'DM Sans', sans-serif; font-size: 13px; color: #5C4033; outline: none; }
        input:focus, textarea:focus { border-color: #D89A9A; background: #FFF; }
        button, .btn { background: #5C4033; color: white; border: none; padding: 10px 18px; border-radius: 25px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: .3s; }
        button:hover, .btn:hover { background: #D89A9A; }
        .btn-edit { background: #99745F; }
        .btn-edit:hover { background: #7A5B49; }
        .btn-danger { background: #C17E86; }
        .btn-danger:hover { background: #A05C64; }
        .btn-cancel { background: #927B6E; margin-left: 10px; }
        .btn-cancel:hover { background: #756155; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; font-size: 11px; color: #A58B7D; padding: 12px; border-bottom: 1px solid #EAD6CC; }
        td { padding: 15px 12px; font-size: 12px; border-bottom: 1px solid #F3E8E2; vertical-align: middle; }
        .thumb-preview { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; background: #EAD6CC; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #8B6F60; }
        .alert-sukses { background: #E2F2E5; color: #56805D; padding: 12px 18px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; font-weight: 600; }
        .alert-error { background: #FBE4E4; color: #B65F5F; padding: 12px 18px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; font-weight: 600; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #5C4033; font-weight: 700; font-size: 13px; }
        .back-link:hover { color: #D89A9A; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">POCKE<span>TA.</span></div>
    <a href="user.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Dashboard Admin</a>
</nav>

<div class="container">

    <a href="user.php" class="back-link"><i class="fa-solid fa-chevron-left"></i> Kembali ke Dashboard</a>

    <?php if ($sukses): ?>
        <div class="alert-sukses"><?= $sukses ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Form Tambah / Edit Artikel -->
    <div class="card">
        <h2><?= $edit_mode ? "Edit Artikel" : "Tambah Artikel & Rekomendasi Link Baru" ?></h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data["id"]) ?>">
            <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($edit_data["gambar"]) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Judul Artikel</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($edit_data["title"]) ?>" placeholder="Contoh: Tips Merawat Dompet">
                </div>
                <div class="form-group">
                    <label>Kategori / Label Badge</label>
                    <input type="text" name="category" required value="<?= htmlspecialchars($edit_data["category"]) ?>" placeholder="Contoh: Perawatan / Tips">
                </div>
                <div class="form-group">
                    <label>URL / Link Artikel</label>
                    <input type="url" name="url" required value="<?= htmlspecialchars($edit_data["url"]) ?>" placeholder="https://contoh.com/artikel">
                </div>
                <div class="form-group">
                    <label>Tanggal / Keterangan Waktu</label>
                    <input type="text" name="published_date" required value="<?= htmlspecialchars($edit_data["published_date"]) ?>" placeholder="Contoh: 10 Nov 2026">
                </div>
                <div class="form-group full">
                    <label>Gambar Thumbnail Artikel <?= $edit_mode ? "(Biarkan kosong jika tidak ingin mengubah gambar)" : "" ?></label>
                    <?php if (!empty($edit_data["gambar"])): ?>
                        <div style="margin-bottom: 8px;">
                            <img src="../images/<?= htmlspecialchars($edit_data["gambar"]) ?>" style="width: 70px; height: 70px; border-radius: 8px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="gambar" accept="image/*">
                </div>
                <div class="form-group full">
                    <label>Ringkasan Singkat</label>
                    <textarea name="summary" rows="3" required placeholder="Deskripsi singkat mengenai isi artikel..."><?= htmlspecialchars($edit_data["summary"]) ?></textarea>
                </div>
                <div class="form-group full">
                    <button type="submit"><i class="fa-solid fa-save"></i> <?= $edit_mode ? "Perbarui Artikel" : "Simpan Artikel" ?></button>
                    <?php if ($edit_mode): ?>
                        <a href="manage-articles.php" class="btn btn-cancel">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Daftar Artikel -->
    <div class="card">
        <h2>Daftar Artikel Aktif</h2>
        <div style="overflow-x: auto;">
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
                                        <img src="../images/<?= htmlspecialchars($art["gambar"]) ?>" class="thumb-preview" alt="Thumb">
                                    <?php else: ?>
                                        <div class="thumb-preview"><i class="fa-solid fa-newspaper"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($art["title"]) ?></strong></td>
                                <td><?= htmlspecialchars($art["category"]) ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="manage-articles.php?edit=<?= $art["id"] ?>" class="btn btn-edit">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a href="manage-articles.php?hapus=<?= $art["id"] ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #A58B7D; padding: 30px;">Belum ada artikel tersimpan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
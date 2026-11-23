<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.html");
    exit;
}

require_once "../config/database.php";

// Proses Ubah Status (Publish / Reject / Delete)
if (isset($_GET["action"]) && isset($_GET["id"])) {
    $id = (int)$_GET["id"];
    $action = $_GET["action"];

    if ($action === "publish") {
        $stmt = $pdo->prepare("UPDATE community_articles SET status = 'published' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === "reject") {
        $stmt = $pdo->prepare("UPDATE community_articles SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action === "delete") {
        $stmt = $pdo->prepare("DELETE FROM community_articles WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: manage-community.php");
    exit;
}

// Ambil semua artikel komunitas beserta nama penggunanya
$stmt = $pdo->query("SELECT c.*, u.nama AS penulis FROM community_articles c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Artikel Komunitas | Admin POCKETA</title>
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
        .container { width: 90%; max-width: 1200px; margin: 40px auto; }
        .card { background: #FFF9F6; padding: 30px; border-radius: 20px; box-shadow: 0 8px 25px rgba(92,64,51,.06); margin-bottom: 30px; border: 1px solid #EAD6CC; }
        h2 { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 20px; color: #5C4033; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; min-width: 700px; }
        th { text-align: left; font-size: 11px; color: #A58B7D; padding: 12px; border-bottom: 1px solid #EAD6CC; }
        td { padding: 15px 12px; font-size: 12px; border-bottom: 1px solid #F3E8E2; vertical-align: middle; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-pending { background: #FFF0D8; color: #A87932; }
        .badge-published { background: #E2F2E5; color: #56805D; }
        .badge-rejected { background: #FBE4E4; color: #B65F5F; }
        .btn { background: #5C4033; color: white; border: none; padding: 8px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: .3s; }
        .btn:hover { background: #D89A9A; }
        .btn-success { background: #56805D; }
        .btn-success:hover { background: #436548; }
        .btn-danger { background: #C17E86; }
        .btn-danger:hover { background: #A05C64; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #5C4033; font-weight: 700; font-size: 13px; }
        .back-link:hover { color: #D89A9A; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">POCKE<span>TA.</span></div>
    <a href="index.php" class="btn"><i class="fa-solid fa-arrow-left"></i> Dashboard Admin</a>
</nav>

<div class="container">
    <a href="index.php" class="back-link"><i class="fa-solid fa-chevron-left"></i> Kembali ke Dashboard</a>

    <div class="card">
        <h2>Moderasi Artikel & Cerita Komunitas Pelanggan</h2>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($articles) > 0): ?>
                        <?php foreach ($articles as $art): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($art["title"]) ?></strong></td>
                                <td><?= htmlspecialchars($art["penulis"]) ?></td>
                                <td><?= htmlspecialchars($art["category"]) ?></td>
                                <td>
                                    <span class="badge badge-<?= $art["status"] ?>">
                                        <?= ucfirst($art["status"]) ?>
                                    </span>
                                </td>
                                <td><?= date("d/m/Y", strtotime($art["created_at"])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <?php if ($art["status"] !== "published"): ?>
                                            <a href="manage-community.php?action=publish&id=<?= $art["id"] ?>" class="btn btn-success">Setujui</a>
                                        <?php endif; ?>
                                        <?php if ($art["status"] !== "rejected"): ?>
                                            <a href="manage-community.php?action=reject&id=<?= $art["id"] ?>" class="btn" style="background: #D89A9A;">Tolak</a>
                                        <?php endif; ?>
                                        <a href="manage-community.php?action=delete&id=<?= $art["id"] ?>" class="btn btn-danger" onclick="return confirm('Hapus artikel ini?')">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #A58B7D; padding: 30px;">Belum ada kiriman artikel dari pelanggan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
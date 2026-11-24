<?php
session_start();

require_once "../config/database.php";

// Ambil ID produk dari URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: products.php");
    exit;
}

// Ambil data produk dari database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika produk tidak ditemukan
if (!$product) {
    http_response_code(404);
    die("Produk tidak ditemukan. <a href='products.php'>Kembali ke katalog</a>");
}

$user_id = $_SESSION["user_id"] ?? 0;
$nama_user = $_SESSION["nama"] ?? "Tamu";
$pesan_review = "";
$error_review = "";

// Proses Kirim / Edit Ulasan (Langsung Approved)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    if (!$user_id) {
        header("Location: ../login.html");
        exit;
    }

    $review_title = trim($_POST["review_title"] ?? "");
    $rating = (int)($_POST["rating"] ?? 0);
    $review_content = trim($_POST["review_content"] ?? "");
    $edit_review_id = isset($_POST["edit_review_id"]) ? (int)$_POST["edit_review_id"] : 0;

    if (!empty($review_title) && !empty($review_content) && $rating >= 1 && $rating <= 5) {
        if ($edit_review_id > 0) {
            // Update ulasan (langsung approved)
            $stmtUpd = $pdo->prepare("UPDATE product_reviews SET review_title = ?, rating = ?, review_content = ?, status = 'approved' WHERE id = ? AND user_id = ?");
            $stmtUpd->execute([$review_title, $rating, $review_content, $edit_review_id, $user_id]);
            $pesan_review = "Ulasan berhasil diperbarui!";
        } else {
            // Tambah ulasan baru (langsung approved)
            $stmtIns = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, user_name, review_title, rating, review_content, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
            $stmtIns->execute([$id, $user_id, $nama_user, $review_title, $rating, $review_content]);
            $pesan_review = "Terima kasih! Ulasan berhasil dikirim.";
        }
    } else {
        $error_review = "Mohon lengkapi judul ulasan, pilih bintang, dan isi ulasan.";
    }
}

// Ambil semua ulasan untuk produk ini
$stmtRev = $pdo->prepare("SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC");
$stmtRev->execute([$id]);
$reviews = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

// Cek jika user ingin mengedit ulasan tertentu
$edit_data = null;
if (isset($_GET["edit_review"]) && $user_id) {
    $edit_id = (int)$_GET["edit_review"];
    foreach ($reviews as $rev) {
        if ((int)$rev["id"] === $edit_id && (int)$rev["user_id"] === $user_id) {
            $edit_data = $rev;
            break;
        }
    }
}

// Format harga
function rupiah($harga) {
    return "Rp" . number_format($harga, 0, ",", ".");
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product["nama_produk"]) ?> | POCKETA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/user-navbar.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #FBE9E7;
            color: #5C4033;
        }

        .container {
            max-width: 1000px;
            width: 90%;
            margin: 50px auto;
        }

        .back {
            display: inline-block;
            text-decoration: none;
            color: #8B6F60;
            margin-bottom: 25px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: #FFF9F6;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08);
            margin-bottom: 30px;
        }

        .product-image {
            min-height: 350px;
            background: #F4C7C3;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 100px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            min-height: 350px;
            object-fit: cover;
        }

        .category {
            color: #A66A6A;
            font-size: 14px;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .price {
            font-size: 25px;
            font-weight: bold;
            color: #A66A6A;
            margin-bottom: 20px;
        }

        .description {
            color: #8B6F60;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .stock {
            margin-bottom: 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        input[type="number"], input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #EAD6CC;
            border-radius: 8px;
            font-size: 15px;
            margin-bottom: 20px;
            background: #FFFDFB;
            color: #5C4033;
            outline: none;
        }

        input[type="number"] {
            width: 90px;
        }

        /* Star Rating Picker Styling */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
            margin-bottom: 20px;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            font-size: 26px;
            color: #EAD6CC;
            cursor: pointer;
            transition: color 0.2s;
            margin-bottom: 0;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #E6A700;
        }

        button {
            display: block;
            width: 100%;
            padding: 14px;
            background: #5C4033;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            cursor: pointer;
        }

        button:hover {
            background: #A66A6A;
        }

        button:disabled {
            background: #BCAAA4;
            cursor: not-allowed;
        }

        .notice {
            color: #A66A6A;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Review Section Styling */
        .reviews-card {
            background: #FFF9F6;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08);
        }
        .reviews-card h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: #5C4033;
        }
        .review-notice {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            background: #E2F2E5;
            color: #56805D;
            font-weight: bold;
        }
        .review-notice.error {
            background: #FBE4E4;
            color: #B65F5F;
        }
        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid #EAD6CC;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .stars {
            color: #E6A700;
            letter-spacing: 2px;
            margin: 6px 0;
            font-size: 16px;
        }
        .review-meta {
            font-size: 12px;
            color: #8B6F60;
            margin-bottom: 8px;
        }
        .edit-review-link {
            font-size: 13px;
            color: #A66A6A;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 6px;
        }
        .edit-review-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 700px) {
            header {
                flex-direction: column;
                gap: 15px;
            }
            nav a {
                margin: 0 8px;
            }
            .product-detail {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .product-image {
                min-height: 250px;
            }
            .product-image img {
                min-height: 250px;
            }
        }
    </style>
</head>

<body>

<?php
$activePage = "home";
include "../includes/user-navbar.php";
?>

<main class="container">

    <a href="products.php" class="back">← Kembali ke Katalog</a>

    <div class="product-detail">

        <div class="product-image">
            <?php if (!empty($product["gambar"])): ?>
                <img
                    src="../images/<?= e(basename($product["gambar"])) ?>"
                    alt="<?= e($product["nama_produk"]) ?>"
                    onerror="this.style.display='none'; this.parentElement.innerHTML='👜';"
                >
            <?php else: ?>
                👜
            <?php endif; ?>
        </div>

        <div class="product-info">
            <div class="category">
                <?= e($product["kategori"]) ?>
            </div>

            <h1><?= e($product["nama_produk"]) ?></h1>

            <div class="price">
                <?= rupiah($product["harga"]) ?>
            </div>

            <div class="description">
                <?= nl2br(e($product["deskripsi"] ?: "Produk handmade dari POCKETA.")) ?>
            </div>

            <div class="stock">
                Status: <strong><?= e($product["status"]) ?></strong>
                <br>
                Stok tersedia: <strong><?= (int)$product["stok"] ?></strong>
            </div>

            <?php if ((int)$product["stok"] > 0): ?>
                <form action="add-to-cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= (int)$product["id"] ?>">
                    <label for="jumlah">Jumlah Pembelian</label>
                    <input
                        type="number"
                        id="jumlah"
                        name="jumlah"
                        value="1"
                        min="1"
                        max="<?= (int)$product["stok"] ?>"
                        required
                    >
                    <button type="submit">Tambah ke Keranjang</button>
                </form>
            <?php else: ?>
                <button type="button" disabled>Stok Habis</button>
                <p class="notice">Maaf, produk ini sedang tidak tersedia.</p>
            <?php endif; ?>

        </div>

    </div>

    <!-- BAGIAN ULASAN PRODUK (NATIVE PHP - STAR RATING) -->
    <div class="reviews-card" id="pocketa-reviews">
        <h3>Ulasan Pelanggan</h3>
        <p style="font-size: 13px; color: #8B6F60; margin-bottom: 25px;">Bagikan pengalaman dan pendapatmu mengenai produk ini.</p>

        <?php if ($pesan_review): ?>
            <div class="review-notice"><?= $pesan_review ?></div>
        <?php endif; ?>
        <?php if ($error_review): ?>
            <div class="review-notice error"><?= $error_review ?></div>
        <?php endif; ?>

        <?php if ($user_id): ?>
            <!-- Form Ulasan dengan Bintang Interaktif -->
            <form method="POST" action="product-detail.php?id=<?= $id ?>#pocketa-reviews" style="margin-bottom: 35px;">
                <input type="hidden" name="edit_review_id" value="<?= $edit_data ? (int)$edit_data["id"] : 0 ?>">
                
                <div>
                    <label for="review_title">Judul Ulasan</label>
                    <input type="text" id="review_title" name="review_title" value="<?= $edit_data ? e($edit_data["review_title"]) : "" ?>">
                </div>

                <div>
                    <label>Rating Bintang</label>
                    <div class="star-rating">
                        <?php 
                        $current_rating = $edit_data ? (int)$edit_data["rating"] : 5;
                        for ($i = 5; $i >= 1; $i--): 
                        ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= ($current_rating === $i) ? 'checked' : '' ?> required>
                            <label for="star<?= $i ?>" title="<?= $i ?> Bintang">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div>
                    <label for="review_content">Deskripsi</label>
                    <textarea id="review_content" name="review_content" rows="4" ><?= $edit_data ? e($edit_data["review_content"]) : "" ?></textarea>
                </div>

                <button type="submit" name="submit_review" style="width: auto; padding: 12px 25px;">
                    <?= $edit_data ? "Simpan Perubahan Ulasan" : "Kirim Ulasan" ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="product-detail.php?id=<?= $id ?>" style="margin-left: 15px; font-size: 13px; color: #8B6F60; text-decoration: none;">Batal edit</a>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <p style="font-size: 13px; color: #8B6F60; margin-bottom: 25px;">
                Silakan <a href="../login.html" style="color: #A66A6A; font-weight: bold;">login</a> untuk menulis ulasan produk.
            </p>
        <?php endif; ?>

        <!-- Daftar Ulasan -->
        <div>
            <h4 style="font-size: 16px; margin-bottom: 15px; color: #5C4033;">Semua Ulasan</h4>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-item">
                        <h5 style="font-size: 15px; color: #5C4033;"><?= e($rev["review_title"]) ?></h5>
                        <div class="stars">
                            <?php 
                            $rating_val = (int)$rev["rating"];
                            echo str_repeat("★", $rating_val) . str_repeat("☆", 5 - $rating_val);
                            ?>
                        </div>
                        <div class="review-meta">
                            <strong><?= e($rev["user_name"]) ?></strong> · <?= date("d M Y", strtotime($rev["created_at"])) ?>
                        </div>
                        <p style="font-size: 13px; line-height: 1.6; color: #5C4033;"><?= nl2br(e($rev["review_content"])) ?></p>
                        
                        <?php if ((int)$rev["user_id"] === $user_id): ?>
                            <a href="product-detail.php?id=<?= $id ?>&edit_review=<?= $rev["id"] ?>#pocketa-reviews" class="edit-review-link">
                                <i class="fa-solid fa-pen-to-square"></i> Edit ulasan saya
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="font-size: 13px; color: #8B6F60;">Belum ada ulasan untuk produk ini. Jadilah yang pertama memberikan ulasan!</p>
            <?php endif; ?>
        </div>
    </div>

</main>

</body>
</html>
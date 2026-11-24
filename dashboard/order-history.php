
<?php
session_start();

require_once "../config/database.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.html");
    exit;
}

$userId = $_SESSION["user_id"];

// Filter pencarian
$keyword = trim($_GET["search"] ?? "");
$filterStatus = trim($_GET["status"] ?? "");

// Ambil riwayat pesanan
$sql = "
    SELECT *
    FROM orders
    WHERE user_id = ?
";

$params = [$userId];

// Pencarian nomor pesanan
if ($keyword !== "") {
    $sql .= " AND order_number LIKE ?";
    $params[] = "%" . $keyword . "%";
}

// Filter status
if ($filterStatus !== "") {
    $sql .= " AND status_pesanan = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function rupiah($harga) {
    return "Rp" . number_format(
        (float)$harga,
        0,
        ",",
        "."
    );
}

// Warna status
function statusClass($status) {

    $status = strtolower(trim((string)$status));

    if ($status === "selesai") {
        return "completed";
    }

    if ($status === "dikirim" || $status === "dalam pengiriman") {
        return "shipping";
    }

    if ($status === "dikemas" || $status === "diproses" || $status === "sedang diproses") {
        return "processing";
    }

    if ($status === "dibatalkan") {
        return "cancelled";
    }

    return "pending";
}

// Statistik pesanan
$countStmt = $pdo->prepare("
    SELECT status_pesanan, COUNT(*) AS jumlah
    FROM orders
    WHERE user_id = ?
    GROUP BY status_pesanan
");

$countStmt->execute([$userId]);
$statusCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$totalOrders = array_sum($statusCounts);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pesanan | POCKETA</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="/assets/css/user-navbar.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: linear-gradient(
                135deg,
                #FBE9E7,
                #F4C7C3,
                #EAD6CC
            );
            color: #5C4033;
            min-height: 100vh;
        }

        .history-container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto 60px;
        }

        /* HEADER */

        .history-heading {
            margin-bottom: 25px;
        }

        .history-heading h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .history-heading p {
            font-size: 14px;
            color: #8B6F60;
        }

        /* STATISTIK */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #FFF9F6;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #EAD6CC;
            box-shadow: 0 5px 15px rgba(92,64,51,.05);
        }

        .stat-card small {
            color: #9B8174;
            font-size: 12px;
        }

        .stat-card h2 {
            font-size: 25px;
            margin-top: 9px;
            color: #A66A6A;
        }

        /* FILTER */

        .filter-card {
            background: #FFF9F6;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #EAD6CC;
            margin-bottom: 22px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-form input,
        .filter-form select {
            flex: 1;
            min-width: 180px;
            padding: 12px 14px;
            border: 1px solid #EAD6CC;
            border-radius: 10px;
            color: #5C4033;
            background: white;
            font-size: 13px;
            outline: none;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: #D89A9A;
        }

        .filter-btn {
            border: none;
            background: #8B6F60;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
        }

        .filter-btn:hover {
            background: #5C4033;
        }

        .reset-btn {
            text-decoration: none;
            color: #A66A6A;
            padding: 12px 15px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
        }

        /* ORDER CARD */

        .order-card {
            background: #FFF9F6;
            border: 1px solid #EAD6CC;
            border-radius: 16px;
            margin-bottom: 18px;
            padding: 22px;
            box-shadow: 0 5px 15px rgba(92,64,51,.05);
            transition: .2s;
        }

        .order-card:hover {
            box-shadow: 0 8px 22px rgba(92,64,51,.09);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            flex-wrap: wrap;
            padding-bottom: 17px;
            border-bottom: 1px solid #EAD6CC;
        }

        .order-number {
            color: #A66A6A;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 7px;
        }

        .order-date {
            color: #9B8174;
            font-size: 12px;
        }

        /* STATUS */

        .status {
            padding: 8px 13px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .pending {
            background: #FFF0D9;
            color: #A87832;
        }

        .processing {
            background: #FCE4D8;
            color: #A65F45;
        }

        .shipping {
            background: #E6E9FA;
            color: #626EA6;
        }

        .completed {
            background: #E4F1E5;
            color: #54805A;
        }

        .cancelled {
            background: #F9E1E1;
            color: #B35F5F;
        }

        /* ORDER INFO */

        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px 0;
        }

        .info-label {
            font-size: 12px;
            color: #9B8174;
            margin-bottom: 7px;
        }

        .info-value {
            font-size: 13px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        /* FOOTER */

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            padding-top: 17px;
            border-top: 1px solid #EAD6CC;
        }

        .total-label {
            color: #9B8174;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .total-price {
            font-size: 19px;
            font-weight: bold;
            color: #A66A6A;
        }

        .detail-btn {
            text-decoration: none;
            background: #8B6F60;
            color: white;
            padding: 12px 18px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: bold;
            transition: .2s;
        }

        .detail-btn:hover {
            background: #5C4033;
        }

        /* EMPTY */

        .empty {
            background: #FFF9F6;
            border-radius: 18px;
            border: 1px solid #EAD6CC;
            padding: 55px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 10px;
        }

        .empty p {
            color: #8B6F60;
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .shop-btn {
            display: inline-block;
            text-decoration: none;
            background: #8B6F60;
            color: white;
            padding: 13px 22px;
            border-radius: 10px;
            font-size: 13px;
        }

        .shop-btn:hover {
            background: #5C4033;
        }

        .result-info {
            font-size: 13px;
            color: #8B6F60;
            margin-bottom: 15px;
        }

        @media(max-width: 650px) {

            .history-container {
                margin-top: 25px;
            }

            .stats-grid {
                gap: 9px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-card h2 {
                font-size: 21px;
            }

            .stat-card small {
                font-size: 10px;
            }

            .order-info {
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form input,
            .filter-form select {
                width: 100%;
            }

            .filter-btn {
                width: 100%;
            }

            .order-card {
                padding: 17px;
            }

            .history-heading h1 {
                font-size: 24px;
            }
        }

    </style>
</head>

<body>

<?php
$activePage = "orders";
include "../includes/user-navbar.php";
?>

<main class="history-container">

    <!-- HEADER -->

    <div class="history-heading">

        <h1>Riwayat Pesanan 🛍️</h1>

        <p>
            Pantau seluruh transaksi dan perjalanan pesananmu di POCKETA.
        </p>

    </div>

    <!-- STATISTIK -->

    <div class="stats-grid">

        <div class="stat-card">
            <small>Total Pesanan</small>
            <h2><?= $totalOrders ?></h2>
        </div>

        <div class="stat-card">
            <small>Sedang Diproses</small>
            <h2>
                <?= (int)($statusCounts["Diproses"] ?? 0)
                    + (int)($statusCounts["Dikemas"] ?? 0)
                    + (int)($statusCounts["Dikirim"] ?? 0)
                    + (int)($statusCounts["Sedang Diproses"] ?? 0)
                    + (int)($statusCounts["Sedang Dikemas"] ?? 0)
                    + (int)($statusCounts["Dalam Pengiriman"] ?? 0) ?>
            </h2>
        </div>

        <div class="stat-card">
            <small>Pesanan Selesai</small>
            <h2>
                <?= (int)($statusCounts["Selesai"] ?? 0) ?>
            </h2>
        </div>

    </div>

    <!-- PENCARIAN DAN FILTER -->

    <section class="filter-card">

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="search"
                placeholder="Cari nomor pesanan..."
                value="<?= e($keyword) ?>"
            >

            <select name="status">

                <option value="">Semua Status</option>

                <?php
                $statuses = [
                    "Menunggu Konfirmasi",
                    "Menunggu Pembayaran",
                    "Diproses",
                    "Dikemas",
                    "Dikirim",
                    "Selesai",
                    "Dibatalkan"
                ];
                ?>

                <?php foreach ($statuses as $statusOption): ?>

                    <option
                        value="<?= e($statusOption) ?>"
                        <?= $filterStatus === $statusOption ? "selected" : "" ?>
                    >
                        <?= e($statusOption) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit" class="filter-btn">
                Cari Pesanan
            </button>

            <a href="order-history.php" class="reset-btn">
                Reset
            </a>

        </form>

    </section>

    <!-- DAFTAR PESANAN -->

    <p class="result-info">
        Menampilkan <?= count($orders) ?> pesanan
    </p>

    <?php if (!empty($orders)): ?>

        <?php foreach ($orders as $order): ?>

            <article class="order-card">

                <!-- HEADER PESANAN -->

                <div class="order-header">

                    <div>

                        <div class="order-number">
                            <?= e($order["order_number"]) ?>
                        </div>

                        <div class="order-date">

                            <?= !empty($order["created_at"])
                                ? date(
                                    "d M Y, H:i",
                                    strtotime($order["created_at"])
                                )
                                : "-"
                            ?>

                        </div>

                    </div>

                    <span class="status <?= statusClass($order["status_pesanan"] ?? "") ?>">
                        <?= e($order["status_pesanan"] ?? "Menunggu Konfirmasi") ?>
                    </span>

                </div>

                <!-- INFORMASI PESANAN -->

                <div class="order-info">

                    <div>

                        <div class="info-label">
                            Nama Penerima
                        </div>

                        <div class="info-value">
                            <?= e($order["nama_penerima"] ?? "-") ?>
                        </div>

                    </div>

                    <div>

                        <div class="info-label">
                            Metode Pembayaran
                        </div>

                        <div class="info-value">
                            <?= e($order["metode_pembayaran"] ?? "-") ?>
                        </div>

                    </div>

                    <div>

                        <div class="info-label">
                            Status Pembayaran
                        </div>

                        <div class="info-value">
                            <?= e($order["status_pembayaran"] ?? "Menunggu Konfirmasi") ?>
                        </div>

                    </div>

                </div>

                <!-- FOOTER PESANAN -->

                <div class="order-footer">

                    <div>

                        <div class="total-label">
                            Total Pembayaran
                        </div>

                        <div class="total-price">
                            <?= rupiah($order["total_harga"] ?? 0) ?>
                        </div>

                    </div>

                    <a
                        href="order-detail.php?id=<?= (int)$order["id"] ?>"
                        class="detail-btn"
                    >
                        Lihat Detail →
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    <?php else: ?>

        <!-- JIKA TIDAK ADA PESANAN -->

        <div class="empty">

            <div class="empty-icon">🛍️</div>

            <?php if ($keyword !== "" || $filterStatus !== ""): ?>

                <h2>Pesanan Tidak Ditemukan</h2>

                <p>
                    Tidak ada pesanan yang sesuai dengan pencarianmu.
                    Coba gunakan kata kunci atau filter lainnya.
                </p>

            <?php else: ?>

                <h2>Belum Ada Pesanan 🩷</h2>

                <p>
                    Kamu belum melakukan transaksi di POCKETA.
                    Yuk, temukan produk handmade favoritmu!
                </p>

            <?php endif; ?>

            <a href="products.php" class="shop-btn">
                Mulai Belanja
            </a>

        </div>

    <?php endif; ?>

</main>

</body>
</html>


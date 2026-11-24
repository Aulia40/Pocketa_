
<?php
session_start();

require_once "../config/database.php";

// Login user
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "user") {
    header("Location: ../login.html");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Ambil gambar dasar avatar
$stmt = $pdo->query("
    SELECT * FROM custom_base
    ORDER BY id DESC
    LIMIT 1
");
$base = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil pilihan yang aktif dan masih tersedia
$stmt = $pdo->query("
    SELECT *
    FROM custom_options
    WHERE status = 'aktif'
      AND stok > 0
    ORDER BY kategori ASC, nama ASC
");
$options = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan pilihan berdasarkan kategori
$grouped = [
    "warna" => [],
    "telinga" => [],
    "mata" => [],
    "motif" => [],
    "aksesori" => []
];

foreach ($options as $option) {
    $kategori = $option["kategori"];

    if (isset($grouped[$kategori])) {
        $grouped[$kategori][] = $option;
    }
}

$baseImage = $base["image_path"] ?? "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kustomisasi Avatar | POCKETA</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #FBE9E7, #F4C7C3, #EAD6CC);
    color: #5C4033;
}

.container {
    max-width: 1150px;
    margin: 35px auto;
    padding: 20px;
}

.page-title {
    text-align: center;
    margin-bottom: 25px;
}

.page-title h1 {
    margin-bottom: 8px;
}

.page-title p {
    color: #87695B;
}

.custom-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 22px;
    align-items: start;
}

.panel {
    background: #FFF9F6;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 5px 20px rgba(92, 64, 51, 0.08);
}

.category {
    margin-bottom: 25px;
}

.category h3 {
    margin-bottom: 12px;
    font-size: 17px;
}

.option-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
    gap: 12px;
}

.option {
    position: relative;
    border: 2px solid #EAD6CC;
    border-radius: 12px;
    padding: 10px;
    cursor: pointer;
    text-align: center;
    background: white;
    transition: 0.2s;
}

.option:hover {
    border-color: #B78370;
}

.option.selected {
    border-color: #A66B55;
    background: #FBE9E7;
}

.option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.option img {
    width: 75px;
    height: 75px;
    object-fit: contain;
    display: block;
    margin: 0 auto 7px;
}

.color-swatch {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: block;
    margin: 8px auto 12px;
    border: 1px solid #ddd;
}

.option-name {
    font-size: 13px;
    font-weight: bold;
    display: block;
}

.option-price {
    font-size: 11px;
    color: #8A6A5D;
    display: block;
    margin-top: 5px;
}

.preview-panel {
    position: sticky;
    top: 20px;
    text-align: center;
}

.preview-area {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #FBE9E7;
    border: 1px dashed #D8B7AA;
    border-radius: 16px;
    position: relative;
    overflow: hidden;
    margin: 15px auto;
}

.preview-area img {
    position: absolute;
    width: 100%;
    height: 100%;
    inset: 0;
    object-fit: contain;
    pointer-events: none;
}

.preview-base {
    z-index: 1;
}

.preview-motif {
    z-index: 2;
}

.preview-telinga {
    z-index: 3;
}

.preview-mata {
    z-index: 4;
}

.preview-aksesori {
    z-index: 5;
}

.price-box {
    background: #FBE9E7;
    border-radius: 12px;
    padding: 15px;
    margin-top: 15px;
}

.price-total {
    font-size: 22px;
    font-weight: bold;
    margin-top: 5px;
}

.btn {
    border: none;
    padding: 12px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.btn-reset {
    background: #EAD6CC;
    color: #5C4033;
    width: 100%;
    margin-top: 12px;
}

.btn-back {
    display: inline-block;
    margin-top: 20px;
    color: #5C4033;
    text-decoration: none;
    font-weight: bold;
}

.empty {
    text-align: center;
    padding: 35px;
}

.stock-info {
    font-size: 11px;
    color: #8A6A5D;
    margin-top: 4px;
}

@media(max-width: 800px) {
    .custom-layout {
        grid-template-columns: 1fr;
    }

    .preview-panel {
        position: static;
        grid-row: 1;
    }

    .preview-area {
        max-width: 330px;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="page-title">
        <h1>✨ Custom Avatar POCKETA</h1>
        <p>Buat gantungan hewan dengan kombinasi pilihanmu sendiri.</p>
    </div>

    <?php if (!$baseImage): ?>

        <div class="panel empty">
            <h3>Avatar belum tersedia</h3>
            <p>Admin belum menambahkan gambar dasar avatar.</p>
        </div>

    <?php else: ?>

    <div class="custom-layout">

        <!-- PILIHAN KUSTOMISASI -->
        <div class="panel">

            <h2>Sesuaikan Avatar</h2>
            <p>Pilih komponen yang kamu inginkan.</p>

            <?php
            $labels = [
                "warna" => "🎨 Pilihan Warna",
                "telinga" => "🐻 Model Telinga",
                "mata" => "👀 Model Mata",
                "motif" => "🌸 Motif",
                "aksesori" => "🎀 Aksesori"
            ];
            ?>

            <?php foreach ($grouped as $kategori => $items): ?>

                <div class="category">

                    <h3><?= e($labels[$kategori]) ?></h3>

                    <?php if (empty($items)): ?>

                        <p>Belum ada pilihan tersedia.</p>

                    <?php else: ?>

                    <div class="option-list">

                        <?php foreach ($items as $item): ?>

                            <label class="option"
                                data-category="<?= e($kategori) ?>"
                                data-price="<?= e($item["harga_tambahan"]) ?>"
                                data-id="<?= e($item["id"]) ?>">

                                <input
                                    type="radio"
                                    name="pilihan_<?= e($kategori) ?>"
                                    value="<?= e($item["id"]) ?>"
                                >

                                <?php if ($kategori === "warna"): ?>

                                    <span class="color-swatch"
                                        style="background-color: <?= e($item["kode_warna"] ?: "#EAD6CC") ?>">
                                    </span>

                                <?php elseif (!empty($item["image_path"])): ?>

                                    <img
                                        src="<?= e($item["image_path"]) ?>"
                                        alt="<?= e($item["nama"]) ?>"
                                    >

                                <?php endif; ?>

                                <span class="option-name">
                                    <?= e($item["nama"]) ?>
                                </span>

                                <span class="option-price">
                                    + Rp<?= number_format((float)$item["harga_tambahan"], 0, ",", ".") ?>
                                </span>

                                <span class="stock-info">
                                    Stok: <?= (int)$item["stok"] ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- PREVIEW -->
        <div class="panel preview-panel">

            <h2>Preview Avatar</h2>
            <p>Lihat hasil pilihanmu secara langsung.</p>

            <div class="preview-area" id="previewArea">

                <img
                    src="<?= e($baseImage) ?>"
                    class="preview-base"
                    alt="Avatar dasar"
                >

                <img id="layer-motif" class="preview-motif" hidden>
                <img id="layer-telinga" class="preview-telinga" hidden>
                <img id="layer-mata" class="preview-mata" hidden>
                <img id="layer-aksesori" class="preview-aksesori" hidden>

            </div>

            <div id="selectedColor">
                Warna: Belum dipilih
            </div>

            <div class="price-box">
                <div>Estimasi tambahan harga</div>
                <div class="price-total" id="totalPrice">Rp0</div>
            </div>

            <button type="button" class="btn btn-reset" id="resetButton">
                ↻ Reset Pilihan
            </button>

            <p style="font-size:12px;color:#987B6F;margin-top:12px;">
                Preview ini hanya untuk mencoba desain. Belum menjadi pesanan.
            </p>

        </div>

    </div>

    <?php endif; ?>

    <a href="user.php" class="btn-back">← Kembali ke Beranda</a>

</div>

<script>
const basePrice = 0;

const selectedColor = document.getElementById("selectedColor");
const totalPrice = document.getElementById("totalPrice");

const layers = {
    motif: document.getElementById("layer-motif"),
    telinga: document.getElementById("layer-telinga"),
    mata: document.getElementById("layer-mata"),
    aksesori: document.getElementById("layer-aksesori")
};

function formatRupiah(number) {
    return "Rp" + Number(number).toLocaleString("id-ID");
}

function updatePreview() {
    let total = basePrice;
    let colorName = "Belum dipilih";

    document.querySelectorAll(".option").forEach(option => {
        const input = option.querySelector("input");

        if (!input.checked) return;

        option.classList.add("selected");

        const category = option.dataset.category;
        const price = Number(option.dataset.price || 0);

        total += price;

        if (category === "warna") {
            colorName = option.querySelector(".option-name").textContent.trim();
        } else if (layers[category]) {
            const image = option.querySelector("img");

            if (image) {
                layers[category].src = image.src;
                layers[category].hidden = false;
            }
        }

    });

    // Hapus tampilan selected pada pilihan yang tidak dipilih
    document.querySelectorAll(".option").forEach(option => {
        if (!option.querySelector("input").checked) {
            option.classList.remove("selected");
        }
    });

    // Sembunyikan layer yang tidak dipilih
    Object.keys(layers).forEach(category => {
        const selected = document.querySelector(
            '.option[data-category="' + category + '"] input:checked'
        );

        if (!selected) {
            layers[category].hidden = true;
            layers[category].removeAttribute("src");
        }
    });

    selectedColor.textContent = "Warna: " + colorName;
    totalPrice.textContent = formatRupiah(total);
}

// Ketika pengguna memilih pilihan
document.querySelectorAll(".option input").forEach(input => {
    input.addEventListener("change", updatePreview);
});

// Reset semua pilihan
document.getElementById("resetButton")?.addEventListener("click", function() {
    document.querySelectorAll(".option input").forEach(input => {
        input.checked = false;
    });

    updatePreview();
});

updatePreview();
</script>

</body>
</html>

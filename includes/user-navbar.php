
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$navNama = $_SESSION["nama"] ?? "Pengguna";
$navInisial = strtoupper(substr($navNama, 0, 1));

$activePage = $activePage ?? "";

$menuItems = [
    "home" => ["Beranda", "user.php"],
    "products" => ["Katalog", "products.php"],
    "orders" => ["Pesanan", "order-history.php"],
    "articles" => ["Artikel", "user.php#artikel"],
    "help" => ["Bantuan", "help.php"]
];
?>
<nav class="pk-navbar">

    <a href="user.php" class="pk-logo">
        POCKE<span>TA.</span>
    </a>

    <button class="pk-toggle" id="pkToggle"
            type="button"
            aria-label="Buka navigasi"
            aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="pk-menu" id="pkMenu">

        <div class="pk-links">

            <?php foreach ($menuItems as $key => $item): ?>

                <a href="<?= htmlspecialchars($item[1]) ?>"
                   class="<?= $activePage === $key ? 'active' : '' ?>">

                    <?= htmlspecialchars($item[0]) ?>

                </a>

            <?php endforeach; ?>

        </div>

        <div class="pk-actions">

            <a href="cart.php" class="pk-icon" title="Keranjang">
                <i class="fa-solid fa-bag-shopping"></i>
            </a>

            <a href="profile.php" class="pk-account">

                <div class="pk-avatar">
                    <?= htmlspecialchars($navInisial) ?>
                </div>

                <div class="pk-account-info">
                    <strong><?= htmlspecialchars($navNama) ?></strong>
                    <small>POCKETA Member</small>
                </div>

            </a>

            <a href="settings.php" class="pk-icon" title="Pengaturan">
                <i class="fa-solid fa-gear"></i>
            </a>

            <a href="../auth/logout.php" class="pk-logout"
               onclick="return confirm('Yakin ingin logout?')">

                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout

            </a>

        </div>

    </div>

</nav>

<script src="../assets/js/user-navbar.js" defer></script>

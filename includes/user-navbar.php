
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    <!-- LOGO -->
    <a href="user.php" class="pk-logo">
        POCKE<span>TA.</span>
    </a>

    <!-- MOBILE TOGGLE -->
    <button class="pk-toggle" id="pkToggle"
            type="button"
            aria-label="Buka navigasi"
            aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="pk-menu" id="pkMenu">

        <!-- MENU LINKS -->
        <div class="pk-links">

            <?php foreach ($menuItems as $key => $item): ?>

                <a href="<?= htmlspecialchars($item[1]) ?>"
                   class="<?= $activePage === $key ? 'active' : '' ?>">

                    <?= htmlspecialchars($item[0]) ?>

                </a>

            <?php endforeach; ?>

        </div>

        <!-- NAVBAR ACTIONS -->
        <div class="pk-actions">

            <!-- KERANJANG -->
            <a href="cart.php"
               class="pk-icon"
               title="Keranjang"
               aria-label="Keranjang">

                <i class="fa-solid fa-bag-shopping"></i>

            </a>

            <!-- AKUN / PROFIL -->
            <a href="profile.php"
               class="pk-icon"
               title="Akun"
               aria-label="Akun pengguna">

                <i class="fa-regular fa-user"></i>

            </a>

            <!-- PENGATURAN -->
            <a href="settings.php"
               class="pk-icon"
               title="Pengaturan"
               aria-label="Pengaturan">

                <i class="fa-solid fa-gear"></i>

            </a>

            <!-- LOGOUT -->
            <a href="../auth/logout.php"
               class="pk-logout"
               onclick="return confirm('Yakin ingin logout?')">

                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout

            </a>

        </div>

    </div>

</nav>

<script src="../assets/js/user-navbar.js" defer></script>

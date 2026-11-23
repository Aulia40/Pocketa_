
document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("pkToggle");
    const menu = document.getElementById("pkMenu");

    if (!toggle || !menu) return;

    toggle.addEventListener("click", function () {

        const isOpen = menu.classList.toggle("show");

        toggle.setAttribute("aria-expanded", isOpen);

        toggle.innerHTML = isOpen
            ? '<i class="fa-solid fa-xmark"></i>'
            : '<i class="fa-solid fa-bars"></i>';

    });

    // Tutup menu setelah memilih navigasi
    menu.querySelectorAll(".pk-links a").forEach(link => {

        link.addEventListener("click", function () {

            menu.classList.remove("show");

            toggle.setAttribute("aria-expanded", "false");

            toggle.innerHTML = '<i class="fa-solid fa-bars"></i>';

        });

    });

});

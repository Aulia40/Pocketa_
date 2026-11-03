// ===========================
// POCKETA ADMIN JAVASCRIPT
// ===========================


// NAVIGASI SIDEBAR

const menuLinks = document.querySelectorAll(
    ".sidebar nav a"
);

const pages = document.querySelectorAll(".page");

menuLinks.forEach(link => {

    link.addEventListener("click", function(event) {

        event.preventDefault();

        const pageId = this.dataset.page;

        menuLinks.forEach(item => {
            item.classList.remove("active");
        });

        this.classList.add("active");

        pages.forEach(page => {
            page.classList.remove("active-page");
        });

        document.getElementById(pageId)
            .classList.add("active-page");

        document.getElementById("pageTitle")
            .textContent =
            this.textContent.trim().replace(
                /^[^\p{L}\p{N}]+/u, ""
            );

    });

});


// DATA PRODUK

let products = [];

try {
    const saved = JSON.parse(
        localStorage.getItem("pocketa_products")
    );

    products = Array.isArray(saved) ? saved : [];
} catch (error) {
    products = [];
}


// FORMAT RUPIAH

function formatRupiah(angka) {

    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0
    }).format(angka);

}


// TAMPILKAN FORM

function showProductForm() {

    const formBox =
        document.getElementById("productFormBox");

    formBox.style.display =
        formBox.style.display === "none"
            ? "block"
            : "none";

}


// SIMPAN PRODUK

document.getElementById("productForm")
    .addEventListener("submit", function(event) {

    event.preventDefault();

    const nama =
        document.getElementById("namaProduk").value.trim();

    const harga =
        Number(document.getElementById("hargaProduk").value);

    const stok =
        Number(document.getElementById("stokProduk").value);

    if (
        !nama ||
        !Number.isFinite(harga) ||
        harga <= 0 ||
        !Number.isInteger(stok) ||
        stok < 0
    ) {
        alert("Masukkan data produk yang valid.");
        return;
    }

    const product = {
        id: crypto.randomUUID(),
        nama: nama,
        harga: harga,
        stok: stok
    };

    products.push(product);

    saveProducts();

    renderProducts();

    this.reset();

    document.getElementById("productFormBox")
        .style.display = "none";

    alert("Produk berhasil ditambahkan!");

});


// SIMPAN KE LOCAL STORAGE

function saveProducts() {

    localStorage.setItem(
        "pocketa_products",
        JSON.stringify(products)
    );

}


// TAMPILKAN PRODUK

function renderProducts() {

    const table =
        document.getElementById("productTable");

    table.innerHTML = "";

    products.forEach(product => {

        const row = document.createElement("tr");

        const namaCell = document.createElement("td");
        namaCell.textContent = product.nama;

        const hargaCell = document.createElement("td");
        hargaCell.textContent = formatRupiah(product.harga);

        const stokCell = document.createElement("td");
        stokCell.textContent = product.stok;

        const aksiCell = document.createElement("td");

        const deleteButton = document.createElement("button");
        deleteButton.textContent = "Hapus";
        deleteButton.className = "primary-btn";

        deleteButton.addEventListener("click", () => {
            deleteProduct(product.id);
        });

        aksiCell.appendChild(deleteButton);

        row.append(
            namaCell,
            hargaCell,
            stokCell,
            aksiCell
        );

        table.appendChild(row);

    });

    document.getElementById("totalProduk")
        .textContent = products.length;

}


// HAPUS PRODUK

function deleteProduct(id) {

    if (confirm("Yakin ingin menghapus produk ini?")) {

        products = products.filter(
            product => product.id !== id
        );

        saveProducts();

        renderProducts();

    }

}


// TAMPILKAN DATA SAAT HALAMAN DIBUKA

renderProducts();
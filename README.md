@'
# POCKETA

Aplikasi web e-commerce dan manajemen pesanan berbasis **PHP**, **MySQL**, **HTML**, **CSS**, dan **JavaScript** (dijalankan secara lokal menggunakan XAMPP / Apache + MySQL).

## Menjalankan Secara Lokal (XAMPP)

1. Letakkan folder proyek di dalam `htdocs` XAMPP (contoh: `C:\xampp\htdocs\POCKETA`).
2. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buat database di `phpMyAdmin`, import tabel yang diperlukan, dan sesuaikan konfigurasi pada `config/database.php`.
4. Akses aplikasi melalui browser lewat `http://localhost/...` (bukan Live Server / Five Server).
'@ | Out-File -FilePath ".\README.md" -Encoding utf8cd C:\xampp\htdocs\POCKETA\POCKETA


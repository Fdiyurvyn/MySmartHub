**MySmartHub**

MySmartHub adalah aplikasi web manajemen tugas dan profil sederhana yang dikembangkan dengan PHP dan MySQL. Aplikasi ini dirancang untuk penggunaan pribadi atau tim kecil untuk membuat, mengelola, dan mengkategorikan tugas harian, serta mengelola profil pengguna.

**Fitur Utama**
- **Profil Pengguna:** Edit nama lengkap dan foto profil dengan validasi ukuran/format.
- **Manajemen Tugas:** Buat, edit, kategori, dan atur prioritas serta status tugas.
- **Keamanan Dasar:** Proteksi CSRF pada form dan pemeriksaan otentikasi sederhana.
- **Upload Aman:** Validasi MIME-type, ekstensi, dan ukuran file untuk unggahan gambar.

**Tech Stack**
- **Backend:** PHP (vanilla)
- **Database:** MySQL (PDO)
- **Frontend:** HTML/CSS/JavaScript sederhana (asset di `assets/`)

**Struktur Direktori (Inti)**
- **`config/`**: Koneksi database dan fungsi utilitas (mis. `redirect()`, CSRF).
- **`modules/`**: Modul aplikasi seperti `profile/` dan `tasks/`.
- **`includes/`**: Template UI seperti `header.php`, `footer.php`, `navbar.php`.
- **`uploads/`**: Penyimpanan file yang diunggah (gambar profil).

**Instalasi & Setup Lokal**
1. Pastikan Anda menjalankan server web lokal (XAMPP, Laragon, MAMP).
2. Salin project ke folder web server (contoh: `C:/laragon/www/MySmartHub`).
3. Buat database MySQL baru (mis. `mysmarthub`).
4. Konfigurasi koneksi database (opsional).

Setelan environment (opsional):
```
DB_HOST=127.0.0.1
DB_NAME=mysmarthub
DB_USER=root
DB_PASS=
```

5. Buka aplikasi di browser:
```
http://localhost/MySmartHub/
```

Catatan: `config/database.php` membuat tabel `users`, `task_categories`, dan `tasks` secara otomatis jika belum ada.

**Penggunaan**
- Daftar / login untuk membuat akun.
- Akses modul tugas di `modules/tasks/` untuk mengelola tugas.
- Edit profil di `modules/profile/` untuk mengubah nama dan foto profil.

**Keamanan & Best Practices**
- Selalu jalankan project di lingkungan yang terlindungi untuk data sensitif.
- Pastikan folder `uploads/` tidak dapat mengeksekusi skrip (atur permission/htaccess jika perlu).
- Gunakan password yang di-hash (sudah menggunakan `password_hash()` di bagian pendaftaran).

**Testing & Debugging**
- Periksa error PHP pada `php_error.log` atau tampilan error server saat development.
- Untuk masalah redirect/URL, cek fungsi `getApplicationBasePath()` dan `redirect()` di `config/database.php`.

**Kontribusi**
- Fork repository.
- Buat branch fitur: `git checkout -b feat/your-feature`.
- Commit perubahan: `git commit -m "Add feature"`.
- Buat PR dan jelaskan perubahan singkat.

**Roadmap / Ide Perbaikan**
- Tambah autentikasi OAuth / social login.
- Tambah notifikasi email atau integrasi real-time (WebSocket).
- API RESTful untuk integrasi pihak ketiga.

**Lisensi**
- Lisensi default: MIT. Sesuaikan jika perlu.

Jika Anda ingin, saya bisa menambahkan badge, screenshot demo, atau instruksi deploy (Docker / CI). 

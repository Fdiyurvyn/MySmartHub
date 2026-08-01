# Todo List Modul

## Tujuan
Modul ini menyediakan fitur CRUD untuk tugas pengguna yang login.

## Alur kerja
1. User membuka halaman todo.
2. Form mengirim data ke file index.php.
3. Backend memvalidasi input, memeriksa CSRF token, lalu menyimpan data ke tabel tasks.
4. Data dipanggil kembali dengan prepared statement dan hanya menampilkan tugas milik user yang login.

## Catatan penting
- Semua query menggunakan prepared statement.
- Semua operasi SELECT/UPDATE/DELETE mengecek user_id.
- Form wajib mengirim token CSRF.

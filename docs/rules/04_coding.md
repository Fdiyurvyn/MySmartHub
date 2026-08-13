# Rule 4 — Coding Rules

## PHP Standards

1. **Versi PHP** — Minimum PHP 8.1. Gunakan fitur modern: `match`, `enum`, `readonly`, named arguments.
2. **PDO Wajib** — Semua query database harus menggunakan PDO prepared statements. DILARANG menggunakan `mysqli_*` atau string query langsung (SQL injection risk).
3. **CSRF Protection** — Setiap form POST wajib menyertakan token CSRF menggunakan `generateCsrfToken()` dan diverifikasi dengan `verifyCsrfToken()` dari `config/database.php`.
4. **XSS Prevention** — Setiap output variabel ke HTML wajib dibungkus `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`. Gunakan shorthand `<?= htmlspecialchars(...) ?>`.
5. **Session Guard** — Setiap halaman yang membutuhkan login wajib memanggil `requireLogin()` atau mengecek `$_SESSION['user_id']` di baris paling atas setelah `require_once config/database.php`.
6. **Error Handling** — Gunakan `try/catch` untuk semua operasi database. Jangan tampilkan error PDO mentah ke user di production.
7. **Naming Convention**:
   - File PHP: `snake_case.php` (misal: `create_task.php`)
   - Fungsi PHP: `camelCase()` (misal: `getTasksByUser()`)
   - Variabel: `$camelCase` (misal: `$taskList`)
   - Konstanta: `UPPER_SNAKE_CASE` (misal: `MAX_FILE_SIZE`)

## JavaScript Standards

1. Gunakan Vanilla JS (ES6+). Dilarang menambah jQuery atau framework JS berat tanpa alasan kuat.
2. Semua file JS ditempatkan di `assets/js/` dan di-load di atas `</body>`.
3. Gunakan `fetch()` API untuk request AJAX ke `api/` endpoint.
4. Deklarasikan variabel dengan `const` / `let`, bukan `var`.

## File & Security

1. Folder `uploads/` tidak boleh dapat dieksekusi PHP. Pastikan konfigurasi server memblokir eksekusi PHP di folder tersebut.
2. File `.env` atau file konfigurasi sensitif tidak boleh ada di dalam root publik tanpa `.htaccess` proteksi.
3. Maksimal ukuran upload file: 2MB. Validasi tipe file hanya mengizinkan: `jpg`, `jpeg`, `png`, `gif`.

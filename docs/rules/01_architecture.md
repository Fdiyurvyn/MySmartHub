# Rule 1 — Architecture Rule

## Struktur Direktori Wajib

```
MySmartHub/
├── config/            # Konfigurasi global (database, env, constants)
├── includes/          # Komponen UI reusable (header, navbar, sidebar, footer)
├── modules/           # Fitur modular, tiap fitur di subfolder sendiri
│   ├── tasks/
│   ├── notes/
│   ├── calendar/
│   ├── finance/
│   ├── habits/
│   └── ai/
├── api/               # REST endpoint (response JSON, tanpa HTML)
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── database/          # Migration SQL & seeder
├── docs/              # Dokumentasi teknis
└── uploads/           # File upload user (foto profil, lampiran)
```

## Prinsip Arsitektur

1. **Separation of Concerns** — Logic PHP dipisah dari tampilan HTML. Semua query database harus ada di bagian `<?php ?>` di atas, bukan di tengah HTML.
2. **Module-First** — Setiap fitur baru wajib dibuat di `modules/<nama_fitur>/` dengan struktur: `index.php`, `create.php`, `edit.php`, `delete.php`, `handler.php`.
3. **Centralized Config** — Semua koneksi DB, helper function, dan konstanta hanya boleh didefinisikan di `config/database.php`. Tidak boleh ada `new PDO()` di file lain selain melalui `getDatabaseConnection()`.
4. **API Separation** — Endpoint yang mengembalikan JSON wajib ditempatkan di folder `api/` dan tidak boleh mengandung output HTML.
5. **Single Entry per Module** — Tiap module punya `handler.php` sebagai single point untuk memproses form POST/DELETE. Tidak boleh ada form action yang mengarah ke `index.php`.

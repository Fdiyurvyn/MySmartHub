# Rule 5 — Schema Rule (Database)

## Konvensi Penamaan Database

1. **Nama tabel**: `snake_case`, plural (misal: `tasks`, `task_categories`, `finance_categories`)
2. **Primary Key**: selalu kolom `id INT AUTO_INCREMENT PRIMARY KEY`
3. **Foreign Key**: penamaan `<nama_tabel_referensi>_id` (misal: `user_id`, `category_id`)
4. **Timestamps**: setiap tabel wajib punya `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`. Tabel yang bisa di-edit wajib tambah `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`
5. **Soft Delete**: gunakan kolom `deleted_at DATETIME DEFAULT NULL` untuk data penting (bukan hard delete)
6. **Engine**: Selalu gunakan `ENGINE=InnoDB` untuk dukungan foreign key dan transaction

## Tabel Inti & Relasinya

```
users (1) ──< tasks (N)                  via user_id
users (1) ──< task_categories (N)        via user_id
task_categories (1) ──< tasks (N)        via category_id

users (1) ──< notes (N)                  via user_id
users (1) ──< note_categories (N)        via user_id

users (1) ──< calendar_events (N)        via user_id
users (1) ──< habits (N)                 via user_id
habits (1) ──< habit_logs (N)            via habit_id

users (1) ──< finances (N)               via user_id
users (1) ──< finance_categories (N)     via user_id

users (1) ──< notifications (N)          via user_id
users (1) ──< activity_logs (N)          via user_id
users (1) ──< ai_history (N)             via user_id
users (1) ──<> user_settings (1)         via user_id
```

## Aturan Migrasi

1. Setiap perubahan skema wajib ditambahkan sebagai file SQL baru di `database/migrations/` dengan format nama: `YYYY_MM_DD_deskripsi.sql`
2. Jangan pernah edit `database.sql` yang sudah ada — tambahkan migration file baru
3. Setiap migration wajib memiliki bagian `-- UP` (apply) dan `-- DOWN` (rollback)
4. Kolom yang tidak digunakan lagi wajib di-comment di migration, bukan langsung di-DROP tanpa review

## Contoh Migration Template

```sql
-- Migration: 2026_08_07_add_is_archived_to_tasks.sql
-- UP
ALTER TABLE tasks ADD COLUMN is_archived TINYINT(1) DEFAULT 0 AFTER status;

-- DOWN
ALTER TABLE tasks DROP COLUMN is_archived;
```

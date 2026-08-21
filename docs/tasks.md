# MySmartHub — Task Checklist

Checklist granular per modul untuk tracking progress pengembangan.
Update status: `[ ]` belum · `[/]` sedang dikerjakan · `[x]` selesai

---

## 🏗️ Foundation & Infrastructure

- [x] Setup project PHP + Laragon
- [x] Buat `config/database.php` (PDO singleton + helpers)
- [x] Buat `database.sql` (skema 14 tabel)
- [x] Buat `assets/css/style.css` (design system)
- [x] Buat `includes/` (header, navbar, sidebar, footer)
- [ ] Buat `database/migrations/` folder untuk migration files
- [ ] Tambah `.htaccess` untuk proteksi folder `uploads/` dan `config/`

---

## 🔐 Auth (Register, Login, Logout)

- [x] `register.php` — form registrasi + validasi input
- [x] `login.php` — form login + session handling
- [x] `logout.php` — session destroy + redirect
- [ ] Validasi email format & uniqueness (server-side)
- [ ] Password strength indicator (client-side JS)
- [ ] Fitur "Lupa Password" (`password_resets` table)

---

## 📊 Dashboard

- [x] Layout dashboard (sidebar + main content)
- [x] Welcome card dengan nama user
- [x] Stats grid (todo aktif, event bulan ini, total pengeluaran)
- [ ] Stats dinamis — query real dari database (bukan hardcoded 0)
- [ ] Widget aktivitas terbaru (5 item terakhir dari semua modul)
- [ ] Quick action buttons mengarah ke halaman yang benar

---

## 📝 Task / Todo (Fase 3 — 🔄 Sedang Dikerjakan)

### Backend
- [x] Fungsi `createTask()` di `tasks.php`
- [x] Fungsi `updateTask()` di `tasks.php`
- [x] Fungsi `deleteTaskForUser()` di `tasks.php`
- [x] Fungsi `getTasksForUser()` dengan filter
- [ ] Fungsi `getTasksByCategory()` — filter per kategori
- [ ] Fungsi `getTaskStats()` — hitung per status

### Frontend
- [x] Form create task (title, description, priority, deadline)
- [x] Daftar task dengan status badge
- [x] Form edit task (inline atau modal)
- [x] Tombol delete task dengan konfirmasi
- [ ] Filter dropdown: status (todo/doing/done)
- [ ] Filter dropdown: priority (low/medium/high)
- [ ] Sorting: deadline, created_at
- [ ] Tampilan Kanban Board (opsional)

### Kategori Task
- [ ] CRUD `task_categories` — buat, edit, hapus kategori
- [ ] Color picker untuk warna kategori
- [ ] Dropdown kategori di form create/edit task
- [ ] Filter task berdasarkan kategori

---

## 📒 Notes (Fase 4 — ⬜ Planned)

### Backend
- [ ] `modules/notes/handler.php` — proses create/edit/delete
- [ ] Fungsi CRUD notes di file terpisah
- [ ] Pin/unpin note (`is_pinned` toggle)

### Frontend
- [ ] `modules/notes/index.php` — daftar notes (grid/list view)
- [ ] `modules/notes/create.php` — form buat catatan
- [ ] `modules/notes/edit.php` — form edit catatan
- [ ] Rich text editor (textarea + markdown preview)
- [ ] Search notes berdasarkan title/content
- [ ] Pinned notes muncul di bagian atas

### Kategori Notes
- [ ] CRUD `note_categories`
- [ ] Filter notes per kategori

---

## 💰 Finance (Fase 5 — ⬜ Planned)

### Backend
- [ ] `modules/finance/handler.php` — proses CRUD transaksi
- [ ] Fungsi CRUD finances
- [ ] Fungsi kalkulasi: total income, total expense, balance

### Frontend
- [ ] `modules/finance/index.php` — daftar transaksi + ringkasan
- [ ] `modules/finance/create.php` — form tambah transaksi
- [ ] Filter: bulan, tipe (income/expense), kategori
- [ ] Chart income vs expense (bulanan)
- [ ] Summary card: total pemasukan, pengeluaran, saldo

### Kategori Finance
- [ ] CRUD `finance_categories` (income/expense type)
- [ ] Dropdown kategori di form transaksi

---

## 📅 Calendar (Fase 6 — ⬜ Planned)

### Backend
- [ ] `modules/calendar/handler.php` — proses CRUD event
- [ ] Fungsi get events per bulan
- [ ] Fungsi create/update/delete event

### Frontend
- [ ] `modules/calendar/index.php` — tampilan kalender bulanan
- [ ] Render kalender dengan CSS Grid
- [ ] Navigasi prev/next bulan
- [ ] Tampilan dot/badge event di cell tanggal
- [ ] Modal create/edit event
- [ ] Detail event on click

---

## 🎯 Habits (Fase 7 — ⬜ Planned)

### Backend
- [ ] `modules/habits/handler.php` — proses CRUD habit + toggle log
- [ ] Fungsi CRUD habits
- [ ] Fungsi toggle `habit_logs` (hari ini)
- [ ] Fungsi hitung streak (hari berturut-turut)

### Frontend
- [ ] `modules/habits/index.php` — daftar habits + checklist harian
- [ ] Tombol toggle selesai/belum per hari
- [ ] Visualisasi streak (heatmap atau bar sederhana)
- [ ] Form create/edit habit (title, target per day, color)

---

## 🤖 AI Assistant (Fase 7 — ✅ COMPLETE)

### Backend
- [x] `api/ai.php` — proxy ke Gemini API (POST request) + local fallback
- [x] Simpan history ke `ai_history` dengan schema (user_id, role, message, created_at)
- [x] Context gathering (active tasks, upcoming events, finance summary)
- [ ] Rate limiting (max request per menit) — future enhancement

### Frontend
- [x] `modules/ai/index.php` — chat UI (bubble style)
- [x] Input field + send button dengan loading state
- [x] Render response dengan role-based styling (user/assistant bubbles)
- [x] Riwayat chat dari `ai_history` (last 30 messages)
- [ ] Markdown formatting untuk response — future enhancement

### Documentation
- [x] `AI_ASSISTANT.md` — Setup guide + usage examples
- [x] Environment variables documented (GEMINI_API_KEY, GEMINI_MODEL)
- [x] Database schema documented
- [x] Troubleshooting guide

---

## 👤 Profile & Settings (Fase 8 — ⬜ Planned)

- [ ] `modules/profile/index.php` — halaman profil
- [ ] Edit nama, email, username
- [ ] Upload foto profil (validasi tipe + ukuran)
- [ ] Ubah password (verifikasi password lama)
- [ ] Theme switcher (dark/light) — update `user_settings`
- [ ] Timezone & language setting

---

## 🔔 Notifications (Fase 8 — ⬜ Planned)

- [ ] Sistem notifikasi in-app (`notifications` table)
- [ ] Bell icon di navbar dengan badge unread count
- [ ] Dropdown notifikasi
- [ ] Mark as read / mark all as read
- [ ] Auto-generate notifikasi saat deadline mendekat

---

## 📋 Activity Log (Fase 8 — ⬜ Planned)

- [ ] Log otomatis setiap aksi user (create/edit/delete)
- [ ] Tampilkan di dashboard "Aktivitas Terbaru"
- [ ] Simpan IP address untuk keamanan

---

*Checklist ini di-update seiring perkembangan project.*

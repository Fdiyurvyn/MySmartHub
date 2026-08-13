# MySmartHub — Implementation Planning

Dokumen ini berisi roadmap pengembangan, strategi teknis, dan milestone per fase.

---

## Fase 1 — Foundation (✅ Selesai)

**Target**: Infrastruktur dasar aplikasi siap digunakan.

### Yang sudah selesai:
- Setup project PHP + MySQL di Laragon
- Struktur folder: `config/`, `includes/`, `modules/`, `api/`, `assets/`, `docs/`
- Konfigurasi database (`config/database.php`) dengan PDO singleton
- Helper functions: `redirect()`, `isLoggedIn()`, `requireLogin()`, `generateCsrfToken()`, `verifyCsrfToken()`
- Skema database lengkap (`database.sql`) dengan 14 tabel
- Design system CSS (dark mode, Poppins font, color palette)

### Deliverables:
- [x] `config/database.php` — koneksi DB + helper functions
- [x] `database.sql` — skema lengkap semua tabel
- [x] `assets/css/style.css` — design system global
- [x] `includes/` — header, navbar, sidebar, footer

---

## Fase 2 — Auth & Dashboard (✅ Selesai)

**Target**: User bisa register, login, dan melihat dashboard.

### Deliverables:
- [x] `register.php` — form registrasi dengan validasi
- [x] `login.php` — form login dengan session
- [x] `logout.php` — destroy session
- [x] `dashboard.php` — welcome card, stats grid, quick actions, sidebar navigasi
- [x] `index.php` — landing page

---

## Fase 3 — Task / Todo Module (🔄 Sedang Dikerjakan)

**Target**: CRUD todo lengkap dengan filter, kategori, dan prioritas.

### Strategi Teknis:
- Modul di `modules/tasks/` — sudah ada `index.php` dan `tasks.php`
- Backend logic di `tasks.php` (fungsi CRUD)
- Form handling langsung di `index.php` (create, edit, delete via POST)
- Filter berdasarkan status (`todo`, `doing`, `done`) dan prioritas (`low`, `medium`, `high`)
- Kategori task dari tabel `task_categories`

### Deliverables:
- [x] `modules/tasks/index.php` — halaman utama todo list
- [x] `modules/tasks/tasks.php` — fungsi CRUD backend
- [ ] Filter & sorting (status, prioritas, deadline)
- [ ] Manajemen kategori task (CRUD `task_categories`)
- [ ] Dashboard stats: hitung todo aktif secara real-time

---

## Fase 4 — Notes Module (⬜ Planned)

**Target**: CRUD catatan dengan fitur pin dan kategori.

### Strategi Teknis:
- Modul di `modules/notes/`
- Rich text editor menggunakan Vanilla JS (contenteditable atau textarea + markdown)
- Pin note (`is_pinned`) — pinned notes muncul di atas
- Kategori note dari tabel `note_categories`

### Deliverables:
- [ ] `modules/notes/index.php` — daftar notes dengan search
- [ ] `modules/notes/create.php` — form buat catatan baru
- [ ] `modules/notes/edit.php` — edit catatan
- [ ] `modules/notes/handler.php` — proses create/edit/delete
- [ ] Manajemen kategori note

---

## Fase 5 — Finance Module (⬜ Planned)

**Target**: Pencatatan pemasukan & pengeluaran dengan grafik ringkasan.

### Strategi Teknis:
- Modul di `modules/finance/`
- Tabel `finances` + `finance_categories`
- Chart menggunakan Canvas API atau library ringan (Chart.js via CDN)
- Laporan bulanan: total income vs expense

### Deliverables:
- [ ] `modules/finance/index.php` — daftar transaksi + ringkasan
- [ ] `modules/finance/create.php` — form tambah transaksi
- [ ] `modules/finance/handler.php` — proses CRUD
- [ ] Chart pemasukan vs pengeluaran (bulanan)
- [ ] Manajemen kategori keuangan

---

## Fase 6 — Calendar Module (⬜ Planned)

**Target**: Manajemen event dengan tampilan kalender bulanan.

### Strategi Teknis:
- Modul di `modules/calendar/`
- Render kalender dengan PHP + CSS Grid (bukan library eksternal)
- Navigasi bulan (prev/next)
- Modal atau halaman terpisah untuk tambah/edit event

### Deliverables:
- [ ] `modules/calendar/index.php` — tampilan kalender bulanan
- [ ] `modules/calendar/handler.php` — proses CRUD event
- [ ] Navigasi antar bulan
- [ ] Tampilan event di cell kalender

---

## Fase 7 — Habits & AI (⬜ Planned)

**Target**: Habit tracker harian + AI assistant berbasis Gemini API.

### Strategi Teknis:
- **Habits**: modul di `modules/habits/`, tabel `habits` + `habit_logs`, streak counter
- **AI**: modul di `modules/ai/`, tabel `ai_history`, integrasi Gemini API via `api/ai.php`

### Deliverables:
- [ ] `modules/habits/index.php` — daftar habits + log harian
- [ ] `modules/habits/handler.php` — toggle status habit hari ini
- [ ] Streak calculator & visualisasi
- [ ] `modules/ai/index.php` — chat UI
- [ ] `api/ai.php` — proxy ke Gemini API

---

## Fase 8 — Profile, Notifications & Polish (⬜ Planned)

**Target**: Fitur pendukung dan finishing.

### Deliverables:
- [ ] `modules/profile/index.php` — edit profil, foto, password
- [ ] `modules/profile/handler.php` — proses update profil
- [ ] Sistem notifikasi in-app (tabel `notifications`)
- [ ] Activity log (tabel `activity_logs`)
- [ ] Theme switcher (dark/light via `user_settings`)
- [ ] Optimasi performa & security audit

---

*Dokumen ini di-update seiring perkembangan project.*

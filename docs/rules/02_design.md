# Rule 2 — Design Rule

## Design System MySmartHub

1. **Font** — Gunakan hanya `Poppins` dari Google Fonts (weight: 400, 500, 600). Tidak boleh menggunakan font lain tanpa persetujuan.
2. **Color Palette** — Wajib mengacu pada variabel CSS berikut:
   ```css
   :root {
     --primary:       #6366F1;   /* Indigo utama */
     --primary-dark:  #4F46E5;   /* Hover state */
     --secondary:     #8B5CF6;   /* Aksen ungu */
     --accent:        #EC4899;   /* Highlight / badge */
     --bg-base:       #0F172A;   /* Background gelap */
     --bg-surface:    #1E293B;   /* Card & panel */
     --bg-elevated:   #334155;   /* Input & elevated element */
     --text-primary:  #F1F5F9;   /* Teks utama */
     --text-muted:    #94A3B8;   /* Teks sekunder */
     --border:        #334155;   /* Border tipis */
     --success:       #10B981;
     --warning:       #F59E0B;
     --danger:        #EF4444;
   }
   ```
3. **Dark Mode First** — Semua UI didesain dengan background gelap (`--bg-base`) sebagai default. Light mode bersifat opsional via class `.light-theme` di `<body>`.
4. **Komponen Standar** — Gunakan class CSS yang sudah ada di `assets/css/style.css`. Dilarang menulis inline style kecuali untuk warna dinamis dari database (misal: warna kategori).
5. **Responsif** — Semua halaman wajib responsif untuk layar mobile (min 360px) dan desktop (max 1440px). Gunakan CSS Grid dan Flexbox, bukan table layout.
6. **Micro-animation** — Setiap tombol, card, dan nav-item wajib punya transition minimal `transition: all 0.2s ease` untuk memberikan feel interaktif.
7. **Ikon** — Gunakan emoji Unicode untuk ikon sederhana (📝, 📅, 💰). Untuk ikon kompleks, gunakan SVG inline. Dilarang menambahkan library ikon eksternal (Font Awesome, Heroicons) tanpa persetujuan.

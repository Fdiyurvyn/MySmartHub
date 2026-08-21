# 🤖 AI Assistant Implementation untuk MySmartHub

## Status: ✅ Selesai

AI Assistant fitur baru untuk MySmartHub yang memungkinkan user bertanya tentang tugas, jadwal, dan keuangan mereka.

## Fitur Utama

### 1. **Chat Interface** (`modules/ai/index.php`)
- Bubble chat UI yang user-friendly
- Riwayat chat otomatis tersimpan per user
- Input textarea dengan limit 1000 karakter
- Tombol "Kirim" dengan loading state
- Auto-scroll ke message terbaru

### 2. **API Endpoint** (`api/ai.php`)
- **Method**: POST
- **Endpoint**: `/api/ai.php`
- **Parameters**:
  - `message` (string, 1-1000 chars) - pesan user
  - `csrf_token` (string) - token keamanan
- **Response**: JSON dengan `success`, `reply`, `provider`

### 3. **Contextual AI**
AI Assistant punya akses ke konteks user:
- **Active Tasks** (max 10) - tugas yang belum selesai
- **Upcoming Events** (max 5) - event mendatang
- **Finance Summary** - pemasukan/pengeluaran bulan ini

### 4. **Dual Provider**
- **Gemini API** (jika `GEMINI_API_KEY` tersedia) - Respons lebih natural dan kontekstual
- **Local Fallback** (tanpa API key) - Respons dasar berbasis pattern matching Bahasa Indonesia

## Setup

### Tanpa Gemini (Fallback Mode)
Langsung bisa digunakan tanpa konfigurasi tambahan. AI akan memberikan respons dasar.

### Dengan Gemini API

1. **Dapatkan API Key**: https://ai.google.dev/
2. **Setup Environment Variable**

   Di server (jangan di `.env` file yang di-push ke repository):
   ```
   export GEMINI_API_KEY="your-api-key-here"
   export GEMINI_MODEL="gemini-2.0-flash"
   ```

   Atau di file `.htaccess` (Apache):
   ```apache
   SetEnv GEMINI_API_KEY "your-api-key-here"
   SetEnv GEMINI_MODEL "gemini-2.0-flash"
   ```

3. **Restart Web Server** agar environment variable terbaca

### Verifikasi Setup
Buka halaman AI dan test chat. Lihat di response JSON `provider` field:
- `"provider": "gemini"` → API key terdeteksi
- `"provider": "local"` → Menggunakan fallback lokal

## Database Schema

### Tabel: `ai_history`
```sql
CREATE TABLE ai_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_history_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

- **Retention**: 30 messages per user (load terbaru)
- **Privacy**: Data per-user, hanya accessible oleh user sendiri (enforce via `user_id`)
- **Auto-cleanup**: Saat table recreation via config, history lama akan di-reset (CASCADE DELETE)

## Security

### Authentication
- ✅ Wajib login (`requireLogin()`)
- ✅ User hanya bisa akses data milik mereka sendiri (`user_id` check)

### Token Security
- ✅ CSRF token pada setiap POST request
- ✅ API key disimpan di server, bukan di JavaScript

### Rate Limiting
- Pending: Implementasi rate limit per user (future enhancement)

### Input Validation
- ✅ Message max 1000 karakter
- ✅ XSS protection via `htmlspecialchars()`

## Usage Examples

### Chat dengan AI
```javascript
// Frontend (ai.js)
const message = "Apa tugas saya hari ini?";
const response = await fetch('/api/ai.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message, csrf_token: token })
});
```

### Respons Fallback Lokal
- "Apa tugas saya?" → Tampilkan daftar active tasks
- "Berapa pengeluaran saya?" → Tampilkan ringkas finance bulan ini
- "Jadwal saya?" → (Future enhancement)

### Respons Gemini
Lebih natural, kontekstual, dan bisa menjawab pertanyaan yang lebih kompleks.

## File Structure

```
MySmartHub/
├── api/
│   └── ai.php ............................ AI endpoint handler
├── modules/
│   └── ai/
│       └── index.php ..................... Chat UI page
├── assets/js/
│   └── ai.js ............................ Frontend chat logic
├── config/
│   └── database.php ..................... Table creation + initialization
├── database/
│   └── migrations/
│       └── 2026_08_21_create_ai_history.sql ... Migration file
└── README.md ............................ Setup docs
```

## Integration Points

### Dashboard
- Link di sidebar: "✨ AI Assistant"
- Quick action: Tombol "Tanya AI" (future enhancement)

### Modules
- Konteks diambil dari: `tasks`, `calendar_events`, `finances`
- Tidak modify module apapun, hanya read

## Browser Support
- ✅ Chrome, Firefox, Safari, Edge (modern versions)
- ✅ Mobile responsive
- ✅ Dark/Light mode compatible

## Troubleshooting

### Error: "Column not found: role"
→ Table belum di-create dengan benar. Restart web server atau akses halaman AI sekali untuk trigger table creation.

### Error: "CSRF token tidak valid"
→ Cookie session expired atau CSRF token tidak dikirim. Refresh halaman.

### API key tidak terdeteksi
→ Check environment variable di server. Format: `GEMINI_API_KEY="sk-..."`

### Response "Saya siap membantu..."
→ Fallback mode aktif (tidak ada API key). Masih bisa digunakan untuk pertanyaan dasar.

## Future Enhancements

- [ ] Rate limiting (max request per menit)
- [ ] Markdown formatting untuk response
- [ ] Voice input/output
- [ ] Action execution (create task, reminder, dll)
- [ ] Multi-turn conversation dengan history context
- [ ] Analytics & usage tracking

## License

Bagian dari MySmartHub. Ikuti license project utama.

---

**Last Updated**: 2026-08-21
**Status**: MVP Ready for Use

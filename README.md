# RuangDengar — Sistem Layanan Terpadu BK Sekolah

Laravel 12 · Blade · Tailwind CSS v4 · MySQL/MariaDB · sidecar AI (FastAPI + model `.pkl`).
Lima peran: **Siswa**, **Anonim**, **Admin**, **Guru BK**, **Wali Kelas** (tanpa TPPK). Desain mengikuti brief UI "Tactile Counseling Companion" (Palet C ungu lembut).

> Catatan versi: rancangan menyebut Laravel 11, tetapi semua rilis 11.x diblokir Composer karena advisory keamanan (EOL), jadi proyek memakai **Laravel 12** (kode kompatibel).

## Jalankan lokal (Laragon, port bawaan)

Port bawaan Laragon: web **80**, MySQL **3306** (user `root`, tanpa sandi). `.env` sudah disetel demikian.
Nama database: **`ruang_dengar`** (sebelumnya `bk_sahabat`).

1. Letakkan folder proyek di `C:\laragon\www\ruangdengar` → Laragon otomatis membuat **http://ruangdengar.test** (ubah `APP_URL` bila perlu).
   Tanpa Laragon: `.\serve-local.ps1` menjalankan `php artisan serve` di **http://127.0.0.1:80**.
2. Start All di Laragon (Apache/Nginx + MySQL), lalu:

```powershell
composer install
copy .env.example .env          # lewati bila .env sudah ada
php artisan key:generate
# buat database kosong "ruang_dengar" (HeidiSQL/phpMyAdmin Laragon), lalu:
php artisan migrate --seed      # skema 28 tabel + data demo (hanya env local)
```

3. CSS Tailwind v4 sudah dibangun di `public/css/app.css`. Tanpa Node, pakai binary standalone
   (unduh `tailwindcss-windows-x64.exe` v4.1 ke `tools/tailwindcss.exe`):
   `composer css` (sekali) atau `composer css:watch`.

### Pindah dari database lama `bk_sahabat`

Salin tabel dan data ke `ruang_dengar` (tanpa menghapus yang lama), lalu `php artisan migrate`:

```sql
CREATE DATABASE ruang_dengar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- per tabel:  CREATE TABLE ruang_dengar.t LIKE bk_sahabat.t;  INSERT INTO ruang_dengar.t SELECT * FROM bk_sahabat.t;
```

Atau `mysqldump bk_sahabat | mysql ruang_dengar`. Setelah yakin berjalan, `bk_sahabat` boleh dihapus.

### Penjadwal (arsip kasus otomatis)

Kasus Selesai diarsipkan 30 hari kemudian (pemberitahuan hari ke-25) oleh `php artisan kasus:arsipkan`, dijadwalkan harian.
Jalankan scheduler tiap menit: cron `* * * * * php artisan schedule:run`, atau Task Scheduler Windows memanggil `php artisan schedule:run`.
### Akun demo (kata sandi: `password`)

| Peran | Login |
|---|---|
| Admin | `admin@sekolah.sch.id` (form login yang sama di `/masuk`) |
| Guru BK | `bk1@sekolah.sch.id` |
| Wali Kelas | `wk1@sekolah.sch.id` (X-3, XI-1) · `wk2@…` · `wk3@…` |
| Siswa | NIS `2024001` (Rani Putri, X-3) |
| Anonim | tombol "Lapor tanpa nama" |

Staf memakai **2FA**; di mode lokal kode OTP ditampilkan di halaman verifikasi (produksi: kirim via email/SMS).

## Deploy model AI (`.pkl`)

Lihat [ai-service/README.md](ai-service/README.md). Ringkas: salin model ke `ai-service/models/model.pkl`,
samakan versi scikit-learn, jalankan `ai-service\run.ps1` (port 8001). Laravel memanggil `POST /classify`;
bila mati, laporan tetap berjalan dan kolom saran AI kosong. Status/versi terlihat di Admin → Pengaturan → AI Model.

## ERD

`docs/ERD.md` (Mermaid per domain + kamus tabel) dan `docs/erd.html` (diagram penuh). Dihasilkan dari skema nyata:
`php artisan docs:erd`.

## Uji

`php artisan test` — smoke test semua halaman per peran + aturan akses (admin tak bisa buka laporan, Wali Kelas baca-saja & terbatas kelas,
saran AI tak bocor ke siswa, 2FA, akun anonim).

## Batasan yang perlu diketahui

* Hak akses per peran diedit admin di Role dan akses (tabel `role_permissions`); ambang skor kredit di Pengaturan.
* Skor kredit tidak direset saat naik kelas (saldo bawaan).
* Gambar informasi BK disajikan lewat `/media/informasi/...` (tanpa `storage:link`).
* Login satu form (`/masuk`): nama lengkap (hanya bila unik), NIS, atau email; peran dikenali otomatis.
* Alur laporan: Baru → Wali Kelas menandai Ditinjau (satu-satunya perubahan status untuk WK) → BK memproses sampai diarsipkan. Laporan berisiko (kata berisiko / AI ≥ 90% Tinggi) langsung terlihat BK.
* Asisten chat di beranda siswa berbasis aturan (bukan LLM).
* Impor massal: **CSV** saja (XLSX butuh PhpSpreadsheet + ekstensi gd).
* Unduhan ringkasan/analitik: **CSV** (bukan PDF/XLSX).
* Matriks Role & Akses: sel dikunci sistem; hanya 3 kebijakan sekolah (Wali Kelas: ubah status, baca chat, catat skor) yang dapat diubah dan benar-benar dipakai.
* Chat memakai polling 8 detik (belum WebSocket/Reverb). Email/SMS (OTP, undangan) belum terhubung.
* Pengecekan visual dilakukan lewat render headless; uji di perangkat/browser nyata sebelum rilis.

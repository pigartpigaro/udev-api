# Aturan Backend UDev

Ikuti juga aturan umum dari `D:\APP\Docker\AGENTS.md`.

## Backend Laravel

- Cara menjalankan lokal harus tersedia di README dan dapat diikuti dari clone bersih. Alur Docker yang didukung: salin `.env.example` ke `.env`, ubah placeholder password lokal, buat `APP_KEY`, jalankan Compose dari folder `udev-api/`, lalu migrasi dan seed; verifikasi `/api/v1/health`. Untuk API+database saja, boleh jalankan service `app` dan `db`; untuk seluruh stack, jalankan juga `frontend` melalui Compose. Jangan gunakan `down -v` dalam alur berhenti biasa karena itu menghapus volume database.
- Jika menyediakan alur tanpa Docker, dokumentasikan prasyarat PHP/Composer/MySQL yang benar-benar didukung dan perintahnya secara terpisah; jangan menyamakan konfigurasi container (misalnya `DB_HOST=db`) dengan koneksi host lokal.
- `.env.example` hanya berisi nilai contoh aman, `.env` tidak boleh dilacak Git, dan perintah setup tidak boleh menampilkan atau menulis rahasia ke log/dokumentasi.

- Gunakan pola Laravel yang sudah ada di proyek dan kelompokkan API baru di bawah `/api/v1`.
- Kelompokkan kode berdasarkan menu/domain agar fitur mudah ditemukan dan dikembangkan:
  - Model master data di `app/Models/Master/` dengan namespace `App\Models\Master`.
  - Model lintas modul seperti navigasi dan permission di `app/Models/AccessControl/` dengan namespace `App\Models\AccessControl`.
  - Controller master data di `app/Http/Controllers/Api/V1/Master/` dengan namespace `App\Http\Controllers\Api\V1\Master`.
  - Controller lintas modul di `app/Http/Controllers/Api/V1/AccessControl/` dengan namespace `App\Http\Controllers\Api\V1\AccessControl`.
  - Form Request master data di `app/Http/Requests/Api/V1/Master/` dengan namespace `App\Http\Requests\Api\V1\Master`.
  - Form Request lintas modul di `app/Http/Requests/Api/V1/AccessControl/` dengan namespace `App\Http\Requests\Api\V1\AccessControl`.
  - Definisi route API di `routes/api/v1/<menu>/<submenu>.php`; route autentikasi berada di `routes/api/v1/auth.php`.
  - Dokumentasi modul mengikuti hierarki menu yang sama di `docs/modules/<menu>/<submenu>.md`; dokumentasi lintas menu boleh tetap langsung di `docs/modules/`.
  - File utama `routes/api.php` hanya mengatur prefix versi, endpoint umum, dan memuat file route per menu.
- Pertahankan struktur standar Laravel untuk migrasi di `database/migrations/` agar dapat ditemukan Artisan; gunakan nama timestamp dengan domain/menu yang jelas.
- Kode lintas menu seperti middleware, provider, dan model autentikasi tetap di lokasi umum Laravel.
- Jangan mengubah URL API yang sudah dipublikasikan saat hanya memindahkan file route; perubahan URL harus disengaja dan didokumentasikan.
- Validasi semua input di server menggunakan Form Request atau validasi Laravel yang sesuai.
- Lindungi route privat dengan autentikasi dan pemeriksaan hak akses di server; jangan mengandalkan pembatasan di frontend.
- Pendaftaran akun publik tidak diaktifkan. Akun tim dibuat melalui alur admin yang terdokumentasi.
- Gunakan transaksi database untuk operasi keuangan yang saling berkaitan. Simpan nilai uang dalam tipe desimal tetap, bukan float.
- Setiap master data yang ditampilkan ke pengguna memiliki `code` bisnis yang unik, stabil, dan dibuat otomatis dengan prefix per domain (contoh pelanggan `U-PL0001`, tipe proyek `U-TP0001`). Kode tidak diedit pengguna dan tidak menggantikan primary key: semua foreign key dan relasi Eloquent tetap memakai `id`.
- Jangan mengubah atau menghapus catatan pembayaran yang sudah disahkan tanpa alur koreksi dan jejak audit.
- Rahasia dan kredensial hanya berasal dari environment atau secret store. Jangan masukkan `.env` ke Git atau image Docker.
- Perbarui `README.md` atau dokumentasi modul terkait saat endpoint, skema, konfigurasi, atau alur berubah.

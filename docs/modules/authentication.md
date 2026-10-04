# Autentikasi dan Akses Tim

## Tanggung jawab

Modul ini menyediakan login internal melalui Laravel Sanctum, identitas pengguna, logout, dan role dasar. Pendaftaran publik tidak dibuka agar akun hanya dibuat oleh pengelola sistem.

## Alur SPA

1. Frontend meminta `GET /sanctum/csrf-cookie` dengan dukungan credentials.
2. Frontend mengirim `POST /api/v1/auth/login` dengan email dan password.
3. Laravel memvalidasi input, memeriksa kredensial melalui guard `web`, membatasi percobaan login, lalu memperbarui session ID.
4. Route privat memakai middleware `auth:sanctum`.
5. `POST /api/v1/auth/logout` mengakhiri guard, membatalkan session, dan membuat ulang token CSRF.

Sanctum memakai cookie session dan perlindungan CSRF untuk SPA milik sendiri. Token bearer tidak diperlukan untuk pola SPA ini.

## Endpoint

- `GET /api/v1/health` — pemeriksaan API publik.
- `POST /api/v1/auth/login` — login; dibatasi lima permintaan per menit per IP.
- `GET /api/v1/auth/user` — profil singkat pengguna saat ini.
- `POST /api/v1/auth/logout` — logout.

Input login harus berupa `email` valid dan `password`. Respons identitas hanya memuat `id`, `name`, `email`, dan `role`.

## Role dan akun pertama

Tabel `users` memiliki role `admin` atau `member`; akun baru secara default menjadi `member`. Buat admin pertama melalui `php artisan udev:make-admin`. Command meminta kata sandi secara interaktif dan mensyaratkan minimal 12 karakter.

Pemeriksaan role untuk endpoint pengelolaan pengguna dan data keuangan akan dibuat saat modul tersebut dibangun. Jangan menambahkan route pengubahan role yang tidak dibatasi ke admin.

## Konfigurasi frontend

Untuk lokal, stateful domains dan CORS dicantumkan pada `.env.example`. Jika alamat frontend berubah, perbarui `SANCTUM_STATEFUL_DOMAINS` dan `CORS_ALLOWED_ORIGINS`. Pada deployment, gunakan HTTPS, cookie secure, dan hanya masukkan domain tepercaya.

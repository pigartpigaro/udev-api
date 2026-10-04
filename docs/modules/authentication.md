# Autentikasi dan Akses Tim

## Tanggung jawab

Modul ini menyediakan login internal melalui Laravel Sanctum, identitas pengguna, logout, dan role dasar. Pendaftaran publik tidak dibuka agar akun hanya dibuat oleh pengelola sistem.

## Alur SPA

1. Frontend meminta `GET /sanctum/csrf-cookie` dengan dukungan credentials.
2. Frontend mengirim `POST /api/v1/auth/login` dengan username dan password.
3. Laravel memvalidasi input, memeriksa kredensial melalui guard `web`, membatasi percobaan login, lalu memperbarui session ID.
4. Route privat memakai middleware `auth:sanctum`.
5. `POST /api/v1/auth/logout` mengakhiri guard, membatalkan session, dan membuat ulang token CSRF.

Sanctum memakai cookie session dan perlindungan CSRF untuk SPA milik sendiri. Token bearer tidak diperlukan untuk pola SPA ini.

## Endpoint

- `GET /api/v1/health` — pemeriksaan API publik.
- `POST /api/v1/auth/login` — login memakai username; dibatasi lima permintaan per menit per IP.
- `GET /api/v1/auth/user` — profil singkat pengguna saat ini.
- `POST /api/v1/auth/logout` — logout.

Input login berupa `username`, `password`, dan opsional `remember`. Username disimpan huruf kecil; login tidak peka huruf besar/kecil. Email tetap disimpan sebagai alamat kontak yang unik. Migrasi username mengisi akun lama dari bagian email sebelum `@`; jika terjadi benturan, angka ditambahkan otomatis. Password akun lama tidak berubah.

Respons identitas memuat `id`, `name`, `username`, `email`, dan `role`.

## Role dan akun pertama

Tabel `users` memiliki role `admin` atau `member`; akun baru melalui halaman **Pengaturan > Pengguna** selalu menjadi `member`. Hanya role admin yang dapat memanggil `POST /api/v1/access-control/users`; akun dibuat pada workspace aktif saja. Form meminta nama, username, email, dan kata sandi awal minimal 6 karakter. Tidak ada pendaftaran akun publik.

Buat admin pertama melalui `php artisan udev:make-admin` atau `php artisan udev:setup` pada instalasi lokal. Password diminta secara interaktif dan wajib minimal 6 karakter. Username admin pada setup dapat dimasukkan saat bootstrap; command `udev:make-admin` membuat username otomatis dari bagian email sebelum `@` (dengan akhiran angka bila perlu).

Endpoint pembuatan akun memeriksa role admin dan permission `users.manage` di backend; role/permission di UI bukan pengganti pemeriksaan server.

| Method | Endpoint | Akses | Keterangan |
| --- | --- | --- | --- |
| GET | `/api/v1/access-control/users` | Admin workspace + `users.manage` | Daftar anggota workspace aktif; mendukung `search`, `page`, dan `per_page` |
| POST | `/api/v1/access-control/users` | Admin workspace + `users.manage` | Membuat akun Anggota hanya untuk workspace aktif |
| PATCH | `/api/v1/access-control/users/{userId}/role` | Admin workspace + `users.manage` | Mengubah role `admin`/`member` pada workspace aktif saja |

Body: `name`, `username`, `email`, `password`, dan `password_confirmation`. Username unik 3–32 karakter (`a-z`, `0-9`, `.`, `_`, `-`); email unik; password minimal 6 karakter. Respons tidak pernah memuat password.

Daftar pengguna hanya mengembalikan anggota workspace aktif. Admin dapat mengubah role anggota antara `admin` dan `member` melalui endpoint PATCH di atas; perubahan hanya menyentuh keanggotaan workspace tersebut. Role akun sendiri dan pemilik workspace dilindungi dari perubahan.

## Konfigurasi frontend

Untuk lokal, `SANCTUM_STATEFUL_DOMAINS` dan `CORS_ALLOWED_ORIGINS` pada `.env.example` mengizinkan `localhost` dan `127.0.0.1` di port Vite 5173. Domain cookie session dibiarkan host-only agar sesuai dengan hostname browser. Frontend memilih host API dari hostname halaman ketika `VITE_API_BASE_URL` kosong. Gunakan hostname yang sama saat membuka frontend dan API; jangan campur `localhost` dengan `127.0.0.1`. Pada deployment, gunakan HTTPS, cookie secure, dan hanya masukkan domain tepercaya.

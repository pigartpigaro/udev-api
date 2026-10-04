# UDev Backend

Backend API untuk **UDev (Udumbara Development)**, aplikasi internal tim Udumbara Informatika untuk mengelola pelanggan, proyek, pemasukan dan pengeluaran, invoice, serta pembayaran bertahap dan kwitansi.

## Teknologi

- Laravel 12 dan PHP 8.2+
- MySQL 8.4
- Laravel Sanctum untuk autentikasi SPA berbasis session/cookie
- Docker Compose untuk menjalankan API dan database

## Menjalankan dengan Docker

1. Pastikan Docker Desktop aktif.
2. Jika belum ada `.env`, salin contoh konfigurasi:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Ganti `DB_PASSWORD` dan `DB_ROOT_PASSWORD` di `.env` dengan password lokal yang unik. Jangan gunakan nilai contoh untuk server produksi.
4. Buat application key:

   ```powershell
   docker compose run --rm app php artisan key:generate
   ```

5. Bangun dan jalankan aplikasi:

   ```powershell
   docker compose up -d --build
   ```

6. Jalankan setup awal. Perintah ini menjalankan migrasi dan seeder menu/permission/role, lalu meminta data admin lokal secara interaktif bila belum ada platform admin. Username admin awal disarankan `admin`; password dimasukkan tersembunyi, wajib minimal 6 karakter, dan tidak memiliki nilai default. Perintah hanya berjalan saat `APP_ENV=local` dan aman dijalankan ulang; admin yang sudah ada tidak diubah.

   ```powershell
   docker compose exec app php artisan udev:setup
   ```

   Migrasi tenant menempatkan data lama ke workspace `Workspace Utama` tanpa menghapus data. Pada database yang sudah memiliki platform admin, setup tidak membuat ulang atau mengganti akun tersebut.

7. Untuk menyiapkan workspace perusahaan baru, jalankan perintah provisioning. Perintah meminta nama perusahaan dan email pemilik; pemilik bisa berupa akun yang sudah ada atau akun baru.

   ```powershell
   docker compose exec app php artisan udev:workspace:create
   ```

Buka <http://localhost:8010> untuk melihat status API. Endpoint pemeriksaan API ada di <http://localhost:8010/api/v1/health>. Frontend mendukung `localhost:5173` dan `127.0.0.1:5173`; gunakan hostname yang sama untuk membuka frontend dan API agar cookie Sanctum terkirim.

Port MySQL lokal adalah `3307`. Data database tersimpan di volume Docker `udev_mysql`.

## API awal

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/v1/health` | Status API |
| GET | `/api/v1/workspaces` | Workspace yang dapat diakses akun aktif |
| GET/PATCH | `/api/v1/workspaces/current` | Melihat atau mengubah nama workspace aktif (`workspaces.manage`) |
| POST | `/api/v1/auth/login` | Masuk dengan username dan password |
| GET | `/api/v1/auth/user` | Informasi akun yang sedang masuk |
| POST | `/api/v1/auth/logout` | Keluar dan menghapus session |
| POST | `/api/v1/access-control/users` | Membuat akun anggota pada workspace aktif (admin) |
| GET | `/api/v1/access-control/users` | Daftar anggota workspace aktif (admin) |
| PATCH | `/api/v1/access-control/users/{userId}/role` | Mengubah role anggota pada workspace aktif (admin) |
| GET | `/api/v1/customers` | Daftar pelanggan (admin) |
| POST | `/api/v1/customers` | Membuat pelanggan (admin) |
| GET | `/api/v1/customers/{customer}` | Detail pelanggan (admin) |
| PUT/PATCH | `/api/v1/customers/{customer}` | Memperbarui pelanggan (admin) |
| DELETE | `/api/v1/customers/{customer}` | Menghapus pelanggan secara soft delete (admin) |
| GET | `/api/v1/master/project-types` | Daftar jenis proyek (permission `master.project-types.manage`) |
| POST | `/api/v1/master/project-types` | Membuat jenis proyek |
| GET | `/api/v1/master/project-types/{project_type}` | Detail jenis proyek |
| PUT/PATCH | `/api/v1/master/project-types/{project_type}` | Memperbarui jenis proyek |
| GET | `/api/v1/projects` | Daftar project (permission `projects.manage`) |
| POST | `/api/v1/projects` | Membuat project; kode dibuat otomatis |
| GET | `/api/v1/projects/{project}` | Detail project |
| PUT/PATCH | `/api/v1/projects/{project}` | Memperbarui project |
| GET | `/api/v1/navigation` | Menu bertingkat yang boleh dilihat pengguna |
| GET/POST/PUT/PATCH/DELETE | `/api/v1/access-control/menus` | Mengelola menu dan submenu (admin) |
| GET/POST/PATCH | `/api/v1/access-control/permissions` | Melihat, membuat, dan memperbarui izin (admin) |
| GET/PUT | `/api/v1/access-control/roles/{role}/permissions` | Melihat atau mengganti izin sebuah role (admin) |
| GET | `/sanctum/csrf-cookie` | Mengambil cookie CSRF untuk SPA |

Endpoint login menerima JSON `username`, `password`, dan opsional `remember`. Admin membuat akun anggota dari **Pengaturan > Pengguna**; endpoint menerima nama, username, email, password, dan password_confirmation. Aplikasi SPA perlu mengaktifkan pengiriman credentials/cookies dan meminta `/sanctum/csrf-cookie` sebelum login. Pendaftaran publik tidak tersedia.

## Keamanan

- Route pengguna memerlukan autentikasi Sanctum.
- Login dibatasi lima percobaan per menit per alamat IP.
- Password di-hash oleh Laravel dan session diperbarui setelah login.
- Cookie session dienkripsi dan hanya dapat dibaca melalui HTTP.
- Akses database Docker dibatasi ke komputer lokal.
- Role awal: `admin` dan `member`; izin fitur disimpan di database dan dipetakan ke role.
- Data pelanggan, jenis proyek, proyek, invoice, pembayaran, pengeluaran, dan mapping role-permission dipisahkan per workspace. API privat menerima `X-Workspace-ID`; server memvalidasi keanggotaan sebelum membaca data.
- Daftar menu dan katalog permission merupakan konfigurasi platform; pengelolaannya khusus platform admin. Mapping hak akses role bersifat per workspace.
- Master pelanggan hanya dapat diakses dengan permission `master.customers.manage`; pelanggan yang dihapus memakai soft delete.
- Untuk deployment, gunakan HTTPS, `APP_DEBUG=false`, cookie secure, password rahasia yang unik, serta daftar CORS dan domain Sanctum yang hanya memuat alamat frontend resmi.

## Dokumentasi modul

- [Autentikasi dan akses tim](docs/modules/authentication.md)
- [Master pelanggan](docs/modules/master/customers.md)
- [Master jenis proyek](docs/modules/master/project-types.md)
- [Project](docs/modules/projects/project.md)
- [Menu dan hak akses](docs/modules/access-control/navigation.md)
- [Workspace dan pemisahan data](docs/modules/workspaces.md)

Kode backend dan dokumentasinya dikelompokkan berdasarkan menu. Master data menggunakan subfolder `Master` pada model, controller API, Form Request, dan `docs/modules/master/`. Definisi route versi API berada di `routes/api/v1/` dengan file terpisah per menu dan submenu; lokasi file tidak mengubah URL API yang sudah digunakan.

Perintah `udev:setup` menjalankan `DatabaseSeeder`, yang memasukkan menu, permission awal, tipe proyek, serta mapping permission untuk role `admin` dan `member`. Seeder aman dijalankan berulang kali dan mempertahankan mapping tambahan yang sudah dibuat. Untuk memperbarui data awal secara terpisah setelah perubahan menu atau permission, gunakan `docker compose exec app php artisan db:seed`.

## Pemeriksaan operasional

```powershell
docker compose ps
docker compose logs -f app
docker compose down
```

`docker compose down` tidak menghapus volume database. Hindari `docker compose down -v` kecuali memang ingin menghapus data lokal.

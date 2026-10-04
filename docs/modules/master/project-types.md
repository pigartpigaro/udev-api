# Master Jenis Proyek

## Tanggung jawab

Modul ini mengelola daftar jenis layanan/proyek untuk mengelompokkan proyek yang dibuat untuk pelanggan. Data jenis proyek dapat dinonaktifkan tanpa dihapus agar relasi dan riwayat proyek tetap terjaga.

## Data jenis proyek

- `code` — kode otomatis unik dengan prefix `U-TP` (contoh `U-TP0001`); kode stabil dan tidak dapat diedit.

- `name` — nama jenis layanan atau proyek, wajib dan unik.
- `description` — keterangan opsional.
- `is_active` — status aktif, default `true`.

## Endpoint

Semua endpoint di bawah `/api/v1/master/project-types` memerlukan autentikasi Sanctum dan permission `master.project-types.manage`.
Pencarian `search` mencakup kode. Foreign key proyek tetap memakai ID internal tipe proyek, bukan `code`.

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/master/project-types` | Daftar; mendukung `search`, `is_active`, dan `per_page` (1–100). |
| POST | `/master/project-types` | Membuat jenis proyek. |
| GET | `/master/project-types/{project_type}` | Detail jenis proyek. |
| PUT/PATCH | `/master/project-types/{project_type}` | Mengubah jenis proyek, termasuk menonaktifkan. |

Contoh payload:

```json
{
  "name": "Pengembangan Website",
  "description": "Pembuatan dan pengembangan aplikasi web",
  "is_active": true
}
```

Jenis proyek tidak dihapus melalui API; set `is_active` menjadi `false` untuk mempertahankan riwayat ketika kelak dipakai oleh proyek.

## Kode terkait

- `app/Models/Master/ProjectType.php`
- `app/Http/Controllers/Api/V1/Master/ProjectTypeController.php`
- `app/Http/Requests/Api/V1/Master/*ProjectTypeRequest.php`
- `routes/api/v1/master/project_types.php`
- `database/migrations/2026_10_01_033000_create_project_types_table.php`

## Tipe awal dan kode

Setiap tipe proyek memiliki kode otomatis unik dengan prefix `U-TP`, misalnya `U-TP0001`. Kode tetap stabil dan tidak diedit; relasi proyek tetap memakai ID internal, bukan kode. Pencarian `search` juga mencakup kode.

`ProjectTypeSeeder` menambahkan tipe secara idempoten bila belum ada: Aplikasi Web, Aplikasi Mobile, Aplikasi Desktop, dan Pengembangan Aplikasi Lainnya. Seeder tidak mengganti nama atau deskripsi tipe yang sudah tersedia.

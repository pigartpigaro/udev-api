# Project

## Tanggung jawab

Modul ini tersedia pada menu `Project > Daftar Project` dan mengelola project yang dimiliki pelanggan. Setiap project memakai foreign key `customer_id` dan `project_type_id` (ID internal); kode master hanya ditampilkan sebagai referensi. Kode project otomatis berbentuk `U-PR-YYYY-NNNN`, misalnya `U-PR-2026-0001`, dan tidak dapat diubah. Project tidak dihapus melalui API; gunakan status `cancelled` agar identitas dan riwayat tetap tersedia.

## Data dan status

Project memiliki nama, pelanggan, jenis project, tanggal mulai, target selesai, status, dan deskripsi opsional. Status yang didukung: `draft`, `active`, `on_hold`, `completed`, `cancelled`. Hanya pelanggan yang belum dihapus dan jenis project aktif yang dapat dipilih pada pembuatan/perubahan project.

## Endpoint

Semua endpoint di bawah `/api/v1/projects` memerlukan Sanctum dan permission `projects.manage`.

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/projects` | Daftar terpaginasikan; mendukung `search`, `status`, dan `per_page`. |
| POST | `/projects` | Membuat project; kode dibuat server. |
| GET | `/projects/{project}` | Detail project dan relasi pelanggan/jenis. |
| PUT/PATCH | `/projects/{project}` | Memperbarui data project tanpa mengubah kode. |

Pencarian meliputi kode/nama project, nama/kode pelanggan, dan nama/kode jenis. API tidak menyediakan delete supaya catatan project dapat dirujuk invoice dan penerimaan yang akan dibuat pada tahap berikutnya.

## Kode terkait

- `app/Models/Projects/Project.php`
- `app/Http/Controllers/Api/V1/Projects/ProjectController.php`
- `app/Http/Requests/Api/V1/Projects/`
- `routes/api/v1/projects/project.php`
- `database/migrations/2026_10_02_020000_create_projects_table.php`

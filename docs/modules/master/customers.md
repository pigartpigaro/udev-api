# Master Pelanggan

## Tanggung jawab

Modul ini mengelola data pelanggan yang akan dipakai oleh modul proyek, invoice, dan pembayaran. Seluruh endpoint memerlukan permission `master.customers.manage`, yang awalnya diberikan ke role `admin`.

## Data pelanggan

- `name` — nama pelanggan atau perusahaan, wajib.
- `contact_name` — nama kontak utama, opsional.
- `email` — email kontak, opsional.
- `phone` — nomor telepon, opsional.
- `address` — alamat, opsional.
- `is_active` — status pelanggan, default `true`.

Penghapusan memakai soft delete. Data yang dihapus tidak muncul pada daftar maupun pencarian API, tetapi catatannya tetap tersimpan untuk kebutuhan relasi dan riwayat data di masa depan.

## Endpoint

Semua endpoint berikut berada di bawah `/api/v1`, memerlukan autentikasi Sanctum, dan memerlukan permission `master.customers.manage`.

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/customers` | Daftar pelanggan, mendukung `search` dan `per_page` (1–100). |
| POST | `/customers` | Membuat pelanggan. |
| GET | `/customers/{customer}` | Detail pelanggan. |
| PUT/PATCH | `/customers/{customer}` | Memperbarui pelanggan. |
| DELETE | `/customers/{customer}` | Soft delete pelanggan. |

Contoh payload membuat atau memperbarui pelanggan:

```json
{
  "name": "PT Contoh Pelanggan",
  "contact_name": "Siti Contoh",
  "email": "siti@example.test",
  "phone": "081234567890",
  "address": "Jakarta",
  "is_active": true
}
```

## Kode terkait

- `app/Models/Master/Customer.php`
- `app/Http/Controllers/Api/V1/Master/CustomerController.php`
- `app/Http/Requests/Api/V1/Master/*CustomerRequest.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `routes/api/v1/master/customers.php`
- `database/migrations/2026_10_01_030000_create_customers_table.php`
- `database/seeders/AccessControlSeeder.php` (permission awal)

Definisi route dipisahkan berdasarkan menu di direktori `routes/api/v1/`; URL publik tetap `/api/v1/customers` untuk menjaga kompatibilitas.

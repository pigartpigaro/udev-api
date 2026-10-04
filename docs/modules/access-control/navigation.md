# Menu, Navigasi, dan Hak Akses

## Tanggung jawab

Menu/submenu disimpan sebagai pohon data di tabel `menus`. Permission tersedia di tabel `permissions`, sedangkan izin role disimpan di `role_permissions`. API navigasi hanya mengembalikan menu aktif yang dapat dilihat oleh role pengguna yang sedang login.

Data menu mengatur label, susunan, ikon, key, dan nama route frontend. Menambahkan menu ke database tidak membuat controller atau endpoint backend secara otomatis; fitur API tetap harus diimplementasikan di kode. Backend memeriksa permission pada setiap route privat.

## Struktur

- `menus.parent_id` membentuk hierarki menu/submenu.
- `menus.permission_id` mengaitkan item dengan permission untuk menampilkan menu.
- `menus.key` menjadi identifier stabil, misalnya `master.project-types`.
- `menus.route_name` adalah nama route frontend yang ditafsirkan frontend; backend tidak menjalankan route dari nilai ini.
- `permissions.key` adalah identifier permission yang dicek middleware, misalnya `master.project-types.manage`.
- `role_permissions` memberikan permission kepada nama role yang sama dengan nilai `users.role`.

Role awal `admin` menerima permission pengelolaan yang disediakan seeder. Role `member` hanya menerima `navigation.view` sampai izin fitur diberikan admin melalui halaman **Pengaturan > Hak Akses > Role dan Izin**. UI saat ini mengatur izin role Member; permission pengelolaan workspace/hak akses dan permission platform tidak dapat diberikan ke Member.

## Endpoint

Semua endpoint pengelolaan memerlukan autentikasi Sanctum dan permission yang sesuai. Seeder awal hanya memberikan permission pengelolaan kepada role `admin`.

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/v1/navigation` | Mengambil pohon menu sesuai permission role login. |
| GET/POST | `/api/v1/access-control/menus` | Daftar atau membuat menu (`access-control.menus.manage`). |
| GET/PUT/PATCH/DELETE | `/api/v1/access-control/menus/{menu}` | Detail, perubahan, atau penghapusan menu. Menu induk yang memiliki submenu tidak dapat dihapus. |
| GET | `/api/v1/access-control/permissions` | Daftar permission beserta role penerimanya (`access-control.permissions.manage`). |
| POST | `/api/v1/access-control/permissions` | Membuat permission baru (`access-control.permissions.manage`). |
| PATCH | `/api/v1/access-control/permissions/{permission}` | Mengubah label permission; key bersifat tetap (`access-control.permissions.manage`). |
| GET | `/api/v1/access-control/roles/{role}/permissions` | Melihat izin suatu role (`access-control.roles.manage`). |
| GET | `/api/v1/access-control/roles/member/permission-options` | Daftar izin yang dapat diatur untuk role Member, beserta status aktifnya (`access-control.roles.manage`). |
| PUT | `/api/v1/access-control/roles/member/permissions` | Mengganti seluruh izin role Member (`access-control.roles.manage`). Izin administrasi yang dilindungi ditolak server. |

Contoh mengganti seluruh izin sebuah role:

```json
{
  "permission_ids": [1, 2, 3]
}
```

Perubahan mapping izin dilakukan dalam transaksi database. Hapus dan penambahan menu melalui endpoint admin tidak otomatis memberikan izin ke role.

## Kode terkait

- `app/Models/AccessControl/`
- `app/Http/Controllers/Api/V1/AccessControl/`
- `app/Http/Middleware/EnsureUserHasPermission.php`
- `database/migrations/2026_10_01_031000_create_permissions_and_role_permissions_tables.php`
- `database/migrations/2026_10_01_032000_create_menus_table.php`
- `database/seeders/AccessControlSeeder.php`
- `routes/api/v1/access-control/menus.php`
- `routes/api/v1/navigation.php`

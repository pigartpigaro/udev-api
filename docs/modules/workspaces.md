# Workspace dan pemisahan data

UDev memakai satu database bersama, dengan setiap data operasional terikat pada `workspace_id`. Workspace mewakili perusahaan/organisasi pelanggan UDev; keanggotaan akun dan role-nya disimpan pada `workspace_user`.

## Data dan akses

Data pelanggan, jenis proyek, proyek, invoice, pembayaran, pengeluaran, serta mapping role-permission terisolasi per workspace. Model operasional menerapkan scope workspace; referensi silang seperti pelanggan pada proyek dan invoice pada pembayaran juga divalidasi terhadap workspace aktif. Route model binding tidak menemukan record dari workspace lain.

Setelah autentikasi, client meminta `GET /api/v1/workspaces`. Request API operasional mengirim header `X-Workspace-ID` sesuai workspace aktif. Server memeriksa keanggotaan dan status workspace, lalu menerapkan role dari membership tersebut. Akun dengan satu workspace dapat dipilih otomatis; jika memiliki beberapa workspace, client harus mengirim ID pilihan. Header dari client tidak dianggap sebagai izin.

Menu dan katalog permission adalah konfigurasi platform global. Endpoint pengelolaannya dibatasi untuk platform administrator. Mapping permission untuk role `admin` dan `member` disimpan per workspace dan dapat diubah pengelola workspace melalui access control.

Admin workspace dapat membuka **Pengaturan > Workspace** untuk melihat identitas workspace aktif dan mengubah namanya. Slug tetap karena menjadi identitas stabil. API `GET/PATCH /api/v1/workspaces/current` memerlukan `workspaces.manage` dan workspace aktif pada header.

## Migrasi dan provisioning

Migrasi `2026_10_03_130000_add_workspaces_and_tenant_scoping` membuat `Workspace Utama`, mengaitkan data lama dan akun lama ke workspace tersebut, serta menandai akun admin lama tertua sebagai platform administrator. Migrasi ini tidak menghapus data. Rollback ditolak jika sudah ada lebih dari satu workspace agar data tenant tidak terhapus secara tak sengaja.

Buat workspace perusahaan melalui:

```powershell
docker compose exec app php artisan udev:workspace:create
```

Perintah interaktif membuat workspace dan pemilik, menyalin role-permission awal serta jenis proyek dari Workspace Utama, dan meminta konfirmasi bila email pemilik sudah menjadi akun. Untuk akun baru, password dimasukkan secara tersembunyi dan wajib minimal 6 karakter. Pendaftaran publik belum tersedia; provisioning dilakukan operator.

## Batas implementasi saat ini

Workspace perusahaan baru masih dibuat operator melalui command provisioning. Admin workspace dapat membuat akun anggota melalui **Pengaturan > Pengguna**; anggota baru hanya ditautkan ke workspace aktif dan tidak otomatis memperoleh keanggotaan workspace lain. Undangan email, daftar/edit/nonaktifkan anggota, pengelolaan langganan/kuota, pendaftaran mandiri, billing SaaS, dan UI administrasi platform belum termasuk implementasi ini. Sebelum membuka tenant untuk pelanggan, provisioning workspace dan pemulihan akses tetap dikelola operator.

## Pemeriksaan

Tes `WorkspaceIsolationTest` memeriksa penyaringan daftar, akses menggunakan workspace aktif, dan penolakan route model binding untuk record tenant lain:

```powershell
docker compose exec app php artisan test --filter=WorkspaceIsolationTest
```

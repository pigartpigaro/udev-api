# Invoice Project

Menu `Project > Invoice` mendukung dua jenis tagihan: bulanan per project/periode (`YYYY-MM`) dan sekali tanpa periode. Hanya satu invoice aktif diperbolehkan per project/bulan untuk tagihan bulanan dan satu tagihan sekali per project. Total tagihan (IDR), tanggal invoice, tanggal jatuh tempo opsional, serta catatan disimpan pada setiap invoice. Kode dibuat server sebagai `U-IN-YYYY-NNNN`. Nilai uang disimpan sebagai `DECIMAL(15,2)`. Data invoice yang sudah ada dimigrasikan sebagai tagihan bulanan berdasarkan bulan tanggal invoice.

Alur status: invoice dibuat sebagai `draft`, draft dapat diedit, lalu diterbitkan menjadi `issued`. Invoice terbit terkunci. Jika invoice terbit salah, admin wajib membatalkannya dengan alasan; sistem menyimpan waktu dan user pembatal. Setelah itu admin dapat membuat invoice pengganti pada project/periode sama, yang mencatat relasi ke invoice batal. Satu invoice aktif (draft/issued) diperbolehkan per project/periode. Invoice lama tidak dihapus. Penerimaan/cicilan belum dicatat di modul ini dan akan memakai modul terpisah.

Endpoint berada di `/api/v1/invoices`, memerlukan Sanctum serta permission `projects.invoices.manage`:

- `GET /invoices`: daftar dengan `search`, `status`, `per_page`.
- `POST /invoices`: membuat draft; kode dibuat backend.
- `GET /invoices/{invoice}` dan `PATCH /invoices/{invoice}`: detail dan ubah draft.
- `POST /invoices/{invoice}/issue` dan `/cancel`: transisi status yang dijaga backend; pembatalan memerlukan alasan.

Frontend menyediakan halaman dokumen di `/projects/invoices/{id}/print`. Tombol **Cetak** membuka dialog print browser. **Download PDF** membuat file PDF langsung di perangkat, termasuk ponsel. Dokumen memuat status batal dan referensi invoice yang digantikan jika ada. Konfirmasi terbit/batal menggunakan modal aplikasi; alasan pembatalan wajib dicatat.

Invoice yang memiliki penerimaan sah tidak dapat dibatalkan sebelum seluruh penerimaan terkait dikoreksi melalui alur audit Pembayaran.

Kode utama: `app/Models/Projects/ProjectInvoice.php`, `app/Http/Controllers/Api/V1/Projects/ProjectInvoiceController.php`, `routes/api/v1/projects/invoices.php`, dan migration `create_project_invoices_table`.

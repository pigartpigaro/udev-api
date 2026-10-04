# Pengeluaran Project dan Operasional

Menu `Project > Pengeluaran` mencatat biaya yang telah dibayarkan, baik yang terkait ke project maupun operasional tim. Hubungan ke project bersifat opsional. Transaksi langsung berstatus sah (`paid`) dan tidak dapat diedit atau dihapus; koreksi mengubah status menjadi `voided` dengan alasan, waktu, serta user pengoreksi agar riwayat tetap tersedia. Kode dibuat server dalam format `U-PG-YYYY-NNNNNN`.

Kategori awal: transportasi, akomodasi, konsumsi, peralatan, software/layanan digital, komunikasi, dan lainnya. Metode pembayaran: tunai, transfer bank, atau lainnya. Referensi transaksi bersifat opsional untuk mencatat nomor kuitansi/transfer; unggah lampiran bukti belum termasuk alur awal.

Endpoint privat memakai Sanctum dan permission `projects.expenses.manage`:

- `GET /api/v1/expenses`: daftar, pencarian global, dan pagination API untuk infinite scroll; mencari kode, penerima, referensi, project, atau nama project.
- `POST /api/v1/expenses`: catat pengeluaran sah; `project_id` opsional.
- `POST /api/v1/expenses/{expense}/void`: koreksi dengan alasan minimal 5 karakter.

Nilai uang disimpan sebagai decimal dua angka di belakang koma. Daftar menampilkan tabel desktop dan kartu mobile; pencarian memakai search global di header.

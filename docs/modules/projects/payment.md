# Pembayaran Project

Menu `Project > Pembayaran` mencatat uang yang sudah diterima atas invoice terbit. Satu invoice dapat memiliki beberapa penerimaan sehingga mendukung DP/uang muka, pembayaran termin/cicilan, dan pelunasan. Pembayaran hanya dapat dicatat jika nilai positif dan tidak melebihi sisa tagihan. Sisa dihitung dari jumlah penerimaan berstatus `received`; kode penerimaan dibuat server dengan format `U-PY-YYYY-NNNNNN`.

Setiap penerimaan langsung berstatus sah (`received`) saat dibuat, mencatat user pencatat, tanggal penerimaan, metode (tunai, transfer bank, lainnya), referensi transaksi opsional, dan catatan. Data tidak dapat diedit atau dihapus. Koreksi dilakukan dengan menandai penerimaan sebagai `voided`, wajib menyimpan alasan, waktu, dan user pengoreksi. Penerimaan terkoreksi tetap terlihat dalam riwayat dan tidak lagi mengurangi sisa invoice. Invoice dengan penerimaan sah tidak dapat dibatalkan sampai penerimaan tersebut dikoreksi.

Kuitansi memuat kode penerimaan, tanggal/metode, pelanggan, invoice dan project, nominal serta terbilang, referensi, catatan, dan pencatat. Penerimaan terkoreksi tetap dapat dicetak/diunduh sebagai salinan bertanda tidak sah beserta alasan koreksi. Tombol PDF di daftar mengunduh langsung; halaman cetak hanya menyediakan aksi cetak.

Endpoint privat membutuhkan Sanctum dan permission `projects.payments.manage`:

- `GET /api/v1/payments`: daftar penerimaan dengan pencarian, pagination API untuk infinite scroll frontend.
- `GET /api/v1/payments/{payment}`: rincian penerimaan untuk membuat atau mencetak kuitansi.
- `GET /api/v1/payments/eligible-invoices`: invoice terbit yang masih memiliki sisa tagihan; menerima `search`, `per_page`.
- `POST /api/v1/payments`: catat penerimaan sah.
- `POST /api/v1/payments/{payment}/void`: koreksi dengan alasan wajib.

Validasi batas sisa tagihan dan penyimpanan dilakukan dalam transaksi dengan penguncian invoice agar dua penerimaan bersamaan tidak melampaui tagihan. UI daftar menggunakan tabel desktop dan kartu mobile; form pencatatan menjadi layar penuh pada ponsel.

# Laporan Keuangan

Menu `Laporan > Laporan Keuangan` merangkum invoice terbit, penerimaan sah, pengeluaran sah, pencairan/pembayaran kasbon tim, arus kas bersih, serta saldo sisa tagihan dan kasbon untuk seluruh project atau project yang dipilih.

Filter periode diterapkan sesuai tanggal peristiwa: invoice pada `issued_at`, penerimaan pada `received_at`, pengeluaran pada `spent_at`, pencairan kasbon pada `issued_at`, dan cicilan pada `repaid_at`. Arus kas bersih mengurangi kasbon yang dicairkan serta menambahkan cicilan yang diterima; pencairan kasbon bukan biaya. Sisa tagihan dan sisa kasbon adalah saldo saat ini dan tidak dibatasi periode. Invoice `draft`/`cancelled`, pembayaran/pengeluaran/kasbon/cicilan `voided` tidak dihitung. Tanpa filter project, total pengeluaran juga mencakup biaya operasional tim.

Endpoint privat memakai Sanctum dan permission `projects.reports.view`:

- `GET /api/v1/projects/reports/financial`: menerima `date_from`, `date_to`, `project_id`, `page`, dan `per_page`; mengembalikan ringkasan sesuai filter serta rincian project berhalaman.

## Ekspor

- PDF dibuat dari dokumen laporan dengan orientasi landscape, berisi ringkasan dan seluruh rincian project yang cocok dengan filter.
- Excel diunduh sebagai `.xlsx` dengan sheet `Laporan Keuangan`, berisi periode filter, ringkasan nilai, dan rincian per project. Nilai uang diekspor sebagai angka agar dapat diolah di spreadsheet.
- Kedua format meminta semua halaman endpoint sebelum membuat berkas; ekspor tidak terbatas pada baris yang sudah dimuat oleh infinite scroll.

Di frontend, halaman berada pada `src/modules/projects/financial-report/`; komponen dokumen PDF berada di `components/document/` dan implementasi ekspor di `utils/reportExport.ts`.

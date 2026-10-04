# Laporan Keuangan

Menu `Laporan > Laporan Keuangan` merangkum invoice terbit, penerimaan sah, pengeluaran sah, arus kas bersih, dan saldo sisa tagihan untuk seluruh project atau project yang dipilih.

Filter periode diterapkan sesuai tanggal peristiwa: invoice pada `issued_at`, penerimaan pada `received_at`, serta pengeluaran pada `spent_at`. Sisa tagihan adalah saldo saat ini dari semua invoice berstatus `issued`; nilainya sengaja tidak dibatasi periode. Invoice `draft`/`cancelled`, pembayaran `voided`, dan pengeluaran `voided` tidak dihitung. Tanpa filter project, total pengeluaran juga mencakup biaya operasional tim yang tidak terikat ke project.

Endpoint privat memakai Sanctum dan permission `projects.reports.view`:

- `GET /api/v1/projects/reports/financial`: menerima `date_from`, `date_to`, `project_id`, `page`, dan `per_page`; mengembalikan ringkasan sesuai filter serta rincian project berhalaman.

## Ekspor

- PDF dibuat dari dokumen laporan dengan orientasi landscape, berisi ringkasan dan seluruh rincian project yang cocok dengan filter.
- Excel diunduh sebagai `.xlsx` dengan sheet `Laporan Keuangan`, berisi periode filter, ringkasan nilai, dan rincian per project. Nilai uang diekspor sebagai angka agar dapat diolah di spreadsheet.
- Kedua format meminta semua halaman endpoint sebelum membuat berkas; ekspor tidak terbatas pada baris yang sudah dimuat oleh infinite scroll.

Di frontend, halaman berada pada `src/modules/projects/financial-report/`; komponen dokumen PDF berada di `components/document/` dan implementasi ekspor di `utils/reportExport.ts`.

# Kasbon Tim

Menu `Keuangan > Kasbon Tim` mencatat uang kas workspace yang dipinjam anggota tim. Saldo awal diperlakukan sebagai Rp0. Kasbon mengurangi saldo kas, tetapi dicatat sebagai piutang (bukan pengeluaran); pengembalian menambah saldo kas dan mengurangi piutang.

Kasbon dapat dilunasi dalam satu kali pembayaran atau beberapa cicilan. Setiap transaksi memiliki kode otomatis (`U-KS-YYYY-NNNNNN` untuk kasbon dan `U-KB-YYYY-NNNNNN` untuk cicilan). Daftar memperlihatkan nominal awal, total pembayaran, sisa per pinjaman, serta ringkasan sisa per anggota. Nominal kasbon baru tidak boleh melebihi saldo tersedia. Semua data dibatasi workspace aktif.

Kasbon/pembayaran yang sudah sah tidak dihapus. Koreksi memerlukan alasan dan disimpan sebagai riwayat; kasbon yang sudah memiliki cicilan harus mengoreksi cicilannya terlebih dahulu sebelum kasbon dapat dikoreksi.

Semua endpoint berikut memakai Sanctum, workspace aktif, dan permission `finance.team-loans.manage`:

- `GET /api/v1/team-loans` — pencarian dan pagination untuk infinite scroll.
- `GET /api/v1/team-loans/members` — anggota workspace untuk pilihan peminjam.
- `GET /api/v1/team-loans/summary` — saldo kas tersedia dan sisa kasbon per anggota.
- `GET /api/v1/team-loans/{id}` — detail kasbon beserta riwayat cicilan.
- `POST /api/v1/team-loans` — catat kasbon sah.
- `POST /api/v1/team-loans/{id}/repayments` — catat cicilan/pelunasan.
- `POST /api/v1/team-loans/{id}/void` — koreksi kasbon tanpa pembayaran, dengan `reason` minimal 5 karakter.
- `POST /api/v1/team-loans/{id}/repayments/{repayment}/void` — koreksi cicilan dengan `reason` minimal 5 karakter.

Menu dan permission di-seed oleh `AccessControlSeeder`; anggota dan admin workspace mendapat akses input. UI berada di `udev-frontend/src/modules/projects/expense/` bersama pola transaksi keuangan yang sudah ada; URL frontend adalah `/finance/team-loans`.

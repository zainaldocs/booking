# Rencana Implementasi Sistem Booking Ruang Meeting (Versi 1.1)

Pengembangan Versi 1.1 berfokus pada penyempurnaan fungsionalitas bagi Pengelola (Admin), dengan menambahkan fitur pelaporan yang komprehensif serta memperluas informasi *dashboard* harian.

## 1. Modul Laporan Approval (`admin/approval.php`)
Fitur ini akan menyempurnakan halaman daftar persetujuan (*approval*) menjadi sebuah pusat laporan yang interaktif dan dapat diekspor.

**Detail Pengerjaan:**
- **Penyesuaian Query**: Menyisipkan query `LEFT JOIN users_admin` untuk dapat mengambil dan menampilkan data nama petugas.
- **Panel Filter Dinamis**: Menambahkan formulir *Filter* di bagian atas tabel, yang memuat:
  1. Input Pencarian Teks (Berdasarkan Nama Peminjam atau Nama Ruangan).
  2. Dropdown Filter Status (Tampilkan Semua, Pending, Approved, atau Rejected).
  3. Filter Rentang Tanggal (Tanggal Awal hingga Tanggal Akhir).
- **Penambahan Kolom Petugas**: Menyisipkan kolom "Petugas Penyiapan" tepat di sebelah kolom status, sehingga admin dapat langsung mengetahui distribusi tanggung jawab tanpa harus membuka halaman detail.
- **Fitur Export (Download)**: Pembuatan file baru `admin/export_approval.php` yang akan dihubungkan ke tombol **"Export ke CSV"**. Sistem *export* ini bersifat dinamis; file Excel/CSV yang diunduh akan berisi data yang sudah tersetel oleh filter yang sedang aktif pada saat itu.

---

## 2. Peningkatan Dashboard Admin (`admin/index.php`)
Dashboard akan menjadi lebih fungsional untuk mendukung kegiatan operasional harian.

**Detail Pengerjaan:**
- **Tabel "Jadwal Disetujui Hari Ini"**: Tepat di bawah kotak statistik total (*Total Ruangan, Total Fasilitas, Pending Hari Ini*), akan ditambahkan sebuah area tabel baru.
- **Logika Data**: Tabel ini secara otomatis menarik data dari database yang berstatus `Approved` DAN tanggal pelaksanaannya bertepatan dengan tanggal hari ini (`CURDATE()`). Kolom yang ditampilkan meliputi Ruangan, Jam, Nama Peminjam, dan Petugas.
- **Tombol Pintasan Kalender**: Di bagian paling bawah tabel tersebut, akan disediakan tombol besar "Lihat Full Kalender" sebagai transisi natural ke fitur kalender admin.

---

## 3. Fitur Kalender Khusus Admin (`admin/kalender.php`)
Admin tidak perlu lagi berpindah ke halaman publik (halaman peminjam) hanya untuk melihat ketersediaan jadwal ruang *meeting*.

**Detail Pengerjaan:**
- **Pembuatan Halaman Baru**: Membuat file `admin/kalender.php` yang memiliki tampilan identik (*layout* & *sidebar*) dengan halaman admin lainnya.
- **Integrasi FullCalendar**: Mengadaptasi logika JavaScript *FullCalendar* yang ada pada halaman depan untuk ditampilkan secara optimal di dalam *container* panel admin. Hal ini akan mempermudah admin dalam melakukan audit slot kosong secara cepat.

---

*Dokumentasi Perencanaan ini disusun sebagai *blueprint* pengerjaan Versi 1.1.*

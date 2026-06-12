# Sistem Booking Ruang Meeting - Versi 1.0.0

Aplikasi berbasis web untuk manajemen dan peminjaman ruang meeting beserta fasilitas tambahan di dalamnya. Sistem ini memisahkan secara jelas antara akses publik (pengguna yang ingin meminjam) dan panel administrator (untuk persetujuan dan pengelolaan data).

---

## 🌟 Fitur Utama (Core Features)

### 1. Halaman Publik (User Umum)
- **Kalender Interaktif**: Menggunakan *FullCalendar* untuk menampilkan jadwal ruang meeting yang telah disetujui (Approved) secara *real-time*.
- **Form Pengajuan Cerdas**: Form peminjaman terintegrasi yang memungkinkan pengguna untuk:
  - Memilih ruangan, tanggal, dan rentang jam.
  - Memilih *Departemen* asal.
  - Mengisi tujuan kegiatan (*Keterangan*).
  - Melakukan *request* (permintaan) unit fasilitas tambahan secara dinamis sesuai kebutuhan.
- **Validasi Waktu Jam Kerja**: Sistem secara otomatis menolak permintaan dengan jam di luar jam kantor (08:00 - 17:00) atau alur jam yang tidak masuk akal (Jam Mulai lebih besar dari Jam Selesai).
- **Desain Modern & Responsif**: Dibangun sepenuhnya dengan estetika *Tailwind CSS* yang bersih dan modern.

### 2. Panel Admin
- **Dashboard Statistik**: Rangkuman total jumlah ruangan, unit fasilitas, dan request *pending* hari ini.
- **Manajemen Approval (Persetujuan)**:
  - Melihat detail peminjaman secara menyeluruh.
  - **Sistem Anti-Bentrok**: Sistem mencegah Admin menyetujui jadwal ruangan atau fasilitas fisik (*nomor seri*) yang secara spesifik sudah dibooking/dipinjam di rentang waktu yang sama.
  - Mengalokasikan/menunjuk nomor seri fasilitas spesifik untuk tiap permintaan.
  - Menunjuk **Petugas** (dari daftar Admin) yang bertanggung jawab menyiapkan fasilitas.
- **Manajemen Data Induk (CRUD)**:
  - Kelola Ruangan (Kapasitas & Nama).
  - Kelola Departemen (Asal departemen karyawan).
  - Kelola Jenis Fasilitas (contoh: Proyektor, Laptop).
  - Kelola Unit Fasilitas (Pencatatan aset fisik berdasarkan Nomor Seri & Kondisi).
- **Manajemen Akun Admin (Multi-Admin)**: Fitur Tambah, Edit Profil/Password, dan Hapus akun pengelola (*Admin/Petugas*).
- **Pengaturan Notifikasi (SMTP Mailer)**: Terintegrasi dengan fitur otomatis pengiriman Email notifikasi kepada peminjam saat status peminjaman Disetujui atau Ditolak. (Template email dan konfigurasi server dapat disetting langsung di Panel Admin).

---

## 🛠️ Teknologi yang Digunakan
- **Backend Language**: PHP (Procedural & PDO API)
- **Database**: MySQL / MariaDB
- **Frontend UI**: HTML5, Vanilla JavaScript, CSS3
- **Styling Framework**: Tailwind CSS (Kompilasi lokal)
- **Plugins**: FullCalendar JS, PHPMailer

---

## 📋 Riwayat Perubahan & Permintaan Fitur (Changelog)

Sepanjang proses pengembangan hingga mencapai versi stabil 1.0.0, aplikasi ini telah melalui serangkaian permintaan perubahan dan perbaikan dari struktur dasarnya, antara lain:

1. **Implementasi Anti-Bentrok Waktu**:
   - *Request*: Menambahkan logika validasi khusus agar jam peminjaman di rentang 08:00 - 17:00 dan tidak tumpang tindih (*overlap*) dengan jadwal ruangan yang sudah di-*Approve* sebelumnya.
2. **Pembaruan Kolom Form Peminjaman**:
   - *Request*: Penambahan *field* **Departemen** (Sumber data dinamis/CRUD) dan teks **Keterangan** (Tujuan acara) karena keadaan real di lapangan. Penambahan sistem fasilitas dinamis agar user bisa meminta misal: 2 Laptop dan 1 Proyektor sekaligus.
3. **Overhaul UI / Redesign Visual Form**:
   - *Request*: Merombak ulang tampilan `form_booking.php` secara total menjadi desain *card* bersusun yang jauh lebih lapang (*spacious*), bersih, modern, dengan *padding* dan *margin* antar input yang terstruktur standar enterprise tanpa mengubah logika *backend*.
4. **Standardisasi Konsistensi Panel Admin**:
   - *Request*: Memperbaiki ketidakkonsistenan warna, bentuk huruf (hilangnya font *Inter*), dan layout *sidebar* pada halaman Dashboard serta beberapa halaman spesifik seperti Departemen dan Unit Fasilitas yang sebelumnya tertulis *hardcoded*.
5. **Restrukturisasi Navigasi Halaman Depan**:
   - *Request*: Menukar letak tombol aksi utama di sidebar `index.php` sehingga tombol '+ Form Peminjaman' diletakkan mencolok di atas, dan tautan 'Login Panel Admin' menjadi elegan di bawah.
6. **Multi-Admin & Penugasan Petugas**:
   - *Request*: Menambahkan fitur "Manajemen Admin" dengan kapabilitas CRUD utuh (termasuk Edit Profil dan Enkripsi Password). Kemudian, mengintegrasikan data admin ini ke dalam alur *Approval*, sehingga penyetuju dapat menunjuk siapa Petugas (*Assignee*) yang secara riil menyiapkan ruangan untuk peminjaman tersebut.

---
*Dokumentasi ini di-generate pada peresmian Versi 1.0.0 Sistem Booking.*

<?php
require_once 'config/database.php';

// Fetch Ruangan
$stmt_ruangan = $pdo->query("SELECT * FROM ruangan");
$ruangans = $stmt_ruangan->fetchAll(PDO::FETCH_ASSOC);

// Fetch Jenis Fasilitas
$stmt_jf = $pdo->query("SELECT * FROM jenis_fasilitas");
$jenis_fasilitas = $stmt_jf->fetchAll(PDO::FETCH_ASSOC);

// Fetch Departemen
$stmt_dept = $pdo->query("SELECT * FROM departemen ORDER BY nama_departemen ASC");
$departemens = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'error') {
        $message = '<div class="bg-red-50 border border-red-100 rounded-2xl p-5 flex items-start space-x-4 mb-8 shadow-sm">
                        <div class="flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-red-800">Terjadi kesalahan sistem</h3>
                            <p class="text-sm text-red-700 mt-1">Silakan periksa kembali isian Anda atau coba muat ulang halaman.</p>
                        </div>
                    </div>';
    } elseif ($_GET['msg'] == 'time_invalid') {
        $message = '<div class="bg-red-50 border border-red-100 rounded-2xl p-5 flex items-start space-x-4 mb-8 shadow-sm">
                        <div class="flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-red-800">Waktu Peminjaman Tidak Valid</h3>
                            <p class="text-sm text-red-700 mt-1">Waktu peminjaman harus berada di antara jam operasional (08:00 - 17:00), dan Jam Mulai tidak boleh melewati Jam Selesai.</p>
                        </div>
                    </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Booking - Sistem Booking Ruang Meeting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-72 bg-white shadow-lg h-full flex flex-col z-20 shrink-0">
        <div class="p-6 border-b border-gray-100 bg-brand-50/50">
            <h1 class="text-2xl font-bold text-brand-600 flex items-center space-x-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>BookingRoom</span>
            </h1>
        </div>
        <nav class="flex-1 p-6 space-y-2">
            <a href="index.php" class="block px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">Dashboard Kalender</a>
            <a href="admin/login.php" class="block px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium transition-colors">Panel Admin</a>
        </nav>
        <div class="p-6 border-t border-gray-100 bg-gray-50">
            <div class="block w-full py-3 px-4 rounded-xl bg-brand-100 text-brand-700 text-center font-semibold border border-brand-200 cursor-default">
                Sedang Mengisi Form
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-y-auto relative">
        <div class="py-12 px-6 sm:px-10 lg:px-16 max-w-4xl w-full mx-auto">
            
            <!-- Page Header -->
            <div class="mb-10 text-center sm:text-left">
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Pengajuan Ruang Meeting</h2>
                <p class="text-base text-gray-500 mt-2">Lengkapi detail formulir di bawah ini untuk mereservasi ruangan dan fasilitas tambahan.</p>
            </div>
            
            <?= $message ?>
            
            <!-- Form Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                <form action="proses_peminjaman.php" method="POST" class="p-8 sm:p-12 flex flex-col gap-12">
                    
                    <!-- Section 1: Informasi Peminjam -->
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3 mb-6">1. Informasi Peminjam</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap <span class="text-red-500 ml-0.5">*</span></label>
                                <input type="text" name="nama_peminjam" required class="form-input" placeholder="Ketik nama Anda...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Email <span class="text-red-500 ml-0.5">*</span></label>
                                <input type="email" name="email_peminjam" required class="form-input" placeholder="email@perusahaan.com">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Departemen <span class="text-red-500 ml-0.5">*</span></label>
                                <select name="departemen_id" required class="form-input sm:w-1/2">
                                    <option value="" disabled selected>-- Pilih Departemen --</option>
                                    <?php foreach($departemens as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama_departemen']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: Detail Kegiatan -->
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3 mb-6 mt-6">2. Detail Kegiatan & Ruangan</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Ruangan <span class="text-red-500 ml-0.5">*</span></label>
                                <select name="ruangan_id" required class="form-input sm:w-1/2">
                                    <option value="" disabled selected>-- Pilih Ruangan --</option>
                                    <?php foreach($ruangans as $r): ?>
                                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama_ruangan']) ?> (Kapasitas: <?= $r['kapasitas'] ?> Orang)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tujuan / Keterangan Kegiatan <span class="text-red-500 ml-0.5">*</span></label>
                                <textarea name="keterangan" required class="form-input h-32 resize-y" placeholder="Jelaskan secara ringkas tujuan peminjaman ruangan ini..."></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Section 3: Waktu Pelaksanaan -->
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3 mb-6 mt-6">3. Waktu Pelaksanaan</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Peminjaman <span class="text-red-500 ml-0.5">*</span></label>
                                <input type="date" name="tanggal" required class="form-input" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai <span class="text-red-500 ml-0.5">*</span></label>
                                <input type="time" name="jam_mulai" required class="form-input" min="08:00" max="17:00">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai <span class="text-red-500 ml-0.5">*</span></label>
                                <input type="time" name="jam_selesai" required class="form-input" min="08:00" max="17:00">
                            </div>
                        </div>
                        <div class="bg-blue-50/50 rounded-xl p-4 flex items-start space-x-3 border border-blue-100">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-blue-800 leading-relaxed mt-2">Jam operasional peminjaman ruangan adalah <strong>08:00 hingga 17:00</strong>. Sistem akan menolak pengajuan di luar batas waktu operasional secara otomatis.</p>
                        </div>
                    </section>

                    <!-- Section 4: Fasilitas Tambahan -->
                    <section>
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-800">4. Fasilitas Tambahan</h3>
                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wider bg-gray-100 px-2 py-1 rounded-md">Opsional</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php if(empty($jenis_fasilitas)): ?>
                                <p class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-xl border border-gray-100 col-span-full">Belum ada fasilitas ekstra yang tersedia untuk dipinjam saat ini.</p>
                            <?php else: ?>
                                <?php foreach($jenis_fasilitas as $jf): ?>
                                <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-brand-400 hover:shadow-md transition-all duration-200 group">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-brand-700 transition-colors"><?= htmlspecialchars($jf['nama_jenis']) ?></span>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Qty</span>
                                        <input type="number" name="fasilitas_jumlah[<?= $jf['id'] ?>]" min="0" value="0" class="w-16 h-10 rounded-lg border border-gray-300 text-center text-sm font-semibold text-gray-800 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all focus:outline-none">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Submit Actions -->
                    <div class="pt-6 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end items-center sm:space-x-4">
                        <a href="index.php" class="w-full sm:w-auto text-center py-3 px-6 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors mt-4 sm:mt-0">
                            Batal & Kembali
                        </a>
                        <button type="submit" class="btn-primary w-full sm:w-auto px-8 py-3.5 shadow-lg shadow-brand-500/30 text-base font-bold transform hover:-translate-y-0.5 transition-all duration-200">
                            Kirim Pengajuan
                        </button>
                    </div>

                </form>
            </div>
            
            <div class="h-16"></div>
        </div>
    </main>

    <script>
        if (window.history.replaceState && window.location.search.includes('msg=')) {
            const url = new URL(window.location);
            url.searchParams.delete('msg');
            window.history.replaceState({path: url.href}, '', url.href);
        }
    </script>
</body>
</html>

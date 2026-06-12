<?php
require_once 'config/database.php';

$peminjaman = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'check') {
    $email = trim($_POST['email']);
    $kode = trim($_POST['kode_booking']);
    
    $stmt = $pdo->prepare("
        SELECT p.*, r.nama_ruangan, d.nama_departemen 
        FROM peminjaman p
        JOIN ruangan r ON p.ruangan_id = r.id
        JOIN departemen d ON p.departemen_id = d.id
        WHERE p.email_peminjam = ? AND p.kode_booking = ?
    ");
    $stmt->execute([$email, $kode]);
    $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$peminjaman) {
        $error = "Data peminjaman tidak ditemukan. Pastikan Alamat Email dan Kode Booking benar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-xl w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Cek Status & Pembatalan</h1>
            <p class="text-gray-500 mt-2">Lacak status atau batalkan jadwal ruangan Anda.</p>
        </div>

        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border border-gray-100">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="check">
                <?php if($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-lg text-sm font-medium border border-red-100"><?= $error ?></div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email Peminjam</label>
                    <input type="email" name="email" required placeholder="Masukkan email yang digunakan saat booking" class="form-input rounded-lg w-full bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Booking</label>
                    <input type="text" name="kode_booking" required placeholder="Contoh: BKG-260610-A1B2" class="form-input rounded-lg w-full bg-gray-50">
                </div>
                <button type="submit" class="w-full btn-primary py-3 shadow-md">Cek Status</button>
            </form>

            <?php if($peminjaman): ?>
                <div class="mt-8 pt-8 border-t border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Hasil Pencarian:</h3>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                        <div class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500 text-sm">Status Saat Ini</span>
                            <?php 
                                $statusClass = [
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    'Approved' => 'bg-green-100 text-green-800',
                                    'Rejected' => 'bg-red-100 text-red-800',
                                    'Canceled' => 'bg-gray-200 text-gray-800'
                                ][$peminjaman['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2.5 py-1 rounded-md text-xs font-bold <?= $statusClass ?>"><?= $peminjaman['status'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Ruangan</span>
                            <span class="font-semibold text-gray-800 text-right"><?= htmlspecialchars($peminjaman['nama_ruangan']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Tanggal Pelaksanaan</span>
                            <span class="font-semibold text-gray-800"><?= date('d M Y', strtotime($peminjaman['tanggal'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 text-sm">Waktu</span>
                            <span class="font-semibold text-gray-800"><?= substr($peminjaman['jam_mulai'],0,5) ?> - <?= substr($peminjaman['jam_selesai'],0,5) ?></span>
                        </div>
                    </div>

                    <?php if(in_array($peminjaman['status'], ['Pending', 'Approved']) && strtotime($peminjaman['tanggal']) >= strtotime('today')): ?>
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-500 mb-3">Tidak jadi menggunakan ruangan ini?</p>
                            <form method="POST" action="cancel.php">
                                <input type="hidden" name="id" value="<?= $peminjaman['id'] ?>">
                                <button type="submit" class="w-full bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-semibold py-2.5 rounded-lg transition-colors">
                                    Batalkan Peminjaman
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="index.php" class="text-brand-600 font-medium hover:text-brand-700 text-sm">Kembali ke Halaman Utama</a>
            </div>
        </div>
    </div>
</body>
</html>

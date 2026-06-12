<?php
require_once 'config/database.php';
require_once 'config/mailer.php';
require_once 'config/functions.php';

$booking = null;
$msg = '';

// 1. Fetch by Token (from Email)
if (isset($_GET['token'])) {
    $stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id WHERE p.cancel_token = ?");
    $stmt->execute([$_GET['token']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} 
// 2. Fetch by POST ID (from cek_status.php)
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id WHERE p.id = ?");
    $stmt->execute([$_POST['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Process Confirmation
if ($booking && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_cancel'])) {
    if (in_array($booking['status'], ['Pending', 'Approved'])) {
        $pdo->prepare("UPDATE peminjaman SET status = 'Canceled' WHERE id = ?")->execute([$booking['id']]);
        
        // Notify Admin
        $admin_email = get_setting($pdo, 'admin_email');
        if ($admin_email) {
            $body = "Peminjaman ruangan <b>{$booking['nama_ruangan']}</b> pada tanggal <b>{$booking['tanggal']}</b> (Kode: {$booking['kode_booking']}) telah dibatalkan secara mandiri oleh pengguna <b>{$booking['nama_peminjam']}</b>.";
            send_email($pdo, $admin_email, "Notifikasi Pembatalan Peminjaman", nl2br($body));
        }
        
        $msg = "success";
        $booking['status'] = 'Canceled';
    } else {
        $msg = "invalid_status";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembatalan Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border border-gray-100 text-center">
            
            <?php if (!$booking): ?>
                <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Link Tidak Valid</h2>
                <p class="text-gray-500 mb-6">Tautan pembatalan tidak ditemukan atau sudah kadaluarsa.</p>
                <a href="index.php" class="btn-primary w-full inline-block py-2.5">Kembali ke Beranda</a>
            
            <?php elseif ($msg == 'success'): ?>
                <div class="w-16 h-16 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Berhasil Dibatalkan</h2>
                <p class="text-gray-500 mb-6">Jadwal Anda untuk <b><?= htmlspecialchars($booking['nama_ruangan']) ?></b> pada <b><?= date('d M Y', strtotime($booking['tanggal'])) ?></b> telah berhasil dibatalkan.</p>
                <a href="index.php" class="btn-primary w-full inline-block py-2.5">Kembali ke Beranda</a>

            <?php elseif ($msg == 'invalid_status' || !in_array($booking['status'], ['Pending', 'Approved'])): ?>
                <div class="w-16 h-16 bg-yellow-100 text-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Tidak Dapat Dibatalkan</h2>
                <p class="text-gray-500 mb-6">Peminjaman ini sudah dalam status <b><?= $booking['status'] ?></b> dan tidak dapat dibatalkan lagi.</p>
                <a href="index.php" class="btn-primary w-full inline-block py-2.5">Kembali ke Beranda</a>

            <?php else: ?>
                <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Pembatalan</h2>
                <p class="text-gray-600 mb-4 text-sm">Apakah Anda yakin ingin membatalkan jadwal peminjaman ruangan ini?</p>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-left mb-6 text-sm">
                    <div class="mb-1"><span class="text-gray-500 font-medium">Ruangan:</span> <span class="font-bold text-gray-800"><?= htmlspecialchars($booking['nama_ruangan']) ?></span></div>
                    <div class="mb-1"><span class="text-gray-500 font-medium">Kode:</span> <span class="font-bold text-gray-800"><?= $booking['kode_booking'] ?></span></div>
                    <div><span class="text-gray-500 font-medium">Waktu:</span> <span class="font-bold text-gray-800"><?= date('d M Y', strtotime($booking['tanggal'])) ?>, <?= substr($booking['jam_mulai'],0,5) ?> - <?= substr($booking['jam_selesai'],0,5) ?></span></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                    <input type="hidden" name="confirm_cancel" value="1">
                    <div class="flex space-x-3">
                        <a href="index.php" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-lg font-semibold hover:bg-gray-200 transition">Tidak Jadi</a>
                        <button type="submit" class="flex-1 bg-red-600 text-white py-2.5 rounded-lg font-semibold hover:bg-red-700 transition shadow-md">Ya, Batalkan</button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';

// Fetch Counters
$count_ruangan = $pdo->query("SELECT COUNT(*) FROM ruangan")->fetchColumn();
$count_fasilitas = $pdo->query("SELECT COUNT(*) FROM fasilitas")->fetchColumn();
$count_pending = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='Pending' AND DATE(created_at) = CURDATE()")->fetchColumn();

// Fetch Latest Pending Requests
$stmt_pending = $pdo->query("SELECT p.*, r.nama_ruangan FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id WHERE p.status = 'Pending' ORDER BY p.created_at DESC LIMIT 5");
$pending_requests = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

// Fetch Approved Today
$stmt_today = $pdo->query("SELECT p.*, r.nama_ruangan, ua.username as nama_petugas FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id LEFT JOIN users_admin ua ON p.petugas_id = ua.id WHERE p.status = 'Approved' AND p.tanggal = CURDATE() ORDER BY p.jam_mulai ASC");
$approved_today = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BookingRoom</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-y-auto bg-gray-50 p-8">
        <div class="max-w-6xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Dashboard Overview</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-brand-50 rounded-bl-full z-0 group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Total Ruangan</p>
                        <h3 class="text-4xl font-extrabold text-gray-800 mt-2"><?= $count_ruangan ?></h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full z-0 group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Total Fasilitas Fisik</p>
                        <h3 class="text-4xl font-extrabold text-gray-800 mt-2"><?= $count_fasilitas ?></h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-yellow-50 rounded-bl-full z-0 group-hover:scale-110 transition-transform"></div>
                    <div class="relative z-10">
                        <p class="text-sm text-gray-500 font-semibold uppercase tracking-wide">Pending Hari Ini</p>
                        <h3 class="text-4xl font-extrabold text-gray-800 mt-2"><?= $count_pending ?></h3>
                    </div>
                </div>
            </div>

            <!-- Jadwal Disetujui Hari Ini -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-10">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Jadwal Disetujui Hari Ini</span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 border-b font-semibold">Ruangan</th>
                                <th class="px-6 py-4 border-b font-semibold">Waktu</th>
                                <th class="px-6 py-4 border-b font-semibold">Peminjam</th>
                                <th class="px-6 py-4 border-b font-semibold">Petugas Penyiapan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($approved_today)): ?>
                                <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Tidak ada jadwal meeting untuk hari ini.</td></tr>
                            <?php else: ?>
                                <?php foreach($approved_today as $today): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800"><?= htmlspecialchars($today['nama_ruangan']) ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-brand-600">
                                        <?= date('H:i', strtotime($today['jam_mulai'])) ?> - <?= date('H:i', strtotime($today['jam_selesai'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <?= htmlspecialchars($today['nama_peminjam']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= $today['nama_petugas'] ? htmlspecialchars($today['nama_petugas']) : '<span class="italic text-gray-400">Belum ada</span>' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <a href="kalender.php" class="block w-full text-center px-4 py-3 bg-white border border-gray-200 rounded-lg text-brand-600 font-semibold hover:bg-gray-100 transition shadow-sm">
                        Lihat Full Kalender
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center space-x-2">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Request Pending Terbaru</span>
                    </h3>
                    <a href="approval.php" class="text-brand-600 hover:text-brand-800 text-sm font-semibold flex items-center space-x-1 group">
                        <span>Lihat Semua</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 border-b font-semibold">Tgl Submit</th>
                                <th class="px-6 py-4 border-b font-semibold">Peminjam</th>
                                <th class="px-6 py-4 border-b font-semibold">Ruangan</th>
                                <th class="px-6 py-4 border-b font-semibold">Jadwal (Waktu)</th>
                                <th class="px-6 py-4 border-b font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($pending_requests)): ?>
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Tidak ada request pending saat ini.</td></tr>
                            <?php else: ?>
                                <?php foreach($pending_requests as $pr): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= date('d M Y', strtotime($pr['created_at'])) ?><br>
                                        <span class="text-xs text-gray-400"><?= date('H:i', strtotime($pr['created_at'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($pr['nama_peminjam']) ?><br>
                                        <a href="mailto:<?= htmlspecialchars($pr['email_peminjam']) ?>" class="text-xs font-normal text-brand-600 hover:underline"><?= htmlspecialchars($pr['email_peminjam']) ?></a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= htmlspecialchars($pr['nama_ruangan']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div class="flex items-center space-x-1">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span><?= date('d M Y', strtotime($pr['tanggal'])) ?></span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 ml-5">
                                            <?= date('H:i', strtotime($pr['jam_mulai'])) ?> - <?= date('H:i', strtotime($pr['jam_selesai'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="approval_detail.php?id=<?= $pr['id'] ?>" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-brand-700 bg-brand-50 border border-brand-200 rounded-lg hover:bg-brand-100 hover:border-brand-300 transition-colors shadow-sm">
                                            Proses
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

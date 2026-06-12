<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
require_once '../config/database.php';
require_once '../config/functions.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan, r.kapasitas, d.nama_departemen FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id LEFT JOIN departemen d ON p.departemen_id = d.id WHERE p.id = ?");
$stmt->execute([$id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die("Data tidak ditemukan.");
}

// Cek Bentrok Ruangan
$ruangan_bentrok = is_ruangan_bentrok($pdo, $req['ruangan_id'], $req['tanggal'], $req['jam_mulai'], $req['jam_selesai'], $id);

// Fasilitas yang diminta
$stmt_jf = $pdo->prepare("SELECT jf.nama_jenis, pjf.jenis_fasilitas_id, pjf.jumlah FROM peminjaman_jenis_fasilitas pjf JOIN jenis_fasilitas jf ON pjf.jenis_fasilitas_id = jf.id WHERE pjf.peminjaman_id = ?");
$stmt_jf->execute([$id]);
$req_fasilitas = $stmt_jf->fetchAll(PDO::FETCH_ASSOC);

// Fasilitas Spesifik yang tersedia (Belum dialokasikan ke booking lain di waktu yang sama)
$available_units = [];
foreach ($req_fasilitas as $jf) {
    $stmt_units = $pdo->prepare("SELECT * FROM fasilitas WHERE jenis_fasilitas_id = ? AND status_kondisi = 'Baik'");
    $stmt_units->execute([$jf['jenis_fasilitas_id']]);
    $all_units = $stmt_units->fetchAll(PDO::FETCH_ASSOC);
    
    $free_units = [];
    foreach ($all_units as $unit) {
        if (!is_fasilitas_bentrok($pdo, $unit['id'], $req['tanggal'], $req['jam_mulai'], $req['jam_selesai'], $id)) {
            $free_units[] = $unit;
        }
    }
    $available_units[$jf['jenis_fasilitas_id']] = $free_units;
}

// Fasilitas yang sudah dialokasikan (jika status Approved)
$allocated_units = [];
if ($req['status'] == 'Approved') {
    $stmt_alloc = $pdo->prepare("SELECT f.nomor_seri, jf.nama_jenis FROM detail_fasilitas_peminjaman df JOIN fasilitas f ON df.fasilitas_id = f.id JOIN jenis_fasilitas jf ON f.jenis_fasilitas_id = jf.id WHERE df.peminjaman_id = ?");
    $stmt_alloc->execute([$id]);
    $allocated_units = $stmt_alloc->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Admins/Petugas for assignment
$stmt_admins = $pdo->query("SELECT id, username FROM users_admin ORDER BY username ASC");
$admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

// Fetch assigned petugas name if approved
$petugas_name = '-';
if ($req['status'] == 'Approved' && !empty($req['petugas_id'])) {
    $stmt_petugas = $pdo->prepare("SELECT username FROM users_admin WHERE id = ?");
    $stmt_petugas->execute([$req['petugas_id']]);
    $petugas_name = $stmt_petugas->fetchColumn() ?: '-';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Approval Booking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto w-full">
            <div class="flex items-center space-x-4 mb-6">
                <a href="approval.php" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-3xl font-bold text-gray-800">Detail Request Booking</h2>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 shadow-sm border border-red-200">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-3 mb-4">Informasi Peminjam</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Nama Lengkap</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($req['nama_peminjam']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Departemen</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($req['nama_departemen'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Email Peminjam</p>
                            <p class="font-medium text-brand-600"><?= htmlspecialchars($req['email_peminjam']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tujuan Kegiatan / Keterangan</p>
                            <p class="font-semibold text-gray-800"><?= nl2br(htmlspecialchars($req['keterangan'] ?? '-')) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status Terkini</p>
                            <p class="font-semibold mt-1">
                                <?php if($req['status'] == 'Pending') echo '<span class="text-yellow-600 bg-yellow-50 px-2 py-1 rounded">Pending</span>'; ?>
                                <?php if($req['status'] == 'Approved') echo '<span class="text-green-600 bg-green-50 px-2 py-1 rounded">Approved</span>'; ?>
                                <?php if($req['status'] == 'Rejected') echo '<span class="text-red-600 bg-red-50 px-2 py-1 rounded">Rejected</span>'; ?>
                                <?php if($req['status'] == 'Canceled') echo '<span class="text-gray-600 bg-gray-200 px-2 py-1 rounded">Canceled</span>'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-3 mb-4">Detail Jadwal & Ruangan</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Ruangan</p>
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($req['nama_ruangan']) ?> <span class="text-gray-400 font-normal">(Kapasitas: <?= $req['kapasitas'] ?>)</span></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Booking</p>
                            <p class="font-semibold text-gray-800"><?= date('d F Y', strtotime($req['tanggal'])) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Waktu (Jam)</p>
                            <p class="font-semibold text-gray-800"><?= date('H:i', strtotime($req['jam_mulai'])) ?> - <?= date('H:i', strtotime($req['jam_selesai'])) ?></p>
                        </div>
                        <?php if($ruangan_bentrok && $req['status'] == 'Pending'): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 p-2 rounded text-sm mt-2 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Perhatian: Jadwal ini bentrok dengan bookingan Approved lainnya.</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if($req['status'] == 'Pending'): ?>
            <form action="proses_approval.php" method="POST" class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="peminjaman_id" value="<?= $id ?>">
                
                <h3 class="text-xl font-bold text-gray-800 mb-6">Alokasi Fasilitas</h3>
                <?php if(empty($req_fasilitas)): ?>
                    <p class="text-gray-500 italic mb-6">Peminjam tidak request fasilitas tambahan.</p>
                <?php else: ?>
                    <div class="space-y-4 mb-8">
                    <?php foreach($req_fasilitas as $jf): ?>
                        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                            <label class="block font-semibold text-gray-800 mb-2">Request: <?= htmlspecialchars($jf['nama_jenis']) ?> (<?= $jf['jumlah'] ?> unit)</label>
                            <?php 
                            $available = $available_units[$jf['jenis_fasilitas_id']];
                            if(count($available) < $jf['jumlah']): ?>
                                <p class="text-red-500 text-sm font-medium">Unit tersedia (<?= count($available) ?>) kurang dari yang direquest (<?= $jf['jumlah'] ?>).</p>
                                <input type="hidden" name="fasilitas_dialokasikan[]" value="">
                            <?php else: ?>
                                <div class="space-y-2">
                                <?php for($i=1; $i<=$jf['jumlah']; $i++): ?>
                                    <select name="fasilitas_dialokasikan[]" required class="form-input rounded-md w-full md:w-1/2 block">
                                        <option value="">-- Pilih Unit Ke-<?= $i ?> --</option>
                                        <?php foreach($available as $unit): ?>
                                            <option value="<?= $unit['id'] ?>"><?= htmlspecialchars($unit['nomor_seri']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="border-t pt-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Tolak Form -->
                    <div class="bg-red-50 p-6 rounded-xl border border-red-100">
                        <h4 class="font-bold text-red-800 mb-3 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Tolak Request</span>
                        </h4>
                        <textarea name="alasan_ditolak" rows="3" class="form-input w-full rounded border-red-200 mb-3" placeholder="Alasan penolakan (wajib jika ditolak)"></textarea>
                        <button type="submit" name="action" value="reject" class="w-full bg-red-600 hover:bg-red-500 text-white font-semibold py-2 px-4 rounded shadow transition-colors">Tolak Booking</button>
                    </div>
                    
                    <!-- Setujui Form -->
                    <div class="bg-green-50 p-6 rounded-xl border border-green-100 flex flex-col justify-center">
                        <h4 class="font-bold text-green-800 mb-3 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Setujui Request</span>
                        </h4>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-green-900 mb-1">Pilih Petugas (Opsional)</label>
                            <select name="petugas_id" class="form-input rounded-md w-full border-green-300 focus:border-green-500 focus:ring-green-500/20">
                                <option value="">-- Tidak ditugaskan --</option>
                                <?php foreach($admins as $adm): ?>
                                    <option value="<?= $adm['id'] ?>"><?= htmlspecialchars($adm['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-green-700 mt-1">Petugas yang bertanggung jawab menyiapkan ruangan.</p>
                        </div>

                        <p class="text-sm text-green-700 mb-4">Pastikan fasilitas yang dialokasikan di atas sudah benar. Jika jadwal ruangan bentrok, sistem akan mencegah persetujuan.</p>
                        <button type="submit" name="action" value="approve" class="w-full bg-green-600 hover:bg-green-500 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition-transform transform hover:-translate-y-0.5 text-lg">Setujui Booking</button>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <!-- Readonly mode for Approved/Rejected -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-3">Review Keputusan</h3>
                    
                    <?php if($req['status'] == 'Approved'): ?>
                        <div class="bg-green-50 text-green-800 p-4 rounded-lg mb-6 font-medium">Request ini telah Disetujui.</div>
                        
                        <div class="mb-5 border-b border-gray-100 pb-4">
                            <p class="text-sm text-gray-500">Petugas Penyiapan</p>
                            <p class="font-bold text-brand-600"><?= htmlspecialchars($petugas_name) ?></p>
                        </div>

                        <h4 class="font-bold text-gray-700 mb-3">Fasilitas yang Dialokasikan:</h4>
                        <?php if(empty($allocated_units)): ?>
                            <p class="text-gray-500 italic">Tidak ada fasilitas.</p>
                        <?php else: ?>
                            <ul class="list-disc list-inside space-y-1">
                                <?php foreach($allocated_units as $au): ?>
                                    <li class="text-gray-700"><?= htmlspecialchars($au['nama_jenis']) ?> - <span class="font-semibold text-gray-900"><?= htmlspecialchars($au['nomor_seri']) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <form action="proses_approval.php" method="POST" class="mt-6 border-t border-gray-100 pt-6">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="peminjaman_id" value="<?= $id ?>">
                            <button type="submit" name="action" value="cancel" class="bg-gray-800 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded shadow transition-colors w-full" onclick="return confirm('Batalkan booking ini secara manual? Status akan berubah jadi Canceled.');">Batalkan Booking Secara Manual</button>
                        </form>
                    <?php endif; ?>

                    <?php if($req['status'] == 'Rejected'): ?>
                        <div class="bg-red-50 text-red-800 p-4 rounded-lg mb-6">
                            <p class="font-bold mb-1">Request ini telah Ditolak.</p>
                            <p class="text-sm">Alasan: <span class="font-medium"><?= htmlspecialchars($req['alasan_ditolak']) ?></span></p>
                        </div>
                    <?php endif; ?>

                    <?php if($req['status'] == 'Canceled'): ?>
                        <div class="bg-gray-100 text-gray-800 p-4 rounded-lg mb-6 font-medium">Request ini telah Dibatalkan (Canceled).</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

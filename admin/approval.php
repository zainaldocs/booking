<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';
require_once '../config/functions.php';

// Filter Logic
$where_clauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $where_clauses[] = "(p.nama_peminjam LIKE ? OR r.nama_ruangan LIKE ?)";
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

if (!empty($_GET['status'])) {
    $where_clauses[] = "p.status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $where_clauses[] = "p.tanggal BETWEEN ? AND ?";
    $params[] = $_GET['start_date'];
    $params[] = $_GET['end_date'];
} elseif (!empty($_GET['start_date'])) {
    $where_clauses[] = "p.tanggal >= ?";
    $params[] = $_GET['start_date'];
} elseif (!empty($_GET['end_date'])) {
    $where_clauses[] = "p.tanggal <= ?";
    $params[] = $_GET['end_date'];
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$count_query = "SELECT COUNT(p.id) FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id LEFT JOIN departemen d ON p.departemen_id = d.id $where_sql";
$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$total_pages = ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

$query = "SELECT p.*, r.nama_ruangan, ua.username as nama_petugas 
          FROM peminjaman p 
          JOIN ruangan r ON p.ruangan_id = r.id 
          LEFT JOIN users_admin ua ON p.petugas_id = ua.id
          $where_sql 
          ORDER BY CASE p.status WHEN 'Pending' THEN 1 WHEN 'Approved' THEN 2 ELSE 3 END, p.created_at DESC 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    if ($status == 'Pending') return '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Pending</span>';
    if ($status == 'Approved') return '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Approved</span>';
    if ($status == 'Canceled') return '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-800">Canceled</span>';
    return '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Rejected</span>';
}

$query_string = $_GET;
unset($query_string['msg']); // remove msg for exports
$export_qs = http_build_query($query_string);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Approval Booking - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-7xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Approval Booking</h2>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 shadow-sm border border-green-200">Status booking berhasil diperbarui dan notifikasi email telah dikirim.</div>
            <?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'success_no_email'): ?>
                <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg mb-6 shadow-sm border border-yellow-200">
                    <p class="font-bold mb-1">Status booking berhasil diperbarui, NAMUN gagal mengirim email notifikasi ke peminjam.</p>
                    <p class="text-sm">Silakan periksa apakah alamat email peminjam valid. Error SMTP: <i><?= htmlspecialchars($_GET['err'] ?? '') ?></i></p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 p-6">
                <form method="GET" action="approval.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Nama peminjam / ruangan..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-brand-500 focus:border-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-brand-500 focus:border-brand-500 text-sm">
                            <option value="">Semua Status</option>
                            <option value="Pending" <?= (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="Approved" <?= (isset($_GET['status']) && $_GET['status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= (isset($_GET['status']) && $_GET['status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                            <option value="Canceled" <?= (isset($_GET['status']) && $_GET['status'] == 'Canceled') ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tgl</label>
                            <input type="date" name="start_date" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tgl</label>
                            <input type="date" name="end_date" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="bg-brand-600 text-white px-4 py-2 rounded-md hover:bg-brand-700 transition text-sm font-medium shadow-sm flex-1">Filter</button>
                        <a href="approval.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition text-sm font-medium text-center">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="text-lg font-bold text-gray-800">Semua Data Peminjaman</h3>
                    <div class="flex space-x-2">
                        <a href="export_pdf.php?<?= $export_qs ?>" target="_blank" class="flex items-center space-x-1 px-3 py-2 bg-red-50 text-red-700 rounded-md hover:bg-red-100 transition text-sm font-medium border border-red-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export PDF</span>
                        </a>
                        <a href="export_excel.php?<?= $export_qs ?>" class="flex items-center space-x-1 px-3 py-2 bg-green-50 text-green-700 rounded-md hover:bg-green-100 transition text-sm font-medium border border-green-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export Excel</span>
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 border-b font-semibold">Tgl Submit</th>
                                <th class="px-6 py-4 border-b font-semibold">Peminjam</th>
                                <th class="px-6 py-4 border-b font-semibold">Ruangan</th>
                                <th class="px-6 py-4 border-b font-semibold">Jadwal (Waktu)</th>
                                <th class="px-6 py-4 border-b font-semibold">Petugas</th>
                                <th class="px-6 py-4 border-b font-semibold text-center">Status</th>
                                <th class="px-6 py-4 border-b font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($requests)): ?>
                                <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 italic">Belum ada request peminjaman.</td></tr>
                            <?php else: ?>
                                <?php foreach($requests as $r): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                                        <span class="text-xs"><?= date('H:i', strtotime($r['created_at'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($r['nama_peminjam']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 font-medium"><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <?= date('d M Y', strtotime($r['tanggal'])) ?><br>
                                        <span class="text-xs text-gray-500"><?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= $r['nama_petugas'] ? htmlspecialchars($r['nama_petugas']) : '<span class="italic text-gray-400">Belum ada</span>' ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?= getStatusBadge($r['status']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center flex justify-center items-center space-x-2">
                                        <a href="approval_detail.php?id=<?= $r['id'] ?>" class="text-brand-600 hover:text-brand-800 font-medium bg-brand-50 px-3 py-1 rounded-md text-sm border border-brand-100">
                                            <?= $r['status'] == 'Pending' ? 'Proses' : 'Detail' ?>
                                        </a>
                                        <?php if(in_array($r['status'], ['Pending', 'Approved'])): ?>
                                        <form action="proses_approval.php" method="POST" class="inline" onsubmit="return confirm('Batalkan booking ini secara manual? Status akan berubah jadi Canceled.');">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="peminjaman_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="text-gray-600 hover:text-red-700 font-medium bg-gray-100 px-3 py-1 rounded-md text-sm border border-gray-200">Cancel</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <div class="text-sm text-gray-500">
                        Menampilkan <?= $offset + 1 ?> sampai <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> data
                    </div>
                    <div class="flex space-x-1">
                        <?php 
                        $qs = $_GET; 
                        unset($qs['msg']);
                        
                        // Prev Button
                        if($page > 1) {
                            $qs['page'] = $page - 1;
                            echo '<a href="approval.php?'.http_build_query($qs).'" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium">Prev</a>';
                        }
                        
                        // Page Numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if($start_page > 1) {
                            $qs['page'] = 1;
                            echo '<a href="approval.php?'.http_build_query($qs).'" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium">1</a>';
                            if($start_page > 2) echo '<span class="px-2 py-1 text-gray-400">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $qs['page'] = $i;
                            $activeClass = $i == $page ? 'bg-brand-600 text-white border-brand-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50';
                            echo '<a href="approval.php?'.http_build_query($qs).'" class="px-3 py-1 rounded border '.$activeClass.' text-sm font-medium">'.$i.'</a>';
                        }
                        
                        if($end_page < $total_pages) {
                            if($end_page < $total_pages - 1) echo '<span class="px-2 py-1 text-gray-400">...</span>';
                            $qs['page'] = $total_pages;
                            echo '<a href="approval.php?'.http_build_query($qs).'" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium">'.$total_pages.'</a>';
                        }
                        
                        // Next Button
                        if($page < $total_pages) {
                            $qs['page'] = $page + 1;
                            echo '<a href="approval.php?'.http_build_query($qs).'" class="px-3 py-1 rounded bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium">Next</a>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>

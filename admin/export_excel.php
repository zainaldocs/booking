<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';

// Filter Logic (sama dengan approval.php)
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

$query = "SELECT p.*, r.nama_ruangan, ua.username as nama_petugas 
          FROM peminjaman p 
          JOIN ruangan r ON p.ruangan_id = r.id 
          LEFT JOIN users_admin ua ON p.petugas_id = ua.id
          $where_sql 
          ORDER BY p.tanggal DESC, p.jam_mulai DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "Laporan_Peminjaman_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center; padding: 10px;">Laporan Peminjaman Ruang Meeting</th>
            </tr>
            <tr>
                <th style="background-color: #f3f4f6;">No</th>
                <th style="background-color: #f3f4f6;">Tgl Submit</th>
                <th style="background-color: #f3f4f6;">Nama Peminjam</th>
                <th style="background-color: #f3f4f6;">Ruangan</th>
                <th style="background-color: #f3f4f6;">Tanggal Pelaksanaan</th>
                <th style="background-color: #f3f4f6;">Waktu</th>
                <th style="background-color: #f3f4f6;">Status</th>
                <th style="background-color: #f3f4f6;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($requests)): ?>
                <tr><td colspan="8" style="text-align: center;">Tidak ada data.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach($requests as $r): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($r['nama_peminjam']) ?></td>
                    <td><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                    <td><?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?></td>
                    <td><?= $r['status'] ?></td>
                    <td><?= $r['nama_petugas'] ? htmlspecialchars($r['nama_petugas']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

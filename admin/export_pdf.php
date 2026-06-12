<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export PDF - Laporan Peminjaman</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #333; line-height: 1.5; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #111; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        table { w-full; border-collapse: collapse; width: 100%; margin-bottom: 20px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 10px 8px; text-align: left; }
        th { background-color: #f9fafb; font-weight: 600; color: #111; }
        tr:nth-child(even) { background-color: #fafafa; }
        .badge-pending { color: #854d0e; font-weight: 600; }
        .badge-approved { color: #166534; font-weight: 600; }
        .badge-rejected { color: #991b1b; font-weight: 600; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 15px; background: #2563eb; color: #fff; border: none; border-radius: 5px; cursor: pointer;">Cetak / Simpan PDF Sekarang</button>
    </div>

    <div class="header">
        <h1>Laporan Peminjaman Ruang Meeting</h1>
        <p>Dicetak pada: <?= date('d M Y H:i') ?></p>
        <?php if(!empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
            <p>Periode: <?= htmlspecialchars($_GET['start_date'] ?? '-') ?> s/d <?= htmlspecialchars($_GET['end_date'] ?? '-') ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Submit</th>
                <th>Peminjam</th>
                <th>Ruangan</th>
                <th>Tanggal Pelaksanaan</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($requests)): ?>
                <tr><td colspan="8" style="text-align: center;">Tidak ada data pada periode/filter ini.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach($requests as $r): 
                    $badgeClass = '';
                    if ($r['status'] == 'Pending') $badgeClass = 'badge-pending';
                    elseif ($r['status'] == 'Approved') $badgeClass = 'badge-approved';
                    else $badgeClass = 'badge-rejected';
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><?= htmlspecialchars($r['nama_peminjam']) ?></td>
                    <td><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                    <td><?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?></td>
                    <td class="<?= $badgeClass ?>"><?= $r['status'] ?></td>
                    <td><?= $r['nama_petugas'] ? htmlspecialchars($r['nama_petugas']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

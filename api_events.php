<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT p.id, p.nama_peminjam, r.nama_ruangan, p.tanggal, p.jam_mulai, p.jam_selesai
    FROM peminjaman p
    JOIN ruangan r ON p.ruangan_id = r.id
    WHERE p.status = 'Approved'
");

$events = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['nama_ruangan'] . ' - ' . $row['nama_peminjam'],
        'start' => $row['tanggal'] . 'T' . $row['jam_mulai'],
        'end' => $row['tanggal'] . 'T' . $row['jam_selesai'],
        'backgroundColor' => '#f97316',
        'borderColor' => '#ea580c'
    ];
}

echo json_encode($events);
?>

<?php
function is_ruangan_bentrok($pdo, $ruangan_id, $tanggal, $jam_mulai, $jam_selesai, $ignore_peminjaman_id = null) {
    $sql = "SELECT id FROM peminjaman 
            WHERE ruangan_id = :ruangan_id 
            AND tanggal = :tanggal 
            AND status = 'Approved' 
            AND (:jam_mulai < jam_selesai AND :jam_selesai > jam_mulai)";
            
    if ($ignore_peminjaman_id) {
        $sql .= " AND id != :ignore_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':ruangan_id', $ruangan_id);
    $stmt->bindParam(':tanggal', $tanggal);
    $stmt->bindParam(':jam_mulai', $jam_mulai);
    $stmt->bindParam(':jam_selesai', $jam_selesai);
    if ($ignore_peminjaman_id) {
        $stmt->bindParam(':ignore_id', $ignore_peminjaman_id);
    }
    
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function is_fasilitas_bentrok($pdo, $fasilitas_id, $tanggal, $jam_mulai, $jam_selesai, $ignore_peminjaman_id = null) {
    $sql = "SELECT p.id FROM peminjaman p
            JOIN detail_fasilitas_peminjaman df ON p.id = df.peminjaman_id
            WHERE df.fasilitas_id = :fasilitas_id
            AND p.tanggal = :tanggal
            AND p.status = 'Approved'
            AND (:jam_mulai < p.jam_selesai AND :jam_selesai > p.jam_mulai)";
            
    if ($ignore_peminjaman_id) {
        $sql .= " AND p.id != :ignore_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':fasilitas_id', $fasilitas_id);
    $stmt->bindParam(':tanggal', $tanggal);
    $stmt->bindParam(':jam_mulai', $jam_mulai);
    $stmt->bindParam(':jam_selesai', $jam_selesai);
    if ($ignore_peminjaman_id) {
        $stmt->bindParam(':ignore_id', $ignore_peminjaman_id);
    }
    
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function get_setting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : null;
}
?>

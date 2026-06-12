<?php

/**
 * Memeriksa apakah sebuah ruangan bentrok pada jadwal tertentu.
 *
 * @param PDO $pdo Objek koneksi PDO
 * @param int $ruangan_id ID ruangan yang akan diperiksa
 * @param string $tanggal Tanggal peminjaman (YYYY-MM-DD)
 * @param string $jam_mulai Jam mulai peminjaman (HH:MM:SS)
 * @param string $jam_selesai Jam selesai peminjaman (HH:MM:SS)
 * @param int|null $ignore_peminjaman_id (Opsional) ID peminjaman yang diabaikan dari pengecekan
 * @return bool True jika bentrok, false jika tersedia
 */
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

/**
 * Memeriksa apakah sebuah unit fasilitas fisik bentrok pada jadwal tertentu.
 *
 * @param PDO $pdo Objek koneksi PDO
 * @param int $fasilitas_id ID detail fasilitas yang akan diperiksa
 * @param string $tanggal Tanggal peminjaman (YYYY-MM-DD)
 * @param string $jam_mulai Jam mulai peminjaman (HH:MM:SS)
 * @param string $jam_selesai Jam selesai peminjaman (HH:MM:SS)
 * @param int|null $ignore_peminjaman_id (Opsional) ID peminjaman yang diabaikan
 * @return bool True jika bentrok, false jika tersedia
 */
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

/**
 * Mengambil nilai pengaturan dari database berdasarkan kunci (key).
 *
 * @param PDO $pdo Objek koneksi PDO
 * @param string $key Kunci pengaturan
 * @return string|null Nilai pengaturan atau null jika tidak ditemukan
 */
function get_setting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
    $stmt->execute(['key' => $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : null;
}

/**
 * Menghasilkan token CSRF dan menyimpannya di session jika belum ada.
 * Harus dipanggil setelah session_start().
 *
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memverifikasi token CSRF dari input form dengan yang ada di session.
 *
 * @param string $token Token yang dikirim via form/POST
 * @return bool True jika valid, false jika tidak valid
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>

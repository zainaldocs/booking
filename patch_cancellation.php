<?php
require_once 'config/database.php';

try {
    // Tambah kolom kode_booking
    $stmt = $pdo->query("SHOW COLUMNS FROM peminjaman LIKE 'kode_booking'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE peminjaman ADD COLUMN kode_booking VARCHAR(50) NULL AFTER id");
        echo "Kolom kode_booking berhasil ditambahkan.<br>";
    }

    // Tambah kolom cancel_token
    $stmt = $pdo->query("SHOW COLUMNS FROM peminjaman LIKE 'cancel_token'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE peminjaman ADD COLUMN cancel_token VARCHAR(100) NULL AFTER kode_booking");
        echo "Kolom cancel_token berhasil ditambahkan.<br>";
    }

    // Perbarui ENUM status
    $pdo->exec("ALTER TABLE peminjaman MODIFY status ENUM('Pending', 'Approved', 'Rejected', 'Canceled') DEFAULT 'Pending'");
    echo "Kolom status berhasil diperbarui dengan opsi 'Canceled'.<br>";

    // Generate kode booking untuk data yang sudah ada (opsional, tapi bagus agar tidak NULL)
    $bookings = $pdo->query("SELECT id FROM peminjaman WHERE kode_booking IS NULL")->fetchAll();
    foreach ($bookings as $b) {
        $kode = 'BKG-OLD-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(16));
        $pdo->prepare("UPDATE peminjaman SET kode_booking = ?, cancel_token = ? WHERE id = ?")->execute([$kode, $token, $b['id']]);
    }
    if (count($bookings) > 0) {
        echo count($bookings) . " data lama berhasil diberikan kode booking.<br>";
    }

    echo "Selesai.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

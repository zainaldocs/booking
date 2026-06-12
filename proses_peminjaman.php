<?php
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama_peminjam'];
    $email = $_POST['email_peminjam'];
    $departemen_id = $_POST['departemen_id'];
    $keterangan = $_POST['keterangan'];
    $ruangan_id = $_POST['ruangan_id'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $fasilitas_jumlah = isset($_POST['fasilitas_jumlah']) ? $_POST['fasilitas_jumlah'] : [];

    // Validasi waktu operasional
    if ($jam_mulai < '08:00' || $jam_selesai > '17:00' || $jam_mulai >= $jam_selesai) {
        header("Location: form_booking.php?msg=time_invalid");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $kode_booking = 'BKG-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
        $cancel_token = bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("INSERT INTO peminjaman (kode_booking, cancel_token, departemen_id, nama_peminjam, email_peminjam, keterangan, ruangan_id, tanggal, jam_mulai, jam_selesai, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$kode_booking, $cancel_token, $departemen_id, $nama, $email, $keterangan, $ruangan_id, $tanggal, $jam_mulai, $jam_selesai]);
        $peminjaman_id = $pdo->lastInsertId();

        if (!empty($fasilitas_jumlah)) {
            $stmt_f = $pdo->prepare("INSERT INTO peminjaman_jenis_fasilitas (peminjaman_id, jenis_fasilitas_id, jumlah) VALUES (?, ?, ?)");
            foreach ($fasilitas_jumlah as $fid => $jumlah) {
                if ($jumlah > 0) {
                    $stmt_f->execute([$peminjaman_id, $fid, $jumlah]);
                }
            }
        }

        $pdo->commit();

        $stmt_r = $pdo->prepare("SELECT nama_ruangan FROM ruangan WHERE id = ?");
        $stmt_r->execute([$ruangan_id]);
        $ruangan_name = $stmt_r->fetchColumn();

        $stmt_d = $pdo->prepare("SELECT nama_departemen FROM departemen WHERE id = ?");
        $stmt_d->execute([$departemen_id]);
        $departemen_name = $stmt_d->fetchColumn();

        $waktu = "$jam_mulai - $jam_selesai";
        $keterangan_safe = nl2br(htmlspecialchars($keterangan));

        // Send Email to Admin
        $admin_email = get_setting($pdo, 'admin_email');
        if ($admin_email) {
            $tpl_admin = get_setting($pdo, 'tpl_email_admin') ?: "Ada request peminjaman baru dari <b>[nama]</b>.\nRuangan: [ruangan]\nTanggal: [tanggal]\nWaktu: [waktu]\nSilakan login ke Admin Panel untuk melakukan Approval.";
            $body_admin = str_replace(['[nama]', '[ruangan]', '[tanggal]', '[waktu]', '[departemen]', '[keterangan]'], [$nama, $ruangan_name, $tanggal, $waktu, $departemen_name, $keterangan_safe], nl2br($tpl_admin));
            $subject_admin = "Request Booking Ruangan Baru - Pending";
            send_email($pdo, $admin_email, $subject_admin, $body_admin);
        }

        // Send Email to User (Tanda Terima)
        $tpl_pending = get_setting($pdo, 'tpl_email_pending') ?: "Halo <b>[nama]</b>,\n\nKami telah menerima request peminjaman ruangan Anda. Saat ini statusnya adalah <b>Pending</b>.\n\nKode Booking: <b>[kode_booking]</b>\nRuangan: [ruangan]\nTanggal: [tanggal]\nWaktu: [waktu]\n\nUntuk melihat status atau membatalkan peminjaman, silakan klik tautan berikut:\n<a href='[cancel_link]'>[cancel_link]</a>\n\nKami akan menginformasikan kembali melalui email setelah admin memproses request Anda. Terima kasih.";
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $cancel_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/booking/cancel.php?token=" . $cancel_token;
        
        $body_user = str_replace(['[nama]', '[kode_booking]', '[ruangan]', '[tanggal]', '[waktu]', '[departemen]', '[keterangan]', '[cancel_link]'], [$nama, $kode_booking, $ruangan_name, $tanggal, $waktu, $departemen_name, $keterangan_safe, $cancel_link], nl2br($tpl_pending));
        $subject_user = "Menunggu Persetujuan: Request Peminjaman Ruangan";
        send_email($pdo, $email, $subject_user, $body_user);

        header("Location: index.php?msg=success");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: form_booking.php?msg=error");
        exit;
    }
}
?>

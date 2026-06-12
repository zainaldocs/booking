<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $peminjaman_id = $_POST['peminjaman_id'];
    $action = $_POST['action'];
    $alasan_ditolak = $_POST['alasan_ditolak'] ?? '';
    $fasilitas_dialokasikan = $_POST['fasilitas_dialokasikan'] ?? [];
    $stmt = $pdo->prepare("SELECT p.*, r.nama_ruangan, d.nama_departemen FROM peminjaman p JOIN ruangan r ON p.ruangan_id = r.id LEFT JOIN departemen d ON p.departemen_id = d.id WHERE p.id = ?");
    $stmt->execute([$peminjaman_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        header("Location: approval.php"); exit;
    }
    
    if ($action == 'cancel') {
        if (!in_array($req['status'], ['Pending', 'Approved'])) {
            header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Hanya booking Pending atau Approved yang bisa dibatalkan."));
            exit;
        }
    } else {
        if ($req['status'] != 'Pending') {
            header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Request tidak valid atau sudah diproses."));
            exit;
        }
    }

    if ($action == 'reject') {
        if (empty(trim($alasan_ditolak))) {
            header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Alasan penolakan wajib diisi."));
            exit;
        }

        $stmt_upd = $pdo->prepare("UPDATE peminjaman SET status = 'Rejected', alasan_ditolak = ? WHERE id = ?");
        $stmt_upd->execute([$alasan_ditolak, $peminjaman_id]);

        $waktu = $req['jam_mulai'] . ' - ' . $req['jam_selesai'];
        $tanggal_fmt = date('d F Y', strtotime($req['tanggal']));
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $cancel_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/booking/cancel.php?token=" . $req['cancel_token'];
        
        $tpl_reject = get_setting($pdo, 'tpl_email_reject') ?: "Halo <b>[nama]</b>,\n\nMohon maaf, request peminjaman ruangan <b>[ruangan]</b> pada tanggal [tanggal] (Jam [waktu]) telah <b>Ditolak</b>.\n\nAlasan Penolakan:\n<i>[alasan]</i>";
        $body = str_replace(
            ['[nama]', '[kode_booking]', '[ruangan]', '[tanggal]', '[waktu]', '[alasan]', '[departemen]', '[keterangan]', '[cancel_link]'], 
            [$req['nama_peminjam'], $req['kode_booking'], $req['nama_ruangan'], $tanggal_fmt, $waktu, htmlspecialchars($alasan_ditolak), $req['nama_departemen'], nl2br(htmlspecialchars($req['keterangan'])), $cancel_link], 
            nl2br($tpl_reject)
        );
        $subject = "Request Peminjaman Ruangan Ditolak";

        $email_res = send_email($pdo, $req['email_peminjam'], $subject, $body);

        if ($email_res !== true) {
            header("Location: approval.php?msg=success_no_email&err=" . urlencode($email_res));
        } else {
            header("Location: approval.php?msg=success");
        }
        exit;

    } elseif ($action == 'approve') {
        // Validasi Anti-Bentrok Ruangan
        if (is_ruangan_bentrok($pdo, $req['ruangan_id'], $req['tanggal'], $req['jam_mulai'], $req['jam_selesai'])) {
            header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Gagal Approve! Ruangan ini sudah dibooking pada jam tersebut."));
            exit;
        }

        // Validasi ketersediaan dan bentrok unit fasilitas spesifik
        foreach ($fasilitas_dialokasikan as $fid) {
            if (empty($fid)) {
                header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Gagal Approve! Ada fasilitas yang tidak dialokasikan unitnya."));
                exit;
            }
            if (is_fasilitas_bentrok($pdo, $fid, $req['tanggal'], $req['jam_mulai'], $req['jam_selesai'])) {
                header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Gagal Approve! Unit fasilitas fisik yang dipilih ternyata bentrok/sedang dipinjam di jadwal lain."));
                exit;
            }
        }

        try {
            $pdo->beginTransaction();

            $petugas_id = !empty($_POST['petugas_id']) ? $_POST['petugas_id'] : null;
            $stmt_upd = $pdo->prepare("UPDATE peminjaman SET status = 'Approved', petugas_id = ? WHERE id = ?");
            $stmt_upd->execute([$petugas_id, $peminjaman_id]);

            $fasilitas_dialokasikan = isset($_POST['fasilitas_dialokasikan']) ? $_POST['fasilitas_dialokasikan'] : [];
            // filter empty dan unique agar unit yang sama tidak masuk 2 kali di booking yg sama
            $fasilitas_dialokasikan = array_unique(array_filter($fasilitas_dialokasikan));

            if (!empty($fasilitas_dialokasikan)) {
                $stmt_df = $pdo->prepare("INSERT INTO detail_fasilitas_peminjaman (peminjaman_id, fasilitas_id) VALUES (?, ?)");
                foreach ($fasilitas_dialokasikan as $fid) {
                    $stmt_df->execute([$peminjaman_id, $fid]);
                }
            }

            $pdo->commit();

            $waktu = $req['jam_mulai'] . ' - ' . $req['jam_selesai'];
            $tanggal_fmt = date('d F Y', strtotime($req['tanggal']));
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $cancel_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/booking/cancel.php?token=" . $req['cancel_token'];
            
            $tpl_approve = get_setting($pdo, 'tpl_email_approve') ?: "Halo <b>[nama]</b>,\n\nSelamat! Request peminjaman ruangan <b>[ruangan]</b> pada tanggal [tanggal] (Jam [waktu]) telah <b>Disetujui</b>.\n\nFasilitas akan disiapkan sesuai ketersediaan. Terima kasih.";
            $body = str_replace(
                ['[nama]', '[kode_booking]', '[ruangan]', '[tanggal]', '[waktu]', '[departemen]', '[keterangan]', '[cancel_link]'], 
                [$req['nama_peminjam'], $req['kode_booking'], $req['nama_ruangan'], $tanggal_fmt, $waktu, $req['nama_departemen'], nl2br(htmlspecialchars($req['keterangan'])), $cancel_link], 
                nl2br($tpl_approve)
            );
            $subject = "Request Peminjaman Ruangan Disetujui";
            
            $email_res = send_email($pdo, $req['email_peminjam'], $subject, $body);

            if ($email_res !== true) {
                header("Location: approval.php?msg=success_no_email&err=" . urlencode($email_res));
            } else {
                header("Location: approval.php?msg=success");
            }
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: approval_detail.php?id=$peminjaman_id&error=" . urlencode("Terjadi kesalahan sistem: " . $e->getMessage()));
            exit;
        }
    } elseif ($action == 'cancel') {
        $stmt_upd = $pdo->prepare("UPDATE peminjaman SET status = 'Canceled' WHERE id = ?");
        $stmt_upd->execute([$peminjaman_id]);

        $waktu = $req['jam_mulai'] . ' - ' . $req['jam_selesai'];
        $tanggal_fmt = date('d F Y', strtotime($req['tanggal']));
        $body = "Halo <b>{$req['nama_peminjam']}</b>,\n\nPeminjaman ruangan <b>{$req['nama_ruangan']}</b> pada tanggal {$tanggal_fmt} (Jam {$waktu}) telah <b>Dibatalkan</b>.\n\nTerima kasih.";
        $subject = "Pemberitahuan: Booking Ruangan Dibatalkan";
        
        $email_res = send_email($pdo, $req['email_peminjam'], $subject, nl2br($body));
        
        if ($email_res !== true) {
            header("Location: approval.php?msg=success_no_email&err=" . urlencode($email_res));
        } else {
            header("Location: approval.php?msg=success");
        }
        exit;
    }
}
?>

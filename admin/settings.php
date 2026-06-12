<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
if ($_SESSION['admin_role'] !== 'superadmin') exit(header("Location: index.php"));
require_once '../config/database.php';
require_once '../config/mailer.php';
require_once '../config/functions.php';

// Proses Update Pengaturan atau Test Mail
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Token keamanan tidak valid. Silakan kembali dan muat ulang halaman.");
    }

    if (isset($_POST['action']) && $_POST['action'] == 'test_mail') {
        $admin_email = get_setting($pdo, 'admin_email');
        $subject = "Test Koneksi SMTP Sistem Booking";
        $body = "Halo,<br><br>Ini adalah email uji coba dari sistem. Jika Anda menerima email ini, berarti konfigurasi SMTP Anda sudah berjalan dengan baik!";
        
        $result = send_email($pdo, $admin_email, $subject, $body);
        if ($result === true) {
            $message = "Email uji coba berhasil dikirim ke: " . htmlspecialchars($admin_email);
            $message_type = "success";
        } else {
            $message = "Gagal mengirim email uji coba. Error: " . htmlspecialchars($result);
            $message_type = "error";
        }
    } else {
        $settings = [
            'smtp_host' => $_POST['smtp_host'],
            'smtp_port' => $_POST['smtp_port'],
            'smtp_secure' => $_POST['smtp_secure'],
            'smtp_user' => $_POST['smtp_user'],
            'smtp_pass' => $_POST['smtp_pass'],
            'admin_email' => $_POST['admin_email'],
            'tpl_email_admin' => $_POST['tpl_email_admin'],
            'tpl_email_pending' => $_POST['tpl_email_pending'],
            'tpl_email_approve' => $_POST['tpl_email_approve'],
            'tpl_email_reject' => $_POST['tpl_email_reject']
        ];

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach ($settings as $key => $value) {
            if ($value !== '') { 
                $stmt->execute([$key, $value, $value]);
            }
        }
        $message = "Pengaturan berhasil disimpan.";
        $message_type = "success";
    }
}

// Ambil Data Pengaturan
$settings_data = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings_data[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan SMTP & Email - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-4xl w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Pengaturan Email & SMTP</h2>
            
            <?php if(isset($message)): ?>
                <div class="p-4 rounded-lg mb-6 shadow-sm border <?= $message_type == 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="border-b border-gray-100 pb-4 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Email Penerima Notifikasi</h3>
                        <p class="text-sm text-gray-500">Email ini akan menerima pemberitahuan setiap ada request booking baru dari user. Anda dapat memasukkan lebih dari satu email, pisahkan dengan koma (<code>,</code>) atau titik koma (<code>;</code>).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Admin</label>
                        <input type="text" name="admin_email" value="<?= htmlspecialchars($settings_data['admin_email'] ?? '') ?>" class="form-input rounded-lg" required placeholder="admin1@email.com, admin2@email.com">
                    </div>

                    <div class="border-b border-gray-100 pb-4 mb-4 mt-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Konfigurasi SMTP Pengirim</h3>
                        <p class="text-sm text-gray-500">Kredensial untuk mengirim email (misal via Gmail SMTP).</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">SMTP Host</label>
                            <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings_data['smtp_host'] ?? '') ?>" class="form-input rounded-lg" placeholder="smtp.gmail.com" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">SMTP Port</label>
                            <input type="text" name="smtp_port" value="<?= htmlspecialchars($settings_data['smtp_port'] ?? '') ?>" class="form-input rounded-lg" placeholder="587" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mode Enkripsi</label>
                            <select name="smtp_secure" class="form-input rounded-lg">
                                <option value="tls" <?= ($settings_data['smtp_secure'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS (STARTTLS)</option>
                                <option value="ssl" <?= ($settings_data['smtp_secure'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL (SMTPS)</option>
                                <option value="none" <?= ($settings_data['smtp_secure'] ?? '') == 'none' ? 'selected' : '' ?>>None (Tidak Enkripsi)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">SMTP Username</label>
                            <input type="email" name="smtp_user" value="<?= htmlspecialchars($settings_data['smtp_user'] ?? '') ?>" class="form-input rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">SMTP Password / App Password</label>
                            <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings_data['smtp_pass'] ?? '') ?>" class="form-input rounded-lg" required>
                        </div>
                    </div>

                    <div class="border-b border-gray-100 pb-4 mb-4 mt-12">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">Kustomisasi Pesan Email</h3>
                        <p class="text-sm text-gray-500">Anda dapat menggunakan variabel otomatis berikut (termasuk kurung sikunya): <br>
                        <code>[nama]</code>, <code>[departemen]</code>, <code>[ruangan]</code>, <code>[tanggal]</code>, <code>[waktu]</code>, <code>[keterangan]</code>, <code>[alasan]</code> (khusus penolakan), <code>[kode_booking]</code> (kode pelacakan), <code>[cancel_link]</code> (tautan pembatalan), <code>[petugas]</code> (khusus disetujui).</p>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan ke Admin (Saat ada request baru)</label>
                            <textarea name="tpl_email_admin" class="form-input rounded-lg h-28" required><?= htmlspecialchars($settings_data['tpl_email_admin'] ?? "Ada request peminjaman baru dari <b>[nama]</b>.\nRuangan: [ruangan]\nTanggal: [tanggal]\nWaktu: [waktu]\nSilakan login ke Admin Panel untuk melakukan Approval.") ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan ke User (Tanda Terima / Pending)</label>
                            <textarea name="tpl_email_pending" class="form-input rounded-lg h-32" required><?= htmlspecialchars($settings_data['tpl_email_pending'] ?? "Halo <b>[nama]</b>,\n\nKami telah menerima request peminjaman ruangan Anda. Saat ini statusnya adalah <b>Pending</b>.\n\nRuangan: [ruangan]\nTanggal: [tanggal]\nWaktu: [waktu]\n\nKami akan menginformasikan kembali melalui email setelah admin memproses request Anda. Terima kasih.") ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan ke User (Disetujui)</label>
                            <textarea name="tpl_email_approve" class="form-input rounded-lg h-32" required><?= htmlspecialchars($settings_data['tpl_email_approve'] ?? "Halo <b>[nama]</b>,\n\nSelamat! Request peminjaman ruangan <b>[ruangan]</b> pada tanggal [tanggal] (Jam [waktu]) telah <b>Disetujui</b>.\n\nFasilitas akan disiapkan sesuai ketersediaan. Terima kasih.") ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pesan ke User (Ditolak)</label>
                            <textarea name="tpl_email_reject" class="form-input rounded-lg h-32" required><?= htmlspecialchars($settings_data['tpl_email_reject'] ?? "Halo <b>[nama]</b>,\n\nMohon maaf, request peminjaman ruangan <b>[ruangan]</b> pada tanggal [tanggal] (Jam [waktu]) telah <b>Ditolak</b>.\n\nAlasan Penolakan:\n<i>[alasan]</i>") ?></textarea>
                        </div>
                    </div>

                    <div class="pt-8 flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-4">
                        <button type="submit" name="action" value="save" class="w-full sm:w-auto btn-primary px-8 shadow-md">Simpan Pengaturan</button>
                        <button type="submit" name="action" value="test_mail" class="w-full sm:w-auto btn-secondary px-6" formnovalidate>Kirim Email Uji Coba</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>

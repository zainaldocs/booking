<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
if ($_SESSION['admin_role'] !== 'superadmin') {
    header("Location: index.php");
    exit;
}
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Token keamanan tidak valid. Silakan kembali dan muat ulang halaman.");
    }

    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO jenis_fasilitas (nama_jenis) VALUES (?)");
        $stmt->execute([$_POST['nama']]);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM jenis_fasilitas WHERE id = ?");
        $stmt->execute([(int) $_POST['id']]);
    }
    header("Location: jenis_fasilitas.php");
    exit;
}

$jenis = $pdo->query("SELECT * FROM jenis_fasilitas ORDER BY nama_jenis ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenis Fasilitas - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-5xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Kelola Jenis Fasilitas</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Jenis</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Jenis (misal: Proyektor)</label>
                            <input type="text" name="nama" required class="form-input rounded-lg">
                        </div>
                        <button type="submit" class="w-full btn-primary py-2.5">Simpan</button>
                    </form>
                </div>
                <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100"><h3 class="text-lg font-bold text-gray-800">Daftar Jenis Fasilitas</h3></div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase"><th class="px-6 py-4 border-b font-semibold">Nama Jenis</th><th class="px-6 py-4 border-b font-semibold text-center">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($jenis as $j): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($j['nama_jenis']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST" onsubmit="return confirm('Hapus jenis ini akan menghapus semua unit fisiknya juga. Yakin?');">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1 rounded text-sm font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

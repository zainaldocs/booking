<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
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
        $stmt = $pdo->prepare("INSERT INTO ruangan (nama_ruangan, kapasitas, keterangan) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['nama'], $_POST['kapasitas'], $_POST['keterangan']]);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM ruangan WHERE id = ?");
        $stmt->execute([(int) $_POST['id']]);
    }
    header("Location: ruangan.php");
    exit;
}

$ruangans = $pdo->query("SELECT * FROM ruangan ORDER BY nama_ruangan ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Ruangan - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Kelola Ruangan</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Form Tambah -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Ruangan Baru</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Ruangan</label>
                                <input type="text" name="nama" required class="form-input rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Kapasitas (Orang)</label>
                                <input type="number" name="kapasitas" required class="form-input rounded-lg" min="1">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Keterangan Tambahan</label>
                                <textarea name="keterangan" rows="3" class="form-input rounded-lg"></textarea>
                            </div>
                            <button type="submit" class="w-full btn-primary py-2.5 shadow-md">Simpan Ruangan</button>
                        </form>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="md:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-gray-800">Daftar Ruangan</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                        <th class="px-6 py-4 border-b font-semibold">Nama Ruangan</th>
                                        <th class="px-6 py-4 border-b font-semibold text-center">Kapasitas</th>
                                        <th class="px-6 py-4 border-b font-semibold">Keterangan</th>
                                        <th class="px-6 py-4 border-b font-semibold text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if(empty($ruangans)): ?>
                                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Belum ada data ruangan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($ruangans as $r): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($r['nama_ruangan']) ?></td>
                                            <td class="px-6 py-4 text-center text-gray-600"><?= $r['kapasitas'] ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-500"><?= htmlspecialchars($r['keterangan']) ?></td>
                                            <td class="px-6 py-4 text-center">
                                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus ruangan ini? Semua data booking terkait juga akan terhapus.');">
                                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1 rounded text-sm font-medium">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

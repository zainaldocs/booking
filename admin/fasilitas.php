<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
if ($_SESSION['admin_role'] !== 'superadmin') exit(header("Location: index.php"));
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $stmt = $pdo->prepare("INSERT INTO fasilitas (jenis_fasilitas_id, nomor_seri, status_kondisi) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['jenis_id'], $_POST['nomor_seri'], $_POST['status_kondisi']]);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM fasilitas WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    header("Location: fasilitas.php");
    exit;
}

$jenis = $pdo->query("SELECT * FROM jenis_fasilitas")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT f.*, jf.nama_jenis FROM fasilitas f JOIN jenis_fasilitas jf ON f.jenis_fasilitas_id = jf.id ORDER BY jf.nama_jenis ASC, f.nomor_seri ASC");
$fasilitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Fasilitas - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Kelola Unit Fasilitas Fisik</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Unit Baru</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pilih Jenis Fasilitas</label>
                            <select name="jenis_id" required class="form-input rounded-lg">
                                <?php foreach($jenis as $j): ?>
                                    <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['nama_jenis']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor Seri / Label / Nama Unit</label>
                            <input type="text" name="nomor_seri" required class="form-input rounded-lg" placeholder="Misal: Proyektor 01, Laptop Asus X550">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kondisi</label>
                            <select name="status_kondisi" class="form-input rounded-lg">
                                <option value="Baik">Baik</option>
                                <option value="Rusak">Rusak</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full btn-primary py-2.5">Simpan Unit</button>
                    </form>
                </div>
                <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100"><h3 class="text-lg font-bold text-gray-800">Daftar Unit Tersedia</h3></div>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase"><th class="px-6 py-4 border-b font-semibold">Jenis</th><th class="px-6 py-4 border-b font-semibold">Nomor Seri / Unit</th><th class="px-6 py-4 border-b font-semibold">Kondisi</th><th class="px-6 py-4 border-b font-semibold text-center">Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($fasilitas as $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($f['nama_jenis']) ?></td>
                                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($f['nomor_seri']) ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if($f['status_kondisi'] == 'Baik') echo '<span class="text-green-600 font-medium">Baik</span>'; else echo '<span class="text-red-600 font-medium">Rusak</span>'; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST" onsubmit="return confirm('Hapus unit fasilitas ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
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

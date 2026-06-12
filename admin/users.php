<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) exit(header("Location: login.php"));
if ($_SESSION['admin_role'] !== 'superadmin') exit(header("Location: index.php"));
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'] ?? 'admin';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users_admin (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'] ?? 'admin';
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users_admin SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $password, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users_admin SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $id]);
        }
        
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        // Prevent deleting the very last admin
        $count = $pdo->query("SELECT COUNT(*) FROM users_admin")->fetchColumn();
        if ($count > 1) {
            $stmt = $pdo->prepare("DELETE FROM users_admin WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    }
    header("Location: users.php");
    exit;
}

$admins = $pdo->query("SELECT * FROM users_admin ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

// For editing mode
$edit_admin = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users_admin WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_admin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Admin - Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto w-full">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Manajemen Admin & Petugas</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Add / Edit -->
                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-3 mb-4">
                        <?= $edit_admin ? 'Edit Data Admin' : 'Tambah Admin Baru' ?>
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="<?= $edit_admin ? 'edit' : 'add' ?>">
                        <?php if($edit_admin): ?>
                            <input type="hidden" name="id" value="<?= $edit_admin['id'] ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username / Nama Lengkap</label>
                            <input type="text" name="username" required value="<?= $edit_admin ? htmlspecialchars($edit_admin['username']) : '' ?>" class="form-input rounded-lg w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                            <input type="email" name="email" required value="<?= $edit_admin ? htmlspecialchars($edit_admin['email']) : '' ?>" class="form-input rounded-lg w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Peran (Role)</label>
                            <select name="role" class="form-input rounded-lg w-full">
                                <option value="admin" <?= ($edit_admin && $edit_admin['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="superadmin" <?= ($edit_admin && $edit_admin['role'] == 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" <?= $edit_admin ? '' : 'required' ?> class="form-input rounded-lg w-full" placeholder="<?= $edit_admin ? 'Kosongkan jika tidak diubah' : 'Password login' ?>">
                        </div>
                        
                        <div class="pt-2 flex flex-col space-y-3">
                            <button type="submit" class="w-full btn-primary py-2.5"><?= $edit_admin ? 'Simpan Perubahan' : 'Tambah Admin' ?></button>
                            <?php if($edit_admin): ?>
                                <a href="users.php" class="w-full text-center text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">Batal Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Table Admin -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800">Daftar Admin / Petugas</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                    <th class="px-6 py-4 border-b font-semibold">Nama Petugas</th>
                                    <th class="px-6 py-4 border-b font-semibold">Email</th>
                                    <th class="px-6 py-4 border-b font-semibold">Peran</th>
                                    <th class="px-6 py-4 border-b font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($admins as $adm): ?>
                                <tr class="hover:bg-gray-50 transition-colors <?= ($edit_admin && $edit_admin['id'] == $adm['id']) ? 'bg-brand-50' : '' ?>">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800"><?= htmlspecialchars($adm['username']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($adm['email']) ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if(isset($adm['role']) && $adm['role'] == 'superadmin'): ?>
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-md text-xs font-semibold">Superadmin</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-md text-xs font-semibold">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <a href="users.php?edit=<?= $adm['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Edit</a>
                                        
                                        <?php if(count($admins) > 1): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus akun admin ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $adm['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-semibold ml-3">Hapus</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm ml-3 cursor-not-allowed" title="Minimal harus ada 1 admin">Hapus</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
</body>
</html>

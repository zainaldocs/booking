<?php
require_once 'config/database.php';
try {
    $pdo->exec("ALTER TABLE peminjaman ADD COLUMN petugas_id INT NULL AFTER departemen_id");
    $pdo->exec("ALTER TABLE peminjaman ADD CONSTRAINT fk_petugas FOREIGN KEY (petugas_id) REFERENCES users_admin(id) ON DELETE SET NULL");
    echo "Success patching database";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

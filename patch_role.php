<?php
require_once 'config/database.php';

try {
    // Update existing users to superadmin
    $pdo->exec("UPDATE users_admin SET role = 'superadmin'");
    echo "Existing users updated to superadmin.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "--- " . $row['setting_key'] . " ---\n";
    echo htmlspecialchars($row['setting_value']) . "\n\n";
}
?>

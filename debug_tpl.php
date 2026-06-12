<?php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tpl_email_pending'");
$tpl = $stmt->fetchColumn();

echo "TEMPLATE IN DB:\n";
echo htmlspecialchars($tpl);
echo "\n\nRAW HEX:\n";
echo bin2hex($tpl);
?>

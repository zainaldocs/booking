<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'tpl_email_pending'");
$tpl = $stmt->fetchColumn();
echo "TEMPLATE_IN_DB: " . $tpl;
?>

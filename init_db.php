<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents('booking.sql');
    $pdo->exec($sql);
    
    echo "Database created successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

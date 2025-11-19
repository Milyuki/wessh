<?php
$dsn = 'mysql:host=localhost;dbname=wessh_db';
$username = 'root';  // Default XAMPP username
$password = '';  // Default XAMPP password
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
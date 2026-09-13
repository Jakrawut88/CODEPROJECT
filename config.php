<?php
session_start();

$host = 'localhost';
$db   = 'bangkok_stay';
$user = 'root';
$pass = ''; // ปล่อยว่าง
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     header('Content-Type: application/json');
     echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
     exit;
}
?>
<?php
// API endpoints that only query data do not need a session. The session-status
// action is the exception because index.html uses it to render its account menu.
$isSessionApiRequest = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api.php'
    && ($_GET['action'] ?? '') === 'get_session';
if ((basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'api.php' || $isSessionApiRequest)
    && session_status() === PHP_SESSION_NONE) {
    // Store sessions inside the project so PHP works consistently with Five
    // Server as well as Apache/XAMPP.
    $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0700, true);
    }
    session_save_path($sessionPath);
    session_start();
}

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

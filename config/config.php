<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "projet_profit";

define('SESSION_LIFETIME', 1800);

define('SMTP_HOST', 'ssl0.ovh.net');
define('SMTP_PORT', 465);
define('SMTP_USER', 'support@prof-it.fr');
define('SMTP_PASS', 'Support2025!');
define('SMTP_FROM_EMAIL', 'support@prof-it.fr');
define('SMTP_FROM_NAME', 'Prof-IT Notification');

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Connexion échouée: " . $e->getMessage());
}

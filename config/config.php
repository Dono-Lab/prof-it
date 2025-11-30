<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "projet_profit";

// Durée de vie de la session en secondes (30 minutes)
define('SESSION_LIFETIME', 1800);

// Configuration SMTP (OVH)
define('SMTP_HOST', 'ssl0.ovh.net'); // Serveur OVH standard
define('SMTP_PORT', 465); // Port SSL
define('SMTP_USER', 'votre-email@votre-domaine.com'); // VOTRE ADRESSE EMAIL COMPLETE
define('SMTP_PASS', 'votre-mot-de-passe-mail'); // LE MOT DE PASSE DE CETTE ADRESSE EMAIL
define('SMTP_FROM_EMAIL', 'no-reply@prof-it.com'); // Doit souvent être identique à SMTP_USER chez OVH
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

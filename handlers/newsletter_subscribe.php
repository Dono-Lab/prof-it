<?php
require_once '../config/config.php';

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$prenom = trim($_POST['prenom'] ?? '');

if (!$email) {
    header("Location: ../public/home.php?error=email_invalide");
    exit();
}

$token = bin2hex(random_bytes(32));

$stmt = $conn->prepare("INSERT INTO newsletter (email, prenom, token_desinscription) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE actif = 1, date_inscription = CURRENT_TIMESTAMP");

if ($stmt->execute([$email, $prenom, $token])) {
    header("Location: ../public/home.php?success=newsletter");
} else {
    header("Location: ../public/home.php?error=newsletter");
}
exit();

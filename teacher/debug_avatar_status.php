<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';

safe_session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    die("Pas de session utilisateur active. Connectez-vous d'abord.");
}

echo "User ID: " . $userId . "\n";

$stmt = $conn->prepare("SELECT role, photo_url FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($user);

if ($user && !empty($user['photo_url'])) {
    $filePath = __DIR__ . '/../' . $user['photo_url'];
    echo "Chemin complet attendu: " . realpath($filePath) . "\n";
    if (file_exists($filePath)) {
        echo "✅ Le fichier existe sur le disque.\n";
        echo "Taille: " . filesize($filePath) . " octets\n";
    } else {
        echo "❌ Le fichier N'EXISTE PAS sur le disque.\n";
        echo "Chemin testé: " . $filePath . "\n";
    }
} else {
    echo "Pas de photo_url en base.\n";
}
?>

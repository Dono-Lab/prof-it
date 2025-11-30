<?php
require_once __DIR__ . '/../config/config.php';

echo "--- Diagnostic Avatars ---\n";

$stmt = $conn->query("SELECT id, role, nom, prenom, photo_url FROM users WHERE photo_url IS NOT NULL ORDER BY id DESC LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    echo "ID: {$u['id']} ({$u['role']}) - {$u['prenom']} {$u['nom']}\n";
    echo "   DB URL: {$u['photo_url']}\n";
    
    $filePath = __DIR__ . '/../' . $u['photo_url'];
    if (file_exists($filePath)) {
        echo "   ✅ Fichier OK (" . filesize($filePath) . " bytes)\n";
        echo "   Path: " . realpath($filePath) . "\n";
    } else {
        echo "   ❌ Fichier MANQUANT\n";
        echo "   Path cherché: " . $filePath . "\n";
    }
    echo "----------------\n";
}
?>

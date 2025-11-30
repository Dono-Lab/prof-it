<?php
require_once __DIR__ . '/../config/config.php';
$stmt = $conn->query("SELECT id, role, photo_url FROM users WHERE photo_url IS NOT NULL");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>

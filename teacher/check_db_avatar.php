<?php
require_once __DIR__ . '/../config/config.php';
// Hardcode a user ID or try to find one.
// Let's list all users with a photo_url set.
$stmt = $conn->query("SELECT id, role, photo_url FROM users WHERE photo_url IS NOT NULL");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
?>

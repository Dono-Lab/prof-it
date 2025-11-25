<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['email'])) {
    echo json_encode([
        'loggedIn' => true,
        'prenom' => $_SESSION['prenom'] ?? '',
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>
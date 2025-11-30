<?php
session_start();
require_once'../includes/csrf.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {

    die('Action non autorisée');
}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM sessions_actives WHERE session_php_id = ?");
        $stmt->execute([session_id()]);
    } catch (PDOException $e) {}
}

session_unset();
session_destroy();
header("Location: ../public/home.php");
exit();

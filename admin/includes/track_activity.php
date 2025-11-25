<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

$session_id = session_id();
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

$current_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$current_url = substr($current_url, 0, 255);
$user_id = $_SESSION['user_id'] ?? null;

try {
    $stmtHistory = $conn->prepare("
        INSERT INTO logs_visites (user_id, session_token, page_url)
        VALUES (?, ?, ?)
    ");
    $stmtHistory->execute([$user_id, $session_id, $current_url]);

    if ($user_id) {
        $stmtActive = $conn->prepare("
            INSERT INTO sessions_actives (user_id, session_php_id, user_agent, current_url)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                user_agent = VALUES(user_agent),
                current_url = VALUES(current_url),
                derniere_activite = CURRENT_TIMESTAMP
        ");
        $stmtActive->execute([$user_id, $session_id, $user_agent, $current_url]);
    }
} catch (PDOException $e) {
    error_log("Erreur track_activity: " . $e->getMessage());
}

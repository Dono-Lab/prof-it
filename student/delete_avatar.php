<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!is_logged_in() || !is_student()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT photo_url FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['photo_url'])) {
        $photoUrl = $user['photo_url'];

        if (strpos($photoUrl, 'presets/') === false) {
            $fullPath = __DIR__ . '/../' . $photoUrl;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    $stmt = $conn->prepare("UPDATE users SET photo_url = NULL WHERE id = ?");
    $stmt->execute([$userId]);

    $_SESSION['avatar_url'] = '';

    echo json_encode([
        'success' => true,
        'message' => 'Avatar supprimé avec succès.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'avatar.']);
}

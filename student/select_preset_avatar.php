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

$avatarNumber = isset($_POST['avatar']) ? intval($_POST['avatar']) : 0;

if ($avatarNumber < 1 || $avatarNumber > 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Avatar invalide.']);
    exit;
}

$userId = $_SESSION['user_id'];
$presetPath = "assets/img/avatars/presets/avatar-{$avatarNumber}.svg";

$fullPath = __DIR__ . '/../' . $presetPath;
if (!file_exists($fullPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fichier d\'avatar introuvable.']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
    $stmt->execute([$presetPath, $userId]);

    $_SESSION['avatar_url'] = $presetPath;

    echo json_encode([
        'success' => true,
        'message' => 'Avatar mis à jour avec succès.',
        'avatar_url' => '../' . $presetPath
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'avatar.']);
}

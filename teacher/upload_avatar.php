<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/config.php';

safe_session_start();
if (!is_teacher()) {
    http_response_code(403);
    exit('Accès refusé');
}

csrf_protect();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    exit('Utilisateur introuvable');
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['success_message'] = 'Aucun fichier envoyé.';
    header('Location: settings.php');
    exit;
}

if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['success_message'] = 'Erreur lors de l\'upload de l\'avatar.';
    header('Location: settings.php');
    exit;
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
];

$type = $_FILES['avatar']['type'] ?? '';
$size = $_FILES['avatar']['size'] ?? 0;
$tmp  = $_FILES['avatar']['tmp_name'] ?? '';

if (!isset($allowedTypes[$type])) {
    $_SESSION['success_message'] = 'Format invalide (JPG, PNG ou GIF uniquement).';
    header('Location: settings.php');
    exit;
}

if ($size > 2 * 1024 * 1024) { // 2 Mo
    $_SESSION['success_message'] = 'Avatar trop volumineux (2 Mo max).';
    header('Location: settings.php');
    exit;
}

$ext = $allowedTypes[$type];
$filename = 'teacher_' . $userId . '_' . time() . '.' . $ext;
$targetDir = __DIR__ . '/../assets/img/avatars';

if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0755, true);
}

$targetPath = $targetDir . '/' . $filename;
if (!move_uploaded_file($tmp, $targetPath)) {
    $_SESSION['success_message'] = 'Erreur lors de l\'upload de l\'avatar.';
    header('Location: settings.php');
    exit;
}

// Chemin utilisable depuis le web (racine du projet)
$photoUrl = 'assets/img/avatars/' . $filename;

// Mise à jour DB
$stmt = $conn->prepare("UPDATE users SET photo_url = ? WHERE id = ?");
$stmt->execute([$photoUrl, $userId]);

// Session + message
$_SESSION['avatar_url'] = $photoUrl;
$_SESSION['success_message'] = 'Avatar mis à jour avec succès.';

header('Location: settings.php');
exit;

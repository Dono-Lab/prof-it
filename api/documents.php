<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';

safe_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    if ($method === 'POST') {
        switch ($action) {
            case 'upload':
                handleUpload($conn, $userId);
                break;
            case 'delete_document':
                handleDeleteDocument($conn, $userId);
                break;
            case 'delete_category':
                handleDeleteCategory($conn, $userId);
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Action POST non autorisée']);
        }
    } elseif ($method === 'GET' && $action === 'category') {
        $category = trim($_GET['name'] ?? '');
        handleCategoryList($conn, $userId, $category);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode ou action non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}

function handleUpload($conn, $userId)
{
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'upload') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
        return;
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
        return;
    }

    $file = $_FILES['document'];
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux (10MB maximum)']);
        return;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'png', 'jpg', 'jpeg'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
        return;
    }

    $uploadDir = __DIR__ . '/../uploads/documents/' . $userId;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier de stockage']);
        return;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
    $newFileName = uniqid('doc_', true) . '_' . $safeName;
    $destination = $uploadDir . '/' . $newFileName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Échec du téléversement du fichier']);
        return;
    }

    $relativePath = 'uploads/documents/' . $userId . '/' . $newFileName;
    $categorie = trim($_POST['categorie'] ?? '') ?: null;

    $stmt = $conn->prepare("
        INSERT INTO document (id_utilisateur, nom_original, fichier_path, type_fichier, taille_octets, categorie, source)
        VALUES (?, ?, ?, ?, ?, ?, 'upload')
    ");
    $stmt->execute([
        $userId,
        $file['name'],
        $relativePath,
        $file['type'] ?: $extension,
        $file['size'],
        $categorie
    ]);

    echo json_encode(['success' => true, 'message' => 'Document ajouté']);
}

function handleCategoryList($conn, $userId, $category)
{
    if ($category === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Catégorie manquante']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT
            id_document,
            nom_original,
            fichier_path,
            type_fichier,
            taille_octets,
            categorie,
            source,
            uploaded_at
        FROM document
        WHERE id_utilisateur = ? AND COALESCE(categorie, 'Autre') = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$userId, $category]);
    $documents = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'category' => $category,
        'documents' => $documents
    ]);
}

function handleDeleteDocument($conn, $userId)
{
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $documentId = (int)($_POST['document_id'] ?? 0);
    if (!$documentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Document invalide']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT fichier_path
        FROM document
        WHERE id_document = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$documentId, $userId]);
    $document = $stmt->fetch();

    if (!$document) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Document introuvable']);
        return;
    }

    $conn->prepare("DELETE FROM document WHERE id_document = ? AND id_utilisateur = ?")
        ->execute([$documentId, $userId]);

    if (!empty($document['fichier_path'])) {
        $absolutePath = realpath(__DIR__ . '/../' . $document['fichier_path']);
        $uploadsRoot = realpath(__DIR__ . '/../uploads/documents');
        if ($absolutePath && $uploadsRoot && strpos($absolutePath, $uploadsRoot) === 0 && file_exists($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Document supprimé']);
}

function handleDeleteCategory($conn, $userId)
{
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $category = trim($_POST['categorie'] ?? '');
    if ($category === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Catégorie invalide']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT id_document, fichier_path
        FROM document
        WHERE id_utilisateur = ? AND COALESCE(categorie, 'Autre') = ?
    ");
    $stmt->execute([$userId, $category]);
    $documents = $stmt->fetchAll();

    if (empty($documents)) {
        echo json_encode(['success' => true, 'message' => 'Aucun document à supprimer']);
        return;
    }

    $ids = array_column($documents, 'id_document');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$userId], $ids);

    $conn->prepare("
        DELETE FROM document
        WHERE id_utilisateur = ? AND id_document IN ($placeholders)
    ")->execute($params);

    $uploadsRoot = realpath(__DIR__ . '/../uploads/documents');
    foreach ($documents as $doc) {
        if (empty($doc['fichier_path'])) {
            continue;
        }
        $absolutePath = realpath(__DIR__ . '/../' . $doc['fichier_path']);
        if ($absolutePath && $uploadsRoot && strpos($absolutePath, $uploadsRoot) === 0 && file_exists($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Dossier supprimé']);
}

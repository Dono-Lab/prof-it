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

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $userId, $userRole);
            break;

        case 'POST':
            handlePostRequest($conn, $userId, $userRole);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}


function handleGetRequest($conn, $userId, $userRole) {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'conversations':
            getConversations($conn, $userId, $userRole);
            break;

        case 'messages':
            $conversationId = $_GET['conversation_id'] ?? null;
            if (!$conversationId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID conversation manquant']);
                return;
            }
            getMessages($conn, $userId, $userRole, $conversationId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function handlePostRequest($conn, $userId, $userRole) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'send_message':
            sendMessage($conn, $userId, $userRole);
            break;

        case 'mark_as_read':
            markAsRead($conn, $userId);
            break;

        case 'delete_conversation':
            deleteConversation($conn, $userId, $userRole);
            break;

        case 'submit_review':
            submitReview($conn, $userId, $userRole);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function getConversations($conn, $userId, $userRole) {
    if ($userRole === 'student') {
        $sql = "
            SELECT DISTINCT
                conv.id_conversation,
                conv.derniere_activite,
                CONCAT(prof.prenom, ' ', prof.nom) as contact_nom,
                prof.photo_url as contact_photo,
                m.nom_matiere,
                (SELECT contenu FROM message WHERE id_conversation = conv.id_conversation ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
                (SELECT date_envoi FROM message WHERE id_conversation = conv.id_conversation ORDER BY date_envoi DESC LIMIT 1) as date_dernier_message,
                (SELECT COUNT(*) FROM message WHERE id_conversation = conv.id_conversation AND id_utilisateur != ? AND lu = 0) as nb_non_lus
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            INNER JOIN users prof ON c.id_utilisateur = prof.id
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE r.id_utilisateur = ? AND conv.archivee = 0
            ORDER BY conv.derniere_activite DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId, $userId]);
    } else {
        $sql = "
            SELECT DISTINCT
                conv.id_conversation,
                conv.derniere_activite,
                CONCAT(etudiant.prenom, ' ', etudiant.nom) as contact_nom,
                etudiant.photo_url as contact_photo,
                m.nom_matiere,
                (SELECT contenu FROM message WHERE id_conversation = conv.id_conversation ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
                (SELECT date_envoi FROM message WHERE id_conversation = conv.id_conversation ORDER BY date_envoi DESC LIMIT 1) as date_dernier_message,
                (SELECT COUNT(*) FROM message WHERE id_conversation = conv.id_conversation AND id_utilisateur != ? AND lu = 0) as nb_non_lus
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE c.id_utilisateur = ? AND conv.archivee = 0
            ORDER BY conv.derniere_activite DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId, $userId]);
    }

    $conversations = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
}

function getMessages($conn, $userId, $userRole, $conversationId) {
    if ($userRole === 'student') {
        $checkSql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            WHERE conv.id_conversation = ? AND r.id_utilisateur = ?
        ";
    } else {
        $checkSql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            WHERE conv.id_conversation = ? AND c.id_utilisateur = ?
        ";
    }

    $stmt = $conn->prepare($checkSql);
    $stmt->execute([$conversationId, $userId]);
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé à cette conversation']);
        return;
    }

    $sqlWithDocuments = "
        SELECT
            m.id_message,
            m.contenu,
            m.date_envoi,
            m.lu,
            m.fichier_joint,
            doc.nom_original as document_nom,
            m.id_utilisateur,
            CONCAT(u.prenom, ' ', u.nom) as auteur_nom,
            u.photo_url as auteur_photo,
            u.role as auteur_role
        FROM message m
        INNER JOIN users u ON m.id_utilisateur = u.id
        LEFT JOIN document doc ON doc.id_message = m.id_message
        WHERE m.id_conversation = ? AND m.supprime = 0
        ORDER BY m.date_envoi ASC
    ";

    try {
        $stmt = $conn->prepare($sqlWithDocuments);
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll();
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'document') !== false) {
            $sqlFallback = "
                SELECT
                    m.id_message,
                    m.contenu,
                    m.date_envoi,
                    m.lu,
                    m.fichier_joint,
                    NULL as document_nom,
                    m.id_utilisateur,
                    CONCAT(u.prenom, ' ', u.nom) as auteur_nom,
                    u.photo_url as auteur_photo,
                    u.role as auteur_role
                FROM message m
                INNER JOIN users u ON m.id_utilisateur = u.id
                WHERE m.id_conversation = ? AND m.supprime = 0
                ORDER BY m.date_envoi ASC
            ";
            $stmt = $conn->prepare($sqlFallback);
            $stmt->execute([$conversationId]);
            $messages = $stmt->fetchAll();
        } else {
            throw $e;
        }
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

function sendMessage($conn, $userId, $userRole) {
    $conversationId = $_POST['conversation_id'] ?? null;
    $contenu = trim($_POST['contenu'] ?? '');
    $hasFile = isset($_FILES['fichier_joint']) && $_FILES['fichier_joint']['error'] === UPLOAD_ERR_OK;

    if (!$conversationId || (empty($contenu) && !$hasFile)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
        return;
    }

    if ($userRole === 'student') {
        $checkSql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            WHERE conv.id_conversation = ? AND r.id_utilisateur = ?
        ";
    } else {
        $checkSql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            WHERE conv.id_conversation = ? AND c.id_utilisateur = ?
        ";
    }

    $stmt = $conn->prepare($checkSql);
    $stmt->execute([$conversationId, $userId]);
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        return;
    }

    $attachment = null;
    if ($hasFile) {
        $attachment = handleMessageAttachment($conversationId);
        if ($attachment === false) {
            return;
        }
    }

    $sql = "INSERT INTO message (id_conversation, id_utilisateur, contenu, fichier_joint) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $userId, $contenu, $attachment['path'] ?? null]);
    $messageId = $conn->lastInsertId();

    if ($attachment) {
        $stmtDoc = $conn->prepare("
            INSERT INTO document (id_utilisateur, id_message, nom_original, fichier_path, type_fichier, taille_octets, categorie, source)
            VALUES (?, ?, ?, ?, ?, ?, 'Messagerie', 'messaging')
        ");
        $stmtDoc->execute([
            $userId,
            $messageId,
            $attachment['name'],
            $attachment['path'],
            $attachment['type'],
            $attachment['size']
        ]);
    }

    $updateSql = "UPDATE conversation SET derniere_activite = NOW() WHERE id_conversation = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->execute([$conversationId]);

    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé'
    ]);
}

function markAsRead($conn, $userId) {
    $conversationId = $_POST['conversation_id'] ?? null;

    if (!$conversationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID conversation manquant']);
        return;
    }

    $sql = "
        UPDATE message
        SET lu = 1, date_lecture = NOW()
        WHERE id_conversation = ? AND id_utilisateur != ? AND lu = 0
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $userId]);

    echo json_encode([
        'success' => true,
        'message' => 'Messages marqués comme lus'
    ]);
}

function deleteConversation($conn, $userId, $userRole) {
    $conversationId = $_POST['conversation_id'] ?? null;
    if (!$conversationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID conversation manquant']);
        return;
    }

    if ($userRole === 'student') {
        $sql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            WHERE conv.id_conversation = ? AND r.id_utilisateur = ?
        ";
    } else {
        $sql = "
            SELECT COUNT(*) as count
            FROM conversation conv
            INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            WHERE conv.id_conversation = ? AND c.id_utilisateur = ?
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $userId]);
    $result = $stmt->fetch();

    if ($result['count'] == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        return;
    }

    $stmtFiles = $conn->prepare("
        SELECT fichier_joint FROM message
        WHERE id_conversation = ? AND fichier_joint IS NOT NULL
    ");
    $stmtFiles->execute([$conversationId]);
    foreach ($stmtFiles->fetchAll() as $row) {
        $filePath = __DIR__ . '/../' . $row['fichier_joint'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM conversation WHERE id_conversation = ?");
    $stmt->execute([$conversationId]);

    echo json_encode(['success' => true, 'message' => 'Conversation supprimée']);
}

function handleMessageAttachment($conversationId)
{
    $file = $_FILES['fichier_joint'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement de la pièce jointe']);
        return false;
    }

    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Pièce jointe trop volumineuse (10MB max)']);
        return false;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format de pièce jointe non supporté']);
        return false;
    }

    $uploadDir = __DIR__ . '/../uploads/messages/' . $conversationId;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier de stockage']);
        return false;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
    $newFileName = uniqid('msg_', true) . '_' . $safeName;
    $destination = $uploadDir . '/' . $newFileName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Impossible d\'enregistrer la pièce jointe']);
        return false;
    }

    return [
        'path' => 'uploads/messages/' . $conversationId . '/' . $newFileName,
        'name' => $file['name'],
        'type' => $file['type'] ?: $extension,
        'size' => $file['size']
    ];
}

function submitReview($conn, $userId, $userRole) {
    if ($userRole !== 'student') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Seuls les étudiants peuvent laisser un avis.']);
        return;
    }

    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $note = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if (!$conversationId || $note < 1 || $note > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Note ou conversation invalide.']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT r.id_reservation, r.statut_reservation
        FROM conversation conv
        INNER JOIN reservation r ON conv.id_reservation = r.id_reservation
        WHERE conv.id_conversation = ? AND r.id_utilisateur = ?
    ");
    $stmt->execute([$conversationId, $userId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Impossible d\'associer la réservation.']);
        return;
    }

    if ($reservation['statut_reservation'] !== 'terminee') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vous pourrez laisser un avis une fois le cours terminé.']);
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO avis (id_reservation, id_utilisateur, note, commentaire)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            note = VALUES(note),
            commentaire = VALUES(commentaire),
            date_modification = NOW()
    ");
    $stmt->execute([
        $reservation['id_reservation'],
        $userId,
        $note,
        $commentaire !== '' ? $commentaire : null
    ]);

    echo json_encode(['success' => true, 'message' => 'Avis enregistré.']);
}

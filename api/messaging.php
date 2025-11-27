<?php
/**
 * API de gestion de la messagerie
 * Gère les conversations et messages entre étudiants et professeurs
 */

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

/**
 * Gestion des requêtes GET
 */
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

/**
 * Gestion des requêtes POST
 */
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

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

/**
 * Récupère toutes les conversations de l'utilisateur
 */
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

/**
 * Récupère les messages d'une conversation
 */
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

    $sql = "
        SELECT
            m.id_message,
            m.contenu,
            m.date_envoi,
            m.lu,
            m.fichier_joint,
            m.id_utilisateur,
            CONCAT(u.prenom, ' ', u.nom) as auteur_nom,
            u.photo_url as auteur_photo,
            u.role as auteur_role
        FROM message m
        INNER JOIN users u ON m.id_utilisateur = u.id
        WHERE m.id_conversation = ? AND m.supprime = 0
        ORDER BY m.date_envoi ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

/**
 * Envoie un nouveau message
 */
function sendMessage($conn, $userId, $userRole) {
    $conversationId = $_POST['conversation_id'] ?? null;
    $contenu = trim($_POST['contenu'] ?? '');

    if (!$conversationId || empty($contenu)) {
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

    $sql = "INSERT INTO message (id_conversation, id_utilisateur, contenu) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conversationId, $userId, $contenu]);

    $updateSql = "UPDATE conversation SET derniere_activite = NOW() WHERE id_conversation = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->execute([$conversationId]);

    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé'
    ]);
}

/**
 * Marque les messages comme lus
 */
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

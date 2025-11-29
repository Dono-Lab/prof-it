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
            handleGetRequest($conn, $userId);
            break;

        case 'POST':
            handlePostRequest($conn, $userId);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}

function handleGetRequest($conn, $userId) {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'tickets':
            getTickets($conn, $userId);
            break;

        case 'stats':
            getStats($conn, $userId);
            break;

        case 'ticket_details':
            $ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
            if ($ticketId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID ticket invalide']);
                return;
            }
            getTicketDetails($conn, $userId, $ticketId);
            break;

        case 'reply_ticket':
            replyTicket($conn, $userId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function handlePostRequest($conn, $userId) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_ticket':
            createTicket($conn, $userId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function getTickets($conn, $userId) {
    $sql = "
        SELECT
            t.id_ticket,
            t.sujet,
            t.categorie,
            t.priorite,
            t.statut_ticket,
            t.cree_le,
            t.dernier_message,
            t.ferme_le
        FROM ticket_support t
        WHERE t.id_utilisateur = ?
        ORDER BY t.cree_le DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);
}

function getStats($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ticket_support WHERE id_utilisateur = ?");
    $stmt->execute([$userId]);
    $total = (int)$stmt->fetch()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as waiting FROM ticket_support WHERE id_utilisateur = ? AND statut_ticket = 'en_cours'");
    $stmt->execute([$userId]);
    $waiting = (int)$stmt->fetch()['waiting'];

    $stmt = $conn->prepare("SELECT COUNT(*) as closed FROM ticket_support WHERE id_utilisateur = ? AND statut_ticket = 'ferme'");
    $stmt->execute([$userId]);
    $closed = (int)$stmt->fetch()['closed'];

    $stmt = $conn->prepare("SELECT COUNT(*) as open FROM ticket_support WHERE id_utilisateur = ? AND statut_ticket = 'ouvert'");
    $stmt->execute([$userId]);
    $open = (int)$stmt->fetch()['open'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $total,
            'waiting' => $waiting,
            'closed' => $closed,
            'open' => $open
        ]
    ]);
}

function getTicketDetails($conn, $userId, $ticketId) {
    $stmt = $conn->prepare("
        SELECT
            id_ticket,
            sujet,
            categorie,
            priorite,
            statut_ticket,
            cree_le,
            dernier_message,
            ferme_le
        FROM ticket_support
        WHERE id_ticket = ? AND id_utilisateur = ?
    ");
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
        return;
    }

    $stmtMessages = $conn->prepare("
        SELECT
            mt.id_message_ticket,
            mt.contenu,
            mt.date_envoi,
            mt.est_admin,
            mt.fichier_joint,
            CONCAT(u.prenom, ' ', u.nom) as auteur,
            u.role
        FROM message_ticket mt
        JOIN users u ON mt.id_utilisateur = u.id
        WHERE mt.id_ticket = ?
        ORDER BY mt.date_envoi ASC
    ");
    $stmtMessages->execute([$ticketId]);
    $messages = $stmtMessages->fetchAll();

    echo json_encode([
        'success' => true,
        'ticket' => $ticket,
        'messages' => $messages
    ]);
}

function createTicket($conn, $userId) {
    $sujet = trim($_POST['sujet'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $priorite = trim($_POST['priorite'] ?? 'normale');
    $description = trim($_POST['description'] ?? '');

    if (empty($sujet) || empty($categorie) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
        return;
    }

    if (strlen($description) < 20) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La description doit contenir au moins 20 caractères']);
        return;
    }

    $categories_valides = ['technique', 'paiement', 'compte', 'reservation', 'autre'];
    $priorites_valides = ['basse', 'normale', 'haute', 'urgente'];

    if (!in_array($categorie, $categories_valides)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Catégorie invalide']);
        return;
    }

    if (!in_array($priorite, $priorites_valides)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Priorité invalide']);
        return;
    }

    $sql = "
        INSERT INTO ticket_support (id_utilisateur, sujet, categorie, priorite, statut_ticket)
        VALUES (?, ?, ?, ?, 'ouvert')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $sujet, $categorie, $priorite]);

    $ticketId = $conn->lastInsertId();

    $sqlMessage = "
        INSERT INTO message_ticket (id_ticket, id_utilisateur, contenu, est_admin)
        VALUES (?, ?, ?, 0)
    ";
    $stmtMessage = $conn->prepare($sqlMessage);
    $stmtMessage->execute([$ticketId, $userId, $description]);

    echo json_encode([
        'success' => true,
        'message' => 'Ticket créé avec succès',
        'ticket_id' => $ticketId
    ]);
}

function replyTicket($conn, $userId) {
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!$ticketId || $message === '' || strlen($message) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Message trop court ou ticket invalide']);
        return;
    }

    $stmt = $conn->prepare("SELECT id_ticket, statut_ticket FROM ticket_support WHERE id_ticket = ? AND id_utilisateur = ?");
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO message_ticket (id_ticket, id_utilisateur, contenu, est_admin)
        VALUES (?, ?, ?, 0)
    ");
    $stmt->execute([$ticketId, $userId, $message]);

    $stmtUser = $conn->prepare("SELECT CONCAT(prenom, ' ', nom) as nom_complet FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    $conn->prepare("
        UPDATE ticket_support
        SET dernier_message = NOW(),
            statut_ticket = CASE WHEN statut_ticket IN ('resolu', 'ferme') THEN 'en_cours' ELSE statut_ticket END
        WHERE id_ticket = ?
    ")->execute([$ticketId]);

    echo json_encode([
        'success' => true,
        'message' => [
            'contenu' => $message,
            'date_envoi' => date('Y-m-d H:i:s'),
            'est_admin' => 0,
            'auteur' => $user['nom_complet'] ?? 'Vous'
        ]
    ]);
}

<?php
/**
 * API de gestion des tickets de support
 * Gère la création et la lecture des tickets
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

/**
 * Gestion des requêtes GET
 */
function handleGetRequest($conn, $userId) {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'tickets':
            getTickets($conn, $userId);
            break;

        case 'stats':
            getStats($conn, $userId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

/**
 * Gestion des requêtes POST
 */
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

/**
 * Récupère tous les tickets de l'utilisateur
 */
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

/**
 * Récupère les statistiques des tickets
 */
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

/**
 * Crée un nouveau ticket
 */
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

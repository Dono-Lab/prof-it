<?php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === '') {
        $search = $_GET['search'] ?? '';
        $statut = $_GET['statut'] ?? '';
        $priorite = $_GET['priorite'] ?? '';
        $categorie = $_GET['categorie'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = "(t.sujet LIKE ? OR u.email LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        if ($statut !== '') {
            $conditions[] = "t.statut_ticket = ?";
            $params[] = $statut;
        }

        if ($priorite !== '') {
            $conditions[] = "t.priorite = ?";
            $params[] = $priorite;
        }

        if ($categorie !== '') {
            $conditions[] = "t.categorie = ?";
            $params[] = $categorie;
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $stmtCount = $conn->prepare("
            SELECT COUNT(*) as total
            FROM ticket_support t
            JOIN users u ON t.id_utilisateur = u.id
            $whereClause
        ");
        $stmtCount->execute($params);
        $total = $stmtCount->fetch()['total'];

        $stmt = $conn->prepare("
            SELECT
                t.id_ticket,
                t.id_utilisateur,
                t.sujet,
                t.categorie,
                t.statut_ticket,
                t.priorite,
                t.cree_le,
                t.ferme_le,
                t.dernier_message,
                u.nom,
                u.prenom,
                u.email,
                (SELECT COUNT(*) FROM message_ticket WHERE id_ticket = t.id_ticket) as nb_messages
            FROM ticket_support t
            JOIN users u ON t.id_utilisateur = u.id
            $whereClause
            ORDER BY
                CASE t.priorite
                    WHEN 'urgente' THEN 1
                    WHEN 'haute' THEN 2
                    WHEN 'normale' THEN 3
                    WHEN 'basse' THEN 4
                END,
                t.dernier_message DESC
            LIMIT $perPage OFFSET $offset
        ");

        $stmt->execute($params);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'tickets' => $tickets,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);

    } elseif ($method === 'GET' && $action === 'details') {
        $ticketId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID ticket invalide']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT
                t.id_ticket,
                t.id_utilisateur,
                t.sujet,
                t.categorie,
                t.statut_ticket,
                t.priorite,
                t.cree_le,
                t.ferme_le,
                t.dernier_message,
                u.nom,
                u.prenom,
                u.email,
                u.photo_url
            FROM ticket_support t
            JOIN users u ON t.id_utilisateur = u.id
            WHERE t.id_ticket = ?
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
            exit;
        }

        $messagePage = isset($_GET['message_page']) ? max(1, (int)$_GET['message_page']) : 1;
        $messagesPerPage = 10;
        $messageOffset = ($messagePage - 1) * $messagesPerPage;

        $stmtCountMessages = $conn->prepare("
            SELECT COUNT(*) as total FROM message_ticket WHERE id_ticket = ?
        ");
        $stmtCountMessages->execute([$ticketId]);
        $totalMessages = $stmtCountMessages->fetch()['total'];

        $stmtMessages = $conn->prepare("
            SELECT
                mt.id_message_ticket,
                mt.id_utilisateur,
                mt.contenu,
                mt.date_envoi,
                mt.fichier_joint,
                mt.est_admin,
                u.nom,
                u.prenom,
                u.photo_url
            FROM message_ticket mt
            JOIN users u ON mt.id_utilisateur = u.id
            WHERE mt.id_ticket = ?
            ORDER BY mt.date_envoi ASC
            LIMIT $messagesPerPage OFFSET $messageOffset
        ");
        $stmtMessages->execute([$ticketId]);
        $messages = $stmtMessages->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'ticket' => $ticket,
            'messages' => $messages,
            'pagination' => [
                'current_page' => $messagePage,
                'per_page' => $messagesPerPage,
                'total' => (int)$totalMessages,
                'total_pages' => ceil($totalMessages / $messagesPerPage)
            ]
        ]);

    } elseif ($method === 'POST' && $action === 'reply') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
            exit;
        }

        $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
        $contenu = trim($_POST['contenu'] ?? '');

        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID ticket invalide']);
            exit;
        }

        if ($contenu === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Le message ne peut pas être vide']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id_ticket FROM ticket_support WHERE id_ticket = ?");
        $stmt->execute([$ticketId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        $stmtInsert = $conn->prepare("
            INSERT INTO message_ticket (id_ticket, id_utilisateur, contenu, est_admin)
            VALUES (?, ?, ?, 1)
        ");
        $stmtInsert->execute([$ticketId, $userId, $contenu]);

        $stmtUpdate = $conn->prepare("
            UPDATE ticket_support
            SET dernier_message = CURRENT_TIMESTAMP
            WHERE id_ticket = ?
        ");
        $stmtUpdate->execute([$ticketId]);

        echo json_encode([
            'success' => true,
            'message' => 'Réponse ajoutée avec succès',
            'id_message' => $conn->lastInsertId()
        ]);

    } elseif ($method === 'PUT' || ($method === 'POST' && $action === 'update')) {
        parse_str(file_get_contents('php://input'), $putData);

        if ($method === 'POST') {
            $putData = $_POST;
        }

        if (!verify_csrf($putData['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
            exit;
        }

        $ticketId = isset($putData['ticket_id']) ? (int)$putData['ticket_id'] : 0;
        $statut = $putData['statut'] ?? null;
        $priorite = $putData['priorite'] ?? null;

        if ($ticketId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID ticket invalide']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id_ticket FROM ticket_support WHERE id_ticket = ?");
        $stmt->execute([$ticketId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket introuvable']);
            exit;
        }

        $updates = [];
        $params = [];

        if ($statut !== null) {
            $validStatuts = ['ouvert', 'en_cours', 'resolu', 'ferme'];
            if (!in_array($statut, $validStatuts)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Statut invalide']);
                exit;
            }
            $updates[] = 'statut_ticket = ?';
            $params[] = $statut;

            if ($statut === 'ferme') {
                $updates[] = 'ferme_le = CURRENT_TIMESTAMP';
            }
        }

        if ($priorite !== null) {
            $validPriorites = ['basse', 'normale', 'haute', 'urgente'];
            if (!in_array($priorite, $validPriorites)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Priorité invalide']);
                exit;
            }
            $updates[] = 'priorite = ?';
            $params[] = $priorite;
        }

        if (count($updates) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Aucune modification à effectuer']);
            exit;
        }

        $params[] = $ticketId;
        $sql = "UPDATE ticket_support SET " . implode(', ', $updates) . " WHERE id_ticket = ?";
        $stmtUpdate = $conn->prepare($sql);
        $stmtUpdate->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Ticket mis à jour avec succès'
        ]);

    } elseif ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
        parse_str(file_get_contents('php://input'), $deleteData);

        if ($method === 'POST') {
            $deleteData = $_POST;
        }

        if (!verify_csrf($deleteData['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
            exit;
        }

        $messageId = isset($deleteData['message_id']) ? (int)$deleteData['message_id'] : 0;

        if ($messageId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID message invalide']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM message_ticket WHERE id_message_ticket = ?");
        $stmt->execute([$messageId]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Message introuvable']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Message supprimé avec succès'
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }

} catch (PDOException $e) {
    error_log("Erreur API tickets: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

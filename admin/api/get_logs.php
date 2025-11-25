<?php
require_once __DIR__ . '/bootstrap.php';

$type = $_GET['type'] ?? '';
try {
    if ($type === 'connexions') {
        $stmt = $conn->prepare("
            SELECT
                id,
                user_id,
                email,
                statut,
                user_agent,
                date_connexion,
                raison_echec
            FROM logs_connexions
            ORDER BY date_connexion DESC
            LIMIT 500
        ");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
    } elseif ($type === 'visites') {
        $stmt = $conn->prepare("
            SELECT
                lv.id,
                lv.user_id,
                lv.session_token,
                lv.page_url,
                lv.date_visite,
                u.nom,
                u.prenom,
                u.email
            FROM logs_visites lv
            LEFT JOIN users u ON lv.user_id = u.id
            ORDER BY lv.date_visite DESC
            LIMIT 500
        ");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Type de log invalide'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

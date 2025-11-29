<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions_user.php';

safe_session_start();
if (!is_teacher()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès refusé']));
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'upcoming_sessions':
            $sessions = get_teacher_upcoming_sessions($userId, $conn, 5);
            echo json_encode(['success' => true, 'data' => $sessions]);
            break;

        case 'stats':
            $stats = get_teacher_stats($userId, $conn);
            echo json_encode([
                'success' => true,
                'data' => format_stats_response($stats)
            ]);
            break;

        case 'available_slots':
            $slots = get_teacher_available_slots($userId, $conn, 10);
            echo json_encode(['success' => true, 'data' => $slots]);
            break;

        case 'all':
            $sessions = get_teacher_upcoming_sessions($userId, $conn, 5);
            $stats = get_teacher_stats($userId, $conn);
            $slots = get_teacher_available_slots($userId, $conn, 10);

            echo json_encode([
                'success' => true,
                'data' => [
                    'sessions' => $sessions,
                    'stats' => format_stats_response($stats),
                    'available_slots' => $slots
                ]
            ]);
            break;

        default:
            echo json_encode(['error' => 'Action invalide']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}

function format_stats_response(array $stats) {
    return [
        'total_students' => $stats['nb_etudiants'] ?? 0,
        'total_reservations' => $stats['nb_reservations'] ?? 0,
        'avg_rating' => $stats['note_moyenne'] ?? 0,
        'total_reviews' => $stats['nb_avis'] ?? 0,
        'total_hours' => $stats['heures_donnees'] ?? 0
    ];
}

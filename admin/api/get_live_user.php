<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $stmt = $conn->prepare("
        SELECT
            s.id as session_id,
            s.user_id,
            s.session_php_id,
            s.current_url,
            s.derniere_activite,
            u.prenom,
            u.nom,
            u.email,
            u.role
        FROM sessions_actives s
        INNER JOIN users u ON s.user_id = u.id
        WHERE s.derniere_activite >= NOW() - INTERVAL 5 MINUTE
        ORDER BY s.derniere_activite DESC
    ");

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'total' => count($users),
        'students' => 0,
        'teachers' => 0,
        'admins' => 0
    ];

    foreach ($users as $user) {
        if ($user['role'] === 'student') {
            $stats['students']++;
        } elseif ($user['role'] === 'teacher') {
            $stats['teachers']++;
        } elseif ($user['role'] === 'admin') {
            $stats['admins']++;
        }
    }
    echo json_encode([
        'success' => true,
        'users' => $users,
        'stats' => $stats
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

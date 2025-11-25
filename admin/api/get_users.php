<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            nom,
            prenom,
            email,
            role,
            telephone,
            adresse,
            ville,
            code_postal,
            actif,
            created_at
        FROM users 
        ORDER BY created_at DESC
    ");

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

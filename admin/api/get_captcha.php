<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $stmt = $conn->prepare("
        SELECT id, question, reponse, actif, created_at
        FROM captcha_questions 
        ORDER BY created_at DESC
    ");

    $stmt->execute();
    $captcha = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'captcha' => $captcha
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

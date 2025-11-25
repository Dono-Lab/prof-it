<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'create':
            $question = trim($_POST['question'] ?? '');
            $reponse = trim($_POST['reponse'] ?? '');
            $actif = isset($_POST['actif']) ? 1 : 0;

            if (empty($question) || empty($reponse)) {
                echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires']);
                exit();
            }

            $stmt = $conn->prepare("SELECT id FROM captcha_questions WHERE question = ?");
            $stmt->execute([$question]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cette question existe déjà']);
                exit();
            }

            $stmt = $conn->prepare("
                INSERT INTO captcha_questions (question, reponse, actif) VALUES (?, ?, ?)
            ");

            if ($stmt->execute([$question, $reponse, $actif])) {
                echo json_encode(['success' => true, 'message' => 'Question créée avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création']);
            }
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $question = trim($_POST['question'] ?? '');
            $reponse = trim($_POST['reponse'] ?? '');
            $actif = isset($_POST['actif']) ? 1 : 0;

            if ($id <= 0 || empty($question) || empty($reponse)) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit();
            }

            $stmt = $conn->prepare("SELECT id FROM captcha_questions WHERE question = ? AND id != ?");
            $stmt->execute([$question, $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cette question existe déjà']);
                exit();
            }

            $stmt = $conn->prepare("
                UPDATE captcha_questions 
                SET question = ?, reponse = ?, actif = ?
                WHERE id = ?
            ");

            if ($stmt->execute([$question, $reponse, $actif, $id])) {
                echo json_encode(['success' => true, 'message' => 'Question modifiée avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la modification']);
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID invalide']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM captcha_questions WHERE id = ?");

            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Question supprimée avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'message' => $e->getMessage()
    ]);
}

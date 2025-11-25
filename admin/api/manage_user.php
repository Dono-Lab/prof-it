<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'create':
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'student';
            $actif = isset($_POST['actif']) ? 1 : 0;
            $telephone = trim($_POST['telephone'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $code_postal = trim($_POST['code_postal'] ?? '');

            if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires']);
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email invalide']);
                exit();
            }

            if (!in_array($role, ['student', 'teacher', 'admin'])) {
                echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
                exit();
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
                exit();
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (nom, prenom, email, password, role, telephone, adresse, ville, code_postal, actif) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$nom, $prenom, $email, $hashedPassword, $role, $telephone, $adresse, $ville, $code_postal, $actif])) {
                echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la création']);
            }
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'student';
            $password = $_POST['password'] ?? '';
            $actif = isset($_POST['actif']) ? 1 : 0;
            $telephone = trim($_POST['telephone'] ?? '');
            $adresse = trim($_POST['adresse'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $code_postal = trim($_POST['code_postal'] ?? '');

            if ($id <= 0 || empty($nom) || empty($prenom) || empty($email)) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
                exit();
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Email invalide']);
                exit();
            }

            if (!in_array($role, ['student', 'teacher', 'admin'])) {
                echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
                exit();
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
                exit();
            }

            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, email = ?, role = ?, password = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, actif = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nom, $prenom, $email, $role, $hashedPassword, $telephone, $adresse, $ville, $code_postal, $actif, $id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nom = ?, prenom = ?, email = ?, role = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, actif = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nom, $prenom, $email, $role, $telephone, $adresse, $ville, $code_postal, $actif, $id]);
            }

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Utilisateur modifié avec succès']);
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

            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas supprimer votre propre compte']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");

            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
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

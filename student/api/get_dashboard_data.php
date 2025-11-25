<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/config.php';

safe_session_start();
if (!is_student()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès refusé']));
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'upcoming_reservations':
            $stmt = $conn->prepare("
                SELECT
                    r.id_reservation,
                    r.statut_reservation,
                    r.mode_choisi,
                    c.date_debut,
                    c.date_fin,
                    c.lieu,
                    CONCAT(u.prenom, ' ', u.nom) as nom_professeur,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                JOIN users u ON c.id_utilisateur = u.id
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE r.id_utilisateur = ?
                    AND r.statut_reservation IN ('en_attente', 'confirmee')
                    AND c.date_debut >= NOW()
                ORDER BY c.date_debut ASC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $reservations = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $reservations]);
            break;

        case 'profile_completion':
            $stmt = $conn->prepare("
                SELECT telephone, adresse, ville, code_postal, bio, photo_url
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            $fields = ['telephone', 'adresse', 'ville', 'code_postal', 'bio', 'photo_url'];
            $completed = 0;
            $missing = [];

            foreach ($fields as $field) {
                if (!empty($profile[$field])) {
                    $completed++;
                } else {
                    $missing[] = $field;
                }
            }

            $percentage = round(($completed / count($fields)) * 100);

            $fieldNames = [
                'telephone' => 'Téléphone',
                'adresse' => 'Adresse',
                'ville' => 'Ville',
                'code_postal' => 'Code postal',
                'bio' => 'Biographie',
                'photo_url' => 'Photo de profil'
            ];

            $missingTranslated = array_map(function ($field) use ($fieldNames) {
                return $fieldNames[$field] ?? $field;
            }, $missing);

            echo json_encode([
                'success' => true,
                'data' => [
                    'percentage' => $percentage,
                    'completed' => $completed,
                    'total' => count($fields),
                    'missing' => $missingTranslated
                ]
            ]);
            break;

        case 'stats':
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_completed
                FROM reservation
                WHERE id_utilisateur = ? AND statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $completed = $stmt->fetch()['total_completed'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)), 0) as total_hours
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                WHERE r.id_utilisateur = ? AND r.statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $hours = $stmt->fetch()['total_hours'];

            $stmt = $conn->prepare("
                SELECT m.nom as matiere, COUNT(*) as count
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE r.id_utilisateur = ?
                GROUP BY m.id_matiere
                ORDER BY count DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $favorite = $stmt->fetch();

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(montant_ttc), 0) as total_spent
                FROM reservation
                WHERE id_utilisateur = ? AND statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $spent = $stmt->fetch()['total_spent'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'completed_courses' => $completed,
                    'total_hours' => $hours,
                    'favorite_subject' => $favorite['matiere'] ?? 'Aucune',
                    'total_spent' => number_format($spent, 2, ',', ' ')
                ]
            ]);
            break;

        case 'all':
            $stmt = $conn->prepare("
                SELECT
                    r.id_reservation,
                    r.statut_reservation,
                    r.mode_choisi,
                    c.date_debut,
                    c.date_fin,
                    c.lieu,
                    CONCAT(u.prenom, ' ', u.nom) as nom_professeur,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                JOIN users u ON c.id_utilisateur = u.id
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE r.id_utilisateur = ?
                    AND r.statut_reservation IN ('en_attente', 'confirmee')
                    AND c.date_debut >= NOW()
                ORDER BY c.date_debut ASC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $reservations = $stmt->fetchAll();

            $stmt = $conn->prepare("
                SELECT telephone, adresse, ville, code_postal, bio, photo_url
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            $fields = ['telephone', 'adresse', 'ville', 'code_postal', 'bio', 'photo_url'];
            $completed = 0;
            $missing = [];

            foreach ($fields as $field) {
                if (!empty($profile[$field])) {
                    $completed++;
                } else {
                    $missing[] = $field;
                }
            }

            $percentage = round(($completed / count($fields)) * 100);

            $fieldNames = [
                'telephone' => 'Téléphone',
                'adresse' => 'Adresse',
                'ville' => 'Ville',
                'code_postal' => 'Code postal',
                'bio' => 'Biographie',
                'photo_url' => 'Photo de profil'
            ];

            $missingTranslated = array_map(function ($field) use ($fieldNames) {
                return $fieldNames[$field] ?? $field;
            }, $missing);

            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_completed
                FROM reservation
                WHERE id_utilisateur = ? AND statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $completedCourses = $stmt->fetch()['total_completed'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)), 0) as total_hours
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                WHERE r.id_utilisateur = ? AND r.statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $hours = $stmt->fetch()['total_hours'];

            $stmt = $conn->prepare("
                SELECT m.nom as matiere, COUNT(*) as count
                FROM reservation r
                JOIN creneau c ON r.id_creneau = c.id_creneau
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE r.id_utilisateur = ?
                GROUP BY m.id_matiere
                ORDER BY count DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $favorite = $stmt->fetch();

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(montant_ttc), 0) as total_spent
                FROM reservation
                WHERE id_utilisateur = ? AND statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $spent = $stmt->fetch()['total_spent'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'reservations' => $reservations,
                    'profile' => [
                        'percentage' => $percentage,
                        'completed' => $completed,
                        'total' => count($fields),
                        'missing' => $missingTranslated
                    ],
                    'stats' => [
                        'completed_courses' => $completedCourses,
                        'total_hours' => $hours,
                        'favorite_subject' => $favorite['matiere'] ?? 'Aucune',
                        'total_spent' => number_format($spent, 2, ',', ' ')
                    ]
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

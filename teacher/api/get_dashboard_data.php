<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/config.php';

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
            $stmt = $conn->prepare("
                SELECT
                    r.id_reservation,
                    r.statut_reservation,
                    r.mode_choisi,
                    c.date_debut,
                    c.date_fin,
                    c.lieu,
                    CONCAT(u.prenom, ' ', u.nom) as nom_etudiant,
                    u.email as email_etudiant,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                JOIN users u ON r.id_utilisateur = u.id
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('en_attente', 'confirmee')
                    AND c.date_debut >= NOW()
                ORDER BY c.date_debut ASC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $sessions]);
            break;

        case 'stats':
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_reservations
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('confirmee', 'terminee')
            ");
            $stmt->execute([$userId]);
            $totalReservations = $stmt->fetch()['total_reservations'];

            $stmt = $conn->prepare("
                SELECT COALESCE(AVG(a.note), 0) as avg_rating, COUNT(a.id_avis) as total_reviews
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                LEFT JOIN avis a ON r.id_reservation = a.id_reservation
                WHERE c.id_utilisateur = ?
            ");
            $stmt->execute([$userId]);
            $rating = $stmt->fetch();

            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT r.id_utilisateur) as total_students
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('confirmee', 'terminee')
            ");
            $stmt->execute([$userId]);
            $totalStudents = $stmt->fetch()['total_students'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(r.montant_ttc), 0) as monthly_revenue
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation = 'terminee'
                    AND MONTH(c.date_debut) = MONTH(CURRENT_DATE())
                    AND YEAR(c.date_debut) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$userId]);
            $monthlyRevenue = $stmt->fetch()['monthly_revenue'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)), 0) as total_hours
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $totalHours = $stmt->fetch()['total_hours'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'total_reservations' => $totalReservations,
                    'avg_rating' => round($rating['avg_rating'], 1),
                    'total_reviews' => $rating['total_reviews'],
                    'total_students' => $totalStudents,
                    'monthly_revenue' => number_format($monthlyRevenue, 2, ',', ' '),
                    'total_hours' => $totalHours
                ]
            ]);
            break;

        case 'available_slots':
            $stmt = $conn->prepare("
                SELECT
                    c.id_creneau,
                    c.date_debut,
                    c.date_fin,
                    c.statut_creneau,
                    c.mode_propose,
                    c.lieu,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM creneau c
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE c.id_utilisateur = ?
                    AND c.statut_creneau = 'disponible'
                    AND c.date_debut >= NOW()
                    AND c.date_debut <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY c.date_debut ASC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $slots = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $slots]);
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
                    CONCAT(u.prenom, ' ', u.nom) as nom_etudiant,
                    u.email as email_etudiant,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                JOIN users u ON r.id_utilisateur = u.id
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('en_attente', 'confirmee')
                    AND c.date_debut >= NOW()
                ORDER BY c.date_debut ASC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $sessions = $stmt->fetchAll();

            $stmt = $conn->prepare("
                SELECT COUNT(*) as total_reservations
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('confirmee', 'terminee')
            ");
            $stmt->execute([$userId]);
            $totalReservations = $stmt->fetch()['total_reservations'];

            $stmt = $conn->prepare("
                SELECT COALESCE(AVG(a.note), 0) as avg_rating, COUNT(a.id_avis) as total_reviews
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                LEFT JOIN avis a ON r.id_reservation = a.id_reservation
                WHERE c.id_utilisateur = ?
            ");
            $stmt->execute([$userId]);
            $rating = $stmt->fetch();

            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT r.id_utilisateur) as total_students
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation IN ('confirmee', 'terminee')
            ");
            $stmt->execute([$userId]);
            $totalStudents = $stmt->fetch()['total_students'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(r.montant_ttc), 0) as monthly_revenue
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation = 'terminee'
                    AND MONTH(c.date_debut) = MONTH(CURRENT_DATE())
                    AND YEAR(c.date_debut) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute([$userId]);
            $monthlyRevenue = $stmt->fetch()['monthly_revenue'];

            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(TIMESTAMPDIFF(HOUR, c.date_debut, c.date_fin)), 0) as total_hours
                FROM creneau c
                JOIN reservation r ON c.id_creneau = r.id_creneau
                WHERE c.id_utilisateur = ?
                    AND r.statut_reservation = 'terminee'
            ");
            $stmt->execute([$userId]);
            $totalHours = $stmt->fetch()['total_hours'];

            $stmt = $conn->prepare("
                SELECT
                    c.id_creneau,
                    c.date_debut,
                    c.date_fin,
                    c.statut_creneau,
                    c.mode_propose,
                    c.lieu,
                    oc.titre as titre_cours,
                    m.nom as nom_matiere
                FROM creneau c
                JOIN offre_cours oc ON c.id_offre = oc.id_offre
                JOIN matiere m ON oc.id_matiere = m.id_matiere
                WHERE c.id_utilisateur = ?
                    AND c.statut_creneau = 'disponible'
                    AND c.date_debut >= NOW()
                    AND c.date_debut <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY c.date_debut ASC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $slots = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => [
                    'sessions' => $sessions,
                    'stats' => [
                        'total_reservations' => $totalReservations,
                        'avg_rating' => round($rating['avg_rating'], 1),
                        'total_reviews' => $rating['total_reviews'],
                        'total_students' => $totalStudents,
                        'monthly_revenue' => number_format($monthlyRevenue, 2, ',', ' '),
                        'total_hours' => $totalHours
                    ],
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

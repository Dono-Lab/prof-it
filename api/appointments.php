<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';

safe_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $userId, $userRole);
            break;

        case 'POST':
            handlePostRequest($conn, $userId, $userRole);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}

function handleGetRequest($conn, $userId, $userRole)
{
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'available_slots':
            getAvailableSlots($conn, $userId, $userRole);
            break;

        case 'upcoming_appointments':
            getUpcomingAppointments($conn, $userId, $userRole);
            break;

        case 'history_appointments':
            getHistoryAppointments($conn, $userId, $userRole);
            break;

        case 'stats':
            getStats($conn, $userId, $userRole);
            break;

        case 'teachers':
            getTeachers($conn);
            break;

        case 'search_courses':
            searchCourses($conn, $userId, $userRole);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function handlePostRequest($conn, $userId, $userRole)
{
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'book_slot':
            if ($userRole !== 'student') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Seuls les étudiants peuvent réserver']);
                return;
            }
            bookSlot($conn, $userId);
            break;

        case 'create_slot':
            if ($userRole !== 'teacher') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Seuls les enseignants peuvent créer des créneaux']);
                return;
            }
            createSlot($conn, $userId);
            break;

        case 'update_status':
            if ($userRole !== 'teacher') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Action réservée aux enseignants']);
                return;
            }
            updateCourseStatus($conn, $userId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
}

function getAvailableSlots($conn, $userId, $userRole)
{
    $offerId = isset($_GET['offre_id']) ? (int)$_GET['offre_id'] : 0;
    $offerSql = '';
    $offerParams = [];
    if ($offerId > 0) {
        $offerSql = ' AND o.id_offre = ? ';
        $offerParams[] = $offerId;
    }

    if ($userRole === 'student') {
        $sql = "
            SELECT
                c.id_creneau,
                c.date_debut,
                c.date_fin,
                c.tarif_horaire,
                c.mode_propose,
                c.lieu,
                o.titre as titre_cours,
                o.id_offre,
                m.nom_matiere,
                m.icone as matiere_icone,
                CONCAT(prof.prenom, ' ', prof.nom) as nom_professeur,
                prof.photo_url as photo_professeur,
                prof.id as id_professeur
            FROM creneau c
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            INNER JOIN users prof ON c.id_utilisateur = prof.id
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE c.statut_creneau = 'disponible'
            AND c.date_debut > NOW()
            {$offerSql}
            AND prof.role = 'teacher'
            ORDER BY c.date_debut ASC
            LIMIT 20
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($offerParams);
    } else {
        $sql = "
            SELECT
                c.id_creneau,
                c.date_debut,
                c.date_fin,
                c.tarif_horaire,
                c.mode_propose,
                c.lieu,
                o.titre as titre_cours,
                m.nom_matiere
            FROM creneau c
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE c.id_utilisateur = ?
            AND c.statut_creneau = 'disponible'
            AND c.date_debut > NOW()
            {$offerSql}
            ORDER BY c.date_debut ASC
            LIMIT 20
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_merge([$userId], $offerParams));
    }

    $slots = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'slots' => $slots
    ]);
}

function getUpcomingAppointments($conn, $userId, $userRole)
{
    if ($userRole === 'student') {
        $sql = "
            SELECT
                r.id_reservation,
                r.statut_reservation,
                r.mode_choisi,
                c.date_debut,
                c.date_fin,
                c.lieu,
                o.titre as titre_cours,
                m.nom_matiere,
                CONCAT(prof.prenom, ' ', prof.nom) as nom_professeur,
                prof.photo_url as photo_professeur
            FROM reservation r
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            INNER JOIN users prof ON c.id_utilisateur = prof.id
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE r.id_utilisateur = ?
            AND (
                (r.statut_reservation IN ('confirmee', 'en_attente') AND c.date_fin >= NOW())
                OR (r.statut_reservation = 'en_cours' AND c.date_debut <= NOW() AND c.date_fin >= NOW())
            )
            ORDER BY c.date_debut ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
    } else {
        $sql = "
            SELECT
                r.id_reservation,
                r.statut_reservation,
                r.mode_choisi,
                c.date_debut,
                c.date_fin,
                c.lieu,
                o.titre as titre_cours,
                m.nom_matiere,
                CONCAT(etudiant.prenom, ' ', etudiant.nom) as nom_etudiant,
                etudiant.photo_url as photo_etudiant
            FROM creneau c
            INNER JOIN reservation r ON c.id_creneau = r.id_creneau
            INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE c.id_utilisateur = ?
            AND (
                (r.statut_reservation IN ('confirmee', 'en_attente') AND c.date_fin >= NOW())
                OR (r.statut_reservation = 'en_cours' AND c.date_debut <= NOW() AND c.date_fin >= NOW())
            )
            ORDER BY c.date_debut ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
    }

    $appointments = $stmt->fetchAll();
    foreach ($appointments as &$appointment) {
        $appointment['statut_cours'] = compute_course_status(
            $appointment['date_debut'],
            $appointment['date_fin'],
            $appointment['statut_reservation']
        );
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
}

function getHistoryAppointments($conn, $userId, $userRole)
{
    if ($userRole === 'student') {
        $sql = "
            SELECT
                r.id_reservation,
                r.statut_reservation,
                r.mode_choisi,
                c.date_debut,
                c.date_fin,
                c.lieu,
                o.titre as titre_cours,
                m.nom_matiere,
                CONCAT(prof.prenom, ' ', prof.nom) as nom_professeur,
                prof.photo_url as photo_professeur
            FROM reservation r
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            INNER JOIN users prof ON c.id_utilisateur = prof.id
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE r.id_utilisateur = ?
            AND (r.statut_reservation = 'terminee' OR c.date_fin < NOW())
            ORDER BY c.date_debut DESC
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
    } else {
        $sql = "
            SELECT
                r.id_reservation,
                r.statut_reservation,
                r.mode_choisi,
                c.date_debut,
                c.date_fin,
                c.lieu,
                o.titre as titre_cours,
                m.nom_matiere,
                CONCAT(etudiant.prenom, ' ', etudiant.nom) as nom_etudiant,
                etudiant.photo_url as photo_etudiant
            FROM creneau c
            INNER JOIN reservation r ON c.id_creneau = r.id_creneau
            INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id
            INNER JOIN offre_cours o ON c.id_offre = o.id_offre
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            WHERE c.id_utilisateur = ?
            AND (r.statut_reservation = 'terminee' OR c.date_fin < NOW())
            ORDER BY c.date_debut DESC
            LIMIT 10
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$userId]);
    }

    $appointments = $stmt->fetchAll();
    foreach ($appointments as &$appointment) {
        $appointment['statut_cours'] = compute_course_status(
            $appointment['date_debut'],
            $appointment['date_fin'],
            $appointment['statut_reservation']
        );
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
}

function searchCourses($conn, $userId, $userRole)
{
    $query = trim($_GET['query'] ?? '');
    $scope = $_GET['scope'] ?? 'public';

    $filterSql = '';
    $params = [];
    if ($query !== '') {
        $filterSql = " AND (o.titre LIKE ? OR m.nom_matiere LIKE ?) ";
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $ownerSql = '';
    if ($scope === 'teacher' && $userRole === 'teacher') {
        $ownerSql = " AND e.id_utilisateur = ? ";
        $params[] = $userId;
    }

    $sql = "
        SELECT
            o.id_offre,
            o.titre,
            COALESCE(NULLIF(m.nom_matiere, ''), o.titre) as nom_matiere,
            CONCAT(prof.prenom, ' ', prof.nom) as nom_professeur,
            prof.photo_url as photo_professeur,
            MIN(c.tarif_horaire) as tarif_min,
            GROUP_CONCAT(DISTINCT c.mode_propose) as modes,
            COUNT(c.id_creneau) as total_slots
        FROM offre_cours o
        INNER JOIN enseigner e ON e.id_offre = o.id_offre AND e.actif = 1
        INNER JOIN users prof ON e.id_utilisateur = prof.id
        LEFT JOIN couvrir co ON o.id_offre = co.id_offre
        LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
        INNER JOIN creneau c ON c.id_offre = o.id_offre
        WHERE prof.role = 'teacher'
          AND c.statut_creneau = 'disponible'
          AND c.date_debut > NOW()
          {$filterSql}
          {$ownerSql}
        GROUP BY o.id_offre
        ORDER BY total_slots DESC
        LIMIT 20
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'courses' => $courses
    ]);
}

function updateCourseStatus($conn, $teacherId)
{
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    $newStatus = $_POST['statut'] ?? '';

    if (!$reservationId || !in_array($newStatus, ['en_attente', 'confirmee', 'terminee', 'annulee', 'en_cours'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT r.id_reservation, r.statut_reservation, c.date_debut, c.date_fin
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneau
        WHERE r.id_reservation = ? AND c.id_utilisateur = ?
    ");
    $stmt->execute([$reservationId, $teacherId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Réservation introuvable']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservation SET statut_reservation = ? WHERE id_reservation = ?");
    $stmt->execute([$newStatus, $reservationId]);

    $statusCourse = compute_course_status($reservation['date_debut'], $reservation['date_fin'], $newStatus);

    echo json_encode([
        'success' => true,
        'reservation_id' => $reservationId,
        'statut_reservation' => $newStatus,
        'statut_cours' => $statusCourse
    ]);
}

function getStats($conn, $userId, $userRole)
{
    if ($userRole === 'student') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM reservation r
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            WHERE r.id_utilisateur = ?
            AND r.statut_reservation IN ('confirmee', 'terminee')
            AND MONTH(c.date_debut) = MONTH(CURRENT_DATE())
            AND YEAR(c.date_debut) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$userId]);
        $coursMonth = (int)$stmt->fetch()['count'];

        $stmt = $conn->prepare("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, c.date_debut, c.date_fin) / 60) as total_heures
            FROM reservation r
            INNER JOIN creneau c ON r.id_creneau = c.id_creneau
            WHERE r.id_utilisateur = ?
            AND r.statut_reservation IN ('confirmee', 'terminee')
        ");
        $stmt->execute([$userId]);
        $totalHeures = round($stmt->fetch()['total_heures'] ?? 0, 0);

        echo json_encode([
            'success' => true,
            'stats' => [
                'cours_month' => $coursMonth,
                'total_heures' => $totalHeures
            ]
        ]);
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM creneau c
            INNER JOIN reservation r ON c.id_creneau = r.id_creneau
            WHERE c.id_utilisateur = ?
            AND r.statut_reservation IN ('confirmee', 'terminee')
            AND MONTH(c.date_debut) = MONTH(CURRENT_DATE())
            AND YEAR(c.date_debut) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute([$userId]);
        $sessionsMonth = (int)$stmt->fetch()['count'];

        $stmt = $conn->prepare("
            SELECT SUM(TIMESTAMPDIFF(MINUTE, c.date_debut, c.date_fin) / 60) as total_heures
            FROM creneau c
            INNER JOIN reservation r ON c.id_creneau = r.id_creneau
            WHERE c.id_utilisateur = ?
            AND r.statut_reservation IN ('confirmee', 'terminee')
        ");
        $stmt->execute([$userId]);
        $totalHeures = round($stmt->fetch()['total_heures'] ?? 0, 0);

        echo json_encode([
            'success' => true,
            'stats' => [
                'sessions_month' => $sessionsMonth,
                'total_heures' => $totalHeures
            ]
        ]);
    }
}

function getTeachers($conn)
{
    $sql = "
        SELECT DISTINCT
            u.id,
            CONCAT(u.prenom, ' ', u.nom) as nom_complet,
            m.nom_matiere,
            o.id_offre,
            o.titre
        FROM users u
        INNER JOIN offre_cours o ON u.id = o.id_utilisateur
        LEFT JOIN couvrir c ON o.id_offre = c.id_offre
        LEFT JOIN matiere m ON c.id_matiere = m.id_matiere
        WHERE u.role = 'teacher'
        AND o.statut_offre = 'active'
        ORDER BY u.nom, u.prenom
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $teachers = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'teachers' => $teachers
    ]);
}

function bookSlot($conn, $userId)
{
    $creneauId = (int)($_POST['creneau_id'] ?? 0);
    $modeChoisi = trim($_POST['mode_choisi'] ?? '');

    if ($creneauId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Créneau invalide']);
        return;
    }

    if (!in_array($modeChoisi, ['presentiel', 'visio', 'domicile'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mode invalide']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT c.id_creneau, c.tarif_horaire, c.mode_propose,
               TIMESTAMPDIFF(MINUTE, c.date_debut, c.date_fin) / 60 as duree_heures
        FROM creneau c
        WHERE c.id_creneau = ?
        AND c.statut_creneau = 'disponible'
        AND c.date_debut > NOW()
    ");
    $stmt->execute([$creneauId]);
    $creneau = $stmt->fetch();

    if (!$creneau) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Créneau non disponible']);
        return;
    }

    $modesPropose = array_filter(array_map('trim', explode(',', $creneau['mode_propose'])));
    if (!in_array($modeChoisi, $modesPropose, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ce mode n\'est pas proposé pour ce créneau']);
        return;
    }

    $prixHoraire = $creneau['tarif_horaire'];
    $dureeHeures = $creneau['duree_heures'];
    $montantHT = $prixHoraire * $dureeHeures;
    $tauxTVA = 20.00;
    $montantTTC = $montantHT * (1 + $tauxTVA / 100);

    $sql = "
        INSERT INTO reservation
        (id_utilisateur, id_creneau, mode_choisi, prix_fige, tva, statut_reservation)
        VALUES (?, ?, ?, ?, ?, 'en_attente')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $userId,
        $creneauId,
        $modeChoisi,
        $montantHT,
        $tauxTVA
    ]);

    $reservationId = $conn->lastInsertId();

    $conversationSql = "
        INSERT INTO conversation (id_reservation)
        SELECT ? FROM DUAL
        WHERE NOT EXISTS (
            SELECT 1 FROM conversation WHERE id_reservation = ?
        )
    ";
    $stmt = $conn->prepare($conversationSql);
    $stmt->execute([$reservationId, $reservationId]);

    $updateSql = "UPDATE creneau SET statut_creneau = 'reserve' WHERE id_creneau = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->execute([$creneauId]);

    echo json_encode([
        'success' => true,
        'message' => 'Réservation créée avec succès',
        'reservation_id' => $reservationId
    ]);
}

function createSlot($conn, $userId)
{
    $offreId = (int)($_POST['offre_id'] ?? 0);
    $dateDebut = trim($_POST['date_debut'] ?? '');
    $dateFin = trim($_POST['date_fin'] ?? '');
    $tarifHoraire = floatval($_POST['tarif_horaire'] ?? 0);
    $modePropose = trim($_POST['mode_propose'] ?? '');
    $lieu = trim($_POST['lieu'] ?? '');

    if ($offreId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Offre invalide']);
        return;
    }

    if (empty($dateDebut) || empty($dateFin)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dates requises']);
        return;
    }

    if ($tarifHoraire <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tarif invalide']);
        return;
    }

    if (empty($modePropose)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mode de cours requis']);
        return;
    }

    $allowedModes = ['presentiel', 'visio', 'domicile'];
    $modes = array_filter(array_map('trim', explode(',', $modePropose)));
    $modes = array_values(array_unique(array_intersect($modes, $allowedModes)));
    if (empty($modes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Mode de cours invalide']);
        return;
    }
    $modePropose = implode(',', $modes);

    $stmt = $conn->prepare("
        SELECT id
        FROM enseigner
        WHERE id_offre = ? AND id_utilisateur = ? AND actif = 1
    ");
    $stmt->execute([$offreId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Offre non autoris�e']);
        return;
    }

    $startObj = DateTime::createFromFormat('Y-m-d\TH:i', $dateDebut);
    $endObj = DateTime::createFromFormat('Y-m-d\TH:i', $dateFin);
    if ($startObj === false || $endObj === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format de date invalide']);
        return;
    }

    $startTs = $startObj->getTimestamp();
    $endTs = $endObj->getTimestamp();
    if ($startTs <= time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La date de d�but doit �tre dans le futur']);
        return;
    }

    if ($endTs <= $startTs) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La date de fin doit �tre apr�s la date de d�but']);
        return;
    }

    $dateDebutSql = $startObj->format('Y-m-d H:i:s');
    $dateFinSql = $endObj->format('Y-m-d H:i:s');

    $sql = "
        INSERT INTO creneau
        (id_utilisateur, id_offre, date_debut, date_fin, tarif_horaire, mode_propose, lieu, statut_creneau)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'disponible')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $userId,
        $offreId,
        $dateDebutSql,
        $dateFinSql,
        $tarifHoraire,
        $modePropose,
        $lieu
    ]);

    $creneauId = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Créneau créé avec succès',
        'creneau_id' => $creneauId
    ]);
}

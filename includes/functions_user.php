<?php
/**
 * Récupère l'URL de l'avatar utilisateur
 * @param int $user_id ID de l'utilisateur
 * @param PDO $conn Connexion à la base de données
 * @return string URL de l'avatar
 */
function get_user_avatar($user_id, $conn) {
    $stmt = $conn->prepare("SELECT photo_url, prenom, nom FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user && !empty($user['photo_url'])) {
        return '../' . ltrim($user['photo_url'], '/');
    }

    // Avatar par défaut avec UI Avatars
    $name = urlencode(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? 'Utilisateur'));
    return "https://ui-avatars.com/api/?name=$name&background=6366f1&color=fff";
}

/**
 * Récupère les statistiques d'un étudiant
 * @param int $user_id ID de l'étudiant
 * @param PDO $conn Connexion à la base de données
 * @return array Tableau des statistiques
 */
function get_student_stats($user_id, $conn) {
    $stats = [
        'cours_termines' => 0,
        'heures_total' => 0,
        'matiere_preferee' => 'N/A',
        'depenses_total' => 0
    ];

    // Nombre de cours terminés
    $stmt = $conn->prepare("
        SELECT COUNT(*) as nb_cours
        FROM reservation
        WHERE id_utilisateur = ? AND statut_reservation = 'terminee'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['cours_termines'] = (int)$result['nb_cours'];

    $stmt = $conn->prepare("
        SELECT
            SUM(TIMESTAMPDIFF(MINUTE, c.date_debut, c.date_fin) / 60) as total_heures,
            SUM(r.montant_ttc) as total_depense
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneau
        WHERE r.id_utilisateur = ? AND r.statut_reservation IN ('confirmee', 'terminee')
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['heures_total'] = round($result['total_heures'] ?? 0, 1);
    $stats['depenses_total'] = round($result['total_depense'] ?? 0, 2);

    $stmt = $conn->prepare("
        SELECT m.nom_matiere, COUNT(*) as nb_cours
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneau
        INNER JOIN offre_cours o ON c.id_offre = o.id_offre
        INNER JOIN couvrir co ON o.id_offre = co.id_offre
        INNER JOIN matiere m ON co.id_matiere = m.id_matiere
        WHERE r.id_utilisateur = ? AND r.statut_reservation IN ('confirmee', 'terminee')
        GROUP BY m.id_matiere
        ORDER BY nb_cours DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $stats['matiere_preferee'] = $result['nom_matiere'];
    }

    return $stats;
}

/**
 * Récupère les statistiques d'un professeur
 * @param int $user_id ID du professeur
 * @param PDO $conn Connexion à la base de données
 * @return array Tableau des statistiques
 */
function get_teacher_stats($user_id, $conn) {
    $stats = [
        'nb_etudiants' => 0,
        'nb_reservations' => 0,
        'note_moyenne' => 0,
        'nb_avis' => 0,
        'heures_donnees' => 0,
        'revenus_total' => 0
    ];

    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT r.id_utilisateur) as nb_etudiants,
            COUNT(*) as nb_reservations
        FROM creneau c
        INNER JOIN reservation r ON c.id_creneau = r.id_creneau
        WHERE c.id_utilisateur = ? AND r.statut_reservation IN ('confirmee', 'terminee')
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['nb_etudiants'] = (int)$result['nb_etudiants'];
    $stats['nb_reservations'] = (int)$result['nb_reservations'];

    $stmt = $conn->prepare("
        SELECT
            AVG(a.note) as note_moyenne,
            COUNT(*) as nb_avis
        FROM creneau c
        INNER JOIN reservation r ON c.id_creneau = r.id_creneau
        INNER JOIN avis a ON r.id_reservation = a.id_reservation
        WHERE c.id_utilisateur = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['note_moyenne'] = round($result['note_moyenne'] ?? 0, 1);
    $stats['nb_avis'] = (int)$result['nb_avis'];

    $stmt = $conn->prepare("
        SELECT
            SUM(TIMESTAMPDIFF(MINUTE, c.date_debut, c.date_fin) / 60) as total_heures,
            SUM(r.prix_fige) as total_revenus
        FROM creneau c
        INNER JOIN reservation r ON c.id_creneau = r.id_creneau
        WHERE c.id_utilisateur = ? AND r.statut_reservation IN ('confirmee', 'terminee')
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $stats['heures_donnees'] = round($result['total_heures'] ?? 0, 1);
    $stats['revenus_total'] = round($result['total_revenus'] ?? 0, 2);

    return $stats;
}

/**
 * Récupère les prochains cours d'un étudiant
 * @param int $user_id ID de l'étudiant
 * @param PDO $conn Connexion à la base de données
 * @param int $limit Nombre maximum de cours à retourner
 * @return array Liste des prochains cours
 */
function get_student_upcoming_courses($user_id, $conn, $limit = 5) {
    $limit = (int)$limit; // Sécurisation du paramètre
    $stmt = $conn->prepare("
        SELECT
            r.id_reservation,
            r.statut_reservation,
            c.date_debut,
            c.date_fin,
            c.mode_propose,
            c.lieu,
            o.titre as titre_cours,
            m.nom_matiere,
            m.icone as matiere_icone,
            CONCAT(prof.prenom, ' ', prof.nom) as nom_professeur,
            prof.photo_url as photo_professeur
        FROM reservation r
        INNER JOIN creneau c ON r.id_creneau = c.id_creneau
        INNER JOIN users prof ON c.id_utilisateur = prof.id
        INNER JOIN offre_cours o ON c.id_offre = o.id_offre
        LEFT JOIN couvrir co ON o.id_offre = co.id_offre
        LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
        WHERE r.id_utilisateur = ?
        AND r.statut_reservation = 'confirmee'
        AND c.date_debut > NOW()
        ORDER BY c.date_debut ASC
        LIMIT $limit
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Récupère les prochaines sessions d'un professeur
 * @param int $user_id ID du professeur
 * @param PDO $conn Connexion à la base de données
 * @param int $limit Nombre maximum de sessions à retourner
 * @return array Liste des prochaines sessions
 */
function get_teacher_upcoming_sessions($user_id, $conn, $limit = 5) {
    $limit = (int)$limit; 
    $stmt = $conn->prepare("
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
        AND r.statut_reservation = 'confirmee'
        AND c.date_debut > NOW()
        ORDER BY c.date_debut ASC
        LIMIT $limit
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Récupère les créneaux disponibles d'un professeur
 * @param int $user_id ID du professeur
 * @param PDO $conn Connexion à la base de données
 * @param int $limit Nombre maximum de créneaux à retourner
 * @return array Liste des créneaux disponibles
 */
function get_teacher_available_slots($user_id, $conn, $limit = 5) {
    $limit = (int)$limit; // Sécurisation du paramètre
    $stmt = $conn->prepare("
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
        ORDER BY c.date_debut ASC
        LIMIT $limit
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Calcule le pourcentage de complétion du profil
 * @param int $user_id ID de l'utilisateur
 * @param PDO $conn Connexion à la base de données
 * @return int Pourcentage de complétion (0-100)
 */
function get_profile_completion($user_id, $conn) {
    $stmt = $conn->prepare("
        SELECT nom, prenom, email, telephone, adresse, ville, code_postal, bio, photo_url
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        return 0;
    }

    $fields = ['nom', 'prenom', 'email', 'telephone', 'adresse', 'ville', 'code_postal', 'bio', 'photo_url'];
    $filled = 0;
    $total = count($fields);

    foreach ($fields as $field) {
        if (!empty($user[$field])) {
            $filled++;
        }
    }

    return round(($filled / $total) * 100);
}

/**
 * Formate une date en français
 * @param string $date Date au format SQL
 * @return string Date formatée
 */
function format_date_fr($date) {
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    $jour_semaine = $jours[date('w', $timestamp)];
    $jour = date('j', $timestamp);
    $mois_nom = $mois[(int)date('n', $timestamp)];
    $heure = date('H:i', $timestamp);

    return "$jour_semaine $jour $mois_nom à $heure";
}

/**
 * Formate une date en format relatif (Il y a X heures/jours)
 * @param string $dateStr Date au format SQL
 * @return string Date relative formatée
 */
function format_relative_date($dateStr) {
    $date = strtotime($dateStr);
    $now = time();
    $diff = $now - $date;

    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Il y a $minutes minute" . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a $hours heure" . ($hours > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a $days jour" . ($days > 1 ? 's' : '');
    } else {
        $weeks = floor($diff / 604800);
        return "Il y a $weeks semaine" . ($weeks > 1 ? 's' : '');
    }
}

/**
 * Retourne la classe Bootstrap pour une priorité
 * @param string
 * @return string 
 */
function get_priority_color($priority) {
    $colors = [
        'basse' => 'secondary',
        'normale' => 'info',
        'haute' => 'warning',
        'urgente' => 'danger'
    ];
    return $colors[$priority] ?? 'secondary';
}

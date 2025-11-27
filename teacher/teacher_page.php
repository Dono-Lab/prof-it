<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('teacher');

$prenom = $_SESSION['prenom'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$pageTitle = 'Espace enseignant';
$currentNav = 'teacher_home';

// Récupération des statistiques
$stats = get_teacher_stats($userId, $conn);
$upcomingSessions = get_teacher_upcoming_sessions($userId, $conn, 3);
$availableSlots = get_teacher_available_slots($userId, $conn, 3);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php require __DIR__ . '/../templates/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <h1 class="page-title">
                <i class="fas fa-chalkboard-teacher me-2"></i>
                Bienvenue, <?= htmlspecialchars(ucfirst($prenom), ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon blue mb-2">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-0"><?= $stats['nb_etudiants'] ?></h3>
                        <p class="text-muted small mb-0">Étudiants</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon green mb-2">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="mb-0"><?= $stats['nb_reservations'] ?></h3>
                        <p class="text-muted small mb-0">Réservations</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon yellow mb-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="mb-0"><?= $stats['note_moyenne'] ?>/5</h3>
                        <p class="text-muted small mb-0">Note moyenne</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon cyan mb-2">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <h3 class="mb-0"><?= $stats['nb_avis'] ?></h3>
                        <p class="text-muted small mb-0">Avis</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon purple mb-2">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="mb-0"><?= $stats['heures_donnees'] ?>h</h3>
                        <p class="text-muted small mb-0">Heures</p>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 mb-3">
                    <div class="stat-card flex-column">
                        <div class="stat-icon red mb-2">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <h3 class="mb-0"><?= number_format($stats['revenus_total'], 0, ',', ' ') ?>€</h3>
                        <p class="text-muted small mb-0">Revenus</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Sessions à venir -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Sessions à venir</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (empty($upcomingSessions)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                    <p>Aucune session planifiée pour le moment.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcomingSessions as $session): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <?php if (!empty($session['photo_etudiant'])): ?>
                                                        <img src="../<?= htmlspecialchars($session['photo_etudiant']) ?>"
                                                             alt="Étudiant" class="rounded-circle" width="45" height="45" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                             style="width: 45px; height: 45px;">
                                                            <i class="fas fa-user-graduate"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($session['nom_matiere'] ?? $session['titre_cours']) ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-user-graduate me-1"></i>
                                                        <?= htmlspecialchars($session['nom_etudiant']) ?>
                                                    </p>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= format_date_fr($session['date_debut']) ?>
                                                    </p>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-<?= $session['mode_choisi'] === 'visio' ? 'video' : 'map-marker-alt' ?> me-1"></i>
                                                        <?= ucfirst($session['mode_choisi']) ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span class="badge-status confirmed">Confirmé</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card-footer-custom text-center">
                                    <a href="rdv.php" class="btn btn-link">
                                        Voir toutes mes sessions <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Créneaux disponibles -->
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-clock me-2"></i>Mes créneaux disponibles</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (empty($availableSlots)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                    <p>Vous n'avez aucun créneau disponible.</p>
                                    <a href="rdv.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-2"></i>Ajouter des disponibilités
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($availableSlots as $slot): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($slot['nom_matiere'] ?? $slot['titre_cours']) ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= format_date_fr($slot['date_debut']) ?>
                                                    </p>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-euro-sign me-1"></i>
                                                        <?= number_format($slot['tarif_horaire'], 2, ',', ' ') ?>€/h
                                                        •
                                                        <i class="fas fa-<?= strpos($slot['mode_propose'], 'visio') !== false ? 'video' : 'map-marker-alt' ?> ms-1 me-1"></i>
                                                        <?= ucfirst($slot['mode_propose']) ?>
                                                    </p>
                                                </div>
                                                <div>
                                                    <span class="badge-status open">Disponible</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="card-footer-custom text-center">
                                    <a href="rdv.php" class="btn btn-link">
                                        Gérer mes disponibilités <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Raccourcis -->
            <div class="row">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-bolt me-2"></i>Accès rapide</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="rdv.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-calendar-plus me-2"></i>Gérer mes créneaux
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="documents.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-folder me-2"></i>Mes documents
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="messagerie.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-comments me-2"></i>Messagerie
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="support.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-headset me-2"></i>Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

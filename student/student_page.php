<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('student');

$prenom = $_SESSION['prenom'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$pageTitle = 'Espace étudiant';
$currentNav = 'student_home';

// Récupération des statistiques
$stats = get_student_stats($userId, $conn);
$upcomingCourses = get_student_upcoming_courses($userId, $conn, 3);
$profileCompletion = get_profile_completion($userId, $conn);
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
                <i class="fas fa-home me-2"></i>
                Bienvenue, <?= htmlspecialchars(ucfirst($prenom), ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['cours_termines'] ?></h3>
                            <p class="text-muted small mb-0">Cours terminés</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['heures_total'] ?>h</h3>
                            <p class="text-muted small mb-0">Heures de cours</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon yellow">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= htmlspecialchars($stats['matiere_preferee']) ?></h3>
                            <p class="text-muted small mb-0">Matière préférée</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= number_format($stats['depenses_total'], 2, ',', ' ') ?>€</h3>
                            <p class="text-muted small mb-0">Total dépensé</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Prochaines réservations -->
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Mes prochains cours</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (empty($upcomingCourses)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                    <p>Vous n'avez aucun cours planifié pour le moment.</p>
                                    <a href="rdv.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus me-2"></i>Réserver un cours
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcomingCourses as $course): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <?php if (!empty($course['photo_professeur'])): ?>
                                                        <img src="../<?= htmlspecialchars($course['photo_professeur']) ?>"
                                                            alt="Prof" class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= htmlspecialchars($course['nom_matiere'] ?? $course['titre_cours']) ?>
                                                    </h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($course['nom_professeur']) ?>
                                                    </p>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= format_date_fr($course['date_debut']) ?>
                                                    </p>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-<?= $course['mode_propose'] === 'visio' ? 'video' : 'map-marker-alt' ?> me-1"></i>
                                                        <?= ucfirst($course['mode_propose']) ?>
                                                        <?php if ($course['mode_propose'] === 'presentiel' && !empty($course['lieu'])): ?>
                                                            - <?= htmlspecialchars($course['lieu']) ?>
                                                        <?php endif; ?>
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
                                        Voir tous mes rendez-vous <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Complétion du profil -->
                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-user-check me-2"></i>Profil</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="text-center mb-3">
                                <div class="position-relative d-inline-block">
                                    <svg width="120" height="120">
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e9ecef" stroke-width="8" />
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="#10b981" stroke-width="8"
                                            stroke-dasharray="<?= ($profileCompletion * 339.292) / 100 ?> 339.292"
                                            stroke-linecap="round" transform="rotate(-90 60 60)" />
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <h2 class="mb-0 text-primary"><?= $profileCompletion ?>%</h2>
                                    </div>
                                </div>
                            </div>
                            <h6 class="text-center mb-3">Complétion du profil</h6>
                            <?php if ($profileCompletion < 100): ?>
                                <div class="alert alert-info py-2 mb-3">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        Complétez votre profil pour une meilleure expérience
                                    </small>
                                </div>
                                <a href="settings.php" class="btn btn-primary w-100">
                                    <i class="fas fa-edit me-2"></i>Compléter mon profil
                                </a>
                            <?php else: ?>
                                <div class="alert alert-success py-2 mb-0">
                                    <small>
                                        <i class="fas fa-check-circle me-1"></i>
                                        Votre profil est complet !
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Raccourcis -->
                    <div class="card-custom mt-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-bolt me-2"></i>Accès rapide</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="d-grid gap-2">
                                <a href="rdv.php" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Nouveau rendez-vous
                                </a>
                                <a href="documents.php" class="btn btn-outline-primary">
                                    <i class="fas fa-folder me-2"></i>Mes documents
                                </a>
                                <a href="messagerie.php" class="btn btn-outline-primary">
                                    <i class="fas fa-comments me-2"></i>Messagerie
                                </a>
                                <a href="support.php" class="btn btn-outline-primary">
                                    <i class="fas fa-headset me-2"></i>Support
                                </a>
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
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
$hasUpcomingCourses = !empty($upcomingCourses);
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
            <div class="row row-cols-xl-4 row-cols-md-2 row-cols-1 g-3 mb-4 justify-content-center stats-row">
                <div class="col d-flex">
                    <div class="stat-card w-100 h-100">
                        <div class="stat-icon blue">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="stat-courses"><?= $stats['cours_termines'] ?></h3>
                            <p class="text-muted small mb-0">Cours terminés</p>
                        </div>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card w-100 h-100">
                        <div class="stat-icon green">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="stat-hours"><?= $stats['heures_total'] ?>h</h3>
                            <p class="text-muted small mb-0">Heures de cours</p>
                        </div>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card w-100 h-100">
                        <div class="stat-icon yellow">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <h3 class="mb-0" id="stat-favorite"><?= htmlspecialchars($stats['matiere_preferee']) ?></h3>
                            <p class="text-muted small mb-0">Matière préférée</p>
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
                            <div class="text-center py-5 text-muted" id="upcoming-empty" style="<?= $hasUpcomingCourses ? 'display:none;' : '' ?>">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Vous n'avez aucun cours planifié pour le moment.</p>
                                <a href="rdv.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus me-2"></i>Réserver un cours
                                </a>
                            </div>
                            <div class="list-group list-group-flush" id="upcoming-list" style="<?= $hasUpcomingCourses ? '' : 'display:none;' ?>">
                                <?php foreach ($upcomingCourses as $course):
                                    $courseStatusLabel = course_status_label($course['statut_cours'] ?? 'a_venir');
                                    $badgeClass = $course['statut_reservation'] === 'confirmee' ? 'confirmed' : 'waiting';
                                    $badgeLabel = $course['statut_reservation'] === 'confirmee' ? 'Confirmé' : 'En attente';
                                ?>
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
                                                <p class="mb-0 text-muted small">
                                                    <i class="fas fa-flag me-1"></i><?= $courseStatusLabel ?>
                                                </p>
                                            </div>
                                            <div>
                                                <span class="badge-status <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-footer-custom text-center" id="upcoming-footer" style="<?= $hasUpcomingCourses ? '' : 'display:none;' ?>">
                            <a href="rdv.php" class="btn btn-link">
                                Voir tous mes rendez-vous <i class="fas fa-arrow-right ms-1"></i>
                            </a>
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
                                            stroke-linecap="round" transform="rotate(-90 60 60)" id="profile-progress-circle" />
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <h2 class="mb-0 text-primary" id="profile-percentage"><?= $profileCompletion ?>%</h2>
                                    </div>
                                </div>
                            </div>
                            <h6 class="text-center mb-3">Complétion du profil</h6>
                            <div class="alert alert-info py-2 mb-3" id="profile-incomplete" style="<?= $profileCompletion < 100 ? '' : 'display:none;' ?>">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Complétez votre profil pour une meilleure expérience
                                </small>
                                <div class="mt-2" id="profile-missing"></div>
                            </div>
                            <div class="alert alert-success py-2 mb-0" id="profile-complete" style="<?= $profileCompletion >= 100 ? '' : 'display:none;' ?>">
                                <small>
                                    <i class="fas fa-check-circle me-1"></i>
                                    Votre profil est complet !
                                </small>
                            </div>
                            <a href="settings.php" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-edit me-2"></i>Gérer mon profil
                            </a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/get_dashboard_data.php?action=all')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data) {
                        return;
                    }
                    const stats = data.data.stats || {};
                    updateStats(stats);
                    renderUpcoming(data.data.reservations || []);
                    updateProfile(data.data.profile || {});
                })
                .catch(error => {
                    console.error('Erreur dashboard étudiant:', error);
                });

            function updateStats(stats) {
                const coursesEl = document.getElementById('stat-courses');
                const hoursEl = document.getElementById('stat-hours');
                const favEl = document.getElementById('stat-favorite');
                if (coursesEl) coursesEl.textContent = stats.completed_courses ?? '0';
                if (hoursEl) hoursEl.textContent = (stats.total_hours ?? 0) + 'h';
                if (favEl) favEl.textContent = stats.favorite_subject ?? 'Aucune';
            }

            function renderUpcoming(reservations) {
                const emptyBox = document.getElementById('upcoming-empty');
                const listBox = document.getElementById('upcoming-list');
                const footer = document.getElementById('upcoming-footer');
                if (!emptyBox || !listBox) return;

                if (!Array.isArray(reservations) || reservations.length === 0) {
                    emptyBox.style.display = '';
                    listBox.style.display = 'none';
                    if (footer) footer.style.display = 'none';
                    return;
                }

                emptyBox.style.display = 'none';
                listBox.style.display = '';
                if (footer) footer.style.display = '';

                listBox.innerHTML = reservations.map(reservation => {
                    const avatar = reservation.photo_professeur
                        ? `<img src="../${escapeHtml(reservation.photo_professeur)}" alt="Prof" class="rounded-circle" width="50" height="50" style="object-fit: cover;">`
                        : `<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="fas fa-user"></i>
                           </div>`;
                    const modeIcon = reservation.mode_choisi === 'visio' ? 'video' : 'map-marker-alt';
                    const modeLabel = reservation.mode_choisi ? capitalize(reservation.mode_choisi) : 'Cours';
                    const lieu = reservation.mode_choisi === 'presentiel' && reservation.lieu ? ` - ${escapeHtml(reservation.lieu)}` : '';
                    return `
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <div class="me-3">${avatar}</div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${escapeHtml(reservation.nom_matiere || reservation.titre_cours || 'Cours')}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user me-1"></i>${escapeHtml(reservation.nom_professeur || '')}
                                    </p>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-calendar me-1"></i>${formatDateFr(reservation.date_debut)}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-${modeIcon} me-1"></i>${modeLabel}${lieu}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-flag me-1"></i>${formatCourseStatus(reservation.statut_cours)}
                                    </p>
                                </div>
                                <div>
                                    <span class="badge-status ${reservation.statut_reservation === 'confirmee' ? 'confirmed' : 'waiting'}">
                                        ${reservation.statut_reservation === 'confirmee' ? 'Confirmé' : 'En attente'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function updateProfile(profile) {
                const percentage = profile.percentage ?? 0;
                const circle = document.getElementById('profile-progress-circle');
                const percentageEl = document.getElementById('profile-percentage');
                const incompleteBox = document.getElementById('profile-incomplete');
                const completeBox = document.getElementById('profile-complete');
                const missingEl = document.getElementById('profile-missing');

                if (circle) {
                    const circumference = 339.292;
                    circle.setAttribute('stroke-dasharray', ((percentage * circumference) / 100) + ' ' + circumference);
                }
                if (percentageEl) {
                    percentageEl.textContent = percentage + '%';
                }
                if (percentage >= 100) {
                    if (incompleteBox) incompleteBox.style.display = 'none';
                    if (completeBox) completeBox.style.display = '';
                } else {
                    if (incompleteBox) incompleteBox.style.display = '';
                    if (completeBox) completeBox.style.display = 'none';
                }
                if (missingEl && Array.isArray(profile.missing)) {
                    missingEl.textContent = profile.missing.length > 0
                        ? 'Manquant : ' + profile.missing.join(', ')
                        : '';
                }
            }

            function formatDateFr(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                }) + ' à ' + date.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function capitalize(text) {
                if (!text) return '';
                return text.charAt(0).toUpperCase() + text.slice(1);
            }

            function formatCourseStatus(status) {
                if (status === 'en_cours') return 'En cours';
                if (status === 'termine') return 'Terminé';
                return 'À venir';
            }
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>

</html>

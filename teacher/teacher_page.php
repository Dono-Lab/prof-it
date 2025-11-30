<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('teacher');

$prenom = $_SESSION['prenom'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$pageTitle = 'Espace enseignant';
$currentNav = 'teacher_home';

$stats = get_teacher_stats($userId, $conn);
$upcomingSessions = get_teacher_upcoming_sessions($userId, $conn, 3);
$availableSlots = get_teacher_available_slots($userId, $conn, 3);
$hasUpcomingSessions = !empty($upcomingSessions);
$hasAvailableSlots = !empty($availableSlots);
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

            <div class="row row-cols-xxl-5 row-cols-lg-3 row-cols-md-2 row-cols-1 g-3 mb-4 justify-content-center stats-row">
                <div class="col d-flex">
                    <div class="stat-card flex-column w-100 h-100">
                        <div class="stat-icon blue mb-2">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-0" id="teacher-stat-students"><?= $stats['nb_etudiants'] ?></h3>
                        <p class="text-muted small mb-0">Étudiants</p>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card flex-column w-100 h-100">
                        <div class="stat-icon green mb-2">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="mb-0" id="teacher-stat-reservations"><?= $stats['nb_reservations'] ?></h3>
                        <p class="text-muted small mb-0">Réservations</p>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card flex-column w-100 h-100">
                        <div class="stat-icon yellow mb-2">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="mb-0" id="teacher-stat-rating"><?= $stats['note_moyenne'] ?>/5</h3>
                        <p class="text-muted small mb-0">Note moyenne</p>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card flex-column w-100 h-100">
                        <div class="stat-icon cyan mb-2">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <h3 class="mb-0" id="teacher-stat-reviews"><?= $stats['nb_avis'] ?></h3>
                        <p class="text-muted small mb-0">Avis</p>
                    </div>
                </div>

                <div class="col d-flex">
                    <div class="stat-card flex-column w-100 h-100">
                        <div class="stat-icon purple mb-2">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="mb-0" id="teacher-stat-hours"><?= $stats['heures_donnees'] ?>h</h3>
                        <p class="text-muted small mb-0">Heures</p>
                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Sessions à venir</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="text-center py-5 text-muted" id="sessions-empty" style="<?= $hasUpcomingSessions ? 'display:none;' : '' ?>">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Aucune session planifiée pour le moment.</p>
                            </div>
                            <div class="list-group list-group-flush" id="sessions-list" style="<?= $hasUpcomingSessions ? '' : 'display:none;' ?>">
                                <?php foreach ($upcomingSessions as $session):
                                    $badgeClass = $session['statut_reservation'] === 'confirmee' ? 'confirmed' : 'waiting';
                                    $badgeLabel = $session['statut_reservation'] === 'confirmee' ? 'Confirmé' : 'En attente';
                                ?>
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
                                                <p class="mb-0 text-muted small">
                                                    <i class="fas fa-flag me-1"></i>
                                                    <?= course_status_label($session['statut_cours'] ?? 'a_venir') ?>
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
                        <div class="card-footer-custom text-center" id="sessions-footer" style="<?= $hasUpcomingSessions ? '' : 'display:none;' ?>">
                            <a href="rdv.php" class="btn btn-link">
                                Voir toutes mes sessions <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-clock me-2"></i>Mes créneaux disponibles</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="text-center py-5 text-muted" id="slots-empty" style="<?= $hasAvailableSlots ? 'display:none;' : '' ?>">
                                <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                <p>Vous n'avez aucun créneau disponible.</p>
                                <a href="rdv.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus me-2"></i>Ajouter des disponibilités
                                </a>
                            </div>
                            <div class="list-group list-group-flush" id="slots-list" style="<?= $hasAvailableSlots ? '' : 'display:none;' ?>">
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
                        </div>
                        <div class="card-footer-custom text-center" id="slots-footer" style="<?= $hasAvailableSlots ? '' : 'display:none;' ?>">
                            <a href="rdv.php" class="btn btn-link">
                                Gérer mes disponibilités <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api/get_dashboard_data.php?action=all')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.data) {
                        return;
                    }
                    updateStats(data.data.stats || {});
                    renderSessions(data.data.sessions || []);
                    renderSlots(data.data.available_slots || []);
                })
                .catch(error => console.error('Erreur dashboard enseignant:', error));

            function updateStats(stats) {
                const studentsEl = document.getElementById('teacher-stat-students');
                const reservationsEl = document.getElementById('teacher-stat-reservations');
                const ratingEl = document.getElementById('teacher-stat-rating');
                const reviewsEl = document.getElementById('teacher-stat-reviews');
                const hoursEl = document.getElementById('teacher-stat-hours');

                if (studentsEl) studentsEl.textContent = stats.total_students ?? 0;
                if (reservationsEl) reservationsEl.textContent = stats.total_reservations ?? 0;
                if (ratingEl) ratingEl.textContent = (stats.avg_rating ?? 0) + '/5';
                if (reviewsEl) reviewsEl.textContent = stats.total_reviews ?? 0;
                if (hoursEl) hoursEl.textContent = (stats.total_hours ?? 0) + 'h';
            }

            function renderSessions(sessions) {
                const emptyBox = document.getElementById('sessions-empty');
                const listBox = document.getElementById('sessions-list');
                const footer = document.getElementById('sessions-footer');
                if (!emptyBox || !listBox) return;

                if (!Array.isArray(sessions) || sessions.length === 0) {
                    emptyBox.style.display = '';
                    listBox.style.display = 'none';
                    if (footer) footer.style.display = 'none';
                    return;
                }

                emptyBox.style.display = 'none';
                listBox.style.display = '';
                if (footer) footer.style.display = '';

                listBox.innerHTML = sessions.map(session => {
                    const avatar = session.photo_etudiant
                        ? `<img src="../${escapeHtml(session.photo_etudiant)}" alt="Étudiant" class="rounded-circle" width="45" height="45" style="object-fit: cover;">`
                        : `<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fas fa-user-graduate"></i>
                           </div>`;
                    const modeIcon = session.mode_choisi === 'visio' ? 'video' : 'map-marker-alt';
                    const status = session.statut_reservation === 'confirmee' ? 'confirmed' : 'waiting';
                    const statusLabel = session.statut_reservation === 'confirmee' ? 'Confirmé' : 'En attente';
                    return `
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <div class="me-3">${avatar}</div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${escapeHtml(session.nom_matiere || session.titre_cours || 'Cours')}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-user-graduate me-1"></i>${escapeHtml(session.nom_etudiant || '')}
                                    </p>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-calendar me-1"></i>${formatDateFr(session.date_debut)}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-${modeIcon} me-1"></i>${capitalize(session.mode_choisi || '')}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-flag me-1"></i>${formatCourseStatus(session.statut_cours)}
                                    </p>
                                </div>
                                <div>
                                    <span class="badge-status ${status}">${statusLabel}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function renderSlots(slots) {
                const emptyBox = document.getElementById('slots-empty');
                const listBox = document.getElementById('slots-list');
                const footer = document.getElementById('slots-footer');
                if (!emptyBox || !listBox) return;

                if (!Array.isArray(slots) || slots.length === 0) {
                    emptyBox.style.display = '';
                    listBox.style.display = 'none';
                    if (footer) footer.style.display = 'none';
                    return;
                }

                emptyBox.style.display = 'none';
                listBox.style.display = '';
                if (footer) footer.style.display = '';

                listBox.innerHTML = slots.map(slot => {
                    const modeIcon = (slot.mode_propose && slot.mode_propose.includes('visio')) ? 'video' : 'map-marker-alt';
                    return `
                        <div class="list-group-item">
                            <div class="d-flex align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${escapeHtml(slot.nom_matiere || slot.titre_cours || 'Créneau')}</h6>
                                    <p class="mb-1 text-muted small">
                                        <i class="fas fa-calendar me-1"></i>${formatDateFr(slot.date_debut)}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-euro-sign me-1"></i>${formatPrice(slot.tarif_horaire)}€/h
                                        • <i class="fas fa-${modeIcon} ms-1 me-1"></i>${escapeHtml(slot.mode_propose || '')}
                                    </p>
                                </div>
                                <div>
                                    <span class="badge-status open">Disponible</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function formatPrice(value) {
                if (value === undefined || value === null || value === '') return '0,00';
                return Number(value).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

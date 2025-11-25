<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('teacher');
$prenom = $_SESSION['prenom'] ?? '';
$pageTitle = 'Espace professeur';
$currentNav = 'teacher_home';
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

            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-chalkboard-teacher me-2"></i>Mes prochaines sessions</h5>
                            <a href="rdv.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                        </div>
                        <div class="card-body-custom" id="upcoming-sessions">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-check me-2"></i>Disponibilités cette semaine</h5>
                        </div>
                        <div class="card-body-custom" id="available-slots">
                            <div class="text-center py-3">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-chart-bar me-2"></i>Mes statistiques</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row" id="teacher-stats">
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon blue mb-2">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-students">0</h3>
                                        <p class="text-muted small mb-0">Étudiants</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon green mb-2">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-reservations">0</h3>
                                        <p class="text-muted small mb-0">Réservations</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon yellow mb-2">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-rating">-</h3>
                                        <p class="text-muted small mb-0">Note moyenne</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon cyan mb-2">
                                            <i class="fas fa-comment-dots"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-reviews">0</h3>
                                        <p class="text-muted small mb-0">Avis</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon purple mb-2">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-hours">0h</h3>
                                        <p class="text-muted small mb-0">Heures</p>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 mb-3">
                                    <div class="stat-card flex-column">
                                        <div class="stat-icon red mb-2">
                                            <i class="fas fa-euro-sign"></i>
                                        </div>
                                        <h3 class="mb-0" id="stat-revenue">0 €</h3>
                                        <p class="text-muted small mb-0">Revenus</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card-custom h-100">
                            <div class="card-body-custom text-center">
                                <div class="stat-icon blue mx-auto mb-3">
                                    <i class="fas fa-book"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Mes Cours</h5>
                                <p class="text-muted mb-0">Gérez vos matières et programmes</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card-custom h-100">
                            <div class="card-body-custom text-center">
                                <div class="stat-icon green mx-auto mb-3">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Mes Étudiants</h5>
                                <p class="text-muted mb-0">Consultez vos étudiants actifs</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-4">
                    <a href="rdv.php" class="text-decoration-none">
                        <div class="card-custom h-100">
                            <div class="card-body-custom text-center">
                                <div class="stat-icon yellow mx-auto mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Rendez-vous</h5>
                                <p class="text-muted mb-0">Gérez votre planning</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
        });

        async function loadDashboardData() {
            try {
                const response = await fetch('api/get_dashboard_data.php?action=all');
                const result = await response.json();

                if (result.success) {
                    displaySessions(result.data.sessions);
                    displayAvailableSlots(result.data.available_slots);
                    displayStats(result.data.stats);
                } else {
                    showError('upcoming-sessions', 'Erreur lors du chargement des données');
                    showError('available-slots', 'Erreur lors du chargement des données');
                    showError('teacher-stats', 'Erreur lors du chargement des données');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('upcoming-sessions', 'Erreur de connexion au serveur');
                showError('available-slots', 'Erreur de connexion au serveur');
                showError('teacher-stats', 'Erreur de connexion au serveur');
            }
        }

        function displaySessions(sessions) {
            const container = document.getElementById('upcoming-sessions');

            if (!sessions || sessions.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chalkboard fa-3x mb-3" style="color: #9ca3af;"></i>
                        <p>Aucune session à venir</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            sessions.forEach(session => {
                const date = new Date(session.date_debut);
                const dateStr = date.toLocaleDateString('fr-FR', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });
                const timeStr = date.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusBadge = session.statut_reservation === 'confirmee'
                    ? '<span class="badge-status confirmed">Confirmée</span>'
                    : '<span class="badge-status waiting">En attente</span>';

                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">${escapeHtml(session.titre_cours)}</h6>
                                <p class="mb-1 text-muted small">
                                    <i class="fas fa-user-graduate"></i> avec ${escapeHtml(session.nom_etudiant)}
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> ${dateStr} à ${timeStr}
                                </small>
                            </div>
                            <div>
                                ${statusBadge}
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function displayAvailableSlots(slots) {
            const container = document.getElementById('available-slots');

            if (!slots || slots.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-times fa-3x mb-3" style="color: #9ca3af;"></i>
                        <p>Aucune disponibilité définie</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="d-flex flex-wrap gap-2">';
            slots.forEach(slot => {
                html += `
                    <div class="badge bg-light text-dark border px-3 py-2">
                        <i class="fas fa-clock text-success me-1"></i>
                        ${escapeHtml(slot.day)} ${escapeHtml(slot.time)}
                    </div>
                `;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function displayStats(stats) {
            document.getElementById('stat-students').textContent = stats.students_count;
            document.getElementById('stat-reservations').textContent = stats.reservations_count;
            document.getElementById('stat-rating').textContent = stats.average_rating + '/5';
            document.getElementById('stat-reviews').textContent = stats.reviews_count;
            document.getElementById('stat-hours').textContent = stats.total_hours + 'h';
            document.getElementById('stat-revenue').textContent = stats.monthly_revenue + ' €';
        }

        function showError(containerId, message) {
            const container = document.getElementById(containerId);
            container.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle"></i> ${escapeHtml(message)}
                </div>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

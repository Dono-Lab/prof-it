<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$prenom = $_SESSION['prenom'] ?? '';
$pageTitle = 'Espace étudiant';
$currentNav = 'student_home';
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

            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Mes prochaines réservations</h5>
                            <a href="rdv.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                        </div>
                        <div class="card-body-custom" id="upcoming-reservations">
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
                            <h5><i class="fas fa-user-check me-2"></i>Complétion du profil</h5>
                        </div>
                        <div class="card-body-custom" id="profile-completion">
                            <div class="text-center py-3">
                                <div class="spinner-border text-info" role="status">
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
                            <div class="row" id="student-stats">
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-icon blue">
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 id="stat-completed">0</h3>
                                            <p>Cours terminés</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-icon green">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 id="stat-hours">0h</h3>
                                            <p>Heures étudiées</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-icon yellow">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 id="stat-subject">-</h3>
                                            <p>Matière favorite</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="stat-card">
                                        <div class="stat-icon red">
                                            <i class="fas fa-euro-sign"></i>
                                        </div>
                                        <div class="stat-info">
                                            <h3 id="stat-spent">0 €</h3>
                                            <p>Total dépensé</p>
                                        </div>
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
                                    <i class="fas fa-search"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Trouver un Prof</h5>
                                <p class="text-muted mb-0">Recherchez le professeur idéal</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-4">
                    <a href="rdv.php" class="text-decoration-none">
                        <div class="card-custom h-100">
                            <div class="card-body-custom text-center">
                                <div class="stat-icon green mx-auto mb-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Mes Cours</h5>
                                <p class="text-muted mb-0">Gérez vos séances de cours</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-4">
                    <a href="#" class="text-decoration-none">
                        <div class="card-custom h-100">
                            <div class="card-body-custom text-center">
                                <div class="stat-icon yellow mx-auto mb-3">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h5 class="fw-semibold mb-2">Ma Progression</h5>
                                <p class="text-muted mb-0">Suivez vos résultats</p>
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
                    displayReservations(result.data.reservations);
                    displayProfileCompletion(result.data.profile);
                    displayStats(result.data.stats);
                } else {
                    showError('upcoming-reservations', 'Erreur lors du chargement des données');
                    showError('profile-completion', 'Erreur lors du chargement des données');
                    showError('student-stats', 'Erreur lors du chargement des données');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('upcoming-reservations', 'Erreur de connexion au serveur');
                showError('profile-completion', 'Erreur de connexion au serveur');
                showError('student-stats', 'Erreur de connexion au serveur');
            }
        }

        function displayReservations(reservations) {
            const container = document.getElementById('upcoming-reservations');

            if (!reservations || reservations.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-times fa-3x mb-3" style="color: #9ca3af;"></i>
                        <p>Aucune réservation à venir</p>
                        <a href="rdv.php" class="btn btn-primary btn-sm">Réserver un cours</a>
                    </div>
                `;
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            reservations.forEach(reservation => {
                const date = new Date(reservation.date_debut);
                const dateStr = date.toLocaleDateString('fr-FR', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short'
                });
                const timeStr = date.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusBadge = reservation.statut_reservation === 'confirmee'
                    ? '<span class="badge-status confirmed">Confirmé</span>'
                    : '<span class="badge-status waiting">En attente</span>';

                const modeIcon = reservation.mode_choisi === 'visio'
                    ? '<i class="fas fa-video text-primary"></i>'
                    : '<i class="fas fa-map-marker-alt text-danger"></i>';

                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">${escapeHtml(reservation.nom_matiere)} - ${escapeHtml(reservation.titre_cours)}</h6>
                                <p class="mb-1 text-muted small">
                                    ${modeIcon} avec ${escapeHtml(reservation.nom_professeur)}
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

        function displayProfileCompletion(profile) {
            const container = document.getElementById('profile-completion');
            const percentage = profile.percentage;

            let progressColor = 'bg-danger';
            if (percentage >= 75) progressColor = 'bg-success';
            else if (percentage >= 50) progressColor = 'bg-warning';

            let html = `
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold">Profil complété à ${percentage}%</span>
                        <span class="text-muted small">${profile.completed}/${profile.total} champs</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${progressColor}" role="progressbar"
                             style="width: ${percentage}%"
                             aria-valuenow="${percentage}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            ${percentage}%
                        </div>
                    </div>
                </div>
            `;

            if (profile.missing.length > 0) {
                html += `
                    <div class="alert alert-info small mb-2">
                        <i class="fas fa-info-circle"></i>
                        <strong>Champs manquants :</strong> ${profile.missing.join(', ')}
                    </div>
                `;
            }

            html += `
                <div class="text-center">
                    <a href="settings.php" class="btn btn-info btn-sm">
                        <i class="fas fa-edit"></i> Compléter mon profil
                    </a>
                </div>
            `;

            container.innerHTML = html;
        }

        function displayStats(stats) {
            document.getElementById('stat-completed').textContent = stats.completed_courses;
            document.getElementById('stat-hours').textContent = stats.total_hours + 'h';
            document.getElementById('stat-subject').textContent = escapeHtml(stats.favorite_subject);
            document.getElementById('stat-spent').textContent = stats.total_spent + ' €';
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

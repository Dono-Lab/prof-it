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

    <body>
        <div class="welcome-section">
            <div class="container text-center">
                <h1>Bienvenue dans votre espace enseignant, <span>
                        <?= htmlspecialchars(ucfirst($prenom), ENT_QUOTES, 'UTF-8'); ?>
                    </span></h1>
                <p class="lead">Gérez vos cours, étudiants et rendez-vous</p>
            </div>
        </div>

        <div class="container mt-5">
            <!-- Widgets de données dynamiques -->
            <div class="row mb-4">
                <!-- Widget: Prochaines sessions -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Mes prochaines sessions
                        </div>
                        <div class="card-body" id="upcoming-sessions">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget: Disponibilités -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-white">
                            <i class="fas fa-calendar-check me-2"></i>Disponibilités cette semaine
                        </div>
                        <div class="card-body" id="available-slots">
                            <div class="text-center py-3">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget: Statistiques -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-chart-bar me-2"></i>Mes statistiques
                        </div>
                        <div class="card-body">
                            <div class="row" id="teacher-stats">
                                <div class="col-md-2 text-center mb-3">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cartes d'action rapides -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-book fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Mes Cours</h5>
                            <p class="card-text">Gérez vos matières et programmes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Mes étudiants</h5>
                            <p class="card-text">Consultez vos élèves</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-alt fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Rendez-vous</h5>
                            <p class="card-text">Planifiez vos séances</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Charger les données du dashboard au chargement de la page
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
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
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
                        ? '<span class="badge bg-success">Confirmée</span>'
                        : '<span class="badge bg-warning">En attente</span>';

                    const modeIcon = session.mode_choisi === 'visio'
                        ? '<i class="fas fa-video text-primary"></i>'
                        : '<i class="fas fa-map-marker-alt text-danger"></i>';

                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${escapeHtml(session.nom_matiere)} - ${escapeHtml(session.titre_cours)}</h6>
                                    <p class="mb-1 text-muted small">
                                        ${modeIcon} avec ${escapeHtml(session.nom_etudiant)}
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
                            <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                            <p>Aucun créneau disponible cette semaine</p>
                            <a href="#" class="btn btn-warning btn-sm">Ajouter des créneaux</a>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="small">
                        <p class="mb-2"><strong>${slots.length} créneau(x) disponible(s)</strong></p>
                        <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                `;

                slots.forEach(slot => {
                    const date = new Date(slot.date_debut);
                    const dateStr = date.toLocaleDateString('fr-FR', {
                        weekday: 'short',
                        day: 'numeric',
                        month: 'short'
                    });
                    const timeStr = date.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const modeIcons = [];
                    if (slot.mode_propose === 'visio' || slot.mode_propose === 'les_deux') {
                        modeIcons.push('<i class="fas fa-video text-primary" title="Visio"></i>');
                    }
                    if (slot.mode_propose === 'presentiel' || slot.mode_propose === 'les_deux') {
                        modeIcons.push('<i class="fas fa-map-marker-alt text-danger" title="Présentiel"></i>');
                    }

                    html += `
                        <div class="list-group-item py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <small class="fw-bold">${escapeHtml(slot.nom_matiere)}</small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> ${dateStr} ${timeStr}
                                        ${modeIcons.join(' ')}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;

                container.innerHTML = html;
            }

            function displayStats(stats) {
                const container = document.getElementById('teacher-stats');

                const html = `
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.total_students}</h3>
                            <p class="text-muted small mb-0">Étudiants</p>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.total_reservations}</h3>
                            <p class="text-muted small mb-0">Réservations</p>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-star fa-2x text-warning mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.avg_rating}/5</h3>
                            <p class="text-muted small mb-0">Note moyenne</p>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-comment fa-2x text-info mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.total_reviews}</h3>
                            <p class="text-muted small mb-0">Avis</p>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-clock fa-2x text-secondary mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.total_hours}h</h3>
                            <p class="text-muted small mb-0">Heures enseignées</p>
                        </div>
                    </div>
                    <div class="col-md-2 text-center mb-3">
                        <div class="stat-card">
                            <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                            <h3 class="fw-bold mb-0">${stats.monthly_revenue} €</h3>
                            <p class="text-muted small mb-0">Revenus du mois</p>
                        </div>
                    </div>
                `;

                container.innerHTML = html;
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
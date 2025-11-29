<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_role('teacher');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'teacher_rdv';

$userId = $_SESSION['user_id'] ?? null;

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_offer'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tarifHoraire = floatval($_POST['tarif_horaire'] ?? 0);
        $matiereId = intval($_POST['matiere_id'] ?? 0);

        if (!empty($titre) && $tarifHoraire > 0 && $matiereId > 0) {
            try {
                $conn->beginTransaction();

                $sqlInsert = "INSERT INTO offre_cours (titre, description_offre, tarif_horaire_defaut) VALUES (?, ?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);
                $stmtInsert->execute([$titre, $description, $tarifHoraire]);
                $offreId = $conn->lastInsertId();

                $sqlEnseigner = "INSERT INTO enseigner (id_utilisateur, id_offre, actif) VALUES (?, ?, 1)";
                $stmtEnseigner = $conn->prepare($sqlEnseigner);
                $stmtEnseigner->execute([$userId, $offreId]);

                $sqlCouvrir = "INSERT INTO couvrir (id_offre, id_matiere) VALUES (?, ?)";
                $stmtCouvrir = $conn->prepare($sqlCouvrir);
                $stmtCouvrir->execute([$offreId, $matiereId]);

                $conn->commit();
                $successMessage = 'Offre créée avec succès !';
            } catch (Exception $e) {
                $conn->rollBack();
                $errorMessage = 'Erreur lors de la création de l\'offre : ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'Veuillez remplir tous les champs obligatoires.';
        }
    } else {
        $errorMessage = 'Token CSRF invalide.';
    }
}

$sql = "
    SELECT
        o.id_offre,
        o.titre,
        o.tarif_horaire_defaut,
        m.nom_matiere
    FROM offre_cours o
    LEFT JOIN enseigner e ON o.id_offre = e.id_offre
    LEFT JOIN couvrir c ON o.id_offre = c.id_offre
    LEFT JOIN matiere m ON c.id_matiere = m.id_matiere
    WHERE e.id_utilisateur = ?
    AND e.actif = 1
    ORDER BY o.titre
";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$offres = $stmt->fetchAll();

$sqlMatieres = "SELECT id_matiere, nom_matiere FROM matiere ORDER BY nom_matiere";
$stmtMatieres = $conn->prepare($sqlMatieres);
$stmtMatieres->execute();
$matieres = $stmtMatieres->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php require __DIR__ . '/../templates/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title mb-0"><i class="fas fa-calendar-check me-2"></i>Gestion des Rendez-vous</h1>
                <button class="btn btn-outline-primary" id="toggle-offer-form">
                    <i class="fas fa-plus me-2"></i>Créer une offre
                </button>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-custom mb-4" id="offer-form-container" style="display: none;">
                <div class="card-header-custom">
                    <h5><i class="fas fa-book me-2"></i>Créer une nouvelle offre de cours</h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="create_offer" value="1">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Titre de l'offre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="titre" required placeholder="Ex: Cours de mathématiques niveau lycée">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select class="form-select" name="matiere_id" required>
                                    <option value="">Sélectionnez...</option>
                                    <?php foreach ($matieres as $matiere): ?>
                                        <option value="<?php echo $matiere['id_matiere']; ?>">
                                            <?php echo htmlspecialchars($matiere['nom_matiere']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tarif horaire (€) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tarif_horaire" step="0.01" min="0" required placeholder="25.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Décrivez votre offre de cours..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer l'offre
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancel-offer-form">
                                <i class="fas fa-times me-2"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Mes disponibilités</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Créneaux disponibles</h5>
                                    <div class="time-slots mt-3">
                                        <div class="row" id="slots-container">
                                            <div class="text-center py-4 text-muted w-100">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                    <span class="visually-hidden">Chargement...</span>
                                                </div>
                                                <p class="mt-2 mb-0 small">Chargement des créneaux...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Ajouter une disponibilité</h5>
                                    <form id="slot-form" class="mt-3">
                                        <?php if (empty($offres)): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Vous devez d'abord créer une offre de cours pour pouvoir ajouter des disponibilités.
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-3">
                                                <label class="form-label">Offre de cours <span class="text-danger">*</span></label>
                                                <select class="form-select" id="offre-id" required>
                                                    <option value="">Sélectionnez une offre</option>
                                                    <?php foreach ($offres as $offre): ?>
                                                        <option value="<?php echo $offre['id_offre']; ?>" data-prix="<?php echo $offre['tarif_horaire_defaut']; ?>">
                                                            <?php echo htmlspecialchars($offre['titre']); ?>
                                                            <?php if ($offre['nom_matiere']): ?>
                                                                - <?php echo htmlspecialchars($offre['nom_matiere']); ?>
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date et heure de début <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="date-debut" step="900" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Date et heure de fin <span class="text-danger">*</span></label>
                                                <input type="datetime-local" class="form-control" id="date-fin" step="900" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tarif horaire (€) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="tarif-horaire" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Mode de cours <span class="text-danger">*</span></label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="presentiel" id="mode-presentiel">
                                                    <label class="form-check-label" for="mode-presentiel">Présentiel</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="visio" id="mode-visio">
                                                    <label class="form-check-label" for="mode-visio">Visio</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="domicile" id="mode-domicile">
                                                    <label class="form-check-label" for="mode-domicile">À domicile</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Lieu (optionnel)</label>
                                                <input type="text" class="form-control" id="lieu" placeholder="Ex: Salle 102, Paris 5ème...">
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-plus me-2"></i>Ajouter le créneau
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-clock me-2"></i>Sessions à venir</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="appointments-list" id="upcoming-sessions-container">
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <p class="mt-2 mb-0 small">Chargement des sessions...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-history me-2"></i>Historique</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="appointments-list" id="history-sessions-container">
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                    <p class="mt-2 mb-0 small">Chargement de l'historique...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-chart-bar me-2"></i>Statistiques</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row text-center" id="stats-container">
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-primary">-</h4>
                                        <small>Sessions ce mois</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-success">-</h4>
                                        <small>Total heures</small>
                                    </div>
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
            const toggleBtn = document.getElementById('toggle-offer-form');
            const offerFormContainer = document.getElementById('offer-form-container');
            const cancelBtn = document.getElementById('cancel-offer-form');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (offerFormContainer.style.display === 'none') {
                        offerFormContainer.style.display = 'block';
                        toggleBtn.innerHTML = '<i class="fas fa-times me-2"></i>Annuler';
                    } else {
                        offerFormContainer.style.display = 'none';
                        toggleBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Créer une offre';
                    }
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    offerFormContainer.style.display = 'none';
                    toggleBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Créer une offre';
                });
            }

            loadAvailableSlots();
            loadUpcomingSessions();
            loadHistorySessions();
            loadStats();

            function roundToQuarterHour(date) {
                const minutes = date.getMinutes();
                const roundedMinutes = Math.ceil(minutes / 15) * 15;
                date.setMinutes(roundedMinutes);
                date.setSeconds(0);
                date.setMilliseconds(0);
                return date;
            }

            function addOneHour(date) {
                const newDate = new Date(date);
                newDate.setHours(newDate.getHours() + 1);
                return newDate;
            }

            function dateToInputValue(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            }

            const offreSelect = document.getElementById('offre-id');
            if (offreSelect) {
                offreSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const prix = selectedOption.dataset.prix;
                    if (prix) {
                        document.getElementById('tarif-horaire').value = prix;
                    }
                });
            }

            const dateDebutInput = document.getElementById('date-debut');
            const dateFinInput = document.getElementById('date-fin');

            if (dateDebutInput) {
                dateDebutInput.addEventListener('change', function() {
                    if (this.value) {
                        const dateDebut = new Date(this.value);
                        const dateFin = addOneHour(dateDebut);
                        dateFinInput.value = dateToInputValue(dateFin);
                    }
                });
            }

            async function loadAvailableSlots() {
                try {
                    const response = await fetch('../api/appointments.php?action=available_slots');
                    const data = await response.json();

                    if (data.success && data.slots) {
                        renderSlots(data.slots);
                    } else {
                        showEmptySlots();
                    }
                } catch (error) {
                    console.error('Erreur chargement créneaux:', error);
                    showEmptySlots();
                }
            }

            function renderSlots(slots) {
                const container = document.getElementById('slots-container');

                if (slots.length === 0) {
                    showEmptySlots();
                    return;
                }

                container.innerHTML = slots.map(slot => {
                    const dateDebut = new Date(slot.date_debut);
                    const dateFin = new Date(slot.date_fin);
                    const timeRange = formatTime(dateDebut) + ' - ' + formatTime(dateFin);
                    const dateStr = formatDateFr(dateDebut);

                    return '<div class="col-6 mb-3">' +
                        '<div class="time-slot">' +
                        '<div>' + timeRange + '</div>' +
                        '<small>' + dateStr + '</small>' +
                        '<small class="d-block text-primary">' + escapeHtml(slot.nom_matiere || 'Cours') + '</small>' +
                        '<small class="d-block">' + slot.tarif_horaire + '€/h</small>' +
                        '</div>' +
                        '</div>';
                }).join('');
            }

            function showEmptySlots() {
                document.getElementById('slots-container').innerHTML =
                    '<div class="text-center py-5 text-muted w-100">' +
                    '<i class="fas fa-calendar-plus fa-3x mb-3"></i>' +
                    '<p>Aucun créneau disponible</p>' +
                    '<small>Utilisez le formulaire pour créer vos disponibilités</small>' +
                    '</div>';
            }

            async function loadUpcomingSessions() {
                try {
                    const response = await fetch('../api/appointments.php?action=upcoming_appointments');
                    const data = await response.json();

                    if (data.success && data.appointments) {
                        renderUpcomingSessions(data.appointments);
                    } else {
                        showEmptyUpcomingSessions();
                    }
                } catch (error) {
                    console.error('Erreur chargement sessions:', error);
                    showEmptyUpcomingSessions();
                }
            }

            function renderUpcomingSessions(sessions) {
                const container = document.getElementById('upcoming-sessions-container');

                if (sessions.length === 0) {
                    showEmptyUpcomingSessions();
                    return;
                }

                container.innerHTML = sessions.map(session => {
                    const statusMap = {
                        'confirmee': {
                            class: 'confirmed',
                            label: 'Confirmé'
                        },
                        'en_attente': {
                            class: 'waiting',
                            label: 'En attente'
                        },
                        'en_cours': {
                            class: 'in-progress',
                            label: 'En cours'
                        }
                    };
                    const status = statusMap[session.statut_reservation] || {
                        class: 'waiting',
                        label: 'En attente'
                    };

                    const modeIcons = {
                        'presentiel': 'fa-building',
                        'visio': 'fa-video',
                        'domicile': 'fa-home'
                    };
                    const modeIcon = modeIcons[session.mode_choisi] || 'fa-question';

                    const dateDebut = new Date(session.date_debut);
                    const dateFin = new Date(session.date_fin);
                    const dateTimeStr = formatDateTimeFr(dateDebut) + ' - ' + formatTime(dateFin);

                    let actionButtons = '';
                    if (session.statut_reservation === 'confirmee') {
                        actionButtons = '<button class="btn btn-sm btn-success mt-2 w-100" onclick="updateSessionStatus(' + session.id_reservation + ', \'en_cours\')">' +
                            '<i class="fas fa-play me-1"></i>Marquer en cours' +
                            '</button>';
                    } else if (session.statut_reservation === 'en_cours') {
                        actionButtons = '<button class="btn btn-sm btn-primary mt-2 w-100" onclick="updateSessionStatus(' + session.id_reservation + ', \'terminee\')">' +
                            '<i class="fas fa-check me-1"></i>Terminer' +
                            '</button>';
                    }

                    return '<div class="appointment-card">' +
                        '<div class="d-flex justify-content-between align-items-start mb-2">' +
                        '<h6 class="mb-0">' + escapeHtml(session.nom_matiere || 'Cours') + ' - ' + escapeHtml(session.titre_cours) + '</h6>' +
                        '<span class="badge-status ' + status.class + '">' + status.label + '</span>' +
                        '</div>' +
                        '<p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>' + escapeHtml(session.nom_etudiant) + '</p>' +
                        '<p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>' + dateTimeStr + '</p>' +
                        '<p class="mb-0 text-muted small"><i class="fas ' + modeIcon + ' me-2"></i>' + ucFirst(session.mode_choisi) + '</p>' +
                        actionButtons +
                        '</div>';
                }).join('');
            }

            function showEmptyUpcomingSessions() {
                document.getElementById('upcoming-sessions-container').innerHTML =
                    '<div class="text-center py-5 text-muted">' +
                    '<i class="fas fa-calendar-alt fa-3x mb-3"></i>' +
                    '<p>Aucune session à venir</p>' +
                    '<small>Les réservations apparaîtront ici</small>' +
                    '</div>';
            }

            async function loadHistorySessions() {
                try {
                    const response = await fetch('../api/appointments.php?action=history_appointments');
                    const data = await response.json();

                    if (data.success && data.appointments) {
                        renderHistorySessions(data.appointments);
                    } else {
                        showEmptyHistorySessions();
                    }
                } catch (error) {
                    console.error('Erreur chargement historique:', error);
                    showEmptyHistorySessions();
                }
            }

            function renderHistorySessions(sessions) {
                const container = document.getElementById('history-sessions-container');

                if (sessions.length === 0) {
                    showEmptyHistorySessions();
                    return;
                }

                container.innerHTML = sessions.map(session => {
                    const statusMap = {
                        'terminee': {
                            class: 'completed',
                            label: 'Terminé'
                        },
                        'annulee': {
                            class: 'cancelled',
                            label: 'Annulé'
                        }
                    };
                    const status = statusMap[session.statut_reservation] || {
                        class: 'completed',
                        label: 'Terminé'
                    };

                    const dateDebut = new Date(session.date_debut);
                    const dateFin = new Date(session.date_fin);
                    const dateTimeStr = formatDateTimeFr(dateDebut) + ' - ' + formatTime(dateFin);

                    return '<div class="appointment-card">' +
                        '<div class="d-flex justify-content-between align-items-start mb-2">' +
                        '<h6 class="mb-0">' + escapeHtml(session.nom_matiere || 'Cours') + ' - ' + escapeHtml(session.titre_cours) + '</h6>' +
                        '<span class="badge-status ' + status.class + '">' + status.label + '</span>' +
                        '</div>' +
                        '<p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>' + escapeHtml(session.nom_etudiant) + '</p>' +
                        '<p class="mb-0 text-muted small"><i class="fas fa-calendar me-2"></i>' + dateTimeStr + '</p>' +
                        '</div>';
                }).join('');
            }

            function showEmptyHistorySessions() {
                document.getElementById('history-sessions-container').innerHTML =
                    '<div class="text-center py-5 text-muted">' +
                    '<i class="fas fa-history fa-3x mb-3"></i>' +
                    '<p>Aucun historique</p>' +
                    '<small>Les sessions terminées apparaîtront ici</small>' +
                    '</div>';
            }

            async function loadStats() {
                try {
                    const response = await fetch('../api/appointments.php?action=stats');
                    const data = await response.json();

                    if (data.success && data.stats) {
                        renderStats(data.stats);
                    }
                } catch (error) {
                    console.error('Erreur chargement stats:', error);
                }
            }

            function renderStats(stats) {
                document.getElementById('stats-container').innerHTML =
                    '<div class="col-6">' +
                    '<div class="border rounded p-3">' +
                    '<h4 class="text-primary">' + stats.sessions_month + '</h4>' +
                    '<small>Sessions ce mois</small>' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-6">' +
                    '<div class="border rounded p-3">' +
                    '<h4 class="text-success">' + stats.total_heures + 'h</h4>' +
                    '<small>Total heures</small>' +
                    '</div>' +
                    '</div>';
            }

            window.updateSessionStatus = async function(reservationId, newStatus) {
                if (!confirm('Confirmer le changement de statut ?')) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('reservation_id', reservationId);
                    formData.append('status', newStatus);
                    formData.append('csrf_token', '<?php echo csrf_token(); ?>');

                    const response = await fetch('../api/appointments.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        loadUpcomingSessions();
                        loadHistorySessions();
                        loadStats();
                    } else {
                        alert('Erreur lors de la mise à jour du statut: ' + (data.error || 'Erreur inconnue'));
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la mise à jour du statut. Veuillez réessayer.');
                }
            };

            const slotForm = document.getElementById('slot-form');
            if (slotForm) {
                slotForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const offreId = document.getElementById('offre-id').value;
                    const dateDebut = document.getElementById('date-debut').value;
                    const dateFin = document.getElementById('date-fin').value;
                    const tarifHoraire = document.getElementById('tarif-horaire').value;
                    const lieu = document.getElementById('lieu').value;

                    const modes = [];
                    if (document.getElementById('mode-presentiel') && document.getElementById('mode-presentiel').checked) {
                        modes.push('presentiel');
                    }
                    if (document.getElementById('mode-visio') && document.getElementById('mode-visio').checked) {
                        modes.push('visio');
                    }
                    if (document.getElementById('mode-domicile') && document.getElementById('mode-domicile').checked) {
                        modes.push('domicile');
                    }

                    if (modes.length === 0) {
                        alert('Veuillez sélectionner au moins un mode de cours');
                        return;
                    }

                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';

                    try {
                        const formData = new FormData();
                        formData.append('action', 'create_slot');
                        formData.append('offre_id', offreId);
                        formData.append('date_debut', dateDebut);
                        formData.append('date_fin', dateFin);
                        formData.append('tarif_horaire', tarifHoraire);
                        formData.append('mode_propose', modes.join(','));
                        formData.append('lieu', lieu);
                        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

                        const response = await fetch('../api/appointments.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Créneau créé avec succès !');
                            this.reset();
                            loadAvailableSlots();
                            loadStats();
                        } else {
                            alert('Erreur lors de la création du créneau: ' + (data.error || 'Erreur inconnue'));
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la création du créneau. Veuillez réessayer.');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Ajouter le créneau';
                    }
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function formatTime(date) {
                return date.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function formatDateFr(date) {
                const jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                const mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                return jours[date.getDay()] + ' ' + date.getDate() + ' ' + mois[date.getMonth()];
            }

            function formatDateTimeFr(date) {
                const jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                const mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
                return jours[date.getDay()] + ' ' + date.getDate() + ' ' + mois[date.getMonth()] + ', ' + formatTime(date);
            }

            function ucFirst(str) {
                if (!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>

</html>
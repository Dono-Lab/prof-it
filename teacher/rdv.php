<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_role('teacher');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'teacher_rdv';

$userId = $_SESSION['user_id'] ?? null;

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
            <h1 class="page-title"><i class="fas fa-calendar-check me-2"></i>Gestion des Rendez-vous</h1>

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
                                                <option value="<?= $offre['id_offre'] ?>" data-prix="<?= $offre['tarif_horaire_defaut'] ?>">
                                                    <?= htmlspecialchars($offre['titre']) ?>
                                                    <?php if ($offre['nom_matiere']): ?>
                                                        - <?= htmlspecialchars($offre['nom_matiere']) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date et heure de début <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="date-debut" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date et heure de fin <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="date-fin" required>
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
                        <div class="appointments-list" id="sessions-container">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2 mb-0 small">Chargement des sessions...</p>
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
            loadAvailableSlots();
            loadUpcomingSessions();
            loadStats();

            document.getElementById('offre-id')?.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const prix = selectedOption.dataset.prix;
                if (prix) {
                    document.getElementById('tarif-horaire').value = prix;
                }
            });

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

                    return `
                        <div class="col-6 mb-3">
                            <div class="time-slot">
                                <div>${timeRange}</div>
                                <small>${dateStr}</small>
                                <small class="d-block text-primary">${escapeHtml(slot.nom_matiere || 'Cours')}</small>
                                <small class="d-block">${slot.tarif_horaire}€/h</small>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function showEmptySlots() {
                document.getElementById('slots-container').innerHTML = `
                    <div class="text-center py-5 text-muted w-100">
                        <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                        <p>Aucun créneau disponible</p>
                        <small>Utilisez le formulaire pour créer vos disponibilités</small>
                    </div>
                `;
            }

            async function loadUpcomingSessions() {
                try {
                    const response = await fetch('../api/appointments.php?action=upcoming_appointments');
                    const data = await response.json();

                    if (data.success && data.appointments) {
                        renderSessions(data.appointments);
                    } else {
                        showEmptySessions();
                    }
                } catch (error) {
                    console.error('Erreur chargement sessions:', error);
                    showEmptySessions();
                }
            }

            function renderSessions(sessions) {
                const container = document.getElementById('sessions-container');

                if (sessions.length === 0) {
                    showEmptySessions();
                    return;
                }

                container.innerHTML = sessions.map(session => {
                    const statusMap = {
                        'confirmee': { class: 'confirmed', label: 'Confirmé' },
                        'en_attente': { class: 'waiting', label: 'En attente' }
                    };
                    const status = statusMap[session.statut_reservation] || { class: 'waiting', label: 'En attente' };

                    const modeIcons = {
                        'presentiel': 'fa-building',
                        'visio': 'fa-video',
                        'domicile': 'fa-home'
                    };
                    const modeIcon = modeIcons[session.mode_choisi] || 'fa-question';

                    const dateDebut = new Date(session.date_debut);
                    const dateFin = new Date(session.date_fin);
                    const dateTimeStr = formatDateTimeFr(dateDebut) + ' - ' + formatTime(dateFin);

                    return `
                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${escapeHtml(session.nom_matiere || 'Cours')} - ${escapeHtml(session.titre_cours)}</h6>
                                <span class="badge-status ${status.class}">${status.label}</span>
                            </div>
                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>${escapeHtml(session.nom_etudiant)}</p>
                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>${dateTimeStr}</p>
                            <p class="mb-0 text-muted small"><i class="fas ${modeIcon} me-2"></i>${ucFirst(session.mode_choisi)}</p>
                        </div>
                    `;
                }).join('');
            }

            function showEmptySessions() {
                document.getElementById('sessions-container').innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <p>Aucune session à venir</p>
                        <small>Les réservations apparaîtront ici</small>
                    </div>
                `;
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
                document.getElementById('stats-container').innerHTML = `
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h4 class="text-primary">${stats.sessions_month}</h4>
                            <small>Sessions ce mois</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3">
                            <h4 class="text-success">${stats.total_heures}h</h4>
                            <small>Total heures</small>
                        </div>
                    </div>
                `;
            }

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
                    if (document.getElementById('mode-presentiel')?.checked) modes.push('presentiel');
                    if (document.getElementById('mode-visio')?.checked) modes.push('visio');
                    if (document.getElementById('mode-domicile')?.checked) modes.push('domicile');

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
                        formData.append('csrf_token', '<?= csrf_token() ?>');

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
                return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
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
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

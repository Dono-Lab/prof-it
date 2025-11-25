<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('teacher');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'teacher_rdv';
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
            <!-- Colonne de gauche : Disponibilités et demandes -->
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
                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <div class="time-slot" onclick="selectTimeSlot(this)">
                                            <div>09:00 - 10:00</div>
                                            <small>Lundi 15 Jan</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="time-slot" onclick="selectTimeSlot(this)">
                                            <div>14:00 - 15:00</div>
                                            <small>Lundi 15 Jan</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="time-slot" onclick="selectTimeSlot(this)">
                                            <div>10:30 - 11:30</div>
                                            <small>Mardi 16 Jan</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="time-slot" onclick="selectTimeSlot(this)">
                                            <div>16:00 - 17:00</div>
                                            <small>Mardi 16 Jan</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Ajouter une disponibilité</h5>
                            <form class="mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Jour</label>
                                    <select class="form-select">
                                        <option>Lundi</option>
                                        <option>Mardi</option>
                                        <option>Mercredi</option>
                                        <option>Jeudi</option>
                                        <option>Vendredi</option>
                                        <option>Samedi</option>
                                        <option>Dimanche</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Heure de début</label>
                                    <input type="time" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Heure de fin</label>
                                    <input type="time" class="form-control">
                                </div>
                                <button type="button" class="btn btn-primary w-100" onclick="addAvailability()">
                                    <i class="fas fa-plus me-2"></i>Ajouter
                                </button>
                            </form>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Sessions à venir -->
            <div class="col-lg-4">
                <div class="card-custom mb-4">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-clock me-2"></i>Sessions à venir</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="appointments-list">
                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">Mathématiques - Algèbre</h6>
                                <span class="badge-status confirmed">Confirmé</span>
                            </div>
                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>Jean Dupont</p>
                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>Lundi 15 Jan, 14:00-15:00</p>
                            <p class="mb-0 text-muted small"><i class="fas fa-video me-2"></i>Visio</p>
                        </div>

                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">Anglais - Conversation</h6>
                                <span class="badge-status waiting">En attente</span>
                            </div>
                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>Marie Lambert</p>
                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>Mardi 16 Jan, 10:30-11:30</p>
                            <p class="mb-0 text-muted small"><i class="fas fa-home me-2"></i>À domicile</p>
                        </div>

                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">Physique - Mécanique</h6>
                                <span class="badge-status confirmed">Confirmé</span>
                            </div>
                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i>Thomas Petit</p>
                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>Mercredi 17 Jan, 16:00-17:00</p>
                            <p class="mb-0 text-muted small"><i class="fas fa-video me-2"></i>Visio</p>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-chart-bar me-2"></i>Statistiques</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row text-center">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="text-primary">3</h4>
                                <small>Sessions ce mois</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="text-success">12h</h4>
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
        function selectTimeSlot(element) {
            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
            element.classList.add('selected');
            const timeText = element.querySelector('div').textContent;
            const dateText = element.querySelector('small').textContent;
        }

        function addAvailability() {
            alert('Disponibilité ajoutée avec succès !');
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

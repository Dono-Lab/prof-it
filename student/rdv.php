<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'student_rdv';
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

    <div class="welcome-section" style="padding: 3rem 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container text-center">
            <h1>Gestion des Rendez-vous</h1>
            <p class="lead">Planifiez et gérez vos séances de cours</p>
        </div>
    </div>

    <div class="container mt-5">
        <div class="row">
            <!-- Colonne de gauche : Calendrier et prise de RDV -->
            <div class="col-lg-8">
                <div class="calendar-container">
                    <h3><i class="fas fa-calendar-alt me-2"></i>Calendrier des disponibilités</h3>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Prochaines disponibilités</h5>
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
                            <h5>Nouveau rendez-vous</h5>
                            <form class="mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Professeur</label>
                                    <select class="form-select">
                                        <option>Prof. Pierre Martin - Anglais</option>
                                        <option>Prof. Marie Dubois - Mathématiques</option>
                                        <option>Prof. Sophie Laurent - Physique</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date et heure sélectionnée</label>
                                    <input type="text" class="form-control" id="selected-time" readonly placeholder="Cliquez sur un créneau">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sujet du cours</label>
                                    <input type="text" class="form-control" placeholder="Ex: Révision algèbre, Conversation anglaise...">
                                </div>
                                <button type="button" class="btn btn-signup w-100" onclick="takeAppointment()">
                                    <i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Rendez-vous à venir -->
            <div class="col-lg-4">
                <div class="calendar-container">
                    <h3><i class="fas fa-clock me-2"></i>Rendez-vous à venir</h3>
                    <div class="appointments-list mt-4">
                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6>Mathématiques - Algèbre</h6>
                                <span class="appointment-status status-confirmed">Confirmé</span>
                            </div>
                            <p class="mb-1"><i class="fas fa-user me-2"></i>Prof. Marie Dubois</p>
                            <p class="mb-1"><i class="fas fa-calendar me-2"></i>Lundi 15 Jan, 14:00-15:00</p>
                            <p class="mb-0"><i class="fas fa-video me-2"></i>Visio</p>
                        </div>

                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6>Anglais - Conversation</h6>
                                <span class="appointment-status status-pending">En attente</span>
                            </div>
                            <p class="mb-1"><i class="fas fa-user me-2"></i>Prof. Pierre Martin</p>
                            <p class="mb-1"><i class="fas fa-calendar me-2"></i>Mardi 16 Jan, 10:30-11:30</p>
                            <p class="mb-0"><i class="fas fa-home me-2"></i>À domicile</p>
                        </div>

                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6>Physique - Mécanique</h6>
                                <span class="appointment-status status-confirmed">Confirmé</span>
                            </div>
                            <p class="mb-1"><i class="fas fa-user me-2"></i>Prof. Sophie Laurent</p>
                            <p class="mb-1"><i class="fas fa-calendar me-2"></i>Mercredi 17 Jan, 16:00-17:00</p>
                            <p class="mb-0"><i class="fas fa-video me-2"></i>Visio</p>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="calendar-container">
                    <h3><i class="fas fa-chart-bar me-2"></i>Statistiques</h3>
                    <div class="row text-center mt-4">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <h4 class="text-primary">3</h4>
                                <small>Cours ce mois</small>
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
    
<script src="index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../templates/footer.php'; ?>
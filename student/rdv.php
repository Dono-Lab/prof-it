<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_role('student');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'student_rdv';

$userId = $_SESSION['user_id'] ?? null;
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
                        <h5><i class="fas fa-search me-2"></i>Rechercher un cours</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control" id="course-search-input" placeholder="Ex : Mathématiques, Anglais, Programmation..." autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">Suggestions populaires :</small>
                            <div class="d-flex flex-wrap gap-2" id="course-suggestions">
                                <button type="button" class="btn btn-sm btn-outline-primary course-suggestion">Mathématiques</button>
                                <button type="button" class="btn btn-sm btn-outline-primary course-suggestion">Physique</button>
                                <button type="button" class="btn btn-sm btn-outline-primary course-suggestion">Anglais</button>
                                <button type="button" class="btn btn-sm btn-outline-primary course-suggestion">Programmation</button>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted">Résultats</h6>
                                <small class="text-muted" id="course-results-count"></small>
                            </div>
                            <div id="course-search-results">
                                <p class="text-muted mb-0">Commencez à saisir un mot clé pour trouver un cours.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-custom mb-4">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Calendrier des disponibilités</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h5 class="mb-0">Prochaines disponibilités</h5>
                                <div class="d-flex align-items-center gap-2" id="selected-course-banner" style="display:none;">
                                    <span class="badge rounded-pill bg-primary" id="selected-course-label"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-course-selection">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="time-slots mt-3" id="slots-wrapper">
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
                            <h5>Nouveau rendez-vous</h5>
                            <form id="booking-form" class="mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Créneau sélectionné</label>
                                    <input type="text" class="form-control" id="selected-slot-display" readonly placeholder="Cliquez sur un créneau">
                                    <input type="hidden" id="selected-slot-id">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mode de cours <span class="text-danger">*</span></label>
                                    <select class="form-select" id="mode-choisi" required>
                                        <option value="">Sélectionnez un mode</option>
                                        <option value="presentiel">Présentiel</option>
                                        <option value="visio">Visio</option>
                                        <option value="domicile">À domicile</option>
                                    </select>
                                    <small class="text-muted" id="mode-info"></small>
                                </div>
                                <button type="submit" class="btn btn-signup w-100">
                                    <i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous
                                </button>
                            </form>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-custom mb-4">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-clock me-2"></i>Rendez-vous à venir</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="appointments-list" id="appointments-container">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2 mb-0 small">Chargement des rendez-vous...</p>
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
                                    <small>Cours ce mois</small>
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
        <div class="row mt-4">
            <div class="col-12">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-list me-2"></i>Gestion de mes rendez-vous</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="row row-cols-1 row-cols-lg-2 g-4">
                            <div class="col">
                                <div class="border rounded-3 p-3 h-100">
                        <h6 class="text-muted mb-3">À venir</h6>
                        <div id="student-manage-upcoming-empty" class="text-muted small">Aucun rendez-vous planifié.</div>
                        <div id="student-manage-upcoming-list" class="rdv-manage-list mt-3"></div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="border rounded-3 p-3 h-100">
                                    <h6 class="text-muted mb-3">Historique</h6>
                                    <div id="student-manage-history-empty" class="text-muted small">Aucun rendez-vous terminé.</div>
                                    <div id="student-manage-history-list" class="rdv-manage-list mt-3"></div>
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
            let availableSlots = [];
            let selectedSlot = null;
            let studentUpcomingAppointments = [];
            let studentHistoryAppointments = [];
            const studentManageUpcomingList = document.getElementById('student-manage-upcoming-list');
            const studentManageHistoryList = document.getElementById('student-manage-history-list');
            const studentManageUpcomingEmpty = document.getElementById('student-manage-upcoming-empty');
            const studentManageHistoryEmpty = document.getElementById('student-manage-history-empty');
            const courseSearchInput = document.getElementById('course-search-input');
            const courseSearchResults = document.getElementById('course-search-results');
            const courseResultsCount = document.getElementById('course-results-count');
            const courseSuggestions = document.querySelectorAll('.course-suggestion');
            const selectedCourseBanner = document.getElementById('selected-course-banner');
            const selectedCourseLabel = document.getElementById('selected-course-label');
            const clearCourseSelectionBtn = document.getElementById('clear-course-selection');
            let selectedOfferId = null;
            let selectedCourseName = '';

            loadAvailableSlots();
            performCourseSearch();
            loadUpcomingAppointments();
            loadHistoryAppointments();
            loadStats();

            courseSearchInput?.addEventListener('input', debounce(function(event) {
                performCourseSearch(event.target.value.trim());
            }, 300));

            courseSuggestions.forEach(button => {
                button.addEventListener('click', function() {
                    const value = this.textContent.trim();
                    if (courseSearchInput) {
                        courseSearchInput.value = value;
                    }
                    performCourseSearch(value);
                });
            });

            courseSearchResults?.addEventListener('click', function(event) {
                const btn = event.target.closest('.select-course-btn');
                if (!btn) return;
                const offerId = btn.dataset.offerId;
                if (!offerId) return;
                selectedOfferId = offerId;
                selectedCourseName = btn.dataset.courseName || '';
                loadAvailableSlots(selectedOfferId);
            });

            clearCourseSelectionBtn?.addEventListener('click', () => {
                selectedOfferId = null;
                selectedCourseName = '';
                loadAvailableSlots();
            });

            async function loadAvailableSlots(offerId = null) {
                if (!offerId) {
                    availableSlots = [];
                    renderSlots([]);
                    updateSelectedCourseBanner();
                    return;
                }
                try {
                    const url = `../api/appointments.php?action=available_slots&offre_id=${encodeURIComponent(offerId)}`;
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data.success && data.slots) {
                        availableSlots = data.slots;
                        renderSlots(data.slots);
                        updateSelectedCourseBanner();
                    } else {
                        showEmptySlots(true);
                    }
                } catch (error) {
                    console.error('Erreur chargement créneaux:', error);
                    showEmptySlots(true);
                }
            }

            function renderSlots(slots) {
                const container = document.getElementById('slots-container');
                const wrapper = document.getElementById('slots-wrapper');

                if (!selectedOfferId) {
                    if (wrapper) wrapper.classList.add('disabled');
                    container.innerHTML = `
                        <div class="text-center py-5 text-muted w-100">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <p>Sélectionnez un cours pour afficher les créneaux disponibles.</p>
                        </div>
                    `;
                    return;
                }

                if (slots.length === 0) {
                    showEmptySlots(true);
                    return;
                }

                if (wrapper) wrapper.classList.remove('disabled');
                container.innerHTML = slots.map(slot => {
                    const dateDebut = new Date(slot.date_debut);
                    const dateFin = new Date(slot.date_fin);
                    const timeRange = formatTime(dateDebut) + ' - ' + formatTime(dateFin);
                    const dateStr = formatDateFr(dateDebut);

                    return `
                        <div class="col-6 mb-3">
                            <div class="time-slot" onclick="selectTimeSlot(${slot.id_creneau})" data-slot-id="${slot.id_creneau}">
                                <div>${timeRange}</div>
                                <small>${dateStr}</small>
                                <small class="d-block text-primary">${escapeHtml(slot.nom_professeur)}</small>
                                <small class="d-block">${escapeHtml(slot.nom_matiere || 'Cours')}</small>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function showEmptySlots(hasSelection) {
                const container = document.getElementById('slots-container');
                if (hasSelection) {
                    container.innerHTML = `
                        <div class="text-center py-5 text-muted w-100">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <p>Aucun créneau disponible pour ce cours</p>
                            <small>Essayez un autre cours ou revenez plus tard.</small>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="text-center py-5 text-muted w-100">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <p>Sélectionnez un cours pour afficher les créneaux disponibles.</p>
                        </div>
                    `;
                }
            }

            function performCourseSearch(term = '') {
                if (!courseSearchResults) return;
                const query = (term || '').trim();
                if (query.length < 2) {
                    courseSearchResults.innerHTML = '<p class="text-muted mb-0">Commencez à saisir un mot clé pour trouver un cours.</p>';
                    if (courseResultsCount) courseResultsCount.textContent = '';
                    return;
                }
                fetch(`../api/appointments.php?action=search_courses&query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            courseSearchResults.innerHTML = `<p class="text-danger mb-0">${data.error || 'Impossible de charger les cours.'}</p>`;
                            if (courseResultsCount) courseResultsCount.textContent = '';
                            return;
                        }
                        renderCourseResults(data.courses || []);
                    })
                    .catch(error => {
                        console.error('Recherche cours:', error);
                        courseSearchResults.innerHTML = '<p class="text-danger mb-0">Erreur lors de la recherche.</p>';
                        if (courseResultsCount) courseResultsCount.textContent = '';
                    });
            }

            function renderCourseResults(courses) {
                if (!courses.length) {
                    if (courseSearchResults) {
                        courseSearchResults.innerHTML = '<p class="text-muted mb-0">Aucun cours trouvé pour cette recherche.</p>';
                    }
                    if (courseResultsCount) courseResultsCount.textContent = '0 résultat';
                    return;
                }
                if (courseResultsCount) courseResultsCount.textContent = `${courses.length} résultat(s)`;
                if (courseSearchResults) {
                    courseSearchResults.innerHTML = courses.map(renderCourseCard).join('');
                }
            }

            function renderCourseCard(course) {
                const modes = (course.modes || '').split(',').filter(Boolean).map(mode => ucFirst(mode));
                const modesLabel = modes.length ? modes.join(', ') : 'Modes non spécifiés';
                const priceLabel = course.tarif_min !== null ? `${Number(course.tarif_min).toFixed(2)}€/h` : 'Tarif à définir';
                return `
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${escapeHtml(course.nom_matiere || course.titre || 'Cours')}</h6>
                                <p class="mb-1 text-muted small"><i class="fas fa-user me-1"></i>${escapeHtml(course.nom_professeur || '')}</p>
                                <p class="mb-1 text-muted small"><i class="fas fa-clock me-1"></i>${priceLabel}</p>
                                <p class="mb-0 text-muted small"><i class="fas fa-layer-group me-1"></i>${modesLabel}</p>
                            </div>
                            <div class="text-end">
                                    <span class="badge bg-secondary mb-2">${course.total_slots || 0} créneau(x)</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary select-course-btn"
                                    data-offer-id="${course.id_offre}" data-course-name="${escapeHtml(course.nom_matiere || course.titre || 'Cours')}">
                                        <i class="fas fa-calendar-check me-1"></i>Voir les créneaux
                                    </button>
                                </div>
                            </div>
                        </div>
                `;
            }

            function updateSelectedCourseBanner() {
                if (!selectedCourseBanner || !selectedCourseLabel) return;
                if (selectedOfferId) {
                    selectedCourseLabel.textContent = selectedCourseName || 'Cours sélectionné';
                    selectedCourseBanner.style.display = 'flex';
                } else {
                    selectedCourseBanner.style.display = 'none';
                }
            }

            window.selectTimeSlot = function(slotId) {
                if (!selectedOfferId) return;
                selectedSlot = availableSlots.find(s => s.id_creneau == slotId);
                if (!selectedSlot) return;

                document.querySelectorAll('.time-slot').forEach(slot => {
                    slot.classList.remove('selected');
                });
                document.querySelector(`[data-slot-id="${slotId}"]`).classList.add('selected');

                const dateDebut = new Date(selectedSlot.date_debut);
                const dateFin = new Date(selectedSlot.date_fin);
                const displayText = `${formatDateTimeFr(dateDebut)} - ${formatTime(dateFin)} - ${selectedSlot.nom_professeur} (${selectedSlot.nom_matiere})`;

                document.getElementById('selected-slot-display').value = displayText;
                document.getElementById('selected-slot-id').value = slotId;

                const modesPropose = selectedSlot.mode_propose.split(',');
                const modeSelect = document.getElementById('mode-choisi');
                Array.from(modeSelect.options).forEach(option => {
                    if (option.value && !modesPropose.includes(option.value)) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });

                document.getElementById('mode-info').textContent = `Modes disponibles: ${modesPropose.join(', ')}`;
            };

            async function loadUpcomingAppointments() {
                try {
                    const response = await fetch('../api/appointments.php?action=upcoming_appointments');
                    const data = await response.json();

                    if (data.success && data.appointments) {
                        studentUpcomingAppointments = data.appointments || [];
                        renderAppointments(studentUpcomingAppointments);
                    } else {
                        studentUpcomingAppointments = [];
                        showEmptyAppointments();
                    }
                } catch (error) {
                    console.error('Erreur chargement rendez-vous:', error);
                    showEmptyAppointments();
                    studentUpcomingAppointments = [];
                }
                renderStudentManageLists();
            }

        async function loadHistoryAppointments() {
                try {
                    const response = await fetch('../api/appointments.php?action=history_appointments');
                    const data = await response.json();
                    if (data.success && Array.isArray(data.appointments)) {
                        studentHistoryAppointments = data.appointments;
                    } else {
                        studentHistoryAppointments = [];
                    }
                } catch (error) {
                    console.error('Erreur chargement historique student:', error);
                    studentHistoryAppointments = [];
                }
                renderStudentManageLists();
            }

            function renderAppointments(appointments) {
                const container = document.getElementById('appointments-container');

                if (appointments.length === 0) {
                    showEmptyAppointments();
                    return;
                }

                container.innerHTML = appointments.map(appt => {
                    const status = getRdvStatus(appt.statut_reservation);

                    const modeIcons = {
                        'presentiel': 'fa-building',
                        'visio': 'fa-video',
                        'domicile': 'fa-home'
                    };
                    const modeIcon = modeIcons[appt.mode_choisi] || 'fa-question';

                    const dateDebut = new Date(appt.date_debut);
                    const dateFin = new Date(appt.date_fin);
                    const dateTimeStr = formatDateTimeFr(dateDebut) + ' - ' + formatTime(dateFin);

                    return `
                        <div class="appointment-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${escapeHtml(appt.nom_matiere || 'Cours')} - ${escapeHtml(appt.titre_cours)}</h6>
                                <span class="badge-status ${status.class}">${status.label}</span>
                            </div>
                            <p class="mb-1 text-muted small"><i class="fas fa-user me-2"></i>${escapeHtml(appt.nom_professeur)}</p>
                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i>${dateTimeStr}</p>
                            <p class="mb-1 text-muted small"><i class="fas ${modeIcon} me-2"></i>${ucFirst(appt.mode_choisi)}</p>
                            <p class="mb-0 text-muted small"><i class="fas fa-flag me-2"></i>${formatCourseStatus(appt.statut_cours)}</p>
                        </div>
                    `;
                }).join('');
            }

            function showEmptyAppointments() {
                document.getElementById('appointments-container').innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                        <p>Aucun rendez-vous à venir</p>
                        <small>Réservez un créneau pour commencer</small>
                    </div>
                `;
            }

            function renderStudentManageLists() {
                renderStudentManageSection(studentManageUpcomingList, studentManageUpcomingEmpty, studentUpcomingAppointments);
                renderStudentManageSection(studentManageHistoryList, studentManageHistoryEmpty, studentHistoryAppointments);
            }

            function renderStudentManageSection(container, emptyElement, list) {
                if (!container || !emptyElement) return;
                if (!Array.isArray(list) || list.length === 0) {
                    emptyElement.style.display = '';
                    container.innerHTML = '';
                    return;
                }
                emptyElement.style.display = 'none';
                container.innerHTML = list.map(renderStudentManageItem).join('');
            }

            function renderStudentManageItem(appt) {
                const status = getRdvStatus(appt.statut_reservation);
                const dateDebut = new Date(appt.date_debut);
                const dateFin = new Date(appt.date_fin);
                const modeIcons = {
                    'presentiel': 'fa-building',
                    'visio': 'fa-video',
                    'domicile': 'fa-home'
                };
                const modeIcon = modeIcons[appt.mode_choisi] || 'fa-question';

                return `
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${escapeHtml(appt.nom_matiere || appt.titre_cours)}</strong>
                                <p class="mb-1 text-muted small"><i class="fas fa-user me-1"></i>${escapeHtml(appt.nom_professeur || '')}</p>
                                <p class="mb-1 text-muted small"><i class="fas fa-calendar me-1"></i>${formatDateTimeFr(dateDebut)} - ${formatTime(dateFin)}</p>
                                <p class="mb-1 text-muted small"><i class="fas ${modeIcon} me-1"></i>${ucFirst(appt.mode_choisi)}</p>
                                <p class="mb-0 text-muted small"><i class="fas fa-flag me-1"></i>${formatCourseStatus(appt.statut_cours)}</p>
                            </div>
                            <span class="badge-status ${status.class}">${status.label}</span>
                        </div>
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
                            <h4 class="text-primary">${stats.cours_month}</h4>
                            <small>Cours ce mois</small>
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

            function getRdvStatus(status) {
                const map = {
                    'confirmee': { class: 'confirmed', label: 'Confirmé' },
                    'en_attente': { class: 'waiting', label: 'En attente' },
                    'en_cours': { class: 'in-progress', label: 'En cours' },
                    'terminee': { class: 'finished', label: 'Terminé' }
                };
                return map[status] || { class: 'waiting', label: 'En attente' };
            }

            function formatCourseStatus(status) {
                if (status === 'en_cours') return 'En cours';
                if (status === 'termine') return 'Terminé';
                return 'À venir';
            }

            document.getElementById('booking-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const slotId = document.getElementById('selected-slot-id').value;
                const modeChoisi = document.getElementById('mode-choisi').value;

                if (!slotId) {
                    alert('Veuillez sélectionner un créneau');
                    return;
                }

                if (!modeChoisi) {
                    alert('Veuillez sélectionner un mode de cours');
                    return;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Réservation en cours...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'book_slot');
                    formData.append('creneau_id', slotId);
                    formData.append('mode_choisi', modeChoisi);
                    formData.append('csrf_token', '<?= csrf_token() ?>');

                    const response = await fetch('../api/appointments.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Réservation créée avec succès ! Le professeur confirmera votre demande.');

                        this.reset();
                        document.getElementById('selected-slot-display').value = '';
                        document.getElementById('selected-slot-id').value = '';
                        document.getElementById('mode-info').textContent = '';
                        document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
                        selectedSlot = null;

                        loadAvailableSlots();
                        loadUpcomingAppointments();
                        loadStats();
                    } else {
                        alert('Erreur lors de la réservation: ' + (data.error || 'Erreur inconnue'));
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la réservation. Veuillez réessayer.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Prendre rendez-vous';
                }
            });

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

            function debounce(fn, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => fn.apply(this, args), delay);
                };
            }
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

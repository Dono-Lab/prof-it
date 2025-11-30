<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_role('teacher');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'teacher_rdv';

$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

$successMessage = '';
$errorMessage = '';
$sessionsMonth = 0;
$totalHeures = 0;

function format_datetime_input($value)
{
    if (empty($value)) {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $ts);
}

function format_date_fr($value)
{
    if (empty($value)) {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '';
    }
    return date('d/m/Y H:i', $ts);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $errorMessage = 'Token CSRF invalide.';
    } else {
        if (isset($_POST['create_offer'])) {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tarifHoraire = floatval($_POST['tarif_horaire'] ?? 0);
            $matiereId = intval($_POST['matiere_id'] ?? 0);

            if ($titre === '' || $tarifHoraire <= 0 || $matiereId <= 0) {
                $errorMessage = 'Veuillez remplir tous les champs obligatoires.';
            } else {
                try {
                    $conn->beginTransaction();

            $stmt = $conn->prepare('INSERT INTO offre_cours (titre, description, tarif_horaire_defaut) VALUES (?, ?, ?)');
            $stmt->execute([$titre, $description, $tarifHoraire]);
                    $offreId = $conn->lastInsertId();

                    $stmt = $conn->prepare('INSERT INTO enseigner (id_utilisateur, id_offre, actif) VALUES (?, ?, 1)');
                    $stmt->execute([$userId, $offreId]);

                    $stmt = $conn->prepare('INSERT INTO couvrir (id_offre, id_matiere) VALUES (?, ?)');
                    $stmt->execute([$offreId, $matiereId]);

                    $conn->commit();
                    $successMessage = 'Offre créée avec succès.';
                } catch (Exception $e) {
                    $conn->rollBack();
                    $errorMessage = 'Erreur lors de la création de l\'offre : ' . $e->getMessage();
                }
            }
        } elseif (isset($_POST['create_slot'])) {
            $offreId = intval($_POST['offre_id'] ?? 0);
            $dateDebut = trim($_POST['date_debut'] ?? '');
            $dateFin = trim($_POST['date_fin'] ?? '');
            $tarifHoraire = floatval($_POST['tarif_horaire'] ?? 0);
            $lieu = trim($_POST['lieu'] ?? '');
            $modes = $_POST['mode_propose'] ?? [];

            $allowedModes = ['presentiel', 'visio', 'domicile'];
            $cleanModes = [];
            foreach ($modes as $mode) {
                $modeClean = strtolower(trim($mode));
                if (in_array($modeClean, $allowedModes, true) && !in_array($modeClean, $cleanModes, true)) {
                    $cleanModes[] = $modeClean;
                }
            }

            if ($offreId <= 0) {
                $errorMessage = 'Offre invalide.';
            } elseif ($dateDebut === '' || $dateFin === '') {
                $errorMessage = 'Dates obligatoires.';
            } elseif ($tarifHoraire <= 0) {
                $errorMessage = 'Tarif invalide.';
            } elseif (count($cleanModes) === 0) {
                $errorMessage = 'Choisissez au moins un mode.';
            } else {
                $startTs = strtotime($dateDebut);
                $endTs = strtotime($dateFin);
                if ($startTs === false || $endTs === false) {
                    $errorMessage = 'Format de date invalide.';
                } elseif ($endTs <= $startTs) {
                    $errorMessage = 'Fin doit être après début.';
                } else {
                    try {
                        $stmt = $conn->prepare('SELECT id FROM enseigner WHERE id_offre = ? AND id_utilisateur = ? AND actif = 1');
                        $stmt->execute([$offreId, $userId]);
                        if (!$stmt->fetch()) {
                            $errorMessage = 'Offre non autorisée.';
                        } else {
                            $stmt = $conn->prepare('INSERT INTO creneau (id_utilisateur, id_offre, date_debut, date_fin, tarif_horaire, mode_propose, lieu, statut_creneau) VALUES (?, ?, ?, ?, ?, ?, ?, "disponible")');
                            $stmt->execute([$userId, $offreId, $dateDebut, $dateFin, $tarifHoraire, implode(',', $cleanModes), $lieu]);
                            $successMessage = 'Créneau ajouté.';
                        }
                    } catch (Exception $e) {
                        $errorMessage = 'Erreur création créneau : ' . $e->getMessage();
                    }
                }
            }
        } elseif (isset($_POST['update_status'])) {
            $reservationId = intval($_POST['reservation_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            if ($reservationId <= 0) {
                $errorMessage = 'Réservation invalide.';
            } elseif ($newStatus !== 'en_cours' && $newStatus !== 'terminee') {
                $errorMessage = 'Statut non autorisé.';
            } else {
                try {
                    $stmt = $conn->prepare('SELECT r.statut_reservation FROM reservation r INNER JOIN creneau c ON r.id_creneau = c.id_creneau WHERE r.id_reservation = ? AND c.id_utilisateur = ?');
                    $stmt->execute([$reservationId, $userId]);
                    $reservation = $stmt->fetch();
                    if (!$reservation) {
                        $errorMessage = 'Réservation introuvable.';
                    } else {
                        $current = $reservation['statut_reservation'];
                        $ok = true;
                        if ($newStatus === 'en_cours' && $current !== 'confirmee') {
                            $ok = false;
                        }
                        if ($newStatus === 'terminee' && $current !== 'en_cours') {
                            $ok = false;
                        }
                        if ($ok) {
                            $stmt = $conn->prepare('UPDATE reservation SET statut_reservation = ? WHERE id_reservation = ?');
                            $stmt->execute([$newStatus, $reservationId]);
                            $successMessage = 'Statut mis à jour.';
                        } else {
                            $errorMessage = 'Transition de statut impossible.';
                        }
                    }
                } catch (Exception $e) {
                    $errorMessage = 'Erreur statut : ' . $e->getMessage();
                }
            }
        } elseif (isset($_POST['update_offer'])) {
            $offreId = intval($_POST['offre_id'] ?? 0);
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tarifHoraire = floatval($_POST['tarif_horaire'] ?? 0);
            $matiereId = intval($_POST['matiere_id'] ?? 0);

            if ($offreId <= 0) {
                $errorMessage = 'Offre invalide.';
            } elseif ($titre === '' || $tarifHoraire <= 0 || $matiereId <= 0) {
                $errorMessage = 'Champs obligatoires manquants.';
            } else {
                try {
                    $stmt = $conn->prepare('SELECT id_offre FROM enseigner WHERE id_offre = ? AND id_utilisateur = ? AND actif = 1');
                    $stmt->execute([$offreId, $userId]);
                    if (!$stmt->fetch()) {
                        $errorMessage = 'Offre non autorisée.';
                    } else {
            $stmt = $conn->prepare('UPDATE offre_cours SET titre = ?, description = ?, tarif_horaire_defaut = ? WHERE id_offre = ?');
            $stmt->execute([$titre, $description, $tarifHoraire, $offreId]);
                        $stmt = $conn->prepare('UPDATE couvrir SET id_matiere = ? WHERE id_offre = ?');
                        $stmt->execute([$matiereId, $offreId]);
                        if ($stmt->rowCount() === 0) {
                            $stmtInsert = $conn->prepare('INSERT INTO couvrir (id_offre, id_matiere) VALUES (?, ?)');
                            $stmtInsert->execute([$offreId, $matiereId]);
                        }
                        $successMessage = 'Offre mise à jour.';
                    }
                } catch (Exception $e) {
                    $errorMessage = 'Erreur mise à jour offre : ' . $e->getMessage();
                }
            }
        } elseif (isset($_POST['delete_offer'])) {
            $offreId = intval($_POST['offre_id'] ?? 0);
            if ($offreId <= 0) {
                $errorMessage = 'Offre invalide.';
            } else {
                try {
                    $stmt = $conn->prepare('SELECT id_offre FROM enseigner WHERE id_offre = ? AND id_utilisateur = ? AND actif = 1');
                    $stmt->execute([$offreId, $userId]);
                    if (!$stmt->fetch()) {
                        $errorMessage = 'Offre non autorisée.';
                    } else {
                        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM creneau WHERE id_offre = ?');
                        $stmt->execute([$offreId]);
                        $row = $stmt->fetch();
                        $hasSlots = false;
                        if ($row && isset($row['total'])) {
                            $hasSlots = (int)$row['total'] > 0;
                        }
                        if ($hasSlots) {
                            $errorMessage = 'Supprimez les créneaux liés avant de retirer cette offre.';
                        } else {
                            $conn->beginTransaction();
                            $conn->prepare('DELETE FROM couvrir WHERE id_offre = ?')->execute([$offreId]);
                            $conn->prepare('DELETE FROM enseigner WHERE id_offre = ? AND id_utilisateur = ?')->execute([$offreId, $userId]);
                            $conn->prepare('DELETE FROM offre_cours WHERE id_offre = ?')->execute([$offreId]);
                            $conn->commit();
                            $successMessage = 'Offre supprimée.';
                        }
                    }
                } catch (Exception $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    $errorMessage = 'Erreur suppression offre : ' . $e->getMessage();
                }
            }
        }
    }
}
try {
    $stmt = $conn->prepare('SELECT o.id_offre, o.titre, o.description, o.tarif_horaire_defaut, m.id_matiere, m.nom_matiere FROM offre_cours o LEFT JOIN enseigner e ON o.id_offre = e.id_offre LEFT JOIN couvrir c ON o.id_offre = c.id_offre LEFT JOIN matiere m ON c.id_matiere = m.id_matiere WHERE e.id_utilisateur = ? AND e.actif = 1 ORDER BY o.titre');
    $stmt->execute([$userId]);
    $offres = $stmt->fetchAll();
} catch (Exception $e) {
    $offres = [];
    if ($errorMessage === '') {
        $errorMessage = 'Impossible de charger vos offres.';
    }
}

try {
    $stmtMatieres = $conn->prepare('SELECT id_matiere, nom_matiere FROM matiere ORDER BY nom_matiere');
    $stmtMatieres->execute();
    $matieres = $stmtMatieres->fetchAll();
} catch (Exception $e) {
    $matieres = [];
}

try {
    $stmtSlots = $conn->prepare('SELECT c.id_creneau, c.date_debut, c.date_fin, c.tarif_horaire, c.mode_propose, c.lieu, o.titre AS titre_cours, m.nom_matiere FROM creneau c INNER JOIN offre_cours o ON c.id_offre = o.id_offre LEFT JOIN couvrir co ON o.id_offre = co.id_offre LEFT JOIN matiere m ON co.id_matiere = m.id_matiere WHERE c.id_utilisateur = ? AND c.statut_creneau = "disponible" AND c.date_debut > NOW() ORDER BY c.date_debut ASC');
    $stmtSlots->execute([$userId]);
    $availableSlots = $stmtSlots->fetchAll();
} catch (Exception $e) {
    $availableSlots = [];
}

try {
    $stmtUpcoming = $conn->prepare('SELECT r.id_reservation, r.statut_reservation, r.mode_choisi, c.date_debut, c.date_fin, c.lieu, o.titre AS titre_cours, m.nom_matiere, CONCAT(etudiant.prenom, " ", etudiant.nom) AS nom_etudiant FROM creneau c INNER JOIN reservation r ON c.id_creneau = r.id_creneau INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id INNER JOIN offre_cours o ON c.id_offre = o.id_offre LEFT JOIN couvrir co ON o.id_offre = co.id_offre LEFT JOIN matiere m ON co.id_matiere = m.id_matiere WHERE c.id_utilisateur = ? AND r.statut_reservation IN ("en_attente", "confirmee", "en_cours") AND c.date_fin >= NOW() ORDER BY c.date_debut ASC');
    $stmtUpcoming->execute([$userId]);
    $upcomingSessions = $stmtUpcoming->fetchAll();
} catch (Exception $e) {
    $upcomingSessions = [];
}

try {
    $stmtHistory = $conn->prepare('SELECT r.id_reservation, r.statut_reservation, r.mode_choisi, c.date_debut, c.date_fin, c.lieu, o.titre AS titre_cours, m.nom_matiere, CONCAT(etudiant.prenom, " ", etudiant.nom) AS nom_etudiant FROM creneau c INNER JOIN reservation r ON c.id_creneau = r.id_creneau INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id INNER JOIN offre_cours o ON c.id_offre = o.id_offre LEFT JOIN couvrir co ON o.id_offre = co.id_offre LEFT JOIN matiere m ON co.id_matiere = m.id_matiere WHERE c.id_utilisateur = ? AND r.statut_reservation = "terminee" ORDER BY c.date_debut DESC LIMIT 10');
    $stmtHistory->execute([$userId]);
    $historySessions = $stmtHistory->fetchAll();
} catch (Exception $e) {
    $historySessions = [];
}

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
            </div>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card-custom mb-4" id="offer-form-container">
                <div class="card-header-custom">
                    <h5><i class="fas fa-book me-2"></i>Créer une nouvelle offre de cours</h5>
                </div>
                <div class="card-body-custom">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="create_offer" value="1">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Titre de l'offre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="titre" required placeholder="Ex : Cours de mathématiques niveau lycée">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Matière <span class="text-danger">*</span></label>
                                <select class="form-select" name="matiere_id" required>
                                    <option value="">Sélectionnez...</option>
                                    <?php foreach ($matieres as $matiere): ?>
                                        <option value="<?php echo (int)$matiere['id_matiere']; ?>"><?php echo htmlspecialchars($matiere['nom_matiere'], ENT_QUOTES, 'UTF-8'); ?></option>
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
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-briefcase me-2"></i>Mes offres</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($offres) === 0): ?>
                                <p class="text-muted mb-0">Aucune offre enregistrée.</p>
                            <?php else: ?>
                                <?php foreach ($offres as $offre): ?>
                                    <?php $offreMatiereId = isset($offre['id_matiere']) ? (int)$offre['id_matiere'] : 0; ?>
                                    <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($offre['titre'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (!empty($offre['nom_matiere'])): ?>
                                                    <div class="small text-muted">Matière : <?php echo htmlspecialchars($offre['nom_matiere'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                                <div class="small text-muted">Tarif défaut : <?php echo number_format((float)$offre['tarif_horaire_defaut'], 2, ',', ' '); ?> €/h</div>
                                            </div>
                                            <div class="d-flex flex-column gap-2 ms-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary offer-edit-btn" data-target="offer-edit-<?php echo (int)$offre['id_offre']; ?>">Modifier</button>
                                                <form method="POST" onsubmit="return confirm('Supprimer cette offre ?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="delete_offer" value="1">
                                                    <input type="hidden" name="offre_id" value="<?php echo (int)$offre['id_offre']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="mt-3 border-top pt-3" id="offer-edit-<?php echo (int)$offre['id_offre']; ?>" style="display: none;">
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="update_offer" value="1">
                                                <input type="hidden" name="offre_id" value="<?php echo (int)$offre['id_offre']; ?>">
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Titre</label>
                                                        <input type="text" class="form-control" name="titre" value="<?php echo htmlspecialchars($offre['titre'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">Matière</label>
                                                        <select class="form-select" name="matiere_id" required>
                                                            <option value="">Sélectionnez...</option>
                                                            <?php foreach ($matieres as $matiere): ?>
                                                                <?php $isSelected = ($offreMatiereId > 0 && $offreMatiereId === (int)$matiere['id_matiere']); ?>
                                                                <?php if ($isSelected): ?>
                                                                    <option value="<?php echo (int)$matiere['id_matiere']; ?>" selected><?php echo htmlspecialchars($matiere['nom_matiere'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                                <?php else: ?>
                                                                    <option value="<?php echo (int)$matiere['id_matiere']; ?>"><?php echo htmlspecialchars($matiere['nom_matiere'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">Tarif (€)</label>
                                                        <input type="number" class="form-control" name="tarif_horaire" step="0.01" min="0" value="<?php echo htmlspecialchars($offre['tarif_horaire_defaut'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($offre['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-calendar-alt me-2"></i>Mes disponibilités</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($availableSlots) === 0): ?>
                                <div class="text-center py-5 text-muted w-100">
                                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                                    <p>Aucun créneau disponible</p>
                                    <small>Utilisez le formulaire pour créer vos disponibilités</small>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($availableSlots as $slot): ?>
                                        <?php
                                        $slotTitle = $slot['titre_cours'];
                                        if (!empty($slot['nom_matiere'])) {
                                            $slotTitle = $slot['nom_matiere'];
                                        }
                                        $slotModes = [];
                                        if (!empty($slot['mode_propose'])) {
                                            $slotModes = array_map('trim', explode(',', $slot['mode_propose']));
                                        }
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="time-slot h-100">
                                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($slotTitle, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars(format_date_fr($slot['date_debut']), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(date('H:i', strtotime($slot['date_fin'])), ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small mt-1">Tarif : <?php echo number_format((float)$slot['tarif_horaire'], 2, ',', ' '); ?> €/h</div>
                                                <?php if (!empty($slot['lieu'])): ?>
                                                    <div class="small text-muted"><i class="fas fa-location-dot me-1"></i><?php echo htmlspecialchars($slot['lieu'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <?php foreach ($slotModes as $mode): ?>
                                                        <?php if ($mode === '') { continue; } ?>
                                                        <span class="badge bg-light text-dark me-1 mb-1"><?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-clock me-2"></i>Créer un créneau</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($offres) === 0): ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Créez d'abord une offre avant d'ajouter des disponibilités.
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="create_slot" value="1">

                                    <div class="mb-3">
                                        <label class="form-label">Offre de cours <span class="text-danger">*</span></label>
                                        <select class="form-select" name="offre_id" id="offre-id" required>
                                            <option value="">Sélectionnez une offre</option>
                                            <?php foreach ($offres as $offre): ?>
                                                <?php $optionLabel = $offre['titre']; if (!empty($offre['nom_matiere'])) { $optionLabel = $offre['titre'] . ' - ' . $offre['nom_matiere']; } ?>
                                                <option value="<?php echo (int)$offre['id_offre']; ?>" data-tarif="<?php echo htmlspecialchars($offre['tarif_horaire_defaut'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date et heure de début <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" name="date_debut" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Date et heure de fin <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" name="date_fin" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tarif horaire (€) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="tarif_horaire" id="tarif-horaire" step="0.01" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Modes proposés <span class="text-danger">*</span></label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="mode_propose[]" value="presentiel" id="mode-presentiel">
                                            <label class="form-check-label" for="mode-presentiel">Présentiel</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="mode_propose[]" value="visio" id="mode-visio">
                                            <label class="form-check-label" for="mode-visio">Visio</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="mode_propose[]" value="domicile" id="mode-domicile">
                                            <label class="form-check-label" for="mode-domicile">À domicile</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Lieu (optionnel)</label>
                                        <input type="text" class="form-control" name="lieu" placeholder="Ex : Salle 102, Paris 5e...">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-plus me-2"></i>Ajouter le créneau
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-clock me-2"></i>Sessions à venir</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($upcomingSessions) === 0): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                    <p>Aucune session à venir</p>
                                    <small>Les réservations apparaîtront ici</small>
                                </div>
                            <?php else: ?>
                                <div class="appointments-list">
                                    <?php foreach ($upcomingSessions as $session): ?>
                                        <?php
                                        $badgeClass = 'waiting';
                                        $badgeLabel = 'En attente';
                                        if ($session['statut_reservation'] === 'confirmee') {
                                            $badgeClass = 'confirmed';
                                            $badgeLabel = 'Confirmé';
                                        } elseif ($session['statut_reservation'] === 'en_cours') {
                                            $badgeClass = 'in-progress';
                                            $badgeLabel = 'En cours';
                                        }
                                        $courseStatus = compute_course_status($session['date_debut'], $session['date_fin'], $session['statut_reservation']);
                                        $modeLabel = $session['mode_choisi'] !== '' ? ucfirst($session['mode_choisi']) : '';
                                        $title = $session['titre_cours'];
                                        if (!empty($session['nom_matiere'])) {
                                            $title = $session['nom_matiere'];
                                        }
                                        ?>
                                        <div class="appointment-card mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h6>
                                                <span class="badge-status <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($session['nom_etudiant'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <p class="mb-1 text-muted small"><i class="fas fa-calendar me-2"></i><?php echo format_date_fr($session['date_debut']); ?> - <?php echo date('H:i', strtotime($session['date_fin'])); ?></p>
                                            <?php if ($modeLabel !== ''): ?>
                                                <p class="mb-1 text-muted small"><i class="fas fa-chalkboard-teacher me-2"></i><?php echo htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($session['lieu'])): ?>
                                                <p class="mb-1 text-muted small"><i class="fas fa-location-dot me-2"></i><?php echo htmlspecialchars($session['lieu'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <?php endif; ?>
                                            <p class="mb-0 text-muted small"><i class="fas fa-info-circle me-2"></i>Status cours : <?php echo htmlspecialchars(course_status_label($courseStatus), ENT_QUOTES, 'UTF-8'); ?></p>

                                            <?php if ($session['statut_reservation'] === 'confirmee'): ?>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="reservation_id" value="<?php echo (int)$session['id_reservation']; ?>">
                                                    <input type="hidden" name="new_status" value="en_cours">
                                                    <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-play me-1"></i>Marquer en cours</button>
                                                </form>
                                            <?php elseif ($session['statut_reservation'] === 'en_cours'): ?>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="reservation_id" value="<?php echo (int)$session['id_reservation']; ?>">
                                                    <input type="hidden" name="new_status" value="terminee">
                                                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-check me-1"></i>Marquer terminé</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-history me-2"></i>Historique</h5>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($historySessions) === 0): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-history fa-3x mb-3"></i>
                                    <p>Aucun historique</p>
                                    <small>Les sessions terminées apparaîtront ici</small>
                                </div>
                            <?php else: ?>
                                <div class="appointments-list">
                                    <?php foreach ($historySessions as $session): ?>
                                        <?php
                                        $badgeClass = 'completed';
                                        $badgeLabel = 'Terminé';
                                        if ($session['statut_reservation'] === 'annulee') {
                                            $badgeClass = 'cancelled';
                                            $badgeLabel = 'Annulé';
                                        }
                                        $title = $session['titre_cours'];
                                        if (!empty($session['nom_matiere'])) {
                                            $title = $session['nom_matiere'];
                                        }
                                        ?>
                                        <div class="appointment-card mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h6>
                                                <span class="badge-status <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <p class="mb-1 text-muted small"><i class="fas fa-user-graduate me-2"></i><?php echo htmlspecialchars($session['nom_etudiant'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            <p class="mb-0 text-muted small"><i class="fas fa-calendar me-2"></i><?php echo format_date_fr($session['date_debut']); ?> - <?php echo date('H:i', strtotime($session['date_fin'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var offreSelect = document.getElementById('offre-id');
            var tarifInput = document.getElementById('tarif-horaire');
            if (offreSelect && tarifInput) {
                offreSelect.addEventListener('change', function() {
                    var selected = this.options[this.selectedIndex];
                    var tarif = selected.getAttribute('data-tarif');
                    if (tarif) {
                        tarifInput.value = tarif;
                    }
                });
            }

            var editButtons = document.querySelectorAll('.offer-edit-btn');
            editButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-target');
                    var block = document.getElementById(targetId);
                    if (!block) {
                        return;
                    }
                    if (block.style.display === 'none' || block.style.display === '') {
                        block.style.display = 'block';
                    } else {
                        block.style.display = 'none';
                    }
                });
            });
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

<?php
$pageTitle = 'Paramètres';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';

safe_session_start();
csrf_protect();

$userId = $_SESSION['user_id'] ?? null;
$successMessage = '';
$errorMessage = '';
unset($_SESSION['success_message']);

if ($userId === null) {
    http_response_code(400);
    die('Utilisateur non trouvé dans la session.');
}

$stmt = $conn->prepare("
    SELECT nom, prenom, email, telephone, adresse, ville, code_postal, bio, photo_url
    FROM users
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die('Utilisateur introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '') {
        $errorMessage = 'Nom, prénom et email sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Email invalide.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $errorMessage = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== '' && $password !== $password_confirm) {
        $errorMessage = 'Les mots de passe ne correspondent pas.';
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->rowCount() > 0) {
            $errorMessage = 'Cet email est déjà utilisé par un autre compte.';
        } else {
            try {
                if ($password !== '') {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUpdate = $conn->prepare("
                        UPDATE users 
                        SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, bio = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$nom, $prenom, $email, $telephone, $adresse, $ville, $code_postal, $bio, $hashedPassword, $userId]);
                } else {
                    $stmtUpdate = $conn->prepare("
                        UPDATE users 
                        SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, bio = ?
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$nom, $prenom, $email, $telephone, $adresse, $ville, $code_postal, $bio, $userId]);
                }

                $_SESSION['name'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;

                $successMessage = 'Paramètres mis à jour avec succès.';

                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['adresse'] = $adresse;
                $user['ville'] = $ville;
                $user['code_postal'] = $code_postal;
                $user['bio'] = $bio;
            } catch (PDOException $e) {
                $errorMessage = 'Erreur lors de la mise à jour des paramètres.';
            }
        }
    }
}
require '../templates/header.php';
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
<div class="container my-5">
    <h1 class="text-center mb-4" style="color: var(--secondary-color);">Paramètres du compte</h1>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <!-- Section Avatar actuel -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Avatar actuel</label>
                        <div class="d-flex align-items-center gap-3">
                            <div id="current-avatar-display">
                                <?php if (!empty($user['photo_url'])): ?>
                                    <img src="../<?= htmlspecialchars($user['photo_url'], ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Avatar" class="rounded-circle" width="64" height="64">
                                <?php else: ?>
                                    <div class="text-muted small">Aucun avatar défini.</div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($user['photo_url'])): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="delete-avatar-btn">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section Avatars prédéfinis -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Choisir un avatar prédéfini</label>
                        <div class="avatar-grid">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <div class="avatar-option" data-avatar="<?= $i ?>">
                                    <img src="../assets/img/avatars/presets/avatar-<?= $i ?>.svg"
                                        alt="Avatar <?= $i ?>" class="avatar-preset-img">
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Section Upload personnalisé -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Ou télécharger un avatar personnalisé</label>
                        <form method="post" action="upload_avatar.php" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="input-group">
                                <input type="file" class="form-control" name="avatar" accept="image/*" required>
                                <button type="submit" class="btn btn-outline-primary">Télécharger</button>
                            </div>
                            <small class="text-muted">JPEG, PNG ou GIF, 2 Mo max.</small>
                        </form>
                    </div>

                    <hr class="my-4">
                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom"
                                value="<?= htmlspecialchars($_POST['prenom'] ?? $user['prenom'], ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom"
                                value="<?= htmlspecialchars($_POST['nom'] ?? $user['nom'], ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" name="telephone"
                                value="<?= htmlspecialchars($_POST['telephone'] ?? ($user['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <textarea class="form-control" name="adresse" rows="2"><?= htmlspecialchars($_POST['adresse'] ?? ($user['adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="ville"
                                    value="<?= htmlspecialchars($_POST['ville'] ?? ($user['ville'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Code postal</label>
                                <input type="text" class="form-control" name="code_postal"
                                    value="<?= htmlspecialchars($_POST['code_postal'] ?? ($user['code_postal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio / Note</label>
                            <textarea class="form-control" name="bio" rows="3"><?= htmlspecialchars($_POST['bio'] ?? ($user['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Sécurité</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?= csrf_field() ?>
                        <p class="text-muted small mb-3">
                            Laissez les champs de mot de passe vides si vous ne souhaitez pas le changer.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" name="password_confirm">
                        </div>
                        <input type="hidden" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? $user['nom'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? $user['prenom'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? ($user['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="adresse" value="<?= htmlspecialchars($_POST['adresse'] ?? ($user['adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="ville" value="<?= htmlspecialchars($_POST['ville'] ?? ($user['ville'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="code_postal" value="<?= htmlspecialchars($_POST['code_postal'] ?? ($user['code_postal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="bio" value="<?= htmlspecialchars($_POST['bio'] ?? ($user['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-primary">Mettre à jour le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-color: #1898e9;
        --secondary-color: #152154;
    }

    .bg-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
    }

    .card {
        border: none;
        border-radius: 10px;
        overflow: hidden;
    }

    .card-header {
        border-bottom: none;
        padding: 1rem 1.5rem;
    }


    .avatar-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 10px;
    }

    .avatar-option {
        cursor: pointer;
        border: 3px solid transparent;
        border-radius: 50%;
        padding: 5px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }

    .avatar-option:hover {
        border-color: #6366f1;
        transform: scale(1.05);
    }

    .avatar-option.selected {
        border-color: #10b981;
        background-color: #e8f5e9;
    }

    .avatar-preset-img {
        width: 100%;
        height: auto;
        border-radius: 50%;
        display: block;
    }

    @media (max-width: 576px) {
        .avatar-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = '<?= csrf_token() ?>';
        const avatarOptions = document.querySelectorAll('.avatar-option');
        const deleteBtn = document.getElementById('delete-avatar-btn');
        const currentAvatarDisplay = document.getElementById('current-avatar-display');

        // Sélection d'un avatar prédéfini
        avatarOptions.forEach(option => {
            option.addEventListener('click', function() {
                const avatarNumber = this.dataset.avatar;

                // Marquer visuellement l'avatar sélectionné
                avatarOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');

                // Envoyer la requête AJAX
                fetch('select_preset_avatar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `csrf_token=${encodeURIComponent(csrfToken)}&avatar=${avatarNumber}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mettre à jour l'affichage de l'avatar actuel
                            currentAvatarDisplay.innerHTML = `
                        <img src="${data.avatar_url}" alt="Avatar" class="rounded-circle" width="64" height="64">
                    `;

                            // Ajouter le bouton supprimer si pas présent
                            if (!deleteBtn) {
                                const deleteButton = document.createElement('button');
                                deleteButton.type = 'button';
                                deleteButton.className = 'btn btn-outline-danger btn-sm';
                                deleteButton.id = 'delete-avatar-btn';
                                deleteButton.innerHTML = '<i class="fas fa-trash"></i> Supprimer';
                                currentAvatarDisplay.parentElement.appendChild(deleteButton);
                                attachDeleteHandler(deleteButton);
                            }

                            // Afficher message de succès
                            showAlert('success', data.message);

                            // Recharger après 1 seconde pour mettre à jour le header
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showAlert('danger', data.message);
                        }
                    })
                    .catch(error => {
                        showAlert('danger', 'Erreur lors de la mise à jour de l\'avatar.');
                        console.error('Error:', error);
                    });
            });
        });

        // Suppression de l'avatar
        if (deleteBtn) {
            attachDeleteHandler(deleteBtn);
        }

        function attachDeleteHandler(btn) {
            btn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer votre avatar ?')) {
                    fetch('delete_avatar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `csrf_token=${encodeURIComponent(csrfToken)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showAlert('success', data.message);
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showAlert('danger', data.message);
                            }
                        })
                        .catch(error => {
                            showAlert('danger', 'Erreur lors de la suppression de l\'avatar.');
                            console.error('Error:', error);
                        });
                }
            });
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

            const mainContent = document.querySelector('.dashboard-content');
            const pageTitle = mainContent.querySelector('.page-title');
            pageTitle.insertAdjacentElement('afterend', alertDiv);
        }
    });
</script>
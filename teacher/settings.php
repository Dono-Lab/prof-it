<?php
$pageTitle = 'Paramètres';
$currentNav = 'teacher_settings';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';

safe_session_start();
require_role('teacher');
csrf_protect();

$userId = $_SESSION['user_id'] ?? null;
$successMessage = '';
$errorMessage = '';

if ($userId === null) {
    http_response_code(400);
    die('Utilisateur non trouvé dans la session.');
}

$stmt = $conn->prepare("SELECT nom, prenom, email, telephone, adresse, ville, code_postal, bio, photo_url FROM users WHERE id = ?");
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
    $changePassword = isset($_POST['change_password']) && $_POST['change_password'] === '1';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($nom === '' || $prenom === '' || $email === '') {
        $errorMessage = 'Nom, prénom et email sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Email invalide.';
    } elseif ($changePassword && strlen($password) < 6) {
        $errorMessage = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($changePassword && $password !== $password_confirm) {
        $errorMessage = 'Les mots de passe ne correspondent pas.';
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheck->execute([$email, $userId]);
        if ($stmtCheck->rowCount() > 0) {
            $errorMessage = 'Cet email est déjà utilisé par un autre compte.';
        } else {
            $photoUrl = $user['photo_url'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                    $type = $_FILES['avatar']['type'];
                    $size = $_FILES['avatar']['size'];
                    
                    if (!isset($allowedTypes[$type])) {
                        $errorMessage = 'Format d\'image invalide (JPG, PNG, GIF).';
                    } elseif ($size > 2 * 1024 * 1024) {
                        $errorMessage = 'L\'image est trop volumineuse (Max 2Mo).';
                    } else {
                        $ext = $allowedTypes[$type];
                        $filename = 'teacher_' . $userId . '_' . time() . '.' . $ext;
                        $targetDir = __DIR__ . '/../assets/img/avatars';
                        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                        
                            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . '/' . $filename)) {
                                $photoUrl = 'assets/img/avatars/' . $filename;
                                $_SESSION['avatar_url'] = $photoUrl;
                            } else {
                                $errorMessage = 'Erreur lors de l\'enregistrement de l\'image.';
                            }
                        }
                } else {
                    $errorMessage = 'Erreur lors du téléchargement de l\'image.';
                }
            }

            if (!$errorMessage) {
                try {
                    if ($changePassword && $password !== '') {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmtUpdate = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, bio = ?, password = ?, photo_url = ? WHERE id = ?");
                        $stmtUpdate->execute([$nom, $prenom, $email, $telephone, $adresse, $ville, $code_postal, $bio, $hashedPassword, $photoUrl, $userId]);
                    } else {
                        $stmtUpdate = $conn->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, ville = ?, code_postal = ?, bio = ?, photo_url = ? WHERE id = ?");
                        $stmtUpdate->execute([$nom, $prenom, $email, $telephone, $adresse, $ville, $code_postal, $bio, $photoUrl, $userId]);
                    }

                    $_SESSION['name'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['email'] = $email;
                    $successMessage = 'Paramètres mis à jour avec succès.';
                    $user = array_merge($user, compact('nom', 'prenom', 'email', 'telephone', 'adresse', 'ville', 'code_postal', 'bio'));
                    $user['photo_url'] = $photoUrl;
                } catch (PDOException $e) {
                    $errorMessage = 'Erreur lors de la mise à jour des paramètres.';
                }
            }
        }
    }
}
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
            <h1 class="page-title"><i class="fas fa-cog me-2"></i>Paramètres du compte</h1>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" id="settingsForm" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="card-custom mb-4">
                            <div class="card-header-custom">
                                <h5><i class="fas fa-user-circle me-2"></i>Avatar</h5>
                            </div>
                            <div class="card-body-custom">
                                <div class="mb-4">
                                    <label class="form-label fw-bold"><i class="fas fa-image me-2"></i>Avatar actuel</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div id="current-avatar-display">
                                            <?php if (!empty($user['photo_url'])): ?>
                                                <img src="../<?= htmlspecialchars($user['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle border" width="80" height="80" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                    <i class="fas fa-user fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($user['photo_url'])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" id="delete-avatar-btn">
                                                <i class="fas fa-trash me-1"></i> Supprimer l'avatar
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold"><i class="fas fa-th me-2"></i>Avatars prédéfinis</label>
                                    <p class="text-muted small mb-3">Cliquez sur un avatar pour le sélectionner</p>
                                    <div class="avatar-grid">
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <div class="avatar-option" data-avatar="<?= $i ?>" title="Cliquer pour sélectionner">
                                                <img src="../assets/img/avatars/presets/avatar-<?= $i ?>.svg" alt="Avatar <?= $i ?>" class="avatar-preset-img">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold"><i class="fas fa-upload me-2"></i>Ou télécharger un avatar personnalisé</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="avatar" accept="image/*">
                                    </div>
                                    <small class="text-muted">Formats acceptés : JPEG, PNG ou GIF. Taille maximale : 2 Mo</small>
                                </div>
                            </div>
                        </div>

                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h5><i class="fas fa-user-edit me-2"></i>Informations personnelles</h5>
                            </div>
                            <div class="card-body-custom">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-user me-2 text-primary"></i>Prénom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? $user['prenom'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="Votre prénom">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label"><i class="fas fa-user me-2 text-primary"></i>Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? $user['nom'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="Votre nom">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope me-2 text-primary"></i>Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8') ?>" required placeholder="votre.email@exemple.com">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-phone me-2 text-muted"></i>Téléphone</label>
                                    <input type="text" class="form-control" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? ($user['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: +33 6 12 34 56 78">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-map-marker-alt me-2 text-muted"></i>Adresse</label>
                                    <textarea class="form-control" name="adresse" rows="2" placeholder="Votre adresse complète"><?= htmlspecialchars($_POST['adresse'] ?? ($user['adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label"><i class="fas fa-city me-2 text-muted"></i>Ville</label>
                                        <input type="text" class="form-control" name="ville" value="<?= htmlspecialchars($_POST['ville'] ?? ($user['ville'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Votre ville">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><i class="fas fa-mail-bulk me-2 text-muted"></i>Code postal</label>
                                        <input type="text" class="form-control" name="code_postal" value="<?= htmlspecialchars($_POST['code_postal'] ?? ($user['code_postal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 75001">
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label"><i class="fas fa-align-left me-2 text-muted"></i>Bio / Note personnelle</label>
                                    <textarea class="form-control" name="bio" rows="3" placeholder="Quelques mots sur vous..."><?= htmlspecialchars($_POST['bio'] ?? ($user['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <h5><i class="fas fa-shield-alt me-2"></i>Sécurité</h5>
                            </div>
                            <div class="card-body-custom">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="change_password" name="change_password" value="1">
                                    <label class="form-check-label fw-bold" for="change_password">
                                        <i class="fas fa-key me-2 text-warning"></i>Modifier mon mot de passe
                                    </label>
                                </div>

                                <div id="password-fields" style="display: none;">
                                    <div class="alert alert-info py-2 mb-3">
                                        <small><i class="fas fa-info-circle me-1"></i><strong>Critères :</strong> Minimum 6 caractères</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirmer le mot de passe</label>
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                                    </div>
                                </div>

                                <hr class="my-3">
                                <div class="alert alert-warning py-2 mb-3">
                                    <small><i class="fas fa-exclamation-triangle me-1"></i>Les champs marqués d'un <span class="text-danger">*</span> sont obligatoires</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                                <button type="button" class="btn btn-outline-secondary w-100" onclick="location.reload();">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <style>
    .avatar-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 10px;
    }
    .avatar-option {
        cursor: pointer;
        border: 3px solid #e9ecef;
        border-radius: 50%;
        padding: 8px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        position: relative;
        overflow: hidden;
    }
    .avatar-option:hover {
        border-color: #6366f1;
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .avatar-option.selected {
        border-color: #10b981;
        background-color: #d1fae5;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    .avatar-option.selected::after {
        content: '\f00c';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: 5px;
        right: 5px;
        background: #10b981;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .avatar-preset-img {
        width: 100%;
        height: auto;
        border-radius: 50%;
        display: block;
    }
    @media (max-width: 768px) {
        .avatar-grid { grid-template-columns: repeat(4, 1fr); gap: 10px; }
    }
    @media (max-width: 576px) {
        .avatar-grid { grid-template-columns: repeat(3, 1fr); }
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = '<?= csrf_token() ?>';
        const changePasswordCheckbox = document.getElementById('change_password');
        const passwordFields = document.getElementById('password-fields');

        changePasswordCheckbox.addEventListener('change', function() {
            passwordFields.style.display = this.checked ? 'block' : 'none';
            document.getElementById('password').required = this.checked;
            document.getElementById('password_confirm').required = this.checked;
        });

        document.querySelectorAll('.avatar-option').forEach(option => {
            option.addEventListener('click', function() {
                const avatarNumber = this.dataset.avatar;
                document.querySelectorAll('.avatar-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');

                fetch('select_preset_avatar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `csrf_token=${encodeURIComponent(csrfToken)}&avatar=${avatarNumber}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('current-avatar-display').innerHTML = `<img src="${data.avatar_url}" alt="Avatar" class="rounded-circle border" width="80" height="80" style="object-fit: cover;">`;
                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        const deleteBtn = document.getElementById('delete-avatar-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer votre avatar ?')) {
                    fetch('delete_avatar.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `csrf_token=${encodeURIComponent(csrfToken)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) setTimeout(() => location.reload(), 1500);
                    });
                }
            });
        }
    });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

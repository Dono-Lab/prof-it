<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../../includes/csrf.php';
require_once '../includes/helpers.php';
require_admin_api();
require_once __DIR__ . '/track_activity.php';

$prenomHeader = $_SESSION['prenom'] ?? 'Admin';
$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($prenomHeader) . '&background=6366f1&color=fff';

if (!empty($_SESSION['avatar_url'])) {
    $avatarUrl = '../' . ltrim($_SESSION['avatar_url'], '/');
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - Prof-IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
</head>

<body>
    <header class="top-header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="header-actions ms-auto">
            <div class="dropdown">
                <div class="user-menu" data-bs-toggle="dropdown">
                    <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                        class="user-avatar" alt="Avatar">
                    <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 12px; color: #6b7280"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Paramètres</a></li>
                    <li><a class="dropdown-item" href="../public/home.php"><i class="fa-solid fa-house me-2"></i>Accueil</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="../auth/logout.php" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left; cursor: pointer;">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <script src="../assets/js/auto_logout.js"></script>
    <script>
        <?php if (defined('SESSION_LIFETIME')): ?>
        initAutoLogout(<?= SESSION_LIFETIME ?>);
        <?php endif; ?>
    </script>
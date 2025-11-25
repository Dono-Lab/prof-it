<?php
require_once __DIR__ . '/../includes/helpers.php';
safe_session_start();
require_once __DIR__ . '/../admin/includes/track_activity.php';

$prenom = $_SESSION['prenom'] ?? '';
$role = $_SESSION['role'] ?? null;
$currentNav = $currentNav ?? '';
$active = fn($key) => $currentNav === $key ? ' style="color:#1898e9;"' : '';

$prenomHeader = $_SESSION['prenom'] ?? 'Utilisateur';
$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($prenomHeader) . '&background=6366f1&color=fff';

if (!empty($_SESSION['avatar_url'])) {
    $avatarUrl = '../' . ltrim($_SESSION['avatar_url'], '/');
}
?>
<header>
    <div id="user-welcome" style="background:#7494ec;color:white;padding:15px;text-align:center;">
        <?php if ($prenom): ?>
            <div class="container">
                <span>Bienvenue <strong><?= htmlspecialchars(ucfirst($prenom), ENT_QUOTES, 'UTF-8') ?></strong> !</span>
            </div>
        <?php endif; ?>
    </div>
    <div class="container header-container">
        <div class="header-content">
            <div class="logo">
                <a href="../index.php">
                    <img id="logo" src="../assets/img/prof_it_logo_blanc.png" alt="Prof-IT">
                </a>
            </div>
            <nav>
                <ul>
                    <?php if ($role === 'student'): ?>
                        <li><a href="../student/student_page.php" <?= $active('student_home') ?>>Accueil</a></li>
                        <li><a href="../student/rdv.php" <?= $active('student_rdv') ?>>Rendez-vous</a></li>
                        <li><a href="../student/documents.php" <?= $active('student_documents') ?>>Documents</a></li>
                        <li><a href="../student/messagerie.php" <?= $active('student_messagerie') ?>>Messagerie</a></li>
                        <li class="dropdown">
                            <a href="#" class="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    class="user-avatar" alt="Avatar">
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Étudiant') ?></span>
                                <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 6px;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="../student/settings.php">
                                        <i class="fas fa-cog me-2"></i>Paramètres
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="../auth/logout.php" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left; cursor: pointer;">
                                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php elseif ($role === 'teacher'): ?>
                        <li><a href="../teacher/teacher_page.php" <?= $active('teacher_home') ?>>Accueil</a></li>
                        <li><a href="../teacher/rdv.php" <?= $active('teacher_rdv') ?>>Rendez-vous</a></li>
                        <li><a href="../teacher/documents.php" <?= $active('teacher_documents') ?>>Documents</a></li>
                        <li><a href="../teacher/messagerie.php" <?= $active('teacher_messagerie') ?>>Messagerie</a></li>
                        <li class="dropdown">
                            <a href="#" class="user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    class="user-avatar" alt="Avatar">
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['prenom'] ?? 'Professeur') ?></span>
                                <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 6px;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="../teacher/settings.php">
                                        <i class="fas fa-cog me-2"></i>Paramètres
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="../auth/logout.php" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <button type="submit" class="dropdown-item" style="border: none; background: none; width: 100%; text-align: left; cursor: pointer;">
                                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="../public/home.html">Accueil</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>

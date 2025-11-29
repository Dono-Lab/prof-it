<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar" id="sidebar">
    <div class="logo-section">
        <div class="logo-icon">
            <img src="../assets/img/prof_it_logo_blanc.png" alt="prof-it logo" height="70px">
        </div>
    </div>
    
    <div class="admin-panel-title">Admin Panel</div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php if ($current_page === 'dashboard.php') echo 'active'; ?>">
                <i class="fas fa-home"></i> Tableau de Bord
            </a>
        </li>

        <li>
            <a href="messaging.php" class="<?php if ($current_page === 'messaging.php') echo 'active'; ?>">
                <i class="fas fa-comments"></i> Support
            </a>
        </li>

        <li>
            <a href="users.php" class="<?php if ($current_page === 'users.php') echo 'active'; ?>">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
        </li>

        <li>
            <a href="live_users.php" class="<?php if ($current_page === 'live_users.php') echo 'active'; ?>">
                <i class="fas fa-user-clock"></i> Connectés
            </a>
        </li>

        <li>
            <a href="captcha.php" class="<?php if ($current_page === 'captcha.php') echo 'active'; ?>">
                <i class="fas fa-shield-alt"></i> CAPTCHA
            </a>
        </li>

        <li>
            <a href="send_newsletter.php" class="<?php if ($current_page === 'send_newsletter.php') echo 'active'; ?>">
                <i class="fas fa-envelope"></i> Newsletter
            </a>
        </li>

        <li>
            <a href="logs.php" class="<?php if ($current_page === 'logs.php') echo 'active'; ?>">
                <i class="fas fa-history"></i> Logs
            </a>
        </li>

        <li>
            <a href="settings.php" class="<?php if ($current_page === 'settings.php') echo 'active'; ?>">
                <i class="fas fa-cog"></i> Paramètres
            </a>
        </li>
    </ul>
</div>

<?php
require_once __DIR__ . '/csrf.php';

function safe_session_start()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_protect()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            die('Erreur de sécurité : token CSRF invalide. <a href="javascript:history.back()">Retour</a>');
        }
    }
}

function logout_button()
{
    $token = csrf_token();
    return '<form method="POST" action="../auth/logout.php" style="display: inline;">
        <input type="hidden" name="csrf_token" value="' . $token . '">
        <button type="submit" class="btn-logout-custom">
            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
        </button>
    </form>';
}

function is_admin()
{
    safe_session_start();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_teacher()
{
    safe_session_start();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function is_student()
{
    safe_session_start();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function is_logged_in()
{
    safe_session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function require_admin()
{
    safe_session_start();
    
    if (!is_admin()) {
        http_response_code(403);
        die('Accès refusé : vous devez être administrateur. <a href="../auth/auth.php">Se connecter</a>');
    }
}

function require_admin_api()
{
    safe_session_start();
    
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        exit();
    }
}

function require_role($role)
{
    safe_session_start();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        die("Accès refusé : vous devez être $role. <a href='../auth/auth.php'>Se connecter</a>");
    }
}
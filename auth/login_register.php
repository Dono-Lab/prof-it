<?php
session_start();
require_once '../config/config.php';
require_once '../includes/helpers.php';

safe_session_start();
csrf_protect();

if (isset($_POST['register'])) {
    require_once '../src/get_captcha.php';

    $captchaId = $_POST['captcha_id'] ?? '';
    $captchaAnswer = $_POST['captcha_answer'] ?? '';

    if (!verifyCaptcha($conn, $captchaId, $captchaAnswer)) {
        $_SESSION['register_error'] = 'La réponse de la question de sécurité est incorrecte.';
        $_SESSION['active_form'] = 'register';
        header("Location: auth.php");
        exit();
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $telephone = trim($_POST['phone'] ?? '');
    $adresse = trim($_POST['address'] ?? '');
    $code_postal = trim($_POST['postal'] ?? '');
    $ville = trim($_POST['city'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = 'Email invalide';
        $_SESSION['active_form'] = 'register';
        header("Location: auth.php");
        exit();
    }

    if (strlen($password_raw) < 6) {
        $_SESSION['register_error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
        $_SESSION['active_form'] = 'register';
        header("Location: auth.php");
        exit();
    }

    if (!in_array($role, ['student', 'teacher', 'admin'])) {
        $_SESSION['register_error'] = 'Rôle invalide';
        $_SESSION['active_form'] = 'register';
        header("Location: auth.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = 'Email déjà utilisé';
        $_SESSION['active_form'] = 'register';
        header("Location: auth.php");
        exit();
    }

    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users (nom, prenom, email, password, role, telephone, adresse, ville, code_postal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$nom, $prenom, $email, $password, $role, $telephone, $adresse, $ville, $code_postal])) {
        $_SESSION['success_message'] = 'Compte créé avec succès ! Vous pouvez vous connecter.';
        $_SESSION['active_form'] = 'login';
    } else {
        $_SESSION['register_error'] = 'Une erreur est survenue lors de l\'inscription.';
        $_SESSION['active_form'] = 'register';
    }

    header("Location: auth.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']) && (int)($user['actif'] ?? 1) === 1) {

        $stmt_log = $conn->prepare("INSERT INTO logs_connexions (user_id, email, ip_address, user_agent, statut) 
                                VALUES (?, ?, ?, ?, 'success')");
        $stmt_log->execute([
            $user['id'],
            $email,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['avatar_url'] = $user['photo_url'] ?? '';

        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else if ($user['role'] === 'teacher') {
            header("Location: ../teacher/teacher_page.php");
        } else {
            header("Location: ../student/student_page.php");
        }
        exit();
    } else {

        $stmt_log = $conn->prepare("INSERT INTO logs_connexions (email, ip_address, user_agent, statut, raison_echec) 
                                VALUES (?, ?, ?, 'failed', 'Identifiants incorrects ou compte inactif')");
        $stmt_log->execute([
            $email,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);

        $_SESSION['login_error'] = 'Email ou mot de passe incorrects';
        $_SESSION['active_form'] = 'login';
        header("Location: auth.php");
        exit();
    }
}


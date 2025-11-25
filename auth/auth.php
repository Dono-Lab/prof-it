<?php
session_start();
require_once '../config/config.php';
require_once '../src/get_captcha.php';
require_once '../includes/helpers.php';

if (isset($_SESSION['email'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else if ($_SESSION['role'] === 'teacher') {
        header("Location: ../teacher/teacher_page.php");
    } else {
        header("Location: ../student/student_page.php");
    }
    exit();
}

if (!isset($_SESSION['captcha_question']) || isset($_GET['new_captcha'])) {
    $captchaData = getCaptcha($conn);
    if ($captchaData) {
        $_SESSION['captcha_question'] = $captchaData['question'];
        $_SESSION['captcha_id'] = $captchaData['id'];
    }
}

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$successMessage = $_SESSION['success_message'] ?? '';
$activeForm = $_SESSION['active_form'] ?? 'login';

$captchaQuestion = $_SESSION['captcha_question'] ?? 'Chargement...';
$captchaId = $_SESSION['captcha_id'] ?? '';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['success_message'], $_SESSION['active_form']);

function showError($error)
{
    return !empty($error) ? "<p class='error-message'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

function showSuccess($message)
{
    return !empty($message) ? "<p class='success-message'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>" : '';
}

function isActiveForm($formName, $activeForm)
{
    return $formName === $activeForm ? 'active' : '';
}
?>
<?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1): ?>
    <div style="position: fixed; top: 10px; right: 10px; z-index: 1000;">
        <a href="admin/" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-cog"></i> Administration
        </a>
    </div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion & Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>
    <a href="../public/home.html" class="btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Retour
    </a>
    <div class="container">
        <div class="container">
            <div class="form-box <?= isActiveForm('login', $activeForm) ?>" id="login-form">
                <form action="login_register.php" method="post">
                    <?= csrf_field() ?>
                    <h2>Connexion</h2>
                    <?= showSuccess($successMessage) ?>
                    <?= showError($errors['login']) ?>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" name="login">Se connecter</button>
                    <p>Vous n'avez pas encore de compte? <a href="#" onclick="showForm('register-form')">S'inscrire</a></p>
                </form>
            </div>

            <div class="form-box <?= isActiveForm('register', $activeForm) ?>" id="register-form">
                <form action="login_register.php" method="post" id="registerForm">

                    <?= csrf_field() ?>
                    <h2>Inscription</h2>
                    <?= showError($errors['register']) ?>

                    <div class="form-grid">
                        <input type="text" name="nom" placeholder="Nom" required>
                        <input type="text" name="prenom" placeholder="Prénom" required>
                    </div>

                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Mot de passe" minlength="6" required>
                    <input type="text" name="phone" placeholder="Numéro de téléphone">
                    <input type="text" name="address" placeholder="Adresse">

                    <div class="form-grid">
                        <input type="text" name="postal" placeholder="Code Postal">
                        <input type="text" name="city" placeholder="Ville">
                    </div>

                    <select name="role" required>
                        <option value="">-- Que souhaitez-vous faire? --</option>
                        <option value="student">Apprendre</option>
                        <option value="teacher">Enseigner</option>
                    </select>

                    <button type="button" onclick="openCaptchaModal()">S'inscrire</button>
                    <p>Vous avez déjà un compte? <a href="#" onclick="showForm('login-form')">Se connecter</a></p>
                </form>
            </div>
        </div>
        <div class="modal fade" id="captchaModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content captcha-modal">
                    <div class="modal-header captcha-header">
                        <h5 class="modal-title">Vérification de sécurité</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="captcha-instruction">Veuillez répondre à la question suivante :</p>
                        <div class="captcha-question"><?= htmlspecialchars($captchaQuestion, ENT_QUOTES, 'UTF-8') ?></div>
                        <input type="text" class="form-control captcha-input" id="captchaAnswer" placeholder="Votre réponse" autocomplete="off">
                        <input type="hidden" id="captchaId" value="<?= $captchaId ?>">
                        <div class="captcha-refresh">
                            <a href="#" onclick="event.preventDefault(); refreshCaptcha();">🔄 Changer de question</a>
                        </div>
                        <div id="captchaError" class="captcha-error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn-captcha-validate" onclick="submitWithCaptcha()">Valider</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function showForm(formID) {
                document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
                document.getElementById(formID).classList.add("active");
            }

            function refreshCaptcha() {
                const questionDiv = document.querySelector('.captcha-question');
                questionDiv.textContent = '⏳ Chargement...';

                fetch('../src/get_new_captcha.php')
                    .then(response => response.json())
                    .then(data => {
                        questionDiv.textContent = data.question;
                        document.getElementById('captchaId').value = data.id;
                        document.getElementById('captchaAnswer').value = '';
                    })
                    .catch(error => {
                        questionDiv.textContent = 'Erreur lors du chargement';
                    });
            }

            document.getElementById('captchaAnswer')?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    submitWithCaptcha();
                }
            });

            function openCaptchaModal() {
                const form = document.getElementById('register-form');

                const nom = form.querySelector('[name=nom]').value.trim();
                const prenom = form.querySelector('[name=prenom]').value.trim()
                const email = form.querySelector('[name=email]').value.trim();
                const password = form.querySelector('[name=password]').value;
                const role = form.querySelector('[name=role]').value;

                if (!nom || !prenom || !email || !password || !role) {
                    alert('Veuillez compléter tous les champs requis');
                    return;
                }

                const modal = new bootstrap.Modal(document.getElementById('captchaModal'));
                modal.show();
            }

            function submitWithCaptcha() {
                const captchaAnswer = document.getElementById('captchaAnswer').value;
                const captchaId = document.getElementById('captchaId').value;
                const form = document.getElementById('registerForm');

                if (!captchaAnswer) {
                    alert('Veuillez répondre à la question de sécurité');
                    return;
                }

                let inputAnswer = document.createElement('input');
                inputAnswer.type = 'hidden';
                inputAnswer.name = 'captcha_answer';
                inputAnswer.value = captchaAnswer;
                form.appendChild(inputAnswer);

                let inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'captcha_id';
                inputId.value = captchaId;
                form.appendChild(inputId);

                const modal = bootstrap.Modal.getInstance(document.getElementById('captchaModal'));
                modal.hide();

                let registerInput = document.createElement('input');
                registerInput.type = 'hidden';
                registerInput.name = 'register';
                registerInput.value = '1';
                form.appendChild(registerInput);

                form.submit();
            }
        </script>
</body>

</html>
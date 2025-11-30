<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/mailer.php';

safe_session_start();
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['email'];
    if (send_email($to, 'Test Prof-IT', '<h1>Ceci est un test</h1><p>Si vous lisez ceci, l\'envoi de mail fonctionne !</p>')) {
        $message = '<div class="alert alert-success">Email envoyé avec succès !</div>';
    } else {
        $message = '<div class="alert alert-danger">Échec de l\'envoi. Vérifiez les logs ou la config SMTP.</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <h1>Test d'envoi d'email</h1>
    <?= $message ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3">
            <label>Envoyer un test à :</label>
            <input type="email" name="email" class="form-control" required placeholder="votre@email.com">
        </div>
        <button type="submit" class="btn btn-primary">Envoyer</button>
    </form>
</body>
</html>

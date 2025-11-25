<?php
$pageTitle = 'Envoyer une Newsletter';
require_once '../includes/helpers.php';
require_admin();

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once '../config/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $sujet = trim($_POST['sujet'] ?? '');
    $contenu = $_POST['contenu'] ?? '';

    if (empty($sujet) || empty($contenu)) {
        $error = "Le sujet et le contenu sont obligatoires.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT email, prenom FROM newsletter WHERE actif = 1");
            $stmt->execute();
            $abonnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nb_envoyes = 0;

            foreach ($abonnes as $abonne) {
                $to = $abonne['email'];
                $prenom = $abonne['prenom'] ?? 'Abonné';
                $message_perso = str_replace('{{prenom}}', $prenom, $contenu);
                $headers = "From: newsletter@prof-it.fr\r\n";
                $headers .= "Reply-To: contact@prof-it.fr\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                if (@mail($to, $sujet, $message_perso, $headers)) {
                    $nb_envoyes++;
                }
            }

            $stmt = $conn->prepare("INSERT INTO newsletter_envoi (sujet, contenu, nb_destinataires, envoye_par) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sujet, $contenu, $nb_envoyes, $_SESSION['user_id']]);
            $success = "Newsletter envoyée à $nb_envoyes abonné(s) sur " . count($abonnes) . " !";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'envoi : " . $e->getMessage();
        }
    }
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM newsletter WHERE actif = 1");
    $nb_abonnes = $stmt->fetch()['total'];
} catch (PDOException $e) {
    $nb_abonnes = 0;
}
?>

<div class="main-content">
    <main class="dashboard-content">
        <h1 class="page-title">Envoyer une Newsletter</h1>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $nb_abonnes ?></h3>
                        <p>Abonnés actifs</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card-custom">
            <div class="card-header-custom">
                <h5><i class="fas fa-paper-plane me-2"></i>Composer votre newsletter</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-4">
                        <label for="sujet" class="form-label">
                            <i class="fas fa-heading me-1"></i>Sujet de l'email *
                        </label>
                        <input type="text"
                            class="form-control form-control-lg"
                            id="sujet"
                            name="sujet"
                            placeholder="Ex: Nouvelle fonctionnalité Prof-IT"
                            required>
                    </div>

                    <div class="mb-4">
                        <label for="contenu" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Contenu HTML *
                        </label>
                        <textarea class="form-control"
                            id="contenu"
                            name="contenu"
                            rows="12"
                            required
                            placeholder="<h2>Bonjour {{prenom}} !</h2>&#10;<p>Nous avons une nouveauté pour vous...</p>"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer la Newsletter
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-custom mt-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-history me-2"></i>Derniers envois</h5>
            </div>
            <div class="card-body-custom">
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT 
                            ne.sujet, 
                            ne.nb_destinataires, 
                            ne.date_envoi,
                            u.prenom, 
                            u.nom
                        FROM newsletter_envoi ne
                        LEFT JOIN users u ON ne.envoye_par = u.id
                        ORDER BY ne.date_envoi DESC
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($historique) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sujet</th>
                                        <th>Destinataires</th>
                                        <th>Envoyé par</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historique as $envoi): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($envoi['sujet']) ?></strong></td>
                                            <td><?= $envoi['nb_destinataires'] ?> abonné(s)</td>
                                            <td><?= htmlspecialchars($envoi['prenom'] ?? 'Inconnu') ?> <?= htmlspecialchars($envoi['nom'] ?? '') ?></td>
                                            <td><?= date('d/m/Y à H:i', strtotime($envoi['date_envoi'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Aucun envoi pour le moment.</p>
                <?php endif;
                } catch (PDOException $e) {
                    echo '<p class="text-danger">Erreur lors du chargement de l\'historique.</p>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>
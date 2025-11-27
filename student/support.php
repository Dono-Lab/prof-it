<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('student');
$pageTitle = 'Support - Prof-IT';
$currentNav = 'student_support';

$userId = $_SESSION['user_id'] ?? null;

$sql = "
    SELECT
        id_ticket,
        sujet,
        categorie,
        priorite,
        statut_ticket,
        cree_le,
        dernier_message
    FROM ticket_support
    WHERE id_utilisateur = ?
    ORDER BY cree_le DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll();

$stats = [
    'total' => 0,
    'waiting' => 0,
    'closed' => 0,
    'open' => 0
];

foreach ($tickets as $ticket) {
    $stats['total']++;
    if ($ticket['statut_ticket'] === 'en_cours') $stats['waiting']++;
    if ($ticket['statut_ticket'] === 'ferme' || $ticket['statut_ticket'] === 'resolu') $stats['closed']++;
    if ($ticket['statut_ticket'] === 'ouvert') $stats['open']++;
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title mb-0"><i class="fas fa-headset me-2"></i>Mon Support</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="fas fa-plus me-2"></i>Nouveau Ticket
                </button>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['total'] ?></h3>
                            <p class="text-muted small mb-0">Total tickets</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon yellow">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['waiting'] ?></h3>
                            <p class="text-muted small mb-0">En attente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['closed'] ?></h3>
                            <p class="text-muted small mb-0">Résolus</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?= $stats['open'] ?></h3>
                            <p class="text-muted small mb-0">Ouvert</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-list me-2"></i>Historique des tickets</h5>
                </div>
                <div class="card-body-custom">
                    <?php if (empty($tickets)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Vous n'avez créé aucun ticket pour le moment.</p>
                            <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                                <i class="fas fa-plus me-2"></i>Créer mon premier ticket
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Sujet</th>
                                        <th>Catégorie</th>
                                        <th>Priorité</th>
                                        <th>Statut</th>
                                        <th>Date de création</th>
                                        <th>Dernière réponse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $ticket):
                                        $statusMap = [
                                            'ouvert' => ['class' => 'open', 'label' => 'Ouvert'],
                                            'en_cours' => ['class' => 'waiting', 'label' => 'En cours'],
                                            'resolu' => ['class' => 'closed', 'label' => 'Résolu'],
                                            'ferme' => ['class' => 'closed', 'label' => 'Fermé']
                                        ];
                                        $status = $statusMap[$ticket['statut_ticket']] ?? ['class' => 'open', 'label' => 'Ouvert'];

                                        $categoryIcons = [
                                            'technique' => 'fa-tools',
                                            'paiement' => 'fa-credit-card',
                                            'reservation' => 'fa-calendar',
                                            'compte' => 'fa-user',
                                            'autre' => 'fa-question-circle'
                                        ];
                                        $icon = $categoryIcons[$ticket['categorie']] ?? 'fa-question-circle';

                                        $lastResponse = $ticket['dernier_message']
                                            ? format_relative_date($ticket['dernier_message'])
                                            : 'Aucune réponse';
                                    ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $ticket['id_ticket'] ?></td>
                                        <td><?= htmlspecialchars($ticket['sujet']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <i class="fas <?= $icon ?> me-1"></i>
                                                <?= htmlspecialchars(ucfirst($ticket['categorie'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= get_priority_color($ticket['priorite']) ?>">
                                                <?= htmlspecialchars(ucfirst($ticket['priorite'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $status['class'] ?>">
                                                <?= $status['label'] ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('d/m/Y', strtotime($ticket['cree_le'])) ?>
                                        </td>
                                        <td class="text-muted small"><?= $lastResponse ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newTicketModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Créer un nouveau ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ticketForm">
                        <div class="mb-3">
                            <label for="ticketSubject" class="form-label">
                                <i class="fas fa-heading me-2 text-primary"></i>Sujet <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="ticketSubject" placeholder="Ex: Problème de connexion" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ticketCategory" class="form-label">
                                    <i class="fas fa-tag me-2 text-primary"></i>Catégorie <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="ticketCategory" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    <option value="technique">Technique</option>
                                    <option value="paiement">Paiement</option>
                                    <option value="reservation">Réservation</option>
                                    <option value="compte">Mon compte</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="ticketPriority" class="form-label">
                                    <i class="fas fa-exclamation-circle me-2 text-primary"></i>Priorité <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="ticketPriority" required>
                                    <option value="">Sélectionnez la priorité</option>
                                    <option value="basse">Basse</option>
                                    <option value="normale" selected>Normale</option>
                                    <option value="haute">Haute</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ticketMessage" class="form-label">
                                <i class="fas fa-comment-dots me-2 text-primary"></i>Message <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="ticketMessage" rows="5" placeholder="Décrivez votre problème ou votre question en détail..." required></textarea>
                            <small class="text-muted">Minimum 20 caractères</small>
                        </div>

                        <div class="alert alert-info py-2">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>Notre équipe support vous répondra dans les 24-48 heures ouvrées.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitTicket()">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer le ticket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function submitTicket() {
            const subject = document.getElementById('ticketSubject').value.trim();
            const category = document.getElementById('ticketCategory').value;
            const priority = document.getElementById('ticketPriority').value;
            const message = document.getElementById('ticketMessage').value.trim();
            const submitBtn = document.querySelector('#newTicketModal .btn-primary');

            if (!subject || !category || !priority || !message) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }

            if (message.length < 20) {
                alert('Le message doit contenir au moins 20 caractères.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';

            try {
                const formData = new FormData();
                formData.append('action', 'create_ticket');
                formData.append('sujet', subject);
                formData.append('categorie', category);
                formData.append('priorite', priority);
                formData.append('description', message);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('../api/support.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Ticket créé avec succès ! Notre équipe vous répondra dans les 24-48 heures.');

                    const modal = bootstrap.Modal.getInstance(document.getElementById('newTicketModal'));
                    modal.hide();
                    document.getElementById('ticketForm').reset();

                    window.location.reload();
                } else {
                    alert('Erreur lors de la création du ticket: ' + (data.error || 'Erreur inconnue'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la création du ticket. Veuillez réessayer.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer le ticket';
            }
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

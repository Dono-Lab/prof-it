<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('teacher');
$pageTitle = 'Support - Prof-IT';
$currentNav = 'teacher_support';

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
                                        <th>Actions</th>
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
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary view-ticket"
                                                data-ticket-id="<?= $ticket['id_ticket'] ?>">
                                                <i class="fas fa-eye me-1"></i>Voir
                                            </button>
                                        </td>
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

    <div class="modal fade" id="viewTicketModal" tabindex="-1" aria-labelledby="viewTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTicketModalLabel">
                        <i class="fas fa-ticket-alt me-2"></i>Détails du ticket
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="ticketDetailsContent">
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <div>
                                <strong>Sujet :</strong>
                                <p class="mb-0" id="viewTicketSubject">-</p>
                            </div>
                            <div>
                                <strong>Catégorie :</strong>
                                <p class="mb-0" id="viewTicketCategory">-</p>
                            </div>
                            <div>
                                <strong>Priorité :</strong>
                                <p class="mb-0" id="viewTicketPriority">-</p>
                            </div>
                            <div>
                                <strong>Statut :</strong>
                                <p class="mb-0"><span class="badge-status" id="viewTicketStatus">-</span></p>
                            </div>
                        </div>
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            <div id="ticketMessagesContainer">
                                <p class="text-muted text-center mb-0">Chargement des messages...</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="ticketReplyMessage" class="form-label">
                                <i class="fas fa-reply me-2 text-primary"></i>Votre réponse
                            </label>
                            <textarea class="form-control" id="ticketReplyMessage" rows="3" placeholder="Saisissez un message clair et précis..."></textarea>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-primary" id="ticketReplyButton" onclick="submitTicketReply()">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer ma réponse
                                </button>
                            </div>
                        </div>
                    </div>
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
                            <input type="text" class="form-control" id="ticketSubject" placeholder="Ex: Problème avec le calendrier" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ticketCategory" class="form-label">
                                    <i class="fas fa-tag me-2 text-primary"></i>Catégorie <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="ticketCategory" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    <option value="technique">Technique</option>
                                    <option value="paiement">Paiement & Revenus</option>
                                    <option value="reservation">Réservations</option>
                                    <option value="compte">Mon profil enseignant</option>
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
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.view-ticket').forEach(button => {
                button.addEventListener('click', function() {
                    const ticketId = this.dataset.ticketId;
                    if (ticketId) {
                        openTicketDetails(ticketId);
                    }
                });
            });
        });

        let viewTicketModalInstance = null;
        let currentTicketId = null;

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

        async function openTicketDetails(ticketId) {
            const modalElement = document.getElementById('viewTicketModal');
            if (!modalElement) {
                return;
            }
            if (!viewTicketModalInstance) {
                viewTicketModalInstance = new bootstrap.Modal(modalElement);
            }
            currentTicketId = ticketId;

            const subjectEl = document.getElementById('viewTicketSubject');
            const categoryEl = document.getElementById('viewTicketCategory');
            const priorityEl = document.getElementById('viewTicketPriority');
            const statusEl = document.getElementById('viewTicketStatus');
            const messagesContainer = document.getElementById('ticketMessagesContainer');
            const replyTextarea = document.getElementById('ticketReplyMessage');
            if (replyTextarea) {
                replyTextarea.value = '';
            }

            subjectEl.textContent = 'Chargement...';
            categoryEl.textContent = '-';
            priorityEl.textContent = '-';
            statusEl.textContent = '-';
            statusEl.className = 'badge-status';
            messagesContainer.innerHTML = '<p class="text-muted text-center mb-0">Chargement des messages...</p>';

            viewTicketModalInstance.show();

            try {
                const response = await fetch(`../api/support.php?action=ticket_details&ticket_id=${ticketId}`);
                const data = await response.json();

                if (!data.success) {
                    messagesContainer.innerHTML = `<p class="text-danger text-center mb-0">${data.error || 'Impossible de charger le ticket.'}</p>`;
                    return;
                }

                const ticket = data.ticket;
                const messages = data.messages || [];

                subjectEl.textContent = ticket.sujet || '-';
                categoryEl.textContent = capitalize(ticket.categorie || '');
                priorityEl.textContent = capitalize(ticket.priorite || '');
                statusEl.textContent = statusLabel(ticket.statut_ticket);
                statusEl.className = `badge-status ${statusClass(ticket.statut_ticket)}`;

                if (messages.length === 0) {
                    messagesContainer.innerHTML = '<p class="text-muted text-center mb-0">Aucun message pour ce ticket.</p>';
                } else {
                    messagesContainer.innerHTML = messages.map(msg => renderTicketMessage(msg)).join('');
                }
            } catch (error) {
                console.error('Ticket details error:', error);
                messagesContainer.innerHTML = '<p class="text-danger text-center mb-0">Erreur lors du chargement du ticket.</p>';
            }
        }

        async function submitTicketReply() {
            if (!currentTicketId) {
                alert('Sélectionnez un ticket avant de répondre.');
                return;
            }
            const textarea = document.getElementById('ticketReplyMessage');
            const replyButton = document.getElementById('ticketReplyButton');
            const message = textarea.value.trim();
            if (!message || message.length < 3) {
                alert('Votre réponse est trop courte.');
                return;
            }

            replyButton.disabled = true;
            replyButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi...';

            try {
                const formData = new FormData();
                formData.append('action', 'reply_ticket');
                formData.append('ticket_id', currentTicketId);
                formData.append('message', message);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                const response = await fetch('../api/support.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.error || 'Impossible d\'envoyer la réponse.');
                    return;
                }

                textarea.value = '';
                appendTicketMessage(data.message);
            } catch (error) {
                console.error('Reply error:', error);
                alert('Erreur lors de l\'envoi de la réponse.');
            } finally {
                replyButton.disabled = false;
                replyButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Envoyer ma réponse';
            }
        }

        function renderTicketMessage(message) {
            const isOwner = !message.est_admin;
            const alignmentClass = isOwner ? 'justify-content-end text-end' : '';
            const bubbleClass = isOwner ? 'bg-primary text-white' : 'bg-light';
            const formattedDate = formatDateTime(message.date_envoi);

            return `
                <div class="mb-3">
                    <div class="d-flex ${alignmentClass}">
                        <div class="ticket-message ${bubbleClass} p-3 rounded-3">
                            <div class="fw-semibold mb-1">${escapeHtml(message.auteur || '')}</div>
                            <div>${escapeHtml(message.contenu || '')}</div>
                            <div class="text-muted small mt-2">${formattedDate}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function appendTicketMessage(message) {
            const messagesContainer = document.getElementById('ticketMessagesContainer');
            if (!messagesContainer) return;
            const newMessageHtml = renderTicketMessage({
                ...message,
                est_admin: message.est_admin ?? 0
            });
            messagesContainer.insertAdjacentHTML('beforeend', newMessageHtml);
            messagesContainer.parentElement.scrollTop = messagesContainer.parentElement.scrollHeight;
        }

        function statusClass(status) {
            switch (status) {
                case 'resolu':
                case 'ferme':
                    return 'closed';
                case 'en_cours':
                    return 'waiting';
                case 'ouvert':
                default:
                    return 'open';
            }
        }

        function statusLabel(status) {
            const labels = {
                'ouvert': 'Ouvert',
                'en_cours': 'En cours',
                'resolu': 'Résolu',
                'ferme': 'Fermé'
            };
            return labels[status] || 'Ouvert';
        }

        function formatDateTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function capitalize(text) {
            if (!text) return '';
            return text.charAt(0).toUpperCase() + text.slice(1);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

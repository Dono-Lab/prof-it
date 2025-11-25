<?php
$pageTitle = 'Support & Messagerie';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <main class="dashboard-content">
        <h1 class="page-title">Support Client - Gestion des Tickets</h1>

        <div class="card-custom mb-4">
            <div class="card-body-custom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchTickets" placeholder="Rechercher...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterStatut">
                            <option value="">Tous les statuts</option>
                            <option value="ouvert">Ouvert</option>
                            <option value="en_cours">En cours</option>
                            <option value="resolu">Résolu</option>
                            <option value="ferme">Fermé</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterPriorite">
                            <option value="">Toutes les priorités</option>
                            <option value="urgente">Urgente</option>
                            <option value="haute">Haute</option>
                            <option value="normale">Normale</option>
                            <option value="basse">Basse</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="filterCategorie">
                            <option value="">Toutes les catégories</option>
                            <option value="technique">Technique</option>
                            <option value="paiement">Paiement</option>
                            <option value="compte">Compte</option>
                            <option value="reservation">Réservation</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary w-100" id="resetFilters">
                            <i class="fas fa-redo me-2"></i>Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0" id="statsTotal">-</h3>
                        <p class="text-muted mb-0">Total tickets</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-success" id="statsOuverts">-</h3>
                        <p class="text-muted mb-0">Ouverts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-warning" id="statsEnCours">-</h3>
                        <p class="text-muted mb-0">En cours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-danger" id="statsUrgents">-</h3>
                        <p class="text-muted mb-0">Urgents</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-body-custom">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Sujet</th>
                                <th>Catégorie</th>
                                <th>Priorité</th>
                                <th>Statut</th>
                                <th>Date création</th>
                                <th>Dernière activité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ticketsTableBody">
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="spinner-border text-primary" role="status"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="paginationInfo" class="text-muted"></div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalTitle">Détails du ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ticketDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?= csrf_token() ?>';
let currentPage = 1;
let currentFilters = {
    search: '',
    statut: '',
    priorite: '',
    categorie: ''
};
let autoRefreshInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    loadTickets();

    document.getElementById('searchTickets').addEventListener('input', debounce(filterTickets, 300));
    document.getElementById('filterStatut').addEventListener('change', filterTickets);
    document.getElementById('filterPriorite').addEventListener('change', filterTickets);
    document.getElementById('filterCategorie').addEventListener('change', filterTickets);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);

    startAutoRefresh();
});

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        loadTickets(currentPage, false);
    }, 5000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

function loadTickets(page = 1, showLoading = true) {
    if (showLoading) {
        document.getElementById('ticketsTableBody').innerHTML =
            '<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>';
    }

    const params = new URLSearchParams({
        page: page,
        ...currentFilters
    });

    fetch('api/tickets.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTickets(data.tickets);
                updatePagination(data.pagination);
                updateStats(data.tickets);
                currentPage = page;
            } else {
                const errorMsg = data.error || 'Erreur lors du chargement des tickets';
                const details = data.details ? ' - ' + data.details : '';
                showError(errorMsg + details);
            }
        })
        .catch(error => {
            showError('Erreur de connexion au serveur');
        });
}

function displayTickets(tickets) {
    const tbody = document.getElementById('ticketsTableBody');

    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Aucun ticket trouvé</td></tr>';
        return;
    }

    tbody.innerHTML = tickets.map(ticket => {
        const prioriteBadge = getPrioriteBadge(ticket.priorite);
        const statutBadge = getStatutBadge(ticket.statut_ticket);
        const categorieBadge = getCategorieBadge(ticket.categorie);

        return `
            <tr>
                <td><strong>#${ticket.id_ticket}</strong></td>
                <td>
                    <strong>${htmlEscape(ticket.prenom)} ${htmlEscape(ticket.nom)}</strong><br>
                    <small class="text-muted">${htmlEscape(ticket.email)}</small>
                </td>
                <td>
                    ${htmlEscape(ticket.sujet.substring(0, 50))}${ticket.sujet.length > 50 ? '...' : ''}<br>
                    <small class="text-muted"><i class="fas fa-comments me-1"></i>${ticket.nb_messages} messages</small>
                </td>
                <td>${categorieBadge}</td>
                <td>${prioriteBadge}</td>
                <td>${statutBadge}</td>
                <td>${formatDateTime(ticket.cree_le)}</td>
                <td>${formatDateTime(ticket.dernier_message)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewTicket(${ticket.id_ticket})">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function getPrioriteBadge(priorite) {
    const badges = {
        'urgente': '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Urgente</span>',
        'haute': '<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up me-1"></i>Haute</span>',
        'normale': '<span class="badge bg-info"><i class="fas fa-minus me-1"></i>Normale</span>',
        'basse': '<span class="badge bg-secondary"><i class="fas fa-arrow-down me-1"></i>Basse</span>'
    };
    return badges[priorite] || priorite;
}

function getStatutBadge(statut) {
    const badges = {
        'ouvert': '<span class="badge bg-success"><i class="fas fa-folder-open me-1"></i>Ouvert</span>',
        'en_cours': '<span class="badge bg-warning text-dark"><i class="fas fa-spinner me-1"></i>En cours</span>',
        'resolu': '<span class="badge bg-info"><i class="fas fa-check me-1"></i>Résolu</span>',
        'ferme': '<span class="badge bg-dark"><i class="fas fa-lock me-1"></i>Fermé</span>'
    };
    return badges[statut] || statut;
}

function getCategorieBadge(categorie) {
    const badges = {
        'technique': '<span class="badge bg-primary"><i class="fas fa-cog me-1"></i>Technique</span>',
        'paiement': '<span class="badge bg-success"><i class="fas fa-credit-card me-1"></i>Paiement</span>',
        'compte': '<span class="badge bg-warning text-dark"><i class="fas fa-user me-1"></i>Compte</span>',
        'reservation': '<span class="badge bg-info"><i class="fas fa-calendar me-1"></i>Réservation</span>',
        'autre': '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i>Autre</span>'
    };
    return badges[categorie] || categorie;
}

function updatePagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const paginationEl = document.getElementById('pagination');

    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);

    info.textContent = `Affichage de ${start} à ${end} sur ${pagination.total} tickets`;

    let html = '';
    html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1}); return false;">Précédent</a>
    </li>`;

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i <= 3 || i > pagination.total_pages - 3 ||
            (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === pagination.current_page - 2 || i === pagination.current_page + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1}); return false;">Suivant</a>
    </li>`;

    paginationEl.innerHTML = html;
}

function changePage(page) {
    loadTickets(page);
    window.scrollTo(0, 0);
}

function filterTickets() {
    currentFilters = {
        search: document.getElementById('searchTickets').value.trim(),
        statut: document.getElementById('filterStatut').value,
        priorite: document.getElementById('filterPriorite').value,
        categorie: document.getElementById('filterCategorie').value
    };
    loadTickets(1);
}

function resetFilters() {
    document.getElementById('searchTickets').value = '';
    document.getElementById('filterStatut').value = '';
    document.getElementById('filterPriorite').value = '';
    document.getElementById('filterCategorie').value = '';
    currentFilters = { search: '', statut: '', priorite: '', categorie: '' };
    loadTickets(1);
}

function updateStats(tickets) {
    const total = tickets.length;
    const ouverts = tickets.filter(t => t.statut_ticket === 'ouvert').length;
    const enCours = tickets.filter(t => t.statut_ticket === 'en_cours').length;
    const urgents = tickets.filter(t => t.priorite === 'urgente').length;

    document.getElementById('statsTotal').textContent = total;
    document.getElementById('statsOuverts').textContent = ouverts;
    document.getElementById('statsEnCours').textContent = enCours;
    document.getElementById('statsUrgents').textContent = urgents;
}

function viewTicket(ticketId) {
    const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
    modal.show();

    stopAutoRefresh();

    document.getElementById('ticketDetails').innerHTML =
        '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch(`api/tickets.php?action=details&id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTicketDetails(data.ticket, data.messages, data.pagination);
            } else {
                showError('Erreur lors du chargement du ticket');
            }
        })
        .catch(error => {
            showError('Erreur de connexion au serveur');
        });

    document.getElementById('ticketModal').addEventListener('hidden.bs.modal', function() {
        startAutoRefresh();
        loadTickets(currentPage, false);
    }, { once: true });
}

function displayTicketDetails(ticket, messages, pagination) {
    const details = document.getElementById('ticketDetails');
    document.getElementById('ticketModalTitle').textContent = `Ticket #${ticket.id_ticket} - ${ticket.sujet}`;

    const html = `
        <div class="row mb-4">
            <div class="col-md-8">
                <h5>Informations du ticket</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="150">Utilisateur:</th>
                        <td>${htmlEscape(ticket.prenom)} ${htmlEscape(ticket.nom)} (${htmlEscape(ticket.email)})</td>
                    </tr>
                    <tr>
                        <th>Catégorie:</th>
                        <td>${getCategorieBadge(ticket.categorie)}</td>
                    </tr>
                    <tr>
                        <th>Créé le:</th>
                        <td>${formatDateTime(ticket.cree_le)}</td>
                    </tr>
                    ${ticket.ferme_le ? `<tr><th>Fermé le:</th><td>${formatDateTime(ticket.ferme_le)}</td></tr>` : ''}
                </table>
            </div>
            <div class="col-md-4">
                <h5>Actions</h5>
                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" id="ticketStatut">
                        <option value="ouvert" ${ticket.statut_ticket === 'ouvert' ? 'selected' : ''}>Ouvert</option>
                        <option value="en_cours" ${ticket.statut_ticket === 'en_cours' ? 'selected' : ''}>En cours</option>
                        <option value="resolu" ${ticket.statut_ticket === 'resolu' ? 'selected' : ''}>Résolu</option>
                        <option value="ferme" ${ticket.statut_ticket === 'ferme' ? 'selected' : ''}>Fermé</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priorité</label>
                    <select class="form-select" id="ticketPriorite">
                        <option value="basse" ${ticket.priorite === 'basse' ? 'selected' : ''}>Basse</option>
                        <option value="normale" ${ticket.priorite === 'normale' ? 'selected' : ''}>Normale</option>
                        <option value="haute" ${ticket.priorite === 'haute' ? 'selected' : ''}>Haute</option>
                        <option value="urgente" ${ticket.priorite === 'urgente' ? 'selected' : ''}>Urgente</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100" onclick="updateTicket(${ticket.id_ticket})">
                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>

        <hr>

        <h5>Messages <small class="text-muted">(${pagination.total} messages)</small></h5>
        <div id="messagesContainer" class="mb-3" style="max-height: 400px; overflow-y: auto;">
            ${messages.map(msg => `
                <div class="card mb-2 ${msg.est_admin ? 'border-primary' : ''}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${htmlEscape(msg.prenom)} ${htmlEscape(msg.nom)}</strong>
                                ${msg.est_admin ? '<span class="badge bg-primary ms-2">Admin</span>' : '<span class="badge bg-secondary ms-2">Client</span>'}
                            </div>
                            <small class="text-muted">${formatDateTime(msg.date_envoi)}</small>
                        </div>
                        <p class="mb-0 mt-2">${htmlEscape(msg.contenu).replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
            `).join('')}
        </div>

        ${pagination.total_pages > 1 ? `
            <div class="d-flex justify-content-center mb-3">
                <nav>
                    <ul class="pagination mb-0">
                        ${Array.from({length: pagination.total_pages}, (_, i) => i + 1).map(page => `
                            <li class="page-item ${page === pagination.current_page ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadTicketMessages(${ticket.id_ticket}, ${page}); return false;">${page}</a>
                            </li>
                        `).join('')}
                    </ul>
                </nav>
            </div>
        ` : ''}

        <div class="card bg-light">
            <div class="card-body">
                <h6>Ajouter une réponse</h6>
                <form id="replyForm" onsubmit="submitReply(event, ${ticket.id_ticket})">
                    <textarea class="form-control mb-2" id="replyContent" rows="3" placeholder="Votre réponse..." required></textarea>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer la réponse
                    </button>
                </form>
            </div>
        </div>
    `;

    details.innerHTML = html;
}

function loadTicketMessages(ticketId, page) {
    fetch(`api/tickets.php?action=details&id=${ticketId}&message_page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTicketDetails(data.ticket, data.messages, data.pagination);
            }
        });
}

function updateTicket(ticketId) {
    const statut = document.getElementById('ticketStatut').value;
    const priorite = document.getElementById('ticketPriorite').value;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('ticket_id', ticketId);
    formData.append('statut', statut);
    formData.append('priorite', priorite);

    fetch('api/tickets.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Ticket mis à jour avec succès');
            viewTicket(ticketId);
        } else {
            showError(data.error || 'Erreur lors de la mise à jour');
        }
    });
}

function submitReply(event, ticketId) {
    event.preventDefault();

    const content = document.getElementById('replyContent').value.trim();
    if (content === '') {
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('ticket_id', ticketId);
    formData.append('contenu', content);

    fetch('api/tickets.php?action=reply', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Réponse ajoutée avec succès');
            document.getElementById('replyContent').value = '';
            viewTicket(ticketId);
        } else {
            showError(data.error || 'Erreur lors de l\'envoi');
        }
    });
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR') + ' à ' +
        date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function htmlEscape(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.dashboard-content').insertBefore(alert, document.querySelector('.dashboard-content').firstChild);
    setTimeout(() => alert.remove(), 5000);
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.dashboard-content').insertBefore(alert, document.querySelector('.dashboard-content').firstChild);
    setTimeout(() => alert.remove(), 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>

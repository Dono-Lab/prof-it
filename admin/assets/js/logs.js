let connexionPage = 1;
let connexionPerPage = 15;
let allConnexions = [];
let filteredConnexions = [];

let visitePage = 1;
let visitePerPage = 15;
let allVisites = [];
let filteredVisites = [];

document.addEventListener('DOMContentLoaded', function () {
    loadConnexions();
    document.getElementById('searchConnexion').addEventListener('input', filterConnexions);
    document.getElementById('statutFilter').addEventListener('change', filterConnexions);
    document.getElementById('dateFilter').addEventListener('change', filterConnexions);
    document.getElementById('searchVisite').addEventListener('input', filterVisites);
    document.getElementById('dateVisiteFilter').addEventListener('change', filterVisites);
    document.getElementById('userVisiteFilter').addEventListener('change', filterVisites);
    document.getElementById('visites-tab').addEventListener('click', function () {
        if (allVisites.length === 0) {
            loadVisites();
        }
    });
});

function loadConnexions() {
    fetch('api/get_logs.php?type=connexions')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allConnexions = data.logs;
                filteredConnexions = allConnexions;
                displayConnexions();
                updateConnexionStats();
            } else {
                showError('Erreur de chargement des logs connexions');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function displayConnexions() {
    const start = (connexionPage - 1) * connexionPerPage;
    const end = start + connexionPerPage;
    const toDisplay = filteredConnexions.slice(start, end);
    const tbody = document.getElementById('connexionsTableBody');

    if (toDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucun log trouvé</td></tr>';
        return;
    }

    tbody.innerHTML = toDisplay.map(log => {
        const statusBadge = log.statut === 'success'
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Réussie</span>'
            : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Échouée</span>';

        const userAgent = log.user_agent ? log.user_agent.substring(0, 50) + '...' : 'N/A';

        return `
            <tr>
                <td><strong>${log.email}</strong></td>
                <td>${statusBadge}</td>
                <td><small class="text-muted">${userAgent}</small></td>
                <td>${formatDateTime(log.date_connexion)}</td>
            </tr>
        `;
    }).join('');

    updateConnexionPagination();
}

function filterConnexions() {
    const searchTerm = document.getElementById('searchConnexion').value.toLowerCase();
    const statutFilter = document.getElementById('statutFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    filteredConnexions = allConnexions.filter(log => {
        const matchSearch = log.email.toLowerCase().includes(searchTerm);
        const matchStatus = statutFilter === '' || log.statut === statutFilter;
        let matchDate = true;
        if (dateFilter) {
            const logDate = log.date_connexion.split(' ')[0];
            matchDate = logDate === dateFilter;
        }
        return matchSearch && matchStatus && matchDate;
    });
    connexionPage = 1;
    displayConnexions();
    updateConnexionStats();
}

function updateConnexionStats() {
    const total = filteredConnexions.length;
    const success = filteredConnexions.filter(log => log.statut === 'success').length;
    const failed = total - success;
    const tauxReussite = total > 0 ? Math.round((success / total) * 100) : 0;
    document.getElementById('totalConnexions').textContent = total;
    document.getElementById('successConnexions').textContent = success;
    document.getElementById('failedConnexions').textContent = failed;
    document.getElementById('tauxReussite').textContent = tauxReussite + '%';
}

function updateConnexionPagination() {
    const totalPages = Math.ceil(filteredConnexions.length / connexionPerPage);

    const start = (connexionPage - 1) * connexionPerPage + 1;
    const end = Math.min(connexionPage * connexionPerPage, filteredConnexions.length);

    document.getElementById('connexionPaginationInfo').textContent =
        `Affichage de ${start} à ${end} sur ${filteredConnexions.length} logs`;

    const pagination = document.getElementById('connexionPagination');
    let html = '';

    html += `<li class="page-item ${connexionPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changeConnexionPage(${connexionPage - 1}); return false;">Précédent</a>
    </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i <= 3 || i > totalPages - 3 || (i >= connexionPage - 1 && i <= connexionPage + 1)) {
            html += `<li class="page-item ${i === connexionPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changeConnexionPage(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === connexionPage - 2 || i === connexionPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${connexionPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changeConnexionPage(${connexionPage + 1}); return false;">Suivant</a>
    </li>`;

    pagination.innerHTML = html;
}

function changeConnexionPage(page) {
    const totalPages = Math.ceil(filteredConnexions.length / connexionPerPage);
    if (page >= 1 && page <= totalPages) {
        connexionPage = page;
        displayConnexions();
    }
}

function resetConnexionFilters() {
    document.getElementById('searchConnexion').value = '';
    document.getElementById('statutFilter').value = '';
    document.getElementById('dateFilter').value = '';
    filteredConnexions = allConnexions;
    connexionPage = 1;
    displayConnexions();
    updateConnexionStats();
}

function loadVisites() {
    fetch('api/get_logs.php?type=visites')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allVisites = data.logs;
                filteredVisites = allVisites;
                displayVisites();
                updateVisiteStats();
            } else {
                showError('Erreur de chargement des logs visites');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function displayVisites() {
    const start = (visitePage - 1) * visitePerPage;
    const end = start + visitePerPage;
    const toDisplay = filteredVisites.slice(start, end);

    const tbody = document.getElementById('visitesTableBody');

    if (toDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Aucun log trouvé</td></tr>';
        return;
    }

    tbody.innerHTML = toDisplay.map(log => {
        const userName = log.user_id
            ? `<strong>${log.prenom || ''} ${log.nom || ''}</strong> <small class="text-muted">(${log.email})</small>`
            : '<span class="text-muted">Visiteur anonyme</span>';

        return `
            <tr>
                <td>${userName}</td>
                <td><code style="font-size: 12px;">${log.page_url}</code></td>
                <td>${formatDateTime(log.date_visite)}</td>
            </tr>
        `;
    }).join('');

    updateVisitePagination();
}

function filterVisites() {
    const searchTerm = document.getElementById('searchVisite').value.toLowerCase();
    const dateFilter = document.getElementById('dateVisiteFilter').value;
    const userFilter = document.getElementById('userVisiteFilter').value;

    filteredVisites = allVisites.filter(log => {
        const matchSearch = log.page_url.toLowerCase().includes(searchTerm);

        let matchDate = true;
        if (dateFilter) {
            const logDate = log.date_visite.split(' ')[0];
            matchDate = logDate === dateFilter;
        }

        let matchUser = true;
        if (userFilter === 'connected') {
            matchUser = log.user_id !== null;
        } else if (userFilter === 'anonymous') {
            matchUser = log.user_id === null;
        }

        return matchSearch && matchDate && matchUser;
    });

    visitePage = 1;
    displayVisites();
    updateVisiteStats();
}

function updateVisiteStats() {
    const total = filteredVisites.length;
    const connected = filteredVisites.filter(log => log.user_id !== null).length;
    const anonymous = total - connected;

    document.getElementById('totalVisites').textContent = total;
    document.getElementById('visitesConnected').textContent = connected;
    document.getElementById('visitesAnonymous').textContent = anonymous;
}

function updateVisitePagination() {
    const totalPages = Math.ceil(filteredVisites.length / visitePerPage);

    const start = (visitePage - 1) * visitePerPage + 1;
    const end = Math.min(visitePage * visitePerPage, filteredVisites.length);

    document.getElementById('visitePaginationInfo').textContent =
        `Affichage de ${start} à ${end} sur ${filteredVisites.length} logs`;

    const pagination = document.getElementById('visitePagination');
    let html = '';

    html += `<li class="page-item ${visitePage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changeVisitePage(${visitePage - 1}); return false;">Précédent</a>
    </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i <= 3 || i > totalPages - 3 || (i >= visitePage - 1 && i <= visitePage + 1)) {
            html += `<li class="page-item ${i === visitePage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changeVisitePage(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === visitePage - 2 || i === visitePage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${visitePage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changeVisitePage(${visitePage + 1}); return false;">Suivant</a>
    </li>`;

    pagination.innerHTML = html;
}

function changeVisitePage(page) {
    const totalPages = Math.ceil(filteredVisites.length / visitePerPage);
    if (page >= 1 && page <= totalPages) {
        visitePage = page;
        displayVisites();
    }
}


function resetVisiteFilters() {
    document.getElementById('searchVisite').value = '';
    document.getElementById('dateVisiteFilter').value = '';
    document.getElementById('userVisiteFilter').value = '';
    filteredVisites = allVisites;
    visitePage = 1;
    displayVisites();
    updateVisiteStats();
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR') + ' à ' +
        date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

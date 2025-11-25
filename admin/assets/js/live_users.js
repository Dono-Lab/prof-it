let refreshInterval = null;
let autoRefresh = true;

document.addEventListener('DOMContentLoaded', function () {
    loadLiveUsers();

    const toggleBtn = document.getElementById('toggleAutoRefresh');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleAutoRefresh);
    }
});

function loadLiveUsers() {
    fetch('api/get_live_user.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
                displayLiveUsers(data.users);
                updateLastRefreshTime();
            } else {
                showError('Erreur de chargement');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function updateStats(stats) {
    document.getElementById('totalConnected').textContent = stats.total || 0;
    document.getElementById('studentsConnected').textContent = stats.students || 0;
    document.getElementById('teachersConnected').textContent = stats.teachers || 0;
    document.getElementById('adminsConnected').textContent = stats.admins || 0;
}

function displayLiveUsers(users) {
    const tbody = document.getElementById('liveUsersTableBody');


    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="fas fa-users-slash fa-3x mb-3 d-block"></i>
                    Aucun utilisateur connecté pour le moment
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = users.map(user => {
        let roleClass = 'secondary';
        let roleLabel = user.role;

        if (user.role === 'admin') {
            roleClass = 'danger';
            roleLabel = 'Admin';
        } else if (user.role === 'teacher') {
            roleClass = 'warning text-dark';
            roleLabel = 'Professeur';
        } else if (user.role === 'student') {
            roleClass = 'primary';
            roleLabel = 'Étudiant';
        }

        let currentPage;
        if (user.current_url) {
            if (user.current_url.length > 40) {
                currentPage = user.current_url.substring(0, 40) + '...';
            } else {
                currentPage = user.current_url;
            }
        } else {
            currentPage = 'N/A';
        }

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-circle text-success me-2" style="font-size: 8px;" title="En ligne"></i>
                        <img src="https://ui-avatars.com/api/?name=${user.prenom}+${user.nom}&background=random" class="rounded-circle me-2" width="32" height="32">
                        <strong>${user.prenom} ${user.nom}</strong>
                    </div>
                </td>
                <td>${user.email}</td>
                <td><span class="badge bg-${roleClass}">${roleLabel}</span></td>
                <td><small class="text-muted">${currentPage}</small></td>
                <td><small class="text-muted">${getTimeAgo(user.derniere_activite)}</small></td>
            </tr>
        `;
    }).join('');
}

function getTimeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 10) return 'À l\'instant';
    if (seconds < 60) return `Il y a ${seconds}s`;

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `Il y a ${minutes} min`;

    const hours = Math.floor(minutes / 60);
    return `Il y a ${hours}h`;
}

function updateLastRefreshTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const refreshInfo = document.getElementById('lastRefreshTime');
    if (refreshInfo) {
        refreshInfo.textContent = `Dernière mise à jour : ${timeStr}`;
    }
}

function refreshNow() {
    loadLiveUsers();
}

window.addEventListener('beforeunload', function () {
    stopAutoRefresh();
});

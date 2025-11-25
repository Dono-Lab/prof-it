const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 991) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
}

function initializeGlobalSearch() {
    const searchInput = document.querySelector('#globalSearch, .search-box input');

    if (!searchInput) return;

    const searchBox = searchInput.parentElement;
    const clearBtn = document.createElement('button');
    clearBtn.innerHTML = '<i class="fas fa-times"></i>';
    clearBtn.className = 'clear-search-btn';
    clearBtn.style.cssText = 'background: none; border: none; color: #6b7280; cursor: pointer; display: none; padding: 0 8px;';
    searchBox.appendChild(clearBtn);

    searchInput.addEventListener('input', function() {
        clearBtn.style.display = this.value.length > 0 ? 'block' : 'none';

        clearTimeout(searchInput.timeout);
        searchInput.timeout = setTimeout(() => {
            performSearch(this.value);
        }, 300);
    });

    clearBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        searchInput.value = '';
        clearBtn.style.display = 'none';
        resetSearch();
        searchInput.focus();
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch(this.value);
        }
    });
}

function performSearch(query) {
    const searchTerm = query.toLowerCase().trim();

    if (searchTerm === '') {
        resetSearch();
        return;
    }

    let resultsFound = false;

    document.querySelectorAll('.stat-card').forEach(card => {
        const cardText = card.textContent.toLowerCase();
        if (cardText.includes(searchTerm)) {
            card.style.backgroundColor = '#f0f9ff';
            card.style.border = '2px solid #3b82f6';
            resultsFound = true;
        } else {
            card.style.backgroundColor = '';
            card.style.border = '';
        }
    });

    document.querySelectorAll('table tbody tr').forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchTerm)) {
            row.style.backgroundColor = '#f0f9ff';
            resultsFound = true;
        } else {
            row.style.backgroundColor = '';
        }
    });

    document.querySelectorAll('.activity-item').forEach(item => {
        const itemText = item.textContent.toLowerCase();
        if (itemText.includes(searchTerm)) {
            item.style.backgroundColor = '#f0f9ff';
            item.style.borderLeft = '4px solid #3b82f6';
            resultsFound = true;
        } else {
            item.style.backgroundColor = '';
            item.style.borderLeft = '';
        }
    });

    showSearchMessage(resultsFound, searchTerm);
}

function resetSearch() {
    document.querySelectorAll('.stat-card').forEach(card => {
        card.style.backgroundColor = '';
        card.style.border = '';
    });

    document.querySelectorAll('table tbody tr').forEach(row => {
        row.style.backgroundColor = '';
    });

    document.querySelectorAll('.activity-item').forEach(item => {
        item.style.backgroundColor = '';
        item.style.borderLeft = '';
    });

    const existingMessage = document.querySelector('.search-message');
    if (existingMessage) {
        existingMessage.remove();
    }
}

function showSearchMessage(resultsFound, searchTerm) {
    const existingMessage = document.querySelector('.search-message');
    if (existingMessage) {
        existingMessage.remove();
    }

    if (!resultsFound) {
        const message = document.createElement('div');
        message.className = 'search-message alert alert-warning';
        message.style.cssText = 'margin: 16px 0; text-align: center;';
        message.innerHTML = `<i class="fas fa-search me-2"></i>Aucun résultat trouvé pour "${searchTerm}"`;

        const pageTitle = document.querySelector('.page-title');
        if (pageTitle) {
            pageTitle.parentNode.insertBefore(message, pageTitle.nextSibling);
        }
    }
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR');
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR') + ' à ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function timeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 10) return "À l'instant";
    if (seconds < 60) return `Il y a ${seconds}s`;

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `Il y a ${minutes} min`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `Il y a ${hours}h`;

    const days = Math.floor(hours / 24);
    return `Il y a ${days}j`;
}

function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const content = document.querySelector('.dashboard-content, .main-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const content = document.querySelector('.dashboard-content, .main-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeGlobalSearch();

    document.querySelectorAll('.notification-list-item').forEach(item => {
        item.addEventListener('click', function() {
            this.classList.remove('unread');
        });
    });
});

function logout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        window.location.href = '../auth/logout.php';
    }
}

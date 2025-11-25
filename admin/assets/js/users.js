let currentPage = 1;
let usersPerPage = 10;
let allUsers = [];
let filteredUsers = [];
let currentEditingUserId = null;

document.addEventListener('DOMContentLoaded', function () {
    loadUsers();

    const searchInput = document.getElementById('searchUser');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const addUserForm = document.getElementById('addUserForm');
    const editUserForm = document.getElementById('editUserForm');

    if (searchInput) searchInput.addEventListener('input', filterUsers);
    if (roleFilter) roleFilter.addEventListener('change', filterUsers);
    if (statusFilter) statusFilter.addEventListener('change', filterUsers);

    if (addUserForm) addUserForm.addEventListener('submit', handleAddUser);
    if (editUserForm) editUserForm.addEventListener('submit', handleEditUser);
});

function loadUsers() {
    fetch('api/get_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allUsers = data.users || [];
                filteredUsers = allUsers.slice();
                displayUsers();
            } else {
                showError('Erreur de chargement des utilisateurs');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function displayUsers() {
    const start = (currentPage - 1) * usersPerPage;
    const end = start + usersPerPage;
    const usersToDisplay = filteredUsers.slice(start, end);

    const tbody = document.getElementById('usersTableBody');

    if (!tbody) {
        return;
    }

    if (usersToDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Aucun utilisateur trouv�</td></tr>';
        const info = document.getElementById('paginationInfo');
        const pagination = document.getElementById('pagination');
        if (info) info.textContent = '';
        if (pagination) pagination.innerHTML = '';
        return;
    }

    tbody.innerHTML = usersToDisplay.map(user => {
        let roleClass = 'secondary';
        let roleLabel = user.role;

        if (user.role === 'admin') {
            roleClass = 'danger';
            roleLabel = 'Admin';
        } else if (user.role === 'teacher') {
            roleClass = 'warning';
            roleLabel = 'Professeur';
        } else if (user.role === 'student') {
            roleClass = 'primary';
            roleLabel = 'Etudiant';
        }

        const statusBadge = String(user.actif) === '1'
            ? '<span class="badge bg-success">Actif</span>'
            : '<span class="badge bg-secondary">Inactif</span>';

        const contactLines = [];
        if (user.telephone) {
            contactLines.push(`Tél : ${user.telephone}`);
        }
        if (user.ville || user.code_postal) {
            const villeCp = `${user.code_postal || ''} ${user.ville || ''}`.trim();
            if (villeCp) {
                contactLines.push(villeCp);
            }
        }
        if (user.adresse) {
            const adr = user.adresse.length > 50 ? user.adresse.substring(0, 50) + '...' : user.adresse;
            contactLines.push(adr);
        }

        const contactHtml = contactLines.length
            ? `<div class="text-muted small mt-1">${contactLines.join('<br>')}</div>`
            : '';

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="https://ui-avatars.com/api/?name=${user.prenom}+${user.nom}&background=random" class="rounded-circle me-2" width="32" height="32">
                        <div>
                            <strong>${user.prenom} ${user.nom}</strong>
                            ${contactHtml}
                        </div>
                    </div>
                </td>
                <td>${user.email}</td>
                <td><span class="badge bg-${roleClass}">${roleLabel}</span></td>
                <td>${formatDate(user.created_at)}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(${user.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${user.prenom} ${user.nom}')" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    updatePagination();
}

function filterUsers() {
    const searchInput = document.getElementById('searchUser');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');

    const searchTerm = (searchInput?.value || '').toLowerCase();
    const roleValue = roleFilter?.value || '';
    const statusValue = statusFilter?.value || '';

    filteredUsers = allUsers.filter(user => {
        const matchSearch =
            user.nom.toLowerCase().includes(searchTerm) ||
            user.prenom.toLowerCase().includes(searchTerm) ||
            user.email.toLowerCase().includes(searchTerm);

        const matchRole = roleValue === '' || user.role === roleValue;
        const matchStatus = statusValue === '' || String(user.actif) === statusValue;

        return matchSearch && matchRole && matchStatus;
    });

    currentPage = 1;
    displayUsers();
}

function resetFilters() {
    const searchInput = document.getElementById('searchUser');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');

    if (searchInput) searchInput.value = '';
    if (roleFilter) roleFilter.value = '';
    if (statusFilter) statusFilter.value = '';

    filteredUsers = allUsers.slice();
    currentPage = 1;
    displayUsers();
}

function updatePagination() {
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);

    const info = document.getElementById('paginationInfo');
    const pagination = document.getElementById('pagination');
    if (!info || !pagination) return;

    const start = (currentPage - 1) * usersPerPage + 1;
    const end = Math.min(currentPage * usersPerPage, filteredUsers.length);
    info.textContent = `Affichage de ${start} à ${end} sur ${filteredUsers.length} utilisateurs`;

    let html = '';

    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Précédent</a>
    </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Suivant</a>
    </li>`;

    pagination.innerHTML = html;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        displayUsers();
    }
}

function handleAddUser(e) {
    e.preventDefault();

    const formData = new FormData(e.target);

    fetch('api/manage_user.php?action=create', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('addUserModal');
                if (modalEl) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                e.target.reset();
                loadUsers();
                showSuccess('Utilisateur créé avec succès');
            } else {
                showError(data.error || 'Erreur lors de la création');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function editUser(id) {
    const user = allUsers.find(u => u.id === id);
    if (!user) return;

    currentEditingUserId = id;

    const idInput = document.getElementById('editUserId');
    const nomInput = document.getElementById('editUserNom');
    const prenomInput = document.getElementById('editUserPrenom');
    const emailInput = document.getElementById('editUserEmail');
    const roleInput = document.getElementById('editUserRole');
    const telInput = document.getElementById('editUserTelephone');
    const adresseInput = document.getElementById('editUserAdresse');
    const villeInput = document.getElementById('editUserVille');
    const cpInput = document.getElementById('editUserCodePostal');
    const editActifCheckbox = document.getElementById('editActif');

    if (idInput) idInput.value = user.id;
    if (nomInput) nomInput.value = user.nom || '';
    if (prenomInput) prenomInput.value = user.prenom || '';
    if (emailInput) emailInput.value = user.email || '';
    if (roleInput) roleInput.value = user.role || 'student';
    if (telInput) telInput.value = user.telephone || '';
    if (adresseInput) adresseInput.value = user.adresse || '';
    if (villeInput) villeInput.value = user.ville || '';
    if (cpInput) cpInput.value = user.code_postal || '';
    if (editActifCheckbox) {
        editActifCheckbox.checked = String(user.actif) === '1';
    }

    const modalEl = document.getElementById('editUserModal');
    if (modalEl) {
        new bootstrap.Modal(modalEl).show();
    }
}

function handleEditUser(e) {
    e.preventDefault();

    const formData = new FormData(e.target);

    fetch('api/manage_user.php?action=update', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('editUserModal');
                if (modalEl) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                }
                loadUsers();
                showSuccess('Utilisateur modifié avec succès');
            } else {
                showError(data.error || 'Erreur lors de la modification');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function deleteUser(id, name) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${name}" ?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);

    fetch('api/manage_user.php?action=delete', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadUsers();
                showSuccess('Utilisateur supprimé avec succès');
            } else {
                showError(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion au serveur');
        });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR');
}

document.addEventListener('DOMContentLoaded', function () {
    const editActifCheckbox = document.getElementById('editActif');
    if (editActifCheckbox) {
        editActifCheckbox.addEventListener('change', function () {
            if (currentEditingUserId === null) return;

            const newValue = this.checked ? 1 : 0;

            const userIndex = allUsers.findIndex(u => u.id === currentEditingUserId);
            if (userIndex !== -1) {
                allUsers[userIndex].actif = newValue;
            }

            const filteredIndex = filteredUsers.findIndex(u => u.id === currentEditingUserId);
            if (filteredIndex !== -1) {
                filteredUsers[filteredIndex].actif = newValue;
            }

            displayUsers();
        });
    }
});

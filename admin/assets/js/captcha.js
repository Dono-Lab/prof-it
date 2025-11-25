let currentPage = 1;
let captchaPerPage = 10;
let allCaptcha = [];
let filteredCaptcha = [];

document.addEventListener('DOMContentLoaded', function() {
    loadCaptcha();
    document.getElementById('searchCaptcha').addEventListener('input', filterCaptcha);
    document.getElementById('statusFilter').addEventListener('change', filterCaptcha);
    document.getElementById('addCaptchaForm').addEventListener('submit', handleAddCaptcha);
    document.getElementById('editCaptchaForm').addEventListener('submit', handleEditCaptcha);
});

function loadCaptcha() {
    fetch('api/get_captcha.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allCaptcha = data.captcha;
                filteredCaptcha = allCaptcha;
                displayCaptcha();
                updateStats();
            } else {
                showError('Erreur de chargement');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showError('Erreur de connexion');
        });
}

function displayCaptcha() {
    const start = (currentPage - 1) * captchaPerPage;
    const end = start + captchaPerPage;
    const toDisplay = filteredCaptcha.slice(start, end);
    const tbody = document.getElementById('captchaTableBody');

    if (toDisplay.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucune question trouvée</td></tr>';
        return;
    }
    tbody.innerHTML = toDisplay.map(captcha => {
        const statusBadge = captcha.actif == 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
        return `
            <tr>
                <td>${captcha.question}</td>
                <td><code>${captcha.reponse}</code></td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="editCaptcha(${captcha.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="deleteCaptcha(${captcha.id}, '${captcha.question.substring(0, 30)}')" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    updatePagination();
}

function filterCaptcha() {
    const searchTerm = document.getElementById('searchCaptcha').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    filteredCaptcha = allCaptcha.filter(captcha => {
        const matchSearch = captcha.question.toLowerCase().includes(searchTerm) || captcha.reponse.toLowerCase().includes(searchTerm);
        const matchStatus = statusFilter === '' || captcha.actif == statusFilter;
        return matchSearch && matchStatus;
    });
    currentPage = 1;
    displayCaptcha();
    updateStats();
}

function resetFilters() {
    document.getElementById('searchCaptcha').value = '';
    document.getElementById('statusFilter').value = '';
    filteredCaptcha = allCaptcha;
    currentPage = 1;
    displayCaptcha();
    updateStats();
}

function updateStats() {
    const total = allCaptcha.length;
    const active = allCaptcha.filter(c => c.actif == 1).length;
    const inactive = total - active;
    document.getElementById('totalQuestions').textContent = total;
    document.getElementById('activeQuestions').textContent = active;
    document.getElementById('inactiveQuestions').textContent = inactive;
}

function updatePagination() {
    const totalPages = Math.ceil(filteredCaptcha.length / captchaPerPage);
    const start = (currentPage - 1) * captchaPerPage + 1;
    const end = Math.min(currentPage * captchaPerPage, filteredCaptcha.length);
    document.getElementById('paginationInfo').textContent = `Affichage de ${start} à ${end} sur ${filteredCaptcha.length} questions`;
    const pagination = document.getElementById('pagination');
    let html = '';

    html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">Précédent</a>
    </li>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
        </li>`;
    }

    html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">Suivant</a>
    </li>`;

    pagination.innerHTML = html;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredCaptcha.length / captchaPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        displayCaptcha();
    }
}

function handleAddCaptcha(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('api/manage_captcha.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addCaptchaModal')).hide();
            e.target.reset();
            loadCaptcha();
            showSuccess('Question créée avec succès');
        } else {
            showError(data.error || 'Erreur lors de la création');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

function editCaptcha(id) {
    const captcha = allCaptcha.find(c => c.id === id);
    if (!captcha) return;
    document.getElementById('editCaptchaId').value = captcha.id;
    document.getElementById('editCaptchaQuestion').value = captcha.question;
    document.getElementById('editCaptchaReponse').value = captcha.reponse;
    document.getElementById('editActif').checked = captcha.actif == 1;
    new bootstrap.Modal(document.getElementById('editCaptchaModal')).show();
}

function handleEditCaptcha(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('api/manage_captcha.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editCaptchaModal')).hide();
            loadCaptcha();
            showSuccess('Question modifiée avec succès');
        } else {
            showError(data.error || 'Erreur lors de la modification');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

function deleteCaptcha(id, questionPreview) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer cette question ?\n"${questionPreview}..."`)) {
        return;
    }
    const formData = new FormData();
    formData.append('id', id);

    fetch('api/manage_captcha.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCaptcha();
            showSuccess('Question supprimée avec succès');
        } else {
            showError(data.error || 'Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    });
}

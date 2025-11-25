<?php
$pageTitle = 'Tableau de Bord';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="main-content">
    <main class="dashboard-content">
        <h1 class="page-title mb-4">Tableau de Bord</h1>

        <div class="row g-4 mb-4" id="statsCards">
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card-custom h-100">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-chart-line me-2"></i>Inscriptions par mois</h5>
                    </div>
                    <div class="card-body-custom">
                        <canvas id="inscriptionsChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card-custom mb-4">
                    <div class="card-body-custom text-center">
                        <div class="stat-icon red mx-auto mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                        <h2 class="mb-1" id="newsletterCount">-</h2>
                        <p class="text-muted mb-0">Abonnés newsletter</p>
                    </div>
                </div>

                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-user-plus me-2"></i>Dernières inscriptions</h5>
                    </div>
                    <div class="card-body-custom" style="max-height: 300px; overflow-y: auto;">
                        <div id="recentUsers">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-6">
                <div class="card-custom h-100">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Pages les plus visitées</h5>
                        <select id="topPagesFilter" class="form-select form-select-sm" style="width: auto;" onchange="loadTopPages()">
                            <option value="day">Aujourd'hui</option>
                            <option value="week">7 derniers jours</option>
                            <option value="month">Ce mois</option>
                            <option value="all">Depuis le début</option>
                        </select>
                    </div>
                    <div class="card-body-custom">
                        <div id="topPagesList">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card-custom h-100">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-history me-2"></i>Activités récentes</h5>
                    </div>
                    <div class="card-body-custom" style="max-height: 400px; overflow-y: auto;">
                        <div id="recentActivities">
                            <div class="text-center p-4">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardStats();
        loadRecentUsers();
        loadRecentActivities();
        loadInscriptionsChart();
        loadTopPages();
    });

    function loadDashboardStats() {
        fetch('api/get_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStats(data.stats);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('statsCards').innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur de chargement</div></div>';
            });
    }

    function displayStats(stats) {
        const html = `
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>${stats.total_users}</h3>
                    <p>Utilisateurs</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon purple" style="background: #f3e8ff; color: #9333ea;">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-info">
                    <h3>${stats.visits_today}</h3>
                    <p>Visiteurs aujourd'hui</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-info">
                    <h3>${stats.students}</h3>
                    <p>Étudiants</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3>${stats.teachers}</h3>
                    <p>Professeurs</p>
                </div>
            </div>
        </div>
    `;
        document.getElementById('statsCards').innerHTML = html;
        document.getElementById('newsletterCount').textContent = stats.newsletter || '0';
    }

    function loadRecentUsers() {
        fetch('api/get_stats.php?action=recent_users')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecentUsers(data.users);
                }
            });
    }

    function displayRecentUsers(users) {
        if (users.length === 0) {
            document.getElementById('recentUsers').innerHTML = '<p class="text-muted text-center">Aucune inscription récente</p>';
            return;
        }

        const html = users.map(user => `
        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
            <img src="https://ui-avatars.com/api/?name=${user.prenom}+${user.nom}&background=random"
                class="rounded-circle me-3" width="40" height="40">
            <div class="flex-grow-1">
                <h6 class="mb-0">${user.prenom} ${user.nom}</h6>
                <small class="text-muted">${user.role}</small>
            </div>
            <small class="text-muted">${formatDate(user.created_at)}</small>
        </div>
    `).join('');
        document.getElementById('recentUsers').innerHTML = html;
    }

    function loadRecentActivities() {
        fetch('api/get_stats.php?action=recent_logs')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayActivities(data.logs);
                }
            });
    }

    function displayActivities(logs) {
        if (logs.length === 0) {
            document.getElementById('recentActivities').innerHTML = '<p class="text-muted text-center p-4">Aucune activité récente</p>';
            return;
        }

        const html = logs.map(log => `
        <div class="activity-item">
            <div class="activity-icon ${log.statut === 'success' ? 'blue' : 'red'}">
                <i class="fas fa-${log.statut === 'success' ? 'sign-in-alt' : 'times'}"></i>
            </div>
            <div class="activity-content">
                <h6>${log.email}</h6>
                <p>${log.statut === 'success' ? 'Connexion réussie' : 'Tentative échouée'}</p>
            </div>
            <div class="activity-time">${formatDate(log.date_connexion)}</div>
        </div>
    `).join('');
        document.getElementById('recentActivities').innerHTML = html;
    }

    function loadInscriptionsChart() {
        fetch('api/get_stats.php?action=chart_inscriptions')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    createChart(data.chartData);
                }
            });
    }

    function createChart(chartData) {
        const ctx = document.getElementById('inscriptionsChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Inscriptions',
                    data: chartData.data,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'À l\'instant';
        if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
        if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)}h`;
        return date.toLocaleDateString('fr-FR');
    }

    function loadTopPages() {
        const filter = document.getElementById('topPagesFilter').value;
        const container = document.getElementById('topPagesList');

        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

        fetch(`api/get_stats.php?action=top_pages&period=${filter}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTopPages(data.pages, data.total_period);
                }
            })
            .catch(error => console.error('Erreur:', error));
    }

    function displayTopPages(pages, total) {
        const container = document.getElementById('topPagesList');

        if (pages.length === 0) {
            container.innerHTML = '<p class="text-muted text-center mb-0">Aucune visite sur cette période.</p>';
            return;
        }

        let html = '<div class="d-flex flex-column gap-3">';

        pages.forEach(page => {
            let urlDisplay = page.page_url.replace(/^.*\/\/[^\/]+/, '');
            if(urlDisplay.length > 30) urlDisplay = '...' + urlDisplay.slice(-27);

            const percent = total > 0 ? Math.round((page.total / total) * 100) : 0;

            html += `
                <div class="page-stat-item">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-bold text-truncate" title="${page.page_url}">${urlDisplay}</small>
                        <small class="fw-bold">${page.total} v.</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${percent}%"></div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }
</script>

<?php require_once 'includes/footer.php'; ?>

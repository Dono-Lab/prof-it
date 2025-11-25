<?php
$pageTitle = 'Utilisateurs Connectés';
require_once '../admin/includes/header.php';
require_once '../admin/includes/sidebar.php';
?>

<div class="main-content">
    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Utilisateurs Connectés</h1>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    <span class="text-muted">Actualisation automatique toutes les 10s</span>
                </div>
                <button class="btn btn-outline-primary btn-sm" onclick="loadLiveUsers()">
                    <i class="fas fa-sync-alt me-1"></i>Actualiser maintenant
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                            <h3 class="mb-0" id="totalConnected">0</h3>
                        </div>
                        <p class="text-muted mb-0">Total connectés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-primary" id="studentsConnected">0</h3>
                        <p class="text-muted mb-0">Étudiants</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-warning" id="teachersConnected">0</h3>
                        <p class="text-muted mb-0">Professeurs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-danger" id="adminsConnected">0</h3>
                        <p class="text-muted mb-0">Administrateurs</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom mb-4">
            <div class="card-body-custom">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchUser" placeholder="Rechercher par nom, email...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">Tous les rôles</option>
                            <option value="student">Étudiants</option>
                            <option value="teacher">Professeurs</option>
                            <option value="admin">Administrateurs</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                            <i class="fas fa-redo me-2"></i>Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-body-custom">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Page actuelle</th>
                                <th>Dernière activité</th>
                            </tr>
                        </thead>
                        <tbody id="liveUsersTableBody">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Chargement...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted" id="lastUpdate">Dernière mise à jour : -</small>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../admin/assets/js/live_users.js?v=2"></script>

<?php require_once '../admin/includes/footer.php'; ?>
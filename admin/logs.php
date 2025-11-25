<?php
$pageTitle = 'Logs';
require_once '../admin/includes/header.php';
require_once '../admin/includes/sidebar.php';
?>

<div class="main-content">
    <main class="dashboard-content">
        <h1 class="page-title">Logs du système</h1>

        <ul class="nav nav-tabs mb-4" id="logsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="connexions-tab" data-bs-toggle="tab" 
                        data-bs-target="#connexions" type="button" role="tab">
                    <i class="fas fa-sign-in-alt me-2"></i>Logs Connexions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="visites-tab" data-bs-toggle="tab" 
                        data-bs-target="#visites" type="button" role="tab">
                    <i class="fas fa-eye me-2"></i>Logs Visites
                </button>
            </li>
            <a href="export_logs.php" class="btn btn-success"><i class="fas fa-file-csv me-2"></i>Exporter en CSV</a>
        </ul>

        <div class="tab-content" id="logsTabContent">          
            <div class="tab-pane fade show active" id="connexions" role="tabpanel">      
                <div class="card-custom mb-4">
                    <div class="card-body-custom">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchConnexion" placeholder="Rechercher par email...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statutFilter">
                                    <option value="">Tous les statuts</option>
                                    <option value="success">Réussies</option>
                                    <option value="failed">Échouées</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="resetConnexionFilters()">
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
                                <h3 class="mb-0" id="totalConnexions">-</h3>
                                <p class="text-muted mb-0">Total tentatives</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0 text-success" id="successConnexions">-</h3>
                                <p class="text-muted mb-0">Réussies</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0 text-danger" id="failedConnexions">-</h3>
                                <p class="text-muted mb-0">Échouées</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0 text-info" id="tauxReussite">-</h3>
                                <p class="text-muted mb-0">Taux de réussite</p>
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
                                        <th>Email</th>
                                        <th>Statut</th>
                                        <th>User Agent</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="connexionsTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="connexionPaginationInfo" class="text-muted"></div>
                            <nav>
                                <ul class="pagination mb-0" id="connexionPagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="visites" role="tabpanel">
                
                <div class="card-custom mb-4">
                    <div class="card-body-custom">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchVisite" placeholder="Rechercher par page...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="dateVisiteFilter">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="userVisiteFilter">
                                    <option value="">Tous les utilisateurs</option>
                                    <option value="connected">Connectés uniquement</option>
                                    <option value="anonymous">Anonymes uniquement</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="resetVisiteFilters()">
                                    <i class="fas fa-redo me-2"></i>Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0" id="totalVisites">-</h3>
                                <p class="text-muted mb-0">Total visites</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0 text-primary" id="visitesConnected">-</h3>
                                <p class="text-muted mb-0">Utilisateurs connectés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-custom">
                            <div class="card-body-custom text-center">
                                <h3 class="mb-0 text-secondary" id="visitesAnonymous">-</h3>
                                <p class="text-muted mb-0">Visiteurs anonymes</p>
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
                                        <th>Page visitée</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="visitesTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="visitePaginationInfo" class="text-muted"></div>
                            <nav>
                                <ul class="pagination mb-0" id="visitePagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="../admin/assets/js/logs.js?v=2"></script>

<?php require_once '../admin/includes/footer.php'; ?>
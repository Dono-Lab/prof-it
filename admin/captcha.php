<?php
$pageTitle = 'Gestion CAPTCHA';
require_once '../admin/includes/header.php';
require_once '../admin/includes/sidebar.php';
?>

<div class="main-content">
    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">Questions CAPTCHA</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCaptchaModal">
                <i class="fas fa-plus me-2"></i>Ajouter une question
            </button>
        </div>

        <div class="card-custom mb-4">
            <div class="card-body-custom">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchCaptcha" placeholder="Rechercher une question...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="statusFilter">
                            <option value="">Tous les statuts</option>
                            <option value="1">Actives</option>
                            <option value="0">Inactives</option>
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

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0" id="totalQuestions">-</h3>
                        <p class="text-muted mb-0">Total questions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-success" id="activeQuestions">-</h3>
                        <p class="text-muted mb-0">Questions actives</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom">
                    <div class="card-body-custom text-center">
                        <h3 class="mb-0 text-secondary" id="inactiveQuestions">-</h3>
                        <p class="text-muted mb-0">Questions inactives</p>
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
                                <th style="width: 40%">Question</th>
                                <th style="width: 25%">Réponse</th>
                                <th style="width: 10%">Statut</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="captchaTableBody">
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

<div class="modal fade" id="addCaptchaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une question CAPTCHA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCaptchaForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Question *</label>
                        <textarea class="form-control" name="question" rows="3" required placeholder="Ex: Quelle est la capitale de la France ?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Réponse *</label>
                        <input type="text" class="form-control" name="reponse" required placeholder="Ex: Paris">
                        <small class="text-muted">La réponse n'est pas sensible à la casse</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" id="addActif" checked>
                            <label class="form-check-label" for="addActif">
                                Question active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCaptchaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCaptchaForm">
                <input type="hidden" name="id" id="editCaptchaId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Question *</label>
                        <textarea class="form-control" name="question" id="editCaptchaQuestion" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Réponse *</label>
                        <input type="text" class="form-control" name="reponse" id="editCaptchaReponse" required>
                        <small class="text-muted">La réponse n'est pas sensible à la casse</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" id="editActif">
                            <label class="form-check-label" for="editActif">
                                Question active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../admin/assets/js/captcha.js"></script>

<?php require_once '../admin/includes/footer.php'; ?>
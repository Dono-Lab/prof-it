<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$pageTitle = 'Rendez-vous - Prof-IT';
$currentNav = 'student_documents';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Prof-IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php require __DIR__ . '/../templates/header.php'; ?>

    <div class="welcome-section" style="padding: 3rem 0; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
        <div class="container text-center">
            <h1>Gestion des Documents</h1>
            <p class="lead">Accédez à tous vos cours et ressources</p>
        </div>
    </div>

    <div class="container mt-5">
        <!-- Dossiers par matière -->
        <div class="documents-container">
            <h3><i class="fas fa-folder me-2"></i>Mes dossiers par matière</h3>
            <div class="row mt-4">
                <div class="col-md-3 mb-4">
                    <div class="folder-card" onclick="openFolder('Mathématiques')">
                        <i class="fas fa-calculator fa-3x text-primary mb-3"></i>
                        <h5>Mathématiques</h5>
                        <small class="text-muted">15 documents</small>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="folder-card" onclick="openFolder('Anglais')">
                        <i class="fas fa-language fa-3x text-success mb-3"></i>
                        <h5>Anglais</h5>
                        <small class="text-muted">8 documents</small>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="folder-card" onclick="openFolder('Physique')">
                        <i class="fas fa-atom fa-3x text-warning mb-3"></i>
                        <h5>Physique</h5>
                        <small class="text-muted">12 documents</small>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="folder-card" onclick="openFolder('Chimie')">
                        <i class="fas fa-flask fa-3x text-danger mb-3"></i>
                        <h5>Chimie</h5>
                        <small class="text-muted">6 documents</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Documents récents -->
            <div class="col-lg-8">
                <div class="documents-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-clock me-2"></i>Documents récents</h3>
                        <button class="btn btn-signup" onclick="showUploadModal()">
                            <i class="fas fa-upload me-2"></i>Ajouter un document
                        </button>
                    </div>

                    <div class="documents-list">
                        <div class="document-card">
                            <div class="d-flex align-items-center">
                                <div class="document-icon icon-pdf">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Cours d'algèbre linéaire.pdf</h6>
                                    <p class="mb-1 text-muted">Mathématiques • 2.4 MB • Ajouté le 10/01/2024</p>
                                    <small class="text-muted">Par Prof. Marie Dubois</small>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="document-card">
                            <div class="d-flex align-items-center">
                                <div class="document-icon icon-word">
                                    <i class="fas fa-file-word"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Exercices anglais conversation.docx</h6>
                                    <p class="mb-1 text-muted">Anglais • 1.8 MB • Ajouté le 09/01/2024</p>
                                    <small class="text-muted">Par Prof. Pierre Martin</small>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="document-card">
                            <div class="d-flex align-items-center">
                                <div class="document-icon icon-powerpoint">
                                    <i class="fas fa-file-powerpoint"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Mécanique classique.pptx</h6>
                                    <p class="mb-1 text-muted">Physique • 4.2 MB • Ajouté le 08/01/2024</p>
                                    <small class="text-muted">Par Prof. Sophie Laurent</small>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="document-card">
                            <div class="d-flex align-items-center">
                                <div class="document-icon icon-excel">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Tableaux de données.xlsx</h6>
                                    <p class="mb-1 text-muted">Physique • 0.8 MB • Ajouté le 07/01/2024</p>
                                    <small class="text-muted">Par Prof. Sophie Laurent</small>
                                </div>
                                <div class="document-actions">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de dépôt et statistiques -->
            <div class="col-lg-4">
                <div class="documents-container">
                    <h3><i class="fas fa-cloud-upload-alt me-2"></i>Déposer un document</h3>
                    <div class="upload-area mt-4" onclick="document.getElementById('file-input').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h5>Glissez-déposez vos fichiers ici</h5>
                        <p class="text-muted">ou cliquez pour parcourir</p>
                        <small class="text-muted">PDF, Word, Excel, PowerPoint (max. 10MB)</small>
                    </div>
                    <input type="file" id="file-input" style="display: none;" onchange="handleFileUpload(this)">
                </div>

                <!-- Statistiques des documents -->
                <div class="documents-container">
                    <h3><i class="fas fa-chart-pie me-2"></i>Statistiques</h3>
                    <div class="row text-center mt-4">
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-primary">41</h4>
                                <small>Documents totaux</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="border rounded p-3">
                                <h4 class="text-success">156MB</h4>
                                <small>Espace utilisé</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Répartition par type :</small>
                        <div class="mt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span>PDF</span>
                                <span>55%</span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: 55%"></div>
                            </div>

                            <div class="d-flex justify-content-between mb-1">
                                <span>Word</span>
                                <span>25%</span>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: 25%"></div>
                            </div>

                            <div class="d-flex justify-content-between mb-1">
                                <span>Autres</span>
                                <span>20%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: 20%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
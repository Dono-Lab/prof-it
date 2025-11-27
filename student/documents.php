<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$pageTitle = 'Documents - Prof-IT';
$currentNav = 'student_documents';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php require __DIR__ . '/../templates/header.php'; ?>

    <div class="dashboard-content">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-folder-open me-2"></i>Gestion des Documents</h1>

            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h5><i class="fas fa-folder me-2"></i>Mes dossiers par matière</h5>
                </div>
                <div class="card-body-custom">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="folder-card" onclick="openFolder('Mathématiques')">
                                <i class="fas fa-calculator fa-3x text-primary mb-3"></i>
                                <h5 class="mb-2">Mathématiques</h5>
                                <small class="text-muted">15 documents</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="folder-card" onclick="openFolder('Anglais')">
                                <i class="fas fa-language fa-3x text-success mb-3"></i>
                                <h5 class="mb-2">Anglais</h5>
                                <small class="text-muted">8 documents</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="folder-card" onclick="openFolder('Physique')">
                                <i class="fas fa-atom fa-3x text-warning mb-3"></i>
                                <h5 class="mb-2">Physique</h5>
                                <small class="text-muted">12 documents</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="folder-card" onclick="openFolder('Chimie')">
                                <i class="fas fa-flask fa-3x text-danger mb-3"></i>
                                <h5 class="mb-2">Chimie</h5>
                                <small class="text-muted">6 documents</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Documents récents</h5>
                                <button class="btn btn-primary btn-sm" onclick="showUploadModal()">
                                    <i class="fas fa-upload me-1"></i>Ajouter
                                </button>
                            </div>
                        </div>
                        <div class="card-body-custom">
                            <div class="documents-list">
                                <div class="document-card">
                                    <div class="d-flex align-items-center">
                                        <div class="document-icon icon-pdf">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Cours d'algèbre linéaire.pdf</h6>
                                            <p class="mb-1 text-muted small">Mathématiques • 2.4 MB • Ajouté le 10/01/2024</p>
                                            <small class="text-muted">Par Prof. Marie Dubois</small>
                                        </div>
                                        <div class="document-actions">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)" title="Partager">
                                                <i class="fas fa-share-alt"></i>
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
                                            <p class="mb-1 text-muted small">Anglais • 1.8 MB • Ajouté le 09/01/2024</p>
                                            <small class="text-muted">Par Prof. Pierre Martin</small>
                                        </div>
                                        <div class="document-actions">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)" title="Partager">
                                                <i class="fas fa-share-alt"></i>
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
                                            <p class="mb-1 text-muted small">Physique • 4.2 MB • Ajouté le 08/01/2024</p>
                                            <small class="text-muted">Par Prof. Sophie Laurent</small>
                                        </div>
                                        <div class="document-actions">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)" title="Partager">
                                                <i class="fas fa-share-alt"></i>
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
                                            <p class="mb-1 text-muted small">Physique • 0.8 MB • Ajouté le 07/01/2024</p>
                                            <small class="text-muted">Par Prof. Sophie Laurent</small>
                                        </div>
                                        <div class="document-actions">
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadDocument(this)" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="shareDocument(this)" title="Partager">
                                                <i class="fas fa-share-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-cloud-upload-alt me-2"></i>Déposer un document</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="upload-area" onclick="document.getElementById('file-input').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Glissez-déposez vos fichiers ici</h5>
                                <p class="text-muted mb-2">ou cliquez pour parcourir</p>
                                <small class="text-muted">PDF, Word, Excel, PowerPoint (max. 10MB)</small>
                            </div>
                            <input type="file" id="file-input" style="display: none;" onchange="handleFileUpload(this)">
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-chart-pie me-2"></i>Statistiques</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-primary mb-1">41</h4>
                                        <small class="text-muted">Documents totaux</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-success mb-1">156MB</h4>
                                        <small class="text-muted">Espace utilisé</small>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted fw-bold">Répartition par type :</small>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small"><i class="fas fa-file-pdf text-danger me-2"></i>PDF</span>
                                        <span class="small fw-bold">55%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-danger" style="width: 55%"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small"><i class="fas fa-file-word text-primary me-2"></i>Word</span>
                                        <span class="small fw-bold">25%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: 25%"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small"><i class="fas fa-file text-secondary me-2"></i>Autres</span>
                                        <span class="small fw-bold">20%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-secondary" style="width: 20%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openFolder(folderName) {
            alert('Ouverture du dossier : ' + folderName);
        }

        function showUploadModal() {
            document.getElementById('file-input').click();
        }

        function handleFileUpload(input) {
            if (input.files && input.files[0]) {
                alert('Fichier sélectionné : ' + input.files[0].name);
            }
        }

        function downloadDocument(button) {
            alert('Téléchargement du document...');
        }

        function shareDocument(button) {
            alert('Partage du document...');
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

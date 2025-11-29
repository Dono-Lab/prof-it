<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions_user.php';
require_role('student');
$pageTitle = 'Documents - Prof-IT';
$currentNav = 'student_documents';
$userId = $_SESSION['user_id'] ?? 0;
$documents = get_user_documents($userId, $conn, 10);
$documentStats = get_user_document_stats($userId, $conn);
$categoryCards = array_slice($documentStats['categories'], 0, 4);
$documentsCsrfToken = csrf_token();

if (!function_exists('format_filesize')) {
    function format_filesize($bytes)
    {
        if (!$bytes) {
            return '0 KB';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}

if (!function_exists('document_icon_class')) {
    function document_icon_class($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => ['icon-pdf', 'fas fa-file-pdf'],
            'doc', 'docx' => ['icon-word', 'fas fa-file-word'],
            'ppt', 'pptx' => ['icon-powerpoint', 'fas fa-file-powerpoint'],
            'xls', 'xlsx', 'csv' => ['icon-excel', 'fas fa-file-excel'],
            default => ['icon-generic', 'fas fa-file'],
        };
    }
}
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
                        <?php if (!empty($categoryCards)): ?>
                            <?php foreach ($categoryCards as $category): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="folder-card open-category" data-category="<?= htmlspecialchars($category['categorie']) ?>">
                                        <div class="d-flex justify-content-between align-items-start w-100">
                                            <div>
                                                <i class="fas fa-folder-open fa-3x text-primary mb-3"></i>
                                                <h5 class="mb-2"><?= htmlspecialchars($category['categorie']) ?></h5>
                                                <small class="text-muted"><?= (int)$category['total'] ?> documents</small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-category" data-category="<?= htmlspecialchars($category['categorie']) ?>" title="Supprimer le dossier">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted mb-0">Aucune catégorie disponible pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom mb-4 d-none" id="category-panel">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0" id="category-title"></h5>
                            <button class="btn btn-sm btn-outline-secondary" id="close-category">
                                <i class="fas fa-times me-1"></i>Fermer
                            </button>
                        </div>
                        <div class="card-body-custom">
                            <div id="category-content" class="documents-list">
                                <p class="text-muted mb-0">Sélectionnez un dossier pour afficher ses documents.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Documents récents</h5>
                        </div>
                        <div class="card-body-custom">
                            <div class="documents-list" id="recent-documents">
                                <?php if (empty($documents)): ?>
                                    <p class="text-muted mb-0">Aucun document n'a encore été ajouté.</p>
                                <?php else: ?>
                                    <?php foreach ($documents as $document):
                                        [$iconClass, $icon] = document_icon_class($document['nom_original']);
                                        $category = $document['categorie'] ?: 'Autre';
                                        $date = date('d/m/Y', strtotime($document['uploaded_at']));
                                    ?>
                                        <div class="document-card">
                                            <div class="d-flex align-items-center">
                                                <div class="document-icon <?= $iconClass ?>">
                                                    <i class="<?= $icon ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($document['nom_original']) ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <?= htmlspecialchars($category) ?>
                                                        • <?= format_filesize($document['taille_octets']) ?>
                                                        • Ajouté le <?= $date ?>
                                                    </p>
                                                    <small class="text-muted"><?= $document['source'] === 'messaging' ? 'Ajouté via messagerie' : 'Ajouté manuellement' ?></small>
                                                </div>
                                                <div class="document-actions">
                                                    <a class="btn btn-sm btn-outline-primary me-1" href="../<?= htmlspecialchars($document['fichier_path']) ?>" download title="Télécharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-document" data-id="<?= (int)$document['id_document'] ?>" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                            <form id="upload-form" enctype="multipart/form-data">
                                <div class="upload-area" onclick="document.getElementById('document-file').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Glissez-déposez vos fichiers ici</h5>
                                    <p class="text-muted mb-2">ou cliquez pour parcourir</p>
                                    <small class="text-muted">PDF, Word, Excel, PowerPoint (max. 10MB)</small>
                                </div>
                                <input type="file" id="document-file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg" style="display:none" required>
                                <div class="mt-3">
                                    <label for="document-category" class="form-label">Catégorie (optionnel)</label>
                                    <input type="text" class="form-control" id="document-category" name="categorie" placeholder="Ex : Mathématiques">
                                </div>
                                <input type="hidden" name="action" value="upload">
                                <input type="hidden" name="csrf_token" value="<?= $documentsCsrfToken ?>">
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-1"></i>Téléverser
                                    </button>
                                </div>
                                <div class="text-muted small mt-2" id="upload-status"></div>
                            </form>
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
                                        <h4 class="text-primary mb-1"><?= $documentStats['total'] ?></h4>
                                        <small class="text-muted">Documents totaux</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3">
                                        <h4 class="text-success mb-1"><?= format_filesize($documentStats['total_size']) ?></h4>
                                        <small class="text-muted">Espace utilisé</small>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted fw-bold">Répartition par type :</small>
                                <div class="mt-3">
                                    <?php
                                    $totalDocs = max(1, $documentStats['total']);
                                    if (empty($documentStats['by_type'])):
                                    ?>
                                        <p class="text-muted small mb-0">Pas encore de documents à analyser.</p>
                                    <?php else:
                                        foreach ($documentStats['by_type'] as $ext => $count):
                                            $percent = round(($count / $totalDocs) * 100);
                                            $label = strtoupper($ext ?: 'Autres');
                                        ?>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small"><i class="fas fa-file text-secondary me-2"></i><?= htmlspecialchars($label) ?></span>
                                                <span class="small fw-bold"><?= $percent ?>%</span>
                                            </div>
                                            <div class="progress mb-3" style="height: 8px;">
                                                <div class="progress-bar" style="width: <?= $percent ?>%"></div>
                                            </div>
                                        <?php endforeach;
                                    endif; ?>
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
        const fileInput = document.getElementById('document-file');
        const uploadForm = document.getElementById('upload-form');
        const uploadStatus = document.getElementById('upload-status');
        const categories = document.querySelectorAll('.open-category');
        const categoryPanel = document.getElementById('category-panel');
        const categoryTitle = document.getElementById('category-title');
        const categoryContent = document.getElementById('category-content');
        const documentCsrfToken = '<?= $documentsCsrfToken ?>';

        fileInput?.addEventListener('change', function() {
            if (fileInput.files[0]) {
                uploadStatus.textContent = 'Fichier sélectionné : ' + fileInput.files[0].name;
            } else {
                uploadStatus.textContent = '';
            }
        });

        categories.forEach(card => {
            card.addEventListener('click', () => loadCategory(card.dataset.category));
        });

        document.getElementById('close-category')?.addEventListener('click', () => {
            categoryPanel.classList.add('d-none');
            categoryContent.innerHTML = '<p class="text-muted mb-0">Sélectionnez un dossier pour afficher ses documents.</p>';
        });

        uploadForm?.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!fileInput?.files.length) {
                uploadStatus.textContent = 'Veuillez sélectionner un fichier.';
                return;
            }

            uploadStatus.textContent = 'Téléversement en cours...';
            const formData = new FormData(uploadForm);

            try {
                const response = await fetch('../api/documents.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    uploadStatus.textContent = 'Document ajouté avec succès.';
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    uploadStatus.textContent = data.error || 'Erreur lors du téléversement.';
                }
            } catch (error) {
                console.error('Document upload error:', error);
                uploadStatus.textContent = 'Erreur réseau lors du téléversement.';
            }
        });

        async function loadCategory(category) {
            if (!category) return;
            categoryPanel.classList.remove('d-none');
            categoryTitle.textContent = category;
            categoryContent.innerHTML = '<p class="text-muted mb-0">Chargement...</p>';

            try {
                const response = await fetch(`../api/documents.php?action=category&name=${encodeURIComponent(category)}`);
                const data = await response.json();
                if (data.success) {
                    if (!data.documents.length) {
                        categoryContent.innerHTML = '<p class="text-muted mb-0">Aucun document dans ce dossier.</p>';
                    } else {
                        categoryContent.innerHTML = data.documents.map(renderDocumentCard).join('');
                    }
                } else {
                    categoryContent.innerHTML = `<p class="text-danger mb-0">${data.error || 'Erreur lors du chargement.'}</p>`;
                }
            } catch (error) {
                console.error('Erreur chargement catégorie:', error);
                categoryContent.innerHTML = '<p class="text-danger mb-0">Erreur réseau.</p>';
            }
        }

        function renderDocumentCard(doc) {
            const date = new Date(doc.uploaded_at).toLocaleDateString('fr-FR');
            return `
                <div class="document-card">
                    <div class="d-flex align-items-center">
                        <div class="document-icon icon-generic">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${escapeHtml(doc.nom_original)}</h6>
                            <p class="mb-1 text-muted small">
                                ${escapeHtml(doc.categorie || 'Autre')}
                                • ${formatSize(doc.taille_octets)}
                                • Ajouté le ${date}
                            </p>
                            <small class="text-muted">${doc.source === 'messaging' ? 'Ajouté via messagerie' : 'Ajouté manuellement'}</small>
                        </div>
                        <div class="document-actions">
                            <a class="btn btn-sm btn-outline-primary me-1" href="../${escapeHtml(doc.fichier_path)}" download title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-document" data-id="${doc.id_document}" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function formatSize(bytes) {
            if (!bytes) return '0 KB';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return Math.round(bytes * 10) / 10 + ' ' + units[i];
        }

        document.addEventListener('click', async function(event) {
            const deleteDocBtn = event.target.closest('.delete-document');
            if (deleteDocBtn) {
                event.stopPropagation();
                await deleteDocument(deleteDocBtn.dataset.id);
                return;
            }

            const deleteCategoryBtn = event.target.closest('.delete-category');
            if (deleteCategoryBtn) {
                event.stopPropagation();
                await deleteCategory(deleteCategoryBtn.dataset.category);
            }
        });

        async function deleteDocument(documentId) {
            if (!documentId) return;
            if (!confirm('Supprimer ce document ?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_document');
            formData.append('document_id', documentId);
            formData.append('csrf_token', documentCsrfToken);

            try {
                const response = await fetch('../api/documents.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.error || 'Erreur lors de la suppression du document.');
                    return;
                }
                window.location.reload();
            } catch (error) {
                console.error('Suppression document:', error);
                alert('Erreur réseau lors de la suppression.');
            }
        }

        async function deleteCategory(category) {
            if (!category) return;
            if (!confirm('Supprimer ce dossier et tous ses documents ?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_category');
            formData.append('categorie', category);
            formData.append('csrf_token', documentCsrfToken);

            try {
                const response = await fetch('../api/documents.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    alert(data.error || 'Erreur lors de la suppression du dossier.');
                    return;
                }
                window.location.reload();
            } catch (error) {
                console.error('Suppression dossier:', error);
                alert('Erreur réseau lors de la suppression.');
            }
        }
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

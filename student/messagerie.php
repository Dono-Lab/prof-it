<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$pageTitle = 'Messagerie - Prof-IT';
$currentNav = 'student_messagerie';
$currentUserId = $_SESSION['user_id'] ?? 0;
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
            <h1 class="page-title"><i class="fas fa-comments me-2"></i>Messagerie</h1>

            <div class="chat-container">
                <div class="chat-sidebar">
                    <div class="p-3 border-bottom">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0"
                                placeholder="Rechercher une conversation..."
                                id="search-conversations">
                        </div>
                    </div>

                    <div class="overflow-auto flex-grow-1" id="conversations-list">
                        <div class="text-center py-5 text-muted" id="loading-conversations">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 mb-0 small">Chargement des conversations...</p>
                        </div>
                    </div>
                </div>

                <div class="chat-main">
                    <div class="p-3 border-bottom bg-white" id="chat-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=Contact&background=3b82f6&color=fff"
                                class="rounded-circle" width="48" height="48" alt="Avatar" id="chat-header-avatar">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold" id="chat-header-name">Sélectionnez un contact</h6>
                                <small class="text-muted" id="chat-header-subject">Aucune matière</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" type="button" id="open-review-modal">
                                            <i class="fas fa-star me-2"></i>Noter ce cours
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-danger" type="button" id="delete-conversation">
                                            <i class="fas fa-trash-alt me-2"></i>Supprimer la conversation
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex-grow-1 overflow-auto p-4" id="messages-area" style="background: #f9fafb;">
                        <div class="d-flex flex-column" id="messages-container">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Sélectionnez une conversation pour voir les messages</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 border-top bg-white">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" title="Joindre un fichier" id="attach-btn">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="text" class="form-control"
                                placeholder="Tapez votre message..."
                                id="message-input"
                                autocomplete="off">
                            <button class="btn btn-primary" type="button" id="send-message">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <input type="file" id="attachment-input" style="display:none"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg">
                        <div class="small text-muted mt-2" id="attachment-name"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="review-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="review-form">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-star me-2 text-warning"></i>Laisser un avis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="review-alert" class="alert d-none" role="alert"></div>
                    <div class="mb-3">
                        <label for="review-note" class="form-label">Note</label>
                        <select class="form-select" id="review-note" required>
                            <option value="">Choisir...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Très bien</option>
                            <option value="3">3 - Correct</option>
                            <option value="2">2 - Moyen</option>
                            <option value="1">1 - À améliorer</option>
                        </select>
                    </div>
                    <div>
                        <label for="review-comment" class="form-label">Commentaire (optionnel)</label>
                        <textarea class="form-control" id="review-comment" rows="3" placeholder="Partagez votre retour d'expérience..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="review-submit">
                        <i class="fas fa-paper-plane me-1"></i>Envoyer mon avis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const conversationsList = document.getElementById('conversations-list');
            const messagesContainer = document.getElementById('messages-container');
            const messagesArea = document.getElementById('messages-area');
            const messageInput = document.getElementById('message-input');
            const sendBtn = document.getElementById('send-message');
            const searchInput = document.getElementById('search-conversations');
            const chatHeader = document.getElementById('chat-header');
            const attachmentInput = document.getElementById('attachment-input');
            const attachmentName = document.getElementById('attachment-name');
            const attachBtn = document.getElementById('attach-btn');
            const deleteConversationBtn = document.getElementById('delete-conversation');
            const openReviewBtn = document.getElementById('open-review-modal');
            const reviewModalEl = document.getElementById('review-modal');
            const reviewForm = document.getElementById('review-form');
            const reviewNote = document.getElementById('review-note');
            const reviewComment = document.getElementById('review-comment');
            const reviewAlert = document.getElementById('review-alert');
            const reviewSubmitBtn = document.getElementById('review-submit');
            const reviewModal = reviewModalEl ? new bootstrap.Modal(reviewModalEl) : null;
            const csrfToken = '<?= csrf_token() ?>';

            let conversations = [];
            let currentConversation = null;
            let currentMessages = [];
            const REFRESH_INTERVAL = 5000;

            loadConversations(true);
            setInterval(() => {
                loadConversations(false);
                if (currentConversation) {
                    loadMessages(currentConversation);
                }
            }, REFRESH_INTERVAL);

            async function loadConversations(autoSelectFirst = true) {
                try {
                    const response = await fetch('../api/messaging.php?action=conversations');
                    const data = await response.json();

                    if (data.success && data.conversations) {
                        conversations = data.conversations;
                        renderConversations(conversations);

                        if (conversations.length > 0 && (!currentConversation || autoSelectFirst)) {
                            const initialConversation = currentConversation ?? conversations[0].id_conversation;
                            loadMessages(initialConversation);
                        }
                    } else {
                        showEmptyConversations();
                    }
                } catch (error) {
                    console.error('Erreur chargement conversations:', error);
                    showEmptyConversations();
                }
            }

            function renderConversations(convs) {
                if (convs.length === 0) {
                    showEmptyConversations();
                    return;
                }

                conversationsList.innerHTML = convs.map((conv, index) => {
                    const unreadBadge = conv.nb_non_lus > 0 ?
                        `<span class="badge bg-primary rounded-pill mt-1">${conv.nb_non_lus}</span>` :
                        '';
                    const unreadClass = conv.nb_non_lus > 0 ? 'unread' : '';
                    const isActive = currentConversation ? (conv.id_conversation == currentConversation) : (index === 0);
                    const activeClass = isActive ? 'active' : '';

                    return `
                        <div class="conversation-item ${unreadClass} ${activeClass}"
                            data-conversation="${conv.id_conversation}"
                            data-contact="${escapeHtml(conv.contact_nom)}"
                            data-photo="${escapeHtml(conv.contact_photo || '')}"
                            data-subject="${escapeHtml(conv.nom_matiere || 'Cours')}">
                            <div class="d-flex align-items-start gap-3">
                                <img src="${conv.contact_photo ? '../' + escapeHtml(conv.contact_photo) : getAvatarUrl(conv.contact_nom)}"
                                    class="rounded-circle" width="48" height="48" alt="Avatar" style="object-fit: cover;">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fw-semibold">Prof. ${escapeHtml(conv.contact_nom)}</h6>
                                        <small class="text-muted">${formatDate(conv.date_dernier_message)}</small>
                                    </div>
                                    <p class="mb-0 text-muted small text-truncate">${escapeHtml(conv.dernier_message || 'Aucun message')}</p>
                                    ${unreadBadge}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const convId = this.dataset.conversation;
                        loadMessages(convId);
                    });
                });
            }

            function showEmptyConversations() {
                conversationsList.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Aucune conversation disponible</p>
                        <small>Les conversations apparaîtront après vos premières réservations</small>
                    </div>
                `;
            }

            async function loadMessages(conversationId) {
                currentConversation = conversationId;
                try {
                    document.querySelectorAll('.conversation-item').forEach(item => {
                        item.classList.remove('active');
                        if (item.dataset.conversation === conversationId) {
                            item.classList.add('active');
                            item.classList.remove('unread');
                            const badge = item.querySelector('.badge');
                            if (badge) badge.remove();
                        }
                    });

                    const response = await fetch(`../api/messaging.php?action=messages&conversation_id=${conversationId}`);
                    const data = await response.json();

                    if (data.success && data.messages) {
                        currentMessages = data.messages;
                        renderMessages(data.messages);
                        updateChatHeader(conversationId);

                        markAsRead(conversationId);
                    } else {
                        showMessagesError();
                    }
                } catch (error) {
                    console.error('Erreur chargement messages:', error);
                    showMessagesError();
                }
            }

            function showMessagesError() {
                messagesContainer.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <p>Erreur lors du chargement des messages</p>
                    </div>
                `;
            }

            function renderMessages(messages) {
                if (messages.length === 0) {
                    messagesContainer.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-comment-dots fa-3x mb-3"></i>
                            <p>Aucun message dans cette conversation</p>
                        </div>
                    `;
                    return;
                }

                messagesContainer.innerHTML = messages.map(msg => {
                    const isSent = msg.id_utilisateur == <?= (int)$currentUserId ?>;
                    const avatarUrl = msg.auteur_photo ?
                        '../' + escapeHtml(msg.auteur_photo) :
                        getAvatarUrl(msg.auteur_nom);

                    if (isSent) {
                        return `
                            <div class="mb-3">
                                <div class="d-flex align-items-end gap-2 justify-content-end">
                                    <div class="text-end">
                                        <div class="message-bubble sent">${escapeHtml(msg.contenu)}</div>
                                        ${renderAttachment(msg)}
                                        <small class="text-muted me-2">${formatMessageDate(msg.date_envoi)}</small>
                                    </div>
                                    <img src="${avatarUrl}" class="rounded-circle" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                                </div>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="mb-3">
                                <div class="d-flex align-items-end gap-2">
                                    <img src="${avatarUrl}" class="rounded-circle" width="32" height="32" alt="Avatar" style="object-fit: cover;">
                                    <div>
                                        <div class="message-bubble received">${escapeHtml(msg.contenu)}</div>
                                        ${renderAttachment(msg)}
                                        <small class="text-muted ms-2">${formatMessageDate(msg.date_envoi)}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                }).join('');

                setTimeout(() => {
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                }, 100);
            }

            function updateChatHeader(conversationId) {
                const conv = conversations.find(c => c.id_conversation == conversationId);
                if (conv) {
                    const avatarUrl = conv.contact_photo ?
                        '../' + escapeHtml(conv.contact_photo) :
                        getAvatarUrl(conv.contact_nom);

                    document.getElementById('chat-header-name').textContent = 'Prof. ' + conv.contact_nom;
                    document.getElementById('chat-header-subject').textContent = conv.nom_matiere || 'Cours';
                    document.getElementById('chat-header-avatar').src = avatarUrl;
                }
            }

            async function markAsRead(conversationId) {
                const formData = new FormData();
                formData.append('action', 'mark_as_read');
                formData.append('conversation_id', conversationId);
                formData.append('csrf_token', csrfToken);

                try {
                    await fetch('../api/messaging.php', {
                        method: 'POST',
                        body: formData
                    });
                } catch (error) {
                    console.error('Erreur mark as read:', error);
                }
            }

            async function sendMessage() {
                const text = messageInput.value.trim();
                const file = attachmentInput?.files[0];
                if ((!text && !file) || !currentConversation) return;

                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('conversation_id', currentConversation);
                formData.append('contenu', text);
                if (file) {
                    formData.append('fichier_joint', file);
                }
                formData.append('csrf_token', csrfToken);

                try {
                    sendBtn.disabled = true;
                    const response = await fetch('../api/messaging.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        messageInput.value = '';
                        if (attachmentInput) {
                            attachmentInput.value = '';
                            attachmentName.textContent = '';
                        }
                        await loadMessages(currentConversation);
                    } else {
                        alert('Erreur lors de l\'envoi du message: ' + (data.error || 'Erreur inconnue'));
                    }
                } catch (error) {
                    console.error('Erreur envoi message:', error);
                    alert('Erreur lors de l\'envoi du message');
                } finally {
                    sendBtn.disabled = false;
                }
            }

            async function deleteConversation() {
                if (!currentConversation) {
                    alert('Sélectionnez une conversation.');
                    return;
                }

                if (!confirm('Supprimer définitivement cette conversation ?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete_conversation');
                formData.append('conversation_id', currentConversation);
                formData.append('csrf_token', csrfToken);

                try {
                    const response = await fetch('../api/messaging.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        currentConversation = null;
                        messagesContainer.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Sélectionnez une conversation pour voir les messages</p>
                            </div>
                        `;
                        loadConversations(true);
                    } else {
                        alert(data.error || 'Impossible de supprimer la conversation.');
                    }
                } catch (error) {
                    console.error('Erreur suppression conversation:', error);
                    alert('Erreur lors de la suppression de la conversation.');
                }
            }

            openReviewBtn?.addEventListener('click', function() {
                if (!currentConversation) {
                    alert('Sélectionnez une conversation à noter.');
                    return;
                }
                reviewForm?.reset();
                setReviewMessage('');
                reviewModal?.show();
            });

            reviewForm?.addEventListener('submit', async function(e) {
                e.preventDefault();
                if (!currentConversation || !reviewNote?.value) {
                    setReviewMessage('Choisissez une note avant d\'envoyer votre avis.', 'danger');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'submit_review');
                formData.append('conversation_id', currentConversation);
                formData.append('note', reviewNote.value);
                formData.append('commentaire', reviewComment?.value || '');
                formData.append('csrf_token', csrfToken);

                try {
                    reviewSubmitBtn.disabled = true;
                    const response = await fetch('../api/messaging.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        setReviewMessage('Merci pour votre avis !', 'success');
                        setTimeout(() => reviewModal?.hide(), 900);
                    } else {
                        setReviewMessage(data.error || 'Impossible d\'enregistrer votre avis.', 'danger');
                    }
                } catch (error) {
                    console.error('Erreur avis:', error);
                    setReviewMessage('Erreur réseau lors de l\'envoi.', 'danger');
                } finally {
                    reviewSubmitBtn.disabled = false;
                }
            });

            sendBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

            attachBtn?.addEventListener('click', function() {
                attachmentInput?.click();
            });

            attachmentInput?.addEventListener('change', function() {
                if (attachmentInput.files[0]) {
                    attachmentName.textContent = 'Pièce jointe : ' + attachmentInput.files[0].name;
                } else {
                    attachmentName.textContent = '';
                }
            });

            deleteConversationBtn?.addEventListener('click', deleteConversation);

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.conversation-item').forEach(item => {
                    const contact = item.dataset.contact.toLowerCase();
                    const subject = item.dataset.subject.toLowerCase();
                    if (contact.includes(searchTerm) || subject.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function getAvatarUrl(name) {
                return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6366f1&color=fff`;
            }

            function formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

                if (diffDays === 0) {
                    return date.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } else if (diffDays === 1) {
                    return 'Hier';
                } else if (diffDays < 7) {
                    return diffDays + ' jours';
                } else {
                    return date.toLocaleDateString('fr-FR', {
                        day: 'numeric',
                        month: 'short'
                    });
                }
            }

            function formatMessageDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const now = new Date();
                const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

                if (diffDays === 0) {
                    return "Aujourd'hui à " + date.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } else if (diffDays === 1) {
                    return 'Hier à ' + date.toLocaleTimeString('fr-FR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } else {
                    return date.toLocaleDateString('fr-FR', {
                            day: 'numeric',
                            month: 'short'
                        }) + ' à ' +
                        date.toLocaleTimeString('fr-FR', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                }
            }

            function renderAttachment(msg) {
                if (!msg.fichier_joint) {
                    return '';
                }
                const name = msg.document_nom || 'Télécharger';
                return `
                    <div class="mt-2">
                        <a href="../${escapeHtml(msg.fichier_joint)}" target="_blank">
                            <i class="fas fa-paperclip me-1"></i>${escapeHtml(name)}
                        </a>
                    </div>
                `;
            }

            function setReviewMessage(message, type = 'success') {
                if (!reviewAlert) return;
                if (!message) {
                    reviewAlert.classList.add('d-none');
                    return;
                }
                reviewAlert.className = 'alert alert-' + type;
                reviewAlert.textContent = message;
                reviewAlert.classList.remove('d-none');
            }
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>

</html>

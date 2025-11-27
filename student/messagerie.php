<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('student');
$pageTitle = 'Messagerie - Prof-IT';
$currentNav = 'student_messagerie';
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
                <!-- Sidebar: List of conversations -->
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

                <!-- Main chat area -->
                <div class="chat-main">
                    <!-- Chat header -->
                    <div class="p-3 border-bottom bg-white" id="chat-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=Marie+Dubois&background=3b82f6&color=fff"
                                class="rounded-circle" width="48" height="48" alt="Avatar" id="chat-header-avatar">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold" id="chat-header-name">Prof. Marie Dubois</h6>
                                <small class="text-muted" id="chat-header-subject">Mathématiques</small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Messages area -->
                    <div class="flex-grow-1 overflow-auto p-4" id="messages-area" style="background: #f9fafb;">
                        <div class="d-flex flex-column" id="messages-container">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Sélectionnez une conversation pour voir les messages</p>
                            </div>
                        </div>
                    </div>

                    <!-- Message input -->
                    <div class="p-3 border-top bg-white">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" title="Joindre un fichier">
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
                    </div>
                </div>
            </div>
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

            let conversations = [];
            let currentConversation = null;
            let currentMessages = [];

            loadConversations();

            async function loadConversations() {
                try {
                    const response = await fetch('../api/messaging.php?action=conversations');
                    const data = await response.json();

                    if (data.success && data.conversations) {
                        conversations = data.conversations;
                        renderConversations(conversations);

                        if (conversations.length > 0) {
                            loadMessages(conversations[0].id_conversation);
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
                    const activeClass = index === 0 ? 'active' : '';

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
                        currentConversation = conversationId;
                        currentMessages = data.messages;
                        renderMessages(data.messages);
                        updateChatHeader(conversationId);

                        markAsRead(conversationId);
                    } else {
                        messagesContainer.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <p>Erreur lors du chargement des messages</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('Erreur chargement messages:', error);
                }
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
                    const isSent = msg.id_utilisateur == <?= $userId ?? 0 ?>;
                    const avatarUrl = msg.auteur_photo ?
                        '../' + escapeHtml(msg.auteur_photo) :
                        getAvatarUrl(msg.auteur_nom);

                    if (isSent) {
                        return `
                            <div class="mb-3">
                                <div class="d-flex align-items-end gap-2 justify-content-end">
                                    <div class="text-end">
                                        <div class="message-bubble sent">${escapeHtml(msg.contenu)}</div>
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
                formData.append('csrf_token', '<?= csrf_token() ?>');

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
                if (!text || !currentConversation) return;

                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('conversation_id', currentConversation);
                formData.append('contenu', text);
                formData.append('csrf_token', '<?= csrf_token() ?>');

                try {
                    sendBtn.disabled = true;
                    const response = await fetch('../api/messaging.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        messageInput.value = '';
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

            sendBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

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
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>

</html>
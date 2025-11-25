<?php
require_once __DIR__ . '/../includes/helpers.php';
require_role('teacher');
$pageTitle = 'Messagerie - Prof-IT';
$currentNav = 'teacher_messagerie';
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
                        <!-- Conversation 1 - Unread -->
                        <div class="conversation-item unread active" data-conversation="1" data-student="Jean Dupont">
                            <div class="d-flex align-items-start gap-3">
                                <img src="https://ui-avatars.com/api/?name=Jean+Dupont&background=3b82f6&color=fff"
                                     class="rounded-circle" width="48" height="48" alt="Avatar">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fw-semibold">Jean Dupont</h6>
                                        <small class="text-muted">16:45</small>
                                    </div>
                                    <p class="mb-0 text-muted small text-truncate">Merci pour le cours, j'ai bien compris !</p>
                                    <span class="badge bg-primary rounded-pill mt-1">1</span>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation 2 -->
                        <div class="conversation-item" data-conversation="2" data-student="Marie Lambert">
                            <div class="d-flex align-items-start gap-3">
                                <img src="https://ui-avatars.com/api/?name=Marie+Lambert&background=10b981&color=fff"
                                     class="rounded-circle" width="48" height="48" alt="Avatar">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fw-semibold">Marie Lambert</h6>
                                        <small class="text-muted">Hier</small>
                                    </div>
                                    <p class="mb-0 text-muted small text-truncate">À quelle heure demain ?</p>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation 3 -->
                        <div class="conversation-item" data-conversation="3" data-student="Thomas Petit">
                            <div class="d-flex align-items-start gap-3">
                                <img src="https://ui-avatars.com/api/?name=Thomas+Petit&background=f59e0b&color=fff"
                                     class="rounded-circle" width="48" height="48" alt="Avatar">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fw-semibold">Thomas Petit</h6>
                                        <small class="text-muted">14 Jan</small>
                                    </div>
                                    <p class="mb-0 text-muted small text-truncate">Pouvez-vous m'envoyer les exercices ?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main chat area -->
                <div class="chat-main">
                    <!-- Chat header -->
                    <div class="p-3 border-bottom bg-white" id="chat-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=Jean+Dupont&background=3b82f6&color=fff"
                                 class="rounded-circle" width="48" height="48" alt="Avatar" id="chat-header-avatar">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold" id="chat-header-name">Jean Dupont</h6>
                                <small class="text-muted" id="chat-header-status">Étudiant</small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Messages area -->
                    <div class="flex-grow-1 overflow-auto p-4" id="messages-area" style="background: #f9fafb;">
                        <div class="d-flex flex-column" id="messages-container">
                            <!-- Conversation 1 messages (default) -->
                            <div class="messages-set" data-conversation="1">
                                <!-- Sent message -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2 justify-content-end">
                                        <div class="text-end">
                                            <div class="message-bubble sent">
                                                Bonjour Jean ! N'oubliez pas le cours de mathématiques demain à 10h.
                                            </div>
                                            <small class="text-muted me-2">Aujourd'hui à 15:30</small>
                                        </div>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['prenom']) ?>&background=6366f1&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                    </div>
                                </div>

                                <!-- Received message -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Jean+Dupont&background=3b82f6&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                D'accord professeur, je serai là !
                                            </div>
                                            <small class="text-muted ms-2">Aujourd'hui à 15:45</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sent message -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2 justify-content-end">
                                        <div class="text-end">
                                            <div class="message-bubble sent">
                                                Parfait ! Préparez vos exercices d'algèbre.
                                            </div>
                                            <small class="text-muted me-2">Aujourd'hui à 16:00</small>
                                        </div>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['prenom']) ?>&background=6366f1&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                    </div>
                                </div>

                                <!-- Received message -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Jean+Dupont&background=3b82f6&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                Merci pour le cours, j'ai bien compris !
                                            </div>
                                            <small class="text-muted ms-2">Aujourd'hui à 16:45</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversation 2 messages (hidden by default) -->
                            <div class="messages-set" data-conversation="2" style="display: none;">
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Marie+Lambert&background=10b981&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                Bonjour, notre prochain cours est bien demain ?
                                            </div>
                                            <small class="text-muted ms-2">Hier à 18:20</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2 justify-content-end">
                                        <div class="text-end">
                                            <div class="message-bubble sent">
                                                Oui Marie, à 14h comme prévu.
                                            </div>
                                            <small class="text-muted me-2">Hier à 18:45</small>
                                        </div>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['prenom']) ?>&background=6366f1&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Marie+Lambert&background=10b981&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                À quelle heure demain ?
                                            </div>
                                            <small class="text-muted ms-2">Hier à 19:00</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversation 3 messages (hidden by default) -->
                            <div class="messages-set" data-conversation="3" style="display: none;">
                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Thomas+Petit&background=f59e0b&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                Bonjour, j'aurais besoin des exercices de révision.
                                            </div>
                                            <small class="text-muted ms-2">14 Jan à 09:15</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2 justify-content-end">
                                        <div class="text-end">
                                            <div class="message-bubble sent">
                                                Bonjour Thomas, je vais les ajouter dans l'espace Documents.
                                            </div>
                                            <small class="text-muted me-2">14 Jan à 10:00</small>
                                        </div>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['prenom']) ?>&background=6366f1&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex align-items-end gap-2">
                                        <img src="https://ui-avatars.com/api/?name=Thomas+Petit&background=f59e0b&color=fff"
                                             class="rounded-circle" width="32" height="32" alt="Avatar">
                                        <div>
                                            <div class="message-bubble received">
                                                Pouvez-vous m'envoyer les exercices ?
                                            </div>
                                            <small class="text-muted ms-2">14 Jan à 10:30</small>
                                        </div>
                                    </div>
                                </div>
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
        // Simple chat interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const conversationItems = document.querySelectorAll('.conversation-item');
            const messageInput = document.getElementById('message-input');
            const sendBtn = document.getElementById('send-message');
            const messagesArea = document.getElementById('messages-area');
            const searchInput = document.getElementById('search-conversations');

            // Switch conversations
            conversationItems.forEach(item => {
                item.addEventListener('click', function() {
                    const conversationId = this.dataset.conversation;
                    const studentName = this.dataset.student;

                    // Update active state
                    document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    this.classList.remove('unread');

                    // Remove badge
                    const badge = this.querySelector('.badge');
                    if (badge) badge.remove();

                    // Update chat header
                    document.getElementById('chat-header-name').textContent = studentName;
                    document.getElementById('chat-header-status').textContent = 'Étudiant';
                    document.getElementById('chat-header-avatar').src =
                        `https://ui-avatars.com/api/?name=${encodeURIComponent(studentName)}&background=3b82f6&color=fff`;

                    // Show corresponding messages
                    document.querySelectorAll('.messages-set').forEach(set => {
                        set.style.display = set.dataset.conversation === conversationId ? 'block' : 'none';
                    });

                    // Scroll to bottom
                    messagesArea.scrollTop = messagesArea.scrollHeight;
                });
            });

            // Send message (static demo)
            function sendMessage() {
                const text = messageInput.value.trim();
                if (!text) return;

                const activeConversation = document.querySelector('.conversation-item.active').dataset.conversation;
                const messagesContainer = document.querySelector(`.messages-set[data-conversation="${activeConversation}"]`);

                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-3';
                messageDiv.innerHTML = `
                    <div class="d-flex align-items-end gap-2 justify-content-end">
                        <div class="text-end">
                            <div class="message-bubble sent">${escapeHtml(text)}</div>
                            <small class="text-muted me-2">À l'instant</small>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['prenom']) ?>&background=6366f1&color=fff"
                             class="rounded-circle" width="32" height="32" alt="Avatar">
                    </div>
                `;
                messagesContainer.appendChild(messageDiv);
                messageInput.value = '';
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }

            sendBtn.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Search conversations
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                conversationItems.forEach(item => {
                    const studentName = item.dataset.student.toLowerCase();
                    if (studentName.includes(searchTerm)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Auto-scroll to bottom on load
            messagesArea.scrollTop = messagesArea.scrollHeight;
        });
    </script>
    <?php require __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>

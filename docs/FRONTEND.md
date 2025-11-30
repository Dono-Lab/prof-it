# Documentation Frontend - Prof-IT

Documentation complète du frontend : pages HTML, JavaScript, CSS, et bibliothèques tierces.

---

## Table des Matières
- [Vue d'Ensemble](#vue-densemble)
- [Pages HTML](#pages-html)
- [JavaScript](#javascript)
  - [Scripts Globaux](#scripts-globaux)
  - [Scripts Admin](#scripts-admin)
  - [Scripts par Page](#scripts-par-page)
- [CSS et Styles](#css-et-styles)
- [Bibliothèques Tierces](#bibliothèques-tierces)
- [Composants Réutilisables](#composants-réutilisables)
- [Intégrations API](#intégrations-api)
- [Gestion des Formulaires](#gestion-des-formulaires)
- [Responsive Design](#responsive-design)

---

## Vue d'Ensemble

### Stack Frontend

| Technologie | Version | Usage |
|-------------|---------|-------|
| **Bootstrap** | 5.3.2 | Framework CSS responsive |
| **Font Awesome** | 6.4.0 | Icônes |
| **Chart.js** | 4.4.0 | Graphiques et statistiques |
| **Leaflet.js** | 1.9.4 | Cartes interactives (géolocalisation professeurs) |
| **Vanilla JavaScript** | ES6+ | Logique frontend (pas de framework JS) |

### Architecture Frontend

```
assets/
├── css/
│   ├── auth.css                # Styles page authentification
│   ├── student.css             # Styles espace étudiant
│   ├── teacher.css             # Styles espace professeur
│   └── admin.css               # Styles espace admin
├── js/
│   ├── auto_logout.js          # Auto-déconnexion (timeout)
│   └── index.js                # Scripts page d'accueil publique
admin/
└── assets/
    └── js/
        ├── admin.js            # Scripts dashboard admin
        ├── users.js            # Gestion utilisateurs
        ├── logs.js             # Consultation logs
        ├── captcha.js          # Gestion CAPTCHA
        └── live_users.js       # Utilisateurs connectés en temps réel
```

---

## Pages HTML

### Pages Publiques

#### `/public/home.php`

**Description** : Page d'accueil publique avec présentation de la plateforme

**Sections** :
- Hero section avec CTA "S'inscrire" / "Se connecter"
- Présentation des fonctionnalités (pour étudiants, pour professeurs)
- Témoignages utilisateurs
- Statistiques plateforme (nombre de profs, étudiants, cours donnés)
- Footer avec liens utiles

**Technologies** :
- Bootstrap Grid pour la mise en page
- Font Awesome pour les icônes
- Animations CSS (transitions, hover effects)

**JavaScript** :
- [assets/js/index.js](../assets/js/index.js) : Animations, scroll smooth, statistiques animées

---

### Pages d'Authentification

#### `/auth/auth.php`

**Description** : Page combinée connexion + inscription avec CAPTCHA

**Composants** :
- **Formulaire de connexion** :
  - Email (type email, required)
  - Mot de passe (type password, required)
  - Token CSRF (hidden)
  - Bouton "Se connecter"
- **Formulaire d'inscription** :
  - Nom, prénom (text, required)
  - Email (email, required)
  - Mot de passe (password, minlength=6, required)
  - Téléphone, adresse, ville, code postal (optionnels)
  - Rôle (select: Apprendre/Enseigner)
  - CAPTCHA modal (question aléatoire)
  - Token CSRF (hidden)

**Fichiers** :
- HTML/PHP : [auth/auth.php](../auth/auth.php)
- Traitement : [auth/login_register.php](../auth/login_register.php)
- CSS : [assets/css/auth.css](../assets/css/auth.css)

**Fonctionnalités JavaScript** :
```javascript
// Basculer entre login/register
function showForm(formID) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formID).classList.add("active");
}

// Ouvrir modal CAPTCHA
function openCaptchaModal() {
    // Valider formulaire avant
    const modal = new bootstrap.Modal(document.getElementById('captchaModal'));
    modal.show();
}

// Rafraîchir CAPTCHA
function refreshCaptcha() {
    fetch('../src/get_new_captcha.php')
        .then(response => response.json())
        .then(data => {
            document.querySelector('.captcha-question').textContent = data.question;
            document.getElementById('captchaId').value = data.id;
        });
}

// Soumettre avec CAPTCHA
function submitWithCaptcha() {
    const form = document.getElementById('registerForm');
    // Ajouter hidden inputs pour captcha_answer, captcha_id
    form.submit();
}
```

**Flux UX** :
1. Utilisateur arrive sur `/auth/auth.php`
2. Formulaire de connexion affiché par défaut (classe `active`)
3. Clic "S'inscrire" → Bascule vers formulaire inscription via `showForm('register-form')`
4. Remplissage formulaire → Clic "S'inscrire" → `openCaptchaModal()`
5. Répondre CAPTCHA → `submitWithCaptcha()` → POST vers `login_register.php`
6. Redirection selon rôle après succès

---

### Pages Étudiant

#### `/student/student_page.php`

**Description** : Dashboard principal de l'étudiant

**Sections** :
- **Header** : Avatar, nom, bouton paramètres
- **Statistiques** (4 cartes) :
  - Cours terminés
  - Heures de cours
  - Matière préférée
  - Dépenses totales
- **Prochains cours** : Liste des réservations confirmées à venir
- **Accès rapides** :
  - Trouver un professeur
  - Mes réservations
  - Messagerie
  - Support

**Fichiers** :
- HTML/PHP : [student/student_page.php](../student/student_page.php)
- CSS : [assets/css/student.css](../assets/css/student.css)
- JavaScript : Auto-logout + fetch stats

**Fonctions PHP utilisées** :
- `get_student_stats($user_id, $conn)` : Récupère statistiques
- `get_student_upcoming_courses($user_id, $conn, 5)` : Prochains cours
- `get_user_avatar($user_id, $conn)` : Avatar

**Exemple de rendu stats** :
```php
<?php $stats = get_student_stats($_SESSION['user_id'], $conn); ?>
<div class="stats-grid">
    <div class="stat-card">
        <i class="fas fa-graduation-cap"></i>
        <h3><?= $stats['cours_termines'] ?></h3>
        <p>Cours terminés</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3><?= $stats['heures_total'] ?>h</h3>
        <p>Heures de cours</p>
    </div>
    <!-- ... -->
</div>
```

---

#### `/student/find_prof.php`

**Description** : Recherche et réservation de professeurs

**Fonctionnalités** :
- **Carte interactive** (Leaflet.js) : Affiche professeurs géolocalisés
- **Filtres** :
  - Matière (dropdown)
  - Niveau (college, lycee, superieur)
  - Mode (visio, presentiel)
  - Prix (slider)
- **Liste des professeurs** : Cartes avec infos (nom, matières, note, tarif)
- **Créneaux disponibles** : Pour chaque professeur, affiche les créneaux à venir

**Fichiers** :
- HTML/PHP : [student/find_prof.php](../student/find_prof.php)
- CSS : [assets/css/student.css](../assets/css/student.css)

**Intégration Leaflet.js** :
```javascript
// Initialisation de la carte
var map = L.map('map').setView([48.8566, 2.3522], 12); // Paris par défaut

// Tuiles OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Ajouter marqueurs pour chaque professeur
<?php foreach ($professeurs as $prof): ?>
    <?php if ($prof['latitude'] && $prof['longitude']): ?>
        L.marker([<?= $prof['latitude'] ?>, <?= $prof['longitude'] ?>])
            .addTo(map)
            .bindPopup(`
                <b><?= htmlspecialchars($prof['nom']) ?></b><br>
                <?= htmlspecialchars($prof['matieres']) ?><br>
                Note: <?= $prof['note_moyenne'] ?>/5<br>
                <a href="#prof-<?= $prof['id'] ?>">Voir créneaux</a>
            `);
    <?php endif; ?>
<?php endforeach; ?>
```

**Réservation de créneau** :
```javascript
function bookSlot(creneauId) {
    const formData = new FormData();
    formData.append('action', 'book_slot');
    formData.append('creneau_id', creneauId);
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

    fetch('/prof-it/api/appointments.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Réservation effectuée ! En attente de confirmation du professeur.');
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    });
}
```

---

### Pages Professeur

#### `/teacher/teacher_page.php`

**Description** : Dashboard professeur

**Sections** :
- **Statistiques** :
  - Nombre d'étudiants
  - Réservations totales
  - Note moyenne (avec nombre d'avis)
  - Heures données
  - Revenus totaux
- **Prochaines sessions** : Réservations confirmées à venir
- **Réservations en attente** : Liste des demandes à valider
- **Créneaux disponibles** : Liste des créneaux non réservés
- **Accès rapides** :
  - Créer une offre de cours
  - Ajouter des créneaux
  - Messagerie
  - Paramètres

**Fonctions PHP** :
- `get_teacher_stats($user_id, $conn)`
- `get_teacher_upcoming_sessions($user_id, $conn, 10)`
- `get_teacher_available_slots($user_id, $conn, 5)`

**Gestion des réservations** :
```javascript
function confirmBooking(reservationId) {
    if (!confirm('Confirmer cette réservation ?')) return;

    const formData = new FormData();
    formData.append('action', 'confirm_booking');
    formData.append('reservation_id', reservationId);
    formData.append('csrf_token', csrfToken);

    fetch('/prof-it/api/appointments.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Réservation confirmée. L\'étudiant a été notifié.');
            location.reload();
        }
    });
}

function rejectBooking(reservationId) {
    if (!confirm('Refuser cette réservation ? Le créneau redeviendra disponible.')) return;

    // Similaire à confirmBooking, action=reject_booking
}
```

---

### Pages Admin

#### `/admin/dashboard.php`

**Description** : Dashboard principal admin avec statistiques globales

**Sections** :
- **KPIs** (cartes) :
  - Utilisateurs totaux (étudiants, professeurs, admins)
  - Réservations actives
  - Revenus du mois
  - Tickets support ouverts
- **Graphiques** (Chart.js) :
  - Inscriptions par mois (line chart)
  - Réservations par matière (bar chart)
  - Répartition des rôles (pie chart)
- **Activité récente** :
  - Dernières inscriptions
  - Dernières réservations
  - Derniers tickets support

**Fichiers** :
- HTML/PHP : [admin/dashboard.php](../admin/dashboard.php)
- JavaScript : [admin/assets/js/admin.js](../admin/assets/js/admin.js)
- CSS : [admin/assets/css/admin.css](../admin/assets/css/admin.css)

**Exemple Chart.js** :
```javascript
// Graphique inscriptions par mois
const ctx = document.getElementById('chartInscriptions').getContext('2d');
const chartInscriptions = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [{
            label: 'Inscriptions',
            data: [12, 19, 8, 15, 22, 18],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
```

---

#### `/admin/users.php`

**Description** : Gestion CRUD des utilisateurs

**Fonctionnalités** :
- **Liste des utilisateurs** : Tableau avec colonnes (ID, Nom, Email, Rôle, Statut, Actions)
- **Recherche** : Filtre par nom/email
- **Filtres** : Par rôle, par statut (actif/inactif)
- **Actions** :
  - Modifier (ouvre modal)
  - Activer/Désactiver
  - Supprimer (avec confirmation)
- **Modal d'édition** : Formulaire pour modifier nom, email, rôle, statut

**Fichiers** :
- HTML/PHP : [admin/users.php](../admin/users.php)
- JavaScript : [admin/assets/js/users.js](../admin/assets/js/users.js)

**JavaScript users.js** :
```javascript
// Charger les utilisateurs via API
function loadUsers(page = 1, search = '', role = '', status = '') {
    fetch(`/prof-it/admin/api/users.php?action=list&page=${page}&search=${search}&role=${role}&status=${status}`)
        .then(res => res.json())
        .then(data => {
            displayUsers(data.users);
            updatePagination(data.pagination);
        });
}

// Afficher les utilisateurs dans le tableau
function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';

    users.forEach(user => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${htmlEscape(user.nom)} ${htmlEscape(user.prenom)}</td>
            <td>${htmlEscape(user.email)}</td>
            <td><span class="badge bg-${getRoleBadge(user.role)}">${user.role}</span></td>
            <td><span class="badge bg-${user.actif ? 'success' : 'secondary'}">${user.actif ? 'Actif' : 'Inactif'}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">Modifier</button>
                <button class="btn btn-sm btn-${user.actif ? 'warning' : 'success'}" onclick="toggleActive(${user.id})">
                    ${user.actif ? 'Désactiver' : 'Activer'}
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Supprimer</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Modifier un utilisateur
function editUser(userId) {
    fetch(`/prof-it/admin/api/users.php?action=get&user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remplir le modal avec les données
                document.getElementById('editUserId').value = data.user.id;
                document.getElementById('editNom').value = data.user.nom;
                document.getElementById('editEmail').value = data.user.email;
                document.getElementById('editRole').value = data.user.role;

                // Ouvrir modal Bootstrap
                const modal = new bootstrap.Modal(document.getElementById('editModal'));
                modal.show();
            }
        });
}

// Sauvegarder les modifications
function saveUser() {
    const formData = new FormData(document.getElementById('editForm'));
    formData.append('action', 'update');
    formData.append('csrf_token', csrfToken);

    fetch('/prof-it/admin/api/users.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Utilisateur modifié avec succès');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadUsers(); // Recharger la liste
        } else {
            alert('Erreur : ' + data.message);
        }
    });
}

// Helpers
function htmlEscape(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function getRoleBadge(role) {
    const badges = { 'admin': 'danger', 'teacher': 'warning', 'student': 'info' };
    return badges[role] || 'secondary';
}
```

---

#### `/admin/logs.php`

**Description** : Consultation des logs (connexions, visites, modifications)

**Fonctionnalités** :
- **Onglets** :
  - Logs de connexions
  - Logs de visites
  - Logs de modifications
- **Filtres** :
  - Plage de dates (date picker)
  - Statut (success/failed pour connexions)
  - Utilisateur (autocomplete)
  - IP address
- **Tableau** : Colonnes dynamiques selon le type de log
- **Export** : Bouton pour exporter en CSV

**Fichiers** :
- HTML/PHP : [admin/logs.php](../admin/logs.php)
- JavaScript : [admin/assets/js/logs.js](../admin/assets/js/logs.js)

**Détection brute force** :
```javascript
// Mettre en évidence les IPs suspectes (nombreuses tentatives échouées)
function highlightSuspiciousIPs(logs) {
    const ipFailures = {};

    logs.forEach(log => {
        if (log.statut === 'failed') {
            ipFailures[log.ip_address] = (ipFailures[log.ip_address] || 0) + 1;
        }
    });

    // Marquer les IPs avec > 5 échecs
    Object.keys(ipFailures).forEach(ip => {
        if (ipFailures[ip] > 5) {
            document.querySelectorAll(`tr[data-ip="${ip}"]`).forEach(row => {
                row.classList.add('table-danger');
                row.title = `${ipFailures[ip]} tentatives échouées`;
            });
        }
    });
}
```

---

## JavaScript

### Scripts Globaux

#### `assets/js/auto_logout.js`

**Description** : Déconnexion automatique après inactivité

**Fonctionnement** :
```javascript
(function() {
    let timeout = 1800 * 1000; // 30 minutes
    let logoutUrl = '/prof-it/auth/logout.php?timeout=1';
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(logout, timeout);
    }

    function logout() {
        window.location.href = logoutUrl;
    }

    function init(serverTimeout) {
        if (serverTimeout) {
            timeout = serverTimeout * 1000;
        }

        // Écouter les événements d'activité
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeypress = resetTimer;
        document.onclick = resetTimer;
        document.onscroll = resetTimer;
        document.ontouchstart = resetTimer; // Support mobile

        resetTimer();
        console.log('Auto-logout initialized with timeout:', timeout / 1000, 'seconds');
    }

    window.initAutoLogout = init;
})();
```

**Utilisation dans les pages** :
```html
<script src="/prof-it/assets/js/auto_logout.js"></script>
<script>
    // Synchroniser avec le timeout serveur
    initAutoLogout(<?= SESSION_LIFETIME ?>);
</script>
```

---

### Scripts Admin

#### `admin/assets/js/live_users.js`

**Description** : Affiche les utilisateurs connectés en temps réel

**Fonctionnalités** :
- Polling toutes les 10 secondes
- Affiche nom, rôle, durée de session, dernière activité
- Badge coloré selon rôle

**Code** :
```javascript
let refreshInterval;

function loadLiveUsers() {
    fetch('/prof-it/admin/api/live_users.php?action=get_active_sessions')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayLiveUsers(data.sessions);
                document.getElementById('liveUserCount').textContent = data.sessions.length;
            }
        })
        .catch(err => console.error('Erreur chargement sessions actives:', err));
}

function displayLiveUsers(sessions) {
    const container = document.getElementById('liveUsersContainer');
    container.innerHTML = '';

    if (sessions.length === 0) {
        container.innerHTML = '<p class="text-muted">Aucun utilisateur connecté</p>';
        return;
    }

    sessions.forEach(session => {
        const card = document.createElement('div');
        card.className = 'live-user-card';
        card.innerHTML = `
            <div class="user-info">
                <img src="${session.avatar_url}" alt="Avatar" class="avatar-sm">
                <div>
                    <strong>${htmlEscape(session.nom)} ${htmlEscape(session.prenom)}</strong>
                    <br>
                    <span class="badge bg-${getRoleBadge(session.role)}">${session.role}</span>
                </div>
            </div>
            <div class="session-info">
                <small>Connecté depuis ${formatDuration(session.session_duration)}</small>
                <br>
                <small class="text-muted">Dernière activité: ${formatRelativeTime(session.last_activity)}</small>
            </div>
        `;
        container.appendChild(card);
    });
}

function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}min`;
}

function formatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffSeconds = Math.floor((now - date) / 1000);

    if (diffSeconds < 60) return 'À l\'instant';
    if (diffSeconds < 3600) return `Il y a ${Math.floor(diffSeconds / 60)} min`;
    return `Il y a ${Math.floor(diffSeconds / 3600)}h`;
}

// Auto-refresh toutes les 10 secondes
function startAutoRefresh() {
    loadLiveUsers(); // Charger immédiatement
    refreshInterval = setInterval(loadLiveUsers, 10000);
}

function stopAutoRefresh() {
    clearInterval(refreshInterval);
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    startAutoRefresh();
});

// Arrêter le polling quand l'utilisateur quitte la page
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
});
```

---

## CSS et Styles

### Structure des Fichiers CSS

#### `assets/css/auth.css`

**Description** : Styles pour la page d'authentification

**Principales classes** :
```css
/* Conteneur principal */
.container {
    max-width: 450px;
    margin: 50px auto;
    padding: 20px;
}

/* Boîtes de formulaire */
.form-box {
    background: #fff;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: none; /* Par défaut caché */
}

.form-box.active {
    display: block; /* Affiché quand actif */
}

/* Champs de formulaire */
input[type="text"],
input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s;
}

input:focus {
    border-color: #6366f1; /* Indigo */
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Grille de formulaire (2 colonnes) */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* Boutons */
button[type="submit"] {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

/* Messages d'erreur */
.error-message {
    background-color: #fee;
    color: #c33;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    border-left: 4px solid #c33;
}

/* Messages de succès */
.success-message {
    background-color: #efe;
    color: #3c3;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    border-left: 4px solid #3c3;
}

/* Modal CAPTCHA */
.captcha-modal .modal-header {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.captcha-question {
    font-size: 18px;
    font-weight: 600;
    text-align: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 15px;
}

.captcha-input {
    font-size: 16px;
    text-align: center;
}

.btn-captcha-validate {
    background: #6366f1;
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 5px;
}
```

---

#### `assets/css/student.css`

**Principales classes** :
```css
/* Dashboard grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Carte de statistique */
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.stat-card i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.9;
}

.stat-card h3 {
    font-size: 32px;
    font-weight: 700;
    margin: 10px 0;
}

.stat-card p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

/* Carte de cours */
.course-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #6366f1;
    transition: box-shadow 0.3s;
}

.course-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.course-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.course-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Carte interactive (Leaflet) */
#map {
    height: 400px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
```

---

## Bibliothèques Tierces

### Bootstrap 5.3.2

**CDN** :
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
```

**Composants utilisés** :
- **Grid System** : Mise en page responsive
- **Cards** : Cartes pour stats, cours, utilisateurs
- **Modals** : Fenêtres modales (édition utilisateur, CAPTCHA)
- **Buttons** : Boutons stylisés
- **Badges** : Labels colorés (rôles, statuts, priorités)
- **Forms** : Inputs, selects, validation
- **Navbar** : Menu de navigation
- **Dropdown** : Menus déroulants
- **Alerts** : Messages d'alerte

---

### Font Awesome 6.4.0

**CDN** :
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Icônes utilisées** :
```html
<!-- Navigation -->
<i class="fas fa-home"></i>          <!-- Accueil -->
<i class="fas fa-user"></i>          <!-- Profil -->
<i class="fas fa-cog"></i>           <!-- Paramètres -->
<i class="fas fa-sign-out-alt"></i>  <!-- Déconnexion -->

<!-- Statistiques -->
<i class="fas fa-graduation-cap"></i> <!-- Cours -->
<i class="fas fa-clock"></i>          <!-- Temps -->
<i class="fas fa-euro-sign"></i>      <!-- Prix -->
<i class="fas fa-star"></i>           <!-- Note -->

<!-- Actions -->
<i class="fas fa-edit"></i>           <!-- Modifier -->
<i class="fas fa-trash"></i>          <!-- Supprimer -->
<i class="fas fa-check"></i>          <!-- Valider -->
<i class="fas fa-times"></i>          <!-- Annuler -->
<i class="fas fa-search"></i>         <!-- Rechercher -->
<i class="fas fa-filter"></i>         <!-- Filtrer -->
```

---

### Chart.js 4.4.0

**CDN** :
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

**Exemples de graphiques** :

**Line Chart (Inscriptions)** :
```javascript
const ctxInscriptions = document.getElementById('chartInscriptions').getContext('2d');
new Chart(ctxInscriptions, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [{
            label: 'Inscriptions',
            data: [12, 19, 8, 15, 22, 18],
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});
```

**Pie Chart (Répartition des rôles)** :
```javascript
const ctxRoles = document.getElementById('chartRoles').getContext('2d');
new Chart(ctxRoles, {
    type: 'pie',
    data: {
        labels: ['Étudiants', 'Professeurs', 'Admins'],
        datasets: [{
            data: [120, 45, 3],
            backgroundColor: [
                'rgba(99, 102, 241, 0.8)',
                'rgba(251, 146, 60, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
```

---

### Leaflet.js 1.9.4

**CDN** :
```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

**Initialisation** :
```javascript
// Créer la carte centrée sur Paris
const map = L.map('map').setView([48.8566, 2.3522], 12);

// Ajouter les tuiles OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);

// Ajouter des marqueurs pour les professeurs
const marker = L.marker([48.8566, 2.3522]).addTo(map);
marker.bindPopup('<b>Professeur Jean</b><br>Mathématiques').openPopup();

// Marqueurs personnalisés avec icônes
const teacherIcon = L.icon({
    iconUrl: '/prof-it/assets/images/teacher-marker.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

L.marker([48.85, 2.35], {icon: teacherIcon}).addTo(map);
```

**Géolocalisation utilisateur** :
```javascript
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(position => {
        const userLat = position.coords.latitude;
        const userLon = position.coords.longitude;

        // Centrer la carte sur la position de l'utilisateur
        map.setView([userLat, userLon], 13);

        // Ajouter un marqueur pour l'utilisateur
        L.marker([userLat, userLon]).addTo(map)
            .bindPopup('Vous êtes ici')
            .openPopup();
    });
}
```

---

## Composants Réutilisables

### Modal de Confirmation

**HTML** :
```html
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage">Êtes-vous sûr de vouloir effectuer cette action ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirmer</button>
            </div>
        </div>
    </div>
</div>
```

**JavaScript** :
```javascript
function confirmAction(message, callback) {
    document.getElementById('confirmMessage').textContent = message;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();

    document.getElementById('confirmButton').onclick = () => {
        callback();
        modal.hide();
    };
}

// Utilisation
confirmAction('Voulez-vous vraiment supprimer cet utilisateur ?', () => {
    deleteUser(userId);
});
```

---

### Toast Notification

**HTML** :
```html
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="toastNotification" class="toast" role="alert">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            <!-- Message dynamique -->
        </div>
    </div>
</div>
```

**JavaScript** :
```javascript
function showToast(message, type = 'info') {
    const toastEl = document.getElementById('toastNotification');
    const toastBody = document.getElementById('toastMessage');

    toastBody.textContent = message;
    toastEl.className = `toast bg-${type} text-white`;

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

// Utilisation
showToast('Utilisateur créé avec succès', 'success');
showToast('Une erreur est survenue', 'danger');
```

---

## Intégrations API

### Pattern Fetch Standard

```javascript
function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (method === 'POST' && data) {
        // Si FormData, ne pas définir Content-Type (auto)
        if (data instanceof FormData) {
            delete options.headers['Content-Type'];
            options.body = data;
        } else {
            options.body = JSON.stringify(data);
        }
    }

    return fetch(endpoint, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Erreur API:', error);
            throw error;
        });
}

// Utilisation
apiRequest('/prof-it/api/appointments.php?action=list', 'GET')
    .then(data => {
        console.log('Réservations:', data.appointments);
    })
    .catch(err => {
        alert('Erreur de chargement');
    });
```

---

## Gestion des Formulaires

### Validation Côté Client

```javascript
// Validation native HTML5
function validateForm(formElement) {
    if (!formElement.checkValidity()) {
        formElement.reportValidity(); // Affiche les messages natifs
        return false;
    }
    return true;
}

// Validation personnalisée
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validatePassword(password) {
    // Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return regex.test(password);
}

// Affichage d'erreurs personnalisées
function showFieldError(inputElement, message) {
    inputElement.classList.add('is-invalid');

    let errorDiv = inputElement.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        inputElement.parentNode.insertBefore(errorDiv, inputElement.nextSibling);
    }
    errorDiv.textContent = message;
}

function clearFieldError(inputElement) {
    inputElement.classList.remove('is-invalid');
    const errorDiv = inputElement.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.remove();
    }
}
```

---

## Responsive Design

### Breakpoints Bootstrap

| Breakpoint | Classe | Largeur |
|------------|--------|---------|
| Extra small | (défaut) | < 576px |
| Small | `sm` | ≥ 576px |
| Medium | `md` | ≥ 768px |
| Large | `lg` | ≥ 992px |
| Extra large | `xl` | ≥ 1200px |
| Extra extra large | `xxl` | ≥ 1400px |

### Exemple de Grid Responsive

```html
<div class="row">
    <!-- 1 colonne sur mobile, 2 sur tablette, 3 sur desktop -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">...</div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">...</div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="stat-card">...</div>
    </div>
</div>
```

### Media Queries Personnalisées

```css
/* Mobile-first approach */
.dashboard {
    padding: 10px;
}

/* Tablette */
@media (min-width: 768px) {
    .dashboard {
        padding: 20px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop */
@media (min-width: 992px) {
    .dashboard {
        padding: 30px;
    }

    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

---

## Performance et Optimisation

### Lazy Loading Images

```html
<img src="placeholder.jpg" data-src="real-image.jpg" loading="lazy" alt="Description">

<script>
// Polyfill pour navigateurs anciens
if ('loading' in HTMLImageElement.prototype) {
    const images = document.querySelectorAll('img[loading="lazy"]');
    images.forEach(img => {
        img.src = img.dataset.src;
    });
} else {
    // Utiliser Intersection Observer
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                observer.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}
</script>
```

### Debounce pour Recherche

```javascript
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Utilisation pour search input
const searchInput = document.getElementById('searchInput');
const debouncedSearch = debounce((query) => {
    fetch(`/api/search.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => displayResults(data));
}, 300); // 300ms de délai

searchInput.addEventListener('input', (e) => {
    debouncedSearch(e.target.value);
});
```

---

**Dernière mise à jour** : Janvier 2025

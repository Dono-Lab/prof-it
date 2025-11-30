# Index de la Documentation Prof-IT

Bienvenue dans la documentation complète du projet **Prof-IT** - une plateforme de gestion pédagogique connectant étudiants, professeurs et administrateurs.

---

## 📖 Comment Utiliser Cette Documentation

Cette documentation est organisée en plusieurs fichiers thématiques pour faciliter la navigation et la maintenance. Chaque fichier couvre un aspect spécifique du projet avec un niveau de détail exhaustif.

### Pour les Nouveaux Utilisateurs
1. Commencez par le **[README.md](../README.md)** pour une vue d'ensemble
2. Consultez **[CONFIGURATION.md](CONFIGURATION.md)** pour l'installation
3. Explorez **[FLOWS.md](FLOWS.md)** pour comprendre les scénarios d'utilisation

### Pour les Développeurs
1. Lisez **[ARCHITECTURE.md](ARCHITECTURE.md)** pour comprendre la structure
2. Consultez **[DATABASE.md](DATABASE.md)** pour le schéma de données
3. Référez-vous à **[API_REFERENCE.md](API_REFERENCE.md)** et **[FUNCTIONS.md](FUNCTIONS.md)** pendant le développement
4. Suivez **[SECURITY.md](SECURITY.md)** pour les bonnes pratiques

### Pour les Administrateurs Système
1. **[CONFIGURATION.md](CONFIGURATION.md)** pour le déploiement
2. **[SECURITY.md](SECURITY.md)** pour la sécurisation
3. **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** pour le dépannage
4. **[TESTING.md](TESTING.md)** pour la validation

---

## 📚 Documentation Générale

### [README.md](../README.md)
**Présentation et démarrage rapide**
- Vue d'ensemble du projet
- Fonctionnalités principales (Étudiant, Professeur, Admin)
- Stack technique (PHP, MySQL, JavaScript)
- Installation rapide
- Comptes de test et premiers pas
- FAQ et problèmes connus

### [ARCHITECTURE.md](ARCHITECTURE.md)
**Architecture technique du projet**
- Vue d'ensemble de l'architecture (LAMP/WAMP)
- Structure en couches (Présentation, Logique, Données)
- Base de données (tables principales, relations)
- Flux de données (exemple : réservation de cours)
- Arborescence complète des fichiers
- Diagrammes d'architecture textuels
- Relations détaillées entre toutes les tables
- Index et contraintes de la BDD

### [MODULES.md](MODULES.md)
**Description détaillée de tous les modules**
- Module Authentification (login, register, logout)
- Module Administrateur (dashboard, users, logs, CAPTCHA)
- Module Professeur (créneaux, messagerie, documents)
- Module Étudiant (réservation, messagerie, documents)
- Bibliothèques partagées (helpers, CSRF, mailer)
- Liste exhaustive de tous les fichiers avec leur rôle
- Paramètres GET/POST de chaque page
- Flux de navigation complet

### [ROLES.md](ROLES.md)
**Répartition des rôles de l'équipe**
- UI/UX Designer & Développeuse Front-end
- DevOps & Développeuse Back-end
- Développeur Fullstack
- Technologies et responsabilités de chacun

---

## 🔧 Documentation Technique

### [TECHNICAL.md](TECHNICAL.md)
**Aspects techniques généraux**
- Sécurité (authentification, CSRF, XSS, SQL injection)
- API interne (format JSON, endpoints)
- Gestion des fichiers (uploads, sécurité)
- Bibliothèques externes (TCPDF, PHPMailer, FontAwesome)
- Algorithmes clés (statistiques, créneaux)
- Configuration détaillée (constantes, variables d'environnement)
- Gestion des sessions (lifetime, régénération, tracking)
- Système de logs complet (connexions, visites, erreurs)
- Performance et optimisations

### [DATABASE.md](DATABASE.md)
**Schéma complet de la base de données**
- Vue d'ensemble de la BDD `projet_profit`
- Description détaillée de **toutes les tables** (25+ tables) :
  - `users`, `roles`, `affecter`
  - `matiere`, `offre_cours`, `enseigner`, `couvrir`
  - `creneau`, `reservation`
  - `conversation`, `message`, `document`
  - `ticket_support`, `message_ticket`
  - `avis`, `facture`, `paiement`
  - `newsletter`, `captcha`
  - `logs_connexions`, `logs_visites`, `sessions_actives`
- Relations entre tables (1:1, 1:N, N:M)
- Diagramme ERD en format texte
- Contraintes d'intégrité référentielle (CASCADE)
- Vues SQL (`vue_stats_professeurs`, `vue_reservations_details`)
- Énumérations complètes (ENUM, SET)
- Index et leur utilité
- Données de référence et initialisation

### [API_REFERENCE.md](API_REFERENCE.md)
**Référence complète de toutes les APIs**
- **API Appointments** (`/api/appointments.php`)
  - GET : available_slots, upcoming_appointments, history, stats, teachers, search_courses
  - POST : book_slot, create_slot, update_status
- **API Messaging** (`/api/messaging.php`)
  - GET : conversations, messages
  - POST : send_message, mark_as_read, delete_conversation, submit_review
- **API Support** (`/api/support.php`)
  - GET : tickets, stats, ticket_details
  - POST : create_ticket, reply_ticket
- **API Admin** (9 endpoints dans `/admin/api/`)
  - manage_user.php : CRUD utilisateurs
  - get_stats.php : Statistiques dashboard, charts
  - tickets.php : Gestion tickets admin
  - manage_captcha.php : CRUD CAPTCHA
  - get_logs.php : Logs connexions/visites
  - get_live_user.php : Utilisateurs connectés
  - get_captcha.php : Récupération CAPTCHA
  - get_users.php : Liste utilisateurs
  - bootstrap.php : Initialisation admin

Pour chaque endpoint :
- URL complète et méthode HTTP
- Authentification et rôles requis
- Paramètres (nom, type, requis/optionnel, validation)
- Format de réponse JSON avec exemples
- Codes d'erreur possibles
- Exemples de requêtes cURL

### [FUNCTIONS.md](FUNCTIONS.md)
**Référence de toutes les fonctions PHP**
- **includes/helpers.php** (134 lignes)
  - `safe_session_start()` : Gestion sécurisée des sessions
  - `csrf_field()`, `csrf_protect()` : Protection CSRF
  - `logout_button()` : Bouton de déconnexion
  - `is_admin()`, `is_teacher()`, `is_student()` : Vérification rôles
  - `is_logged_in()` : Vérification connexion
  - `require_admin()`, `require_admin_api()`, `require_role()` : Contrôle d'accès
  - `compute_course_status()` : Calcul statut cours (à venir, en cours, terminé)
  - `course_status_label()` : Label d'affichage du statut
- **includes/csrf.php** (19 lignes)
  - `csrf_token()` : Génération token CSRF (32 bytes)
  - `verify_csrf()` : Vérification token avec hash_equals
- **src/get_captcha.php**
  - Fonctions de récupération et vérification CAPTCHA
- Toutes les autres fonctions métier

Pour chaque fonction :
- Signature complète avec types
- Description du rôle et comportement
- Paramètres (nom, type, description, valeur par défaut)
- Valeur de retour (type, description)
- Exceptions/erreurs possibles
- Dépendances (autres fonctions, constantes)
- Exemples d'utilisation concrets

---

## 📊 Flux de Données & Scénarios

### [FLOWS.md](FLOWS.md)
**Flux de données détaillés et scénarios complets**
- **Flux Authentification**
  - Inscription complète (formulaire → validation → hash → BDD → session)
  - Connexion (vérification → password_verify → régénération session → redirection)
  - Déconnexion (destruction session → logs)
  - Timeout automatique (inactivité 30 min)

- **Flux Réservation Complète** (étudiant → professeur)
  1. Recherche de cours (API search_courses)
  2. Consultation créneaux disponibles (API available_slots)
  3. Réservation (validation → INSERT reservation → INSERT conversation → UPDATE creneau)
  4. Notification professeur
  5. Validation/Refus professeur (UPDATE statut)
  6. Cours (en_cours → terminé)
  7. Avis étudiant (INSERT avis)

- **Flux Messagerie**
  1. Création conversation (automatique lors réservation)
  2. Liste conversations (GET conversations avec derniers messages)
  3. Visualisation messages (GET messages avec auteurs)
  4. Envoi message (validation → upload fichier → INSERT message → UPDATE conversation)
  5. Marquage comme lu (UPDATE lu=1)

- **Flux Support**
  1. Création ticket (validation → INSERT ticket → INSERT message_ticket)
  2. Réponse utilisateur (INSERT message_ticket)
  3. Réponse admin (INSERT message_ticket avec is_admin=1)
  4. Clôture ticket (UPDATE statut)

- **Flux Admin**
  - CRUD utilisateurs (validation → password_hash → INSERT/UPDATE/DELETE)
  - Monitoring temps réel (sessions_actives < 5 min)
  - Consultation logs (logs_connexions, logs_visites)
  - Gestion CAPTCHA (CRUD questions)

Pour chaque flux :
- Diagramme de séquence en format texte (ASCII)
- État initial du système
- Actions utilisateur détaillées
- Traitements backend étape par étape
- Requêtes SQL exécutées (avec tables impactées)
- Validations effectuées (côté client et serveur)
- Réponses et redirections
- État final du système

---

## 💻 Frontend & Interface

### [FRONTEND.md](FRONTEND.md)
**Documentation complète du frontend**
- **Structure des pages**
  - Pages publiques (home.php, auth.php)
  - Pages étudiants (student_page.php, rdv.php, messagerie.php, documents.php, settings.php)
  - Pages professeurs (teacher_page.php, rdv.php, messagerie.php, documents.php, settings.php)
  - Pages admin (dashboard.php, users.php, captcha.php, live_users.php, logs.php)

- **Composants JavaScript**
  - `/assets/js/index.js` : Carte Leaflet, recherche professeurs, géolocalisation
  - `/assets/js/auto_logout.js` : Timeout automatique 30 min
  - `/admin/assets/js/admin.js` : Fonctions globales (search, formatDate, showSuccess, logout)
  - `/admin/assets/js/users.js` : CRUD utilisateurs avec pagination
  - `/admin/assets/js/captcha.js` : Gestion CAPTCHA avec filtres
  - `/admin/assets/js/live_users.js` : Monitoring temps réel (refresh 10s)
  - `/admin/assets/js/logs.js` : Visualisation logs (connexions, visites)

- **Bibliothèques utilisées**
  - Bootstrap 5.3.2 (composants, grille responsive)
  - Font Awesome 6.4.0 (icônes vectorielles)
  - Chart.js 4.4.0 (graphiques dashboard)
  - Leaflet.js (cartes interactives OpenStreetMap)
  - ui-avatars.com API (génération avatars)

- **Appels API depuis le frontend**
  - Liste complète de tous les `fetch()` avec URL, méthode, données
  - Gestion des réponses JSON et erreurs
  - Mise à jour dynamique du DOM

- **Formulaires**
  - Formulaires d'authentification (login, register avec CAPTCHA)
  - Formulaires admin (CRUD users, CAPTCHA)
  - Validation côté client (HTML5, JS)
  - Validation côté serveur (PHP avec échappement)
  - Gestion CSRF (tokens dans tous les POST)

- **Navigation**
  - Arborescence complète de navigation par rôle
  - Règles d'accès (require_admin, require_role)
  - Menu responsive (sidebar admin, header global)

---

## 🔐 Sécurité & Configuration

### [SECURITY.md](SECURITY.md)
**Guide de sécurité approfondi**
- **Authentification**
  - Mécanisme de hash : `password_hash()` avec PASSWORD_DEFAULT (Bcrypt/Argon2)
  - Vérification : `password_verify()`
  - Politique de mots de passe (min 6 caractères)
  - Comptes de test et sécurité

- **Sessions**
  - Configuration : SESSION_LIFETIME = 1800 secondes (30 min)
  - Régénération d'ID : `session_regenerate_id(true)` après auth
  - Tracking d'activité : `sessions_actives` (dernière_activite)
  - Auto-logout : JavaScript (auto_logout.js) + PHP (safe_session_start)

- **Protection CSRF (Cross-Site Request Forgery)**
  - Génération de tokens : `bin2hex(random_bytes(32))` (64 caractères hex)
  - Validation avec `hash_equals()` (prévient timing attacks)
  - Implémentation dans tous les formulaires POST
  - Fonction helper : `csrf_field()`, `csrf_protect()`

- **Protection XSS (Cross-Site Scripting)**
  - Échappement des sorties : `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`
  - Où échapper : affichage de données utilisateur dans HTML
  - Content-Security-Policy (recommandé pour production)

- **Protection SQL Injection**
  - PDO avec prepared statements (toutes les requêtes)
  - Placeholders : `?` ou `:nom`
  - Jamais de concaténation directe de variables utilisateur
  - Exemples de requêtes sécurisées vs non sécurisées

- **Upload de fichiers**
  - Validation des extensions : whitelist stricte (pdf, doc, xls, png, jpg, txt)
  - Vérification MIME type
  - Taille maximale : 10 MB (10*1024*1024 bytes)
  - Nommage sécurisé : `uniqid('msg_', true)` + sanitize nom original
  - Permissions des dossiers : 0755 pour uploads/
  - Stockage : uploads/messages/{conversation_id}/

- **Validation des entrées**
  - Email : `filter_var($email, FILTER_VALIDATE_EMAIL)`
  - Rôles : `in_array($role, ['student', 'teacher', 'admin'], true)`
  - Dates : format ISO (Y-m-d H:i:s), vérification DateTime
  - Montants : floats positifs, calculs TVA sécurisés
  - Enums : vérification stricte avec whitelist

- **Logs de sécurité**
  - logs_connexions : enregistrement tentatives (success/failed)
  - Détection de tentatives suspectes (raison_echec)
  - logs_visites : tracking pages visitées
  - sessions_actives : monitoring utilisateurs en ligne

- **Configuration sensible**
  - ⚠️ Problème : config/config.php contient credentials en dur
  - Recommandations :
    - Utiliser variables d'environnement (.env avec vlucas/phpdotenv)
    - Ou gestionnaire de secrets (Vault, AWS Secrets Manager)
    - Ne jamais commiter config.php avec credentials réels
    - Protéger config/ avec .htaccess (Deny from all)

- **Checklist sécurité production**
  - [ ] Credentials en variables d'environnement
  - [ ] HTTPS activé (Let's Encrypt)
  - [ ] Sessions sécurisées (cookie_secure, cookie_httponly, cookie_samesite)
  - [ ] error_reporting(0) et display_errors=Off
  - [ ] Logs de sécurité activés et monitorés
  - [ ] Backups BDD automatisés
  - [ ] Firewall configuré (port 443 seulement)
  - [ ] Fail2ban pour bloquer attaques bruteforce

### [CONFIGURATION.md](CONFIGURATION.md)
**Installation et configuration détaillée**
- **Prérequis système**
  - PHP 8.0+ avec extensions (PDO, pdo_mysql, mbstring, openssl, fileinfo, gd)
  - MySQL 5.7+ ou MariaDB 10.3+
  - Apache 2.4+ avec mod_rewrite activé
  - Composer (optionnel, pour dépendances futures)

- **Installation pas à pas**
  - Étape 1 : Clone du repository
  - Étape 2 : Configuration serveur web (DocumentRoot, VirtualHost)
  - Étape 3 : Création base de données `projet_profit`
  - Étape 4 : Import du fichier `db.sql` (400+ lignes)
  - Étape 5 : Configuration `config/config.php`
  - Étape 6 : Création dossier `uploads/` avec permissions 0755
  - Étape 7 : Configuration SMTP (support@prof-it.fr)
  - Étape 8 : Tests de connexion

- **Configuration des constantes** (config/config.php)
  - `SESSION_LIFETIME` : 1800 secondes (30 min)
  - `SMTP_HOST` : ssl0.ovh.net
  - `SMTP_PORT` : 465
  - `SMTP_USER` : support@prof-it.fr
  - `SMTP_PASS` : Support2025!
  - `SMTP_FROM_EMAIL` : support@prof-it.fr
  - `SMTP_FROM_NAME` : Prof-IT Notification

- **Comptes de test** (créés automatiquement par db.sql)
  - Admin : admin@prof-it.fr / Admin2024!
  - Teacher : prof@prof-it.fr / Prof2024!
  - Student : student@prof-it.fr / Student2024!

- **Variables d'environnement recommandées** (.env)
  ```
  DB_HOST=localhost
  DB_NAME=projet_profit
  DB_USER=root
  DB_PASS=
  SMTP_HOST=ssl0.ovh.net
  SMTP_PORT=465
  SMTP_USER=support@prof-it.fr
  SMTP_PASS=Support2025!
  SESSION_LIFETIME=1800
  ```

- **Permissions fichiers/dossiers**
  - uploads/ : 0755 (rwxr-xr-x)
  - config/ : 0644 (rw-r--r--)
  - .htaccess pour protéger config/ : Deny from all

- **Configuration Apache**
  - mod_rewrite activé
  - AllowOverride All dans VirtualHost
  - .htaccess pour URLs propres (si nécessaire)

- **Configuration PHP (php.ini)**
  - upload_max_filesize = 10M
  - post_max_size = 12M
  - session.cookie_lifetime = 0 (expire à la fermeture navigateur)
  - session.cookie_httponly = 1
  - session.cookie_secure = 1 (en production HTTPS)
  - session.cookie_samesite = Strict

---

## 🧪 Tests & Maintenance

### [TESTING.md](TESTING.md)
**Guide de tests et validation**
- **Tests manuels recommandés**
  - Scénario 1 : Inscription complète
    1. Remplir formulaire register avec CAPTCHA
    2. Vérifier hash password en BDD
    3. Connexion avec nouveau compte
    4. Vérifier redirection selon rôle
  - Scénario 2 : Réservation complète (étudiant → professeur)
    1. Étudiant recherche cours
    2. Réserve créneau (vérifier INSERT reservation, conversation)
    3. Professeur valide (vérifier UPDATE statut)
    4. Messagerie fonctionne
    5. Avis post-cours (vérifier INSERT avis)
  - Scénario 3 : Messagerie avec fichier joint
    1. Envoi message avec PDF (vérifier upload)
    2. Vérifier fichier dans uploads/messages/{id}/
    3. Vérifier INSERT document
    4. Téléchargement fichier
  - Scénario 4 : Admin gère utilisateurs
    1. CRUD complet (create, read, update, delete)
    2. Vérifier validations (email unique, rôle whitelist)
    3. Logs enregistrés correctement

- **Validation de sécurité**
  - Test CSRF : Soumettre formulaire sans token → Erreur attendue
  - Test SQL injection : Entrées malveillantes (`' OR '1'='1`) → Échec attendu
  - Test XSS : Script dans champ texte (`<script>alert('XSS')</script>`) → Échappé
  - Test upload malveillant : .php, .exe → Rejeté
  - Test bruteforce : Multiples tentatives login → Logs enregistrés

- **Tests de performance**
  - Temps de réponse des API (< 200ms attendu)
  - Nombre de requêtes SQL par page (< 10 idéal)
  - Optimisation index (EXPLAIN sur requêtes lentes)
  - Cache (si implémenté)

- **Tests de compatibilité**
  - Navigateurs : Chrome, Firefox, Safari, Edge
  - Responsive : Mobile (320px), Tablet (768px), Desktop (1920px)
  - PHP : 8.0, 8.1, 8.2
  - MySQL : 5.7, 8.0
  - MariaDB : 10.3, 10.5

- **Checklist avant production**
  - [ ] Tous les scénarios manuels passent
  - [ ] Pas de warnings/notices PHP
  - [ ] Credentials en variables d'environnement
  - [ ] HTTPS activé et forcé
  - [ ] Logs de sécurité activés
  - [ ] Sessions sécurisées (secure, httponly)
  - [ ] Backups BDD configurés (quotidiens)
  - [ ] Monitoring activé (Sentry, New Relic, ou custom)
  - [ ] Tests de charge (Apache Bench, JMeter)
  - [ ] Documentation à jour

### [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
**Résolution de problèmes courants**
- **Erreurs fréquentes**
  - "Connexion échouée: SQLSTATE[HY000] [1045]"
    - Cause : Mauvais credentials BDD
    - Solution : Vérifier config/config.php (host, user, pass, database)
  - "Token CSRF invalide"
    - Cause : Session non démarrée ou token expiré
    - Solution : Vérifier session_start() en haut du fichier
  - "Fichier non uploadé"
    - Cause : Permissions dossier uploads/ insuffisantes
    - Solution : `chmod -R 0755 uploads/` ou vérifier upload_max_filesize
  - "Timeout session"
    - Cause : SESSION_LIFETIME trop court
    - Solution : Augmenter dans config/config.php (ex: 3600 pour 1h)
  - "Undefined index: user_id in session"
    - Cause : Utilisateur non connecté ou session perdue
    - Solution : Vérifier is_logged_in(), rediriger vers auth.php

- **Déboggage**
  - Activer erreurs PHP :
    ```php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ```
  - Consulter logs Apache : `/var/log/apache2/error.log`
  - Consulter logs PHP : `php_errors.log`
  - Vérifier logs_connexions pour erreurs auth
  - Utiliser `var_dump()` ou `print_r()` pour débogguer variables
  - Xdebug pour debugging avancé

- **Problèmes de base de données**
  - "Table doesn't exist"
    - Solution : Ré-importer db.sql, vérifier nom BDD
  - "Too many connections"
    - Solution : Vérifier fermeture connexions PDO, augmenter max_connections
  - Requêtes lentes
    - Solution : Ajouter index, optimiser requêtes avec EXPLAIN

- **Problèmes connus**
  - Auto-logout trop agressif
    - Solution : Augmenter SESSION_LIFETIME ou ajuster auto_logout.js
  - Emails en spam
    - Solution : Configurer SPF, DKIM, DMARC records
  - Permissions uploads/ sur Windows
    - Solution : Exécuter XAMPP en administrateur
  - Carte Leaflet ne charge pas
    - Solution : Vérifier connexion internet (tiles OpenStreetMap)

- **Support et Assistance**
  - Consulter cette documentation complète
  - Vérifier GitHub Issues
  - Contact : support@prof-it.fr

---

## 🔍 Navigation Rapide

### Par Rôle Utilisateur

**Je suis Étudiant** :
- [Comment m'inscrire ?](CONFIGURATION.md#comptes-de-test)
- [Comment réserver un cours ?](FLOWS.md#flux-réservation-complète)
- [Comment envoyer un message ?](FLOWS.md#flux-messagerie)
- [Comment laisser un avis ?](API_REFERENCE.md#api-messaging)

**Je suis Professeur** :
- [Comment créer des créneaux ?](API_REFERENCE.md#api-appointments)
- [Comment valider une réservation ?](FLOWS.md#flux-réservation-complète)
- [Comment partager des documents ?](FRONTEND.md#upload-de-fichiers)

**Je suis Administrateur** :
- [Comment gérer les utilisateurs ?](FLOWS.md#flux-admin)
- [Comment consulter les logs ?](API_REFERENCE.md#api-admin)
- [Comment gérer le CAPTCHA ?](API_REFERENCE.md#api-admin)

**Je suis Développeur** :
- [Quelle est l'architecture ?](ARCHITECTURE.md)
- [Comment sont structurées les données ?](DATABASE.md)
- [Quels sont les endpoints API ?](API_REFERENCE.md)
- [Quelles fonctions utiliser ?](FUNCTIONS.md)
- [Comment sécuriser mon code ?](SECURITY.md)

---

## 📝 Conventions de Documentation

- **Markdown** : Tous les fichiers utilisent la syntaxe GitHub-Flavored Markdown
- **Liens relatifs** : Navigation entre fichiers avec chemins relatifs
- **Code blocks** : Syntaxe highlighting avec \`\`\`php, \`\`\`sql, \`\`\`bash
- **Tableaux** : Pour lister paramètres, endpoints, etc.
- **Diagrammes** : En format texte (ASCII art) pour compatibilité universelle
- **Exemples** : Code concret et exemples de requêtes/réponses

---

## 🔄 Mise à Jour de la Documentation

Cette documentation est maintenue par l'équipe de développement. Dernière mise à jour : **Janvier 2025**

Pour contribuer à la documentation :
1. Modifiez le fichier Markdown concerné
2. Respectez la structure et le format existants
3. Ajoutez des exemples concrets
4. Mettez à jour cet INDEX.md si nécessaire
5. Créez une Pull Request avec description des changements

---

**Bonne lecture !** 📖

Si vous ne trouvez pas l'information recherchée, consultez [TROUBLESHOOTING.md](TROUBLESHOOTING.md) ou contactez support@prof-it.fr.

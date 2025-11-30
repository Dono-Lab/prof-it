# Prof-IT 🎓

**Projet Annuel - ESGI 1AJ2 24/25**

Prof-IT est une plateforme web de gestion pédagogique facilitant les interactions entre étudiants, professeurs et administrateurs. Elle permet la gestion des rendez-vous, le partage de documents, la messagerie instantanée et le suivi administratif.

---

## 🚀 Fonctionnalités Principales

### 👨‍🎓 Espace Étudiant
- **Tableau de bord** : Vue d'ensemble des prochains cours et statistiques.
- **Rendez-vous** : Prise de rendez-vous avec les professeurs (créneaux disponibles).
- **Documents** : Accès aux supports de cours et téléchargement de fichiers.
- **Messagerie** : Communication directe avec les professeurs et l'administration.
- **Profil** : Gestion des informations personnelles et préférences (Mode Sombre).

### 👨‍🏫 Espace Professeur
- **Gestion des disponibilités** : Définition des créneaux horaires pour les rendez-vous.
- **Suivi des rendez-vous** : Validation ou annulation des demandes étudiants.
- **Partage de documents** : Mise en ligne de supports pédagogiques (PDF, Word, Excel).
- **Messagerie** : Échanges avec les étudiants.

### 🛠 Espace Administrateur
- **Dashboard Analytique** : Statistiques en temps réel (utilisateurs connectés, activité).
- **Gestion des Utilisateurs** : CRUD (Créer, Lire, Mettre à jour, Supprimer) pour Étudiants et Professeurs.
- **Sécurité & Logs** : Suivi de l'activité des utilisateurs et gestion des sessions.
- **Maintenance** : Accès aux configurations globales.

### 🌟 Fonctionnalités Transverses
- **Sécurité** : Protection CSRF, échappement XSS, hachage des mots de passe (Argon2/Bcrypt).
- **Déconnexion Automatique** : Sécurité accrue avec déconnexion après inactivité.
- **Export PDF** : Génération de documents administratifs (via TCPDF).
- **Notifications Email** : Envoi d'emails transactionnels (via PHPMailer).

---

## 💻 Stack Technique

- **Langage Backend** : PHP 8.x (Native/Vanilla)
- **Base de Données** : MySQL / MariaDB
- **Frontend** :
  - HTML5 / CSS3 (Custom + Bootstrap 5.3)
  - JavaScript (Vanilla ES6+)
- **Bibliothèques** :
  - [TCPDF](https://tcpdf.org/) (Génération PDF)
  - [PHPMailer](https://github.com/PHPMailer/PHPMailer) (Envoi d'emails)
  - [FontAwesome](https://fontawesome.com/) (Icônes)
- **Serveur Web** : Apache (via XAMPP/WAMP)

---

## ⚙️ Installation

### Prérequis
- Un environnement serveur local (XAMPP, WAMP, MAMP) avec PHP 8+ et MySQL.
- Composer (optionnel, si gestion des dépendances avancée).

### Étapes
1.  **Cloner le projet** dans le dossier racine de votre serveur web (ex: `htdocs` ou `www`).
    ```bash
    git clone https://github.com/Dono-Lab/prof-it.git
    ```

2.  **Base de Données** :
    - Ouvrez phpMyAdmin (ou votre client SQL).
    - Créez une base de données nommée `prof_it`.
    - Importez le fichier `db.sql` situé à la racine du projet.

3.  **Configuration** :
    - Renommez ou vérifiez le fichier `config/config.php`.
    - Assurez-vous que les identifiants de base de données correspondent à votre installation locale :
      ```php
      define('DB_HOST', 'localhost');
      define('DB_NAME', 'prof_it');
      define('DB_USER', 'root');
      define('DB_PASS', '');
      ```

4.  **Lancement** :
    - Accédez à `http://localhost/prof-it/` dans votre navigateur.

---

## 📂 Structure du Projet

```
prof-it/
├── admin/                      # Panneau d'administration (Back-office)
│   ├── api/                    # Endpoints API internes pour l'admin
│   │   ├── bootstrap.php       # Vérification admin + inclusion helpers
│   │   ├── get_captcha.php     # Récupération questions CAPTCHA
│   │   ├── get_live_user.php   # Utilisateurs connectés en temps réel
│   │   ├── get_logs.php        # Logs connexions et visites
│   │   ├── get_stats.php       # Statistiques dashboard (KPIs, charts)
│   │   ├── get_users.php       # Liste des utilisateurs
│   │   ├── manage_captcha.php  # CRUD questions CAPTCHA
│   │   ├── manage_user.php     # CRUD utilisateurs (create/update/delete)
│   │   └── tickets.php         # Gestion tickets support (admin side)
│   ├── assets/                 # CSS/JS/Images spécifiques à l'admin
│   │   ├── css/                # Styles admin (admin.css, sidebar.css)
│   │   └── js/                 # Scripts admin (admin.js, users.js, captcha.js, logs.js, live_users.js)
│   ├── includes/               # Fragments de code admin (header, sidebar, navigation)
│   ├── captcha.php             # Page gestion CAPTCHA
│   ├── dashboard.php           # Tableau de bord principal admin
│   ├── live_users.php          # Monitoring utilisateurs en ligne
│   ├── logs.php                # Visualisation logs système
│   └── users.php               # Gestion des utilisateurs (interface)
├── api/                        # API Backend principale (Students & Teachers)
│   ├── appointments.php        # Gestion rendez-vous et créneaux (booking, slots, stats)
│   ├── messaging.php           # Messagerie (conversations, messages, fichiers)
│   └── support.php             # Support tickets (create, reply, details)
├── assets/                     # Ressources publiques (CSS, JS, Images)
│   ├── css/                    # Feuilles de style globales
│   ├── img/                    # Images (logos, backgrounds, icônes)
│   └── js/                     # Scripts frontend (index.js, auto_logout.js)
├── auth/                       # Scripts d'authentification
│   ├── auth.php                # Page formulaire Login/Register
│   ├── login_register.php      # Traitement Login/Register
│   └── logout.php              # Déconnexion
├── config/                     # Fichiers de configuration
│   └── config.php              # Configuration BDD, SMTP, constantes (SESSION_LIFETIME)
├── docs/                       # Documentation du projet
│   ├── ARCHITECTURE.md         # Architecture technique
│   ├── MODULES.md              # Description des modules
│   ├── ROLES.md                # Répartition des rôles équipe
│   └── TECHNICAL.md            # Aspects techniques (sécurité, API)
├── includes/                   # Bibliothèques et helpers PHP
│   ├── csrf.php                # Protection CSRF (génération/vérification tokens)
│   ├── helpers.php             # Fonctions utilitaires (session, auth, roles, status)
│   ├── phpmailer/              # Bibliothèque PHPMailer (envoi emails)
│   └── tcpdf/                  # Bibliothèque TCPDF (génération PDF)
├── public/                     # Pages publiques
│   └── home.php                # Page d'accueil publique
├── src/                        # Sources additionnelles
│   ├── get_captcha.php         # Récupération/vérification CAPTCHA (frontend)
│   ├── newsletter_subscribe.php # Inscription newsletter
│   └── track_activity.php      # Tracking sessions actives
├── student/                    # Espace Étudiant
│   ├── messagerie.php          # Interface messagerie étudiant
│   ├── rdv.php                 # Réservation de cours
│   └── student_page.php        # Dashboard étudiant
├── teacher/                    # Espace Professeur
│   ├── messagerie.php          # Interface messagerie professeur
│   ├── rdv.php                 # Gestion créneaux/disponibilités
│   └── teacher_page.php        # Dashboard professeur
├── templates/                  # Templates HTML partagés
│   ├── footer.php              # Footer global
│   └── header.php              # Header global
├── uploads/                    # Dossier de stockage des fichiers utilisateurs
│   └── messages/               # Fichiers joints des conversations
├── check_session.php           # Vérification session utilisateur (AJAX)
├── db.sql                      # Script d'initialisation de la BDD (400+ lignes)
├── index.php                   # Point d'entrée (Redirection selon rôle)
└── README.md                   # Ce fichier
```

---

## 🚦 Démarrage Rapide

### Comptes de Test

Une fois l'installation terminée et la base de données initialisée, vous pouvez utiliser les comptes de test suivants :

| Rôle | Email | Mot de passe | Accès |
|------|-------|--------------|-------|
| **Administrateur** | admin@prof-it.fr | Admin2024! | [/admin/dashboard.php](http://localhost/prof-it/admin/dashboard.php) |
| **Professeur** | prof@prof-it.fr | Prof2024! | [/teacher/teacher_page.php](http://localhost/prof-it/teacher/teacher_page.php) |
| **Étudiant** | student@prof-it.fr | Student2024! | [/student/student_page.php](http://localhost/prof-it/student/student_page.php) |

> **Note** : Ces comptes sont créés automatiquement lors de l'importation du fichier `db.sql`. Changez les mots de passe en production.

### Premier Lancement

1. Démarrez XAMPP/WAMP et activez Apache + MySQL
2. Accédez à [http://localhost/prof-it/](http://localhost/prof-it/)
3. Connectez-vous avec un compte de test
4. Explorez les fonctionnalités selon votre rôle

---

## 💡 Exemples de Scénarios d'Utilisation

### Scénario 1 : Réservation de Cours (Étudiant)

1. **Connexion** : Se connecter avec un compte étudiant
2. **Navigation** : Aller sur [Rendez-vous](student/rdv.php)
3. **Recherche** : Rechercher un professeur ou une matière (ex: "Mathématiques")
4. **Sélection** : Choisir un créneau disponible
5. **Réservation** : Confirmer la réservation (statut: en_attente)
6. **Messagerie** : Conversation automatiquement créée avec le professeur
7. **Validation** : Attendre la validation du professeur
8. **Cours** : Assister au cours à la date/heure prévue
9. **Avis** : Laisser un avis après le cours terminé

### Scénario 2 : Gestion de Créneaux (Professeur)

1. **Connexion** : Se connecter avec un compte professeur
2. **Disponibilités** : Aller sur [Rendez-vous](teacher/rdv.php)
3. **Création** : Créer un nouveau créneau (date, heure, tarif, mode: présentiel/visio)
4. **Offre** : Associer le créneau à une offre de cours existante
5. **Réservations** : Consulter les demandes de réservation (statut: en_attente)
6. **Validation** : Accepter ou refuser les réservations
7. **Communication** : Échanger avec l'étudiant via la messagerie
8. **Session** : Confirmer le cours comme "en_cours" puis "terminé"
9. **Avis** : Consulter les avis laissés par les étudiants

### Scénario 3 : Administration (Admin)

1. **Connexion** : Se connecter avec un compte admin
2. **Dashboard** : Visualiser les statistiques (utilisateurs, réservations, revenus)
3. **Utilisateurs** : [Gérer les utilisateurs](admin/users.php) (CRUD)
   - Créer un nouvel utilisateur (prof ou étudiant)
   - Modifier les informations d'un utilisateur
   - Activer/désactiver un compte
   - Supprimer un utilisateur
4. **Monitoring** : [Utilisateurs en ligne](admin/live_users.php) (temps réel)
5. **Sécurité** : [Consulter les logs](admin/logs.php) (connexions, visites)
6. **CAPTCHA** : [Gérer les questions](admin/captcha.php) de sécurité
7. **Support** : Répondre aux tickets de support

---

## 🛡 Sécurité

Le projet met en œuvre plusieurs bonnes pratiques de sécurité :

### Authentification
- **Hash des mots de passe** : `password_hash()` avec algorithme Bcrypt/Argon2
- **Vérification sécurisée** : `password_verify()`
- **Déconnexion automatique** : Après 30 minutes d'inactivité (configurable via `SESSION_LIFETIME`)

### Protection des Données
- **CSRF Protection** : Tokens uniques vérifiés sur tous les formulaires POST
- **Sessions Sécurisées** : Régénération d'ID de session après authentification
- **Prepared Statements** : Toutes les requêtes SQL utilisent PDO avec placeholders
- **XSS Filtering** : Échappement systématique des sorties HTML (`htmlspecialchars`)

### Upload de Fichiers
- **Whitelist d'extensions** : Seulement PDF, DOC, XLS, PNG, JPG, TXT autorisés
- **Limite de taille** : 10 MB maximum par fichier
- **Nommage sécurisé** : `uniqid()` pour éviter les conflits

### Logs et Monitoring
- **Logs de connexion** : Enregistrement de toutes les tentatives (succès/échec)
- **Logs de visites** : Tracking des pages visitées par session
- **Sessions actives** : Monitoring en temps réel des utilisateurs connectés

> ⚠️ **Avertissement** : Le fichier `config/config.php` contient des credentials en dur. En production, utilisez des variables d'environnement (.env) ou un gestionnaire de secrets.

---

## 📚 Documentation Complète

Pour une documentation exhaustive du projet, consultez le dossier [docs/](docs/) :

- **[INDEX.md](docs/INDEX.md)** - Index de navigation de la documentation
- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** - Architecture technique détaillée
- **[DATABASE.md](docs/DATABASE.md)** - Schéma complet de la base de données
- **[API_REFERENCE.md](docs/API_REFERENCE.md)** - Référence complète des APIs
- **[FUNCTIONS.md](docs/FUNCTIONS.md)** - Documentation de toutes les fonctions PHP
- **[FLOWS.md](docs/FLOWS.md)** - Flux de données et scénarios détaillés
- **[FRONTEND.md](docs/FRONTEND.md)** - Documentation frontend (HTML/CSS/JS)
- **[SECURITY.md](docs/SECURITY.md)** - Guide de sécurité approfondi
- **[CONFIGURATION.md](docs/CONFIGURATION.md)** - Installation et configuration avancée
- **[TESTING.md](docs/TESTING.md)** - Guide de tests et validation
- **[TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)** - Résolution de problèmes courants

---

## ❓ FAQ

### Installation & Configuration

**Q : L'import de db.sql échoue avec une erreur de syntaxe**
R : Assurez-vous d'utiliser MySQL 5.7+ ou MariaDB 10.3+. Vérifiez que le charset est bien UTF-8.

**Q : Erreur "Connexion échouée" au lancement**
R : Vérifiez les credentials dans `config/config.php`. Par défaut : localhost, root, pas de mot de passe.

**Q : Les emails ne sont pas envoyés**
R : Vérifiez la configuration SMTP dans `config/config.php`. Le serveur de dev peut nécessiter un serveur SMTP local comme MailHog.

### Fonctionnalités

**Q : Comment créer un nouveau professeur ?**
R : Connectez-vous en tant qu'admin, allez dans [Utilisateurs](admin/users.php), cliquez sur "Ajouter un utilisateur", choisissez le rôle "teacher".

**Q : Un étudiant peut-il réserver sans validation du professeur ?**
R : Non. Toute réservation est d'abord en statut "en_attente". Le professeur doit la valider (statut "confirmée") ou la refuser.

**Q : Les fichiers uploadés sont stockés où ?**
R : Dans le dossier `uploads/messages/{conversation_id}/`. Assurez-vous que ce dossier a les permissions 0755.

**Q : Comment modifier le timeout de session (30 min par défaut) ?**
R : Modifiez la constante `SESSION_LIFETIME` dans `config/config.php` (valeur en secondes).

### Sécurité

**Q : Les mots de passe sont-ils sécurisés ?**
R : Oui, hashés avec `password_hash()` (Bcrypt/Argon2). Jamais stockés en clair.

**Q : Le site est-il protégé contre les injections SQL ?**
R : Oui, toutes les requêtes utilisent PDO avec prepared statements.

**Q : Comment activer HTTPS en production ?**
R : Configurez votre serveur Apache/Nginx avec un certificat SSL (Let's Encrypt recommandé). Forcez HTTPS dans .htaccess.

---

## 🐛 Problèmes Connus

- **Auto-logout agressif** : Si vous êtes déconnecté trop rapidement, augmentez `SESSION_LIFETIME` dans `config/config.php`
- **Emails en spam** : Les emails envoyés depuis localhost peuvent finir en spam. Utilisez un serveur SMTP configuré (Gmail, SendGrid, etc.)
- **Permissions uploads/** : Sur certains systèmes, vous devrez manuellement créer le dossier `uploads/` et lui donner les permissions 0755

Pour plus de détails, consultez [TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

---

## 🔄 Mises à Jour et Versions

**Version actuelle** : 1.0.0 (Janvier 2025)

### Fonctionnalités à venir
- [ ] Système de paiement en ligne (Stripe/PayPal)
- [ ] Notifications push en temps réel (WebSockets)
- [ ] Application mobile (React Native)
- [ ] Visioconférence intégrée (Jitsi/WebRTC)
- [ ] Tableau blanc collaboratif
- [ ] Export des statistiques en PDF/Excel

---

## 👥 Auteurs

Projet réalisé par **Dono**, **Faria**, **Diana** dans le cadre du cursus **ESGI 1AJ2 24/25**.

---

## 📄 Licence

Ce projet est un projet pédagogique et n'a pas de licence publique. Tous droits réservés aux auteurs.

---

## 🤝 Contribution

Pour contribuer au projet :
1. Forkez le repository
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Pushez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

---

## 📧 Contact

Pour toute question ou assistance :
- **Email de support** : support@prof-it.fr
- **GitHub Issues** : [Signaler un problème](https://github.com/votre-repo/prof-it/issues)

---

**Prof-IT** - Plateforme de gestion pédagogique 🎓
*Facilitez l'apprentissage, connectez les talents*

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
    git clone https://github.com/votre-repo/prof-it.git
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
├── admin/              # Panneau d'administration (Back-office)
│   ├── api/            # Endpoints API internes pour l'admin
│   ├── assets/         # CSS/JS spécifiques à l'admin
│   └── includes/       # Fragments de code admin (header, sidebar)
├── assets/             # Ressources publiques (CSS, JS, Images)
├── auth/               # Scripts d'authentification (Login, Logout, Signup)
├── config/             # Fichiers de configuration (DB, SMTP)
├── includes/           # Bibliothèques et helpers PHP (TCPDF, PHPMailer)
├── student/            # Espace Étudiant
├── teacher/            # Espace Professeur
├── templates/          # Templates HTML partagés (Header, Footer)
├── uploads/            # Dossier de stockage des fichiers utilisateurs
├── db.sql              # Script d'initialisation de la BDD
└── index.php           # Point d'entrée (Redirection)
```

---

## 🛡 Sécurité

Le projet met en œuvre plusieurs bonnes pratiques de sécurité :
- **Protection CSRF** : Tokens vérifiés sur tous les formulaires POST.
- **Sessions Sécurisées** : Gestion stricte des cookies de session.
- **Prepared Statements** : Prévention des injections SQL via PDO.
- **XSS Filtering** : Échappement des sorties HTML (`htmlspecialchars`).

---

## 👥 Auteurs

Projet réalisé par **Dono**, **Faria**, **Diana** dans le cadre du cursus ESGI.

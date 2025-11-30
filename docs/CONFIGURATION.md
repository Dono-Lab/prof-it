# Guide de Configuration - Prof-IT

Guide complet d'installation et de configuration du projet Prof-IT.

---

## Table des Matières
- [Prérequis Système](#prérequis-système)
- [Installation Pas à Pas](#installation-pas-à-pas)
- [Configuration de la Base de Données](#configuration-de-la-base-de-données)
- [Configuration PHP](#configuration-php)
- [Configuration Apache](#configuration-apache)
- [Variables d'Environnement](#variables-denvironnement)
- [Comptes de Test](#comptes-de-test)
- [Vérification de l'Installation](#vérification-de-linstallation)
- [Configuration Avancée](#configuration-avancée)
- [Déploiement en Production](#déploiement-en-production)

---

## Prérequis Système

### Logiciels Requis

| Logiciel | Version Minimale | Recommandée | Notes |
|----------|------------------|-------------|-------|
| **PHP** | 8.0 | 8.2 | Avec extensions listées ci-dessous |
| **MySQL** | 5.7 | 8.0 | Ou MariaDB 10.3+ |
| **Apache** | 2.4 | 2.4+ | Avec mod_rewrite activé |
| **Serveur local** | - | XAMPP 8.2 / WAMP | Pour développement |

### Extensions PHP Requises

Vérifiez que ces extensions sont activées dans `php.ini` :

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=fileinfo
extension=gd
extension=curl
extension=json
```

**Vérification** :
```bash
php -m | grep -E '(pdo_mysql|mbstring|openssl|fileinfo|gd)'
```

### Configuration PHP Minimale

Dans `php.ini` :
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M
```

---

## Installation Pas à Pas

### Étape 1 : Cloner le Repository

```bash
# Dans le dossier htdocs (XAMPP) ou www (WAMP)
cd c:\xampp\htdocs
git clone https://github.com/votre-repo/prof-it.git
cd prof-it
```

**Ou télécharger le ZIP** :
1. Télécharger depuis GitHub
2. Extraire dans `c:\xampp\htdocs\prof-it\`

---

### Étape 2 : Démarrer les Services

**XAMPP** :
1. Lancer XAMPP Control Panel
2. Démarrer **Apache**
3. Démarrer **MySQL**

**Vérification** :
- Apache : http://localhost/ doit afficher une page
- MySQL : http://localhost/phpmyadmin/ doit être accessible

---

### Étape 3 : Créer la Base de Données

**Option A - Via phpMyAdmin** :

1. Ouvrir http://localhost/phpmyadmin/
2. Cliquer sur "Nouvelle base de données"
3. Nom : `projet_profit`
4. Interclassement : `utf8mb4_unicode_ci`
5. Cliquer "Créer"
6. Sélectionner la BDD
7. Onglet "Importer"
8. Choisir le fichier `c:\xampp\htdocs\prof-it\db.sql`
9. Cliquer "Exécuter"

**Option B - Via ligne de commande** :

```bash
# Se connecter à MySQL
mysql -u root -p

# Créer la BDD et importer
CREATE DATABASE projet_profit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE projet_profit;
SOURCE c:/xampp/htdocs/prof-it/db.sql;
EXIT;
```

**Vérification** :
```sql
SELECT COUNT(*) FROM users;
-- Devrait retourner au moins 1 (compte admin)
```

---

### Étape 4 : Configurer config.php

Ouvrir `c:\xampp\htdocs\prof-it\config\config.php` :

```php
<?php
// Configuration Base de Données
$host = "localhost";
$user = "root";
$password = ""; // Vide par défaut sur XAMPP
$database = "projet_profit";

// Configuration Session
define('SESSION_LIFETIME', 1800); // 30 minutes en secondes

// Configuration SMTP (pour envoi emails)
define('SMTP_HOST', 'ssl0.ovh.net');
define('SMTP_PORT', 465);
define('SMTP_USER', 'support@prof-it.fr');
define('SMTP_PASS', 'Support2025!');
define('SMTP_FROM_EMAIL', 'support@prof-it.fr');
define('SMTP_FROM_NAME', 'Prof-IT Notification');

// Connexion PDO
try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Connexion échouée: " . $e->getMessage());
}
```

**⚠️ Sécurité** : En production, utilisez des variables d'environnement (voir section dédiée).

---

### Étape 5 : Créer le Dossier uploads/

```bash
cd c:\xampp\htdocs\prof-it
mkdir uploads
mkdir uploads\messages
```

**Permissions Windows** :
- Clic droit sur `uploads` → Propriétés → Sécurité
- Donner les droits "Lecture et écriture" à "Utilisateurs"

**Permissions Linux/Mac** :
```bash
chmod -R 0755 uploads/
```

---

### Étape 6 : Tester l'Accès

Ouvrir dans le navigateur :
```
http://localhost/prof-it/
```

**Attendu** :
- Redirection vers la page d'accueil publique
- Ou page de connexion si `index.php` redirige vers `auth/auth.php`

**En cas d'erreur** :
- Vérifier que Apache est démarré
- Vérifier que le chemin est correct (`htdocs/prof-it/`)
- Consulter les logs Apache : `c:\xampp\apache\logs\error.log`

---

## Configuration de la Base de Données

### Connexion PDO

Le projet utilise **PDO** (PHP Data Objects) pour la sécurité et l'abstraction.

**Paramètres** :
```php
[
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // Lève des exceptions en cas d'erreur SQL

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    // Retourne les résultats en tableaux associatifs
]
```

### Structure de la BDD

**Base** : `projet_profit`
**Tables** : 25 tables (voir [DATABASE.md](DATABASE.md))
**Vues** : 2 vues SQL
**Charset** : utf8mb4 (support emoji et caractères internationaux)

### Données Initiales

Le fichier `db.sql` crée automatiquement :
- **1 compte admin** : admin@prof-it.fr / password (hash)
- **3 rôles** : ADMIN, TEACHER, STUDENT
- **10 matières** : Maths, Français, Anglais, etc.
- **20 questions CAPTCHA**

---

## Configuration PHP

### php.ini

**Localisation XAMPP** : `c:\xampp\php\php.ini`

**Paramètres recommandés** :
```ini
; Uploads
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20

; Execution
max_execution_time = 60
max_input_time = 60
memory_limit = 256M

; Erreurs (développement uniquement)
display_errors = On
error_reporting = E_ALL

; Sessions
session.gc_maxlifetime = 1800
session.cookie_lifetime = 0
session.cookie_httponly = 1
session.cookie_samesite = Strict

; Production : activer ces lignes
; session.cookie_secure = 1
; display_errors = Off
; error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

**Redémarrer Apache** après modification de `php.ini`.

---

## Configuration Apache

### mod_rewrite

**Vérifier** que `mod_rewrite` est activé dans `httpd.conf` :

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Décommenter si nécessaire (enlever le `#`).

### VirtualHost (Optionnel)

Pour avoir `http://prof-it.local/` au lieu de `http://localhost/prof-it/` :

**1. Éditer** `c:\xampp\apache\conf\extra\httpd-vhosts.conf` :

```apache
<VirtualHost *:80>
    ServerName prof-it.local
    DocumentRoot "c:/xampp/htdocs/prof-it"
    <Directory "c:/xampp/htdocs/prof-it">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/prof-it-error.log"
    CustomLog "logs/prof-it-access.log" common
</VirtualHost>
```

**2. Éditer** `C:\Windows\System32\drivers\etc\hosts` (en admin) :

```
127.0.0.1 prof-it.local
```

**3. Redémarrer Apache**, puis accéder à http://prof-it.local/

---

## Variables d'Environnement

### Utilisation de .env (Recommandé en Production)

**1. Installer vlucas/phpdotenv** (si Composer) :

```bash
composer require vlucas/phpdotenv
```

**2. Créer** `.env` à la racine :

```env
# Base de Données
DB_HOST=localhost
DB_NAME=projet_profit
DB_USER=root
DB_PASS=

# SMTP
SMTP_HOST=ssl0.ovh.net
SMTP_PORT=465
SMTP_USER=support@prof-it.fr
SMTP_PASS=Support2025!
SMTP_FROM_EMAIL=support@prof-it.fr
SMTP_FROM_NAME=Prof-IT Notification

# Session
SESSION_LIFETIME=1800
```

**3. Modifier** `config/config.php` :

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];

define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME']);
// etc.
```

**4. Ajouter .env au .gitignore** :

```
.env
```

---

## Comptes de Test

Après import de `db.sql`, ces comptes sont disponibles :

| Rôle | Email | Mot de passe | Accès |
|------|-------|--------------|-------|
| **Admin** | admin@prof-it.fr | password | http://localhost/prof-it/admin/dashboard.php |
| **Professeur** | prof@prof-it.fr | Prof2024! | (à créer manuellement ou via admin) |
| **Étudiant** | student@prof-it.fr | Student2024! | (à créer manuellement ou via admin) |

**⚠️ Important** : Changez ces mots de passe en production !

**Créer un compte manuellement** :

```php
// Via PHP ou phpMyAdmin
$password_hash = password_hash('VotreMotDePasse', PASSWORD_DEFAULT);
// Copier le hash et l'insérer dans la table users
```

---

## Vérification de l'Installation

### Checklist

- [ ] Apache et MySQL démarrés
- [ ] BDD `projet_profit` créée
- [ ] Tables importées (25 tables)
- [ ] `config/config.php` configuré
- [ ] Dossier `uploads/` créé avec permissions
- [ ] Accès à http://localhost/prof-it/ fonctionne
- [ ] Connexion avec admin@prof-it.fr réussie
- [ ] Dashboard admin accessible

### Tests Manuels

**1. Test de connexion** :
- Aller sur http://localhost/prof-it/auth/auth.php
- Email : admin@prof-it.fr
- Mot de passe : password
- CAPTCHA : répondre à la question
- Vérifier redirection vers dashboard admin

**2. Test CRUD utilisateur** :
- Dashboard admin → Utilisateurs
- Créer un nouvel étudiant
- Vérifier apparition dans la liste
- Modifier l'étudiant
- Supprimer l'étudiant

**3. Test upload fichier** :
- Se connecter en tant qu'étudiant (créer un compte)
- Créer une conversation (via réservation)
- Envoyer un message avec fichier PDF
- Vérifier fichier dans `uploads/messages/{id}/`

**4. Test logs** :
- Dashboard admin → Logs
- Onglet Connexions : vérifier présence des tentatives
- Onglet Visites : vérifier tracking des pages

---

## Configuration Avancée

### Auto-Logout

Modifier le timeout dans `config/config.php` :

```php
define('SESSION_LIFETIME', 3600); // 1 heure au lieu de 30 min
```

Et dans `assets/js/auto_logout.js` :

```javascript
const timeout = 3600000; // 1 heure en millisecondes (3600 * 1000)
```

### SMTP pour Envoi d'Emails

Le projet utilise **PHPMailer** pour envoyer des emails (confirmations, notifications).

**Configuration** dans `config/config.php` :

```php
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail
define('SMTP_PORT', 587); // TLS
define('SMTP_USER', 'votre-email@gmail.com');
define('SMTP_PASS', 'mot-de-passe-application'); // Mot de passe d'application Gmail
define('SMTP_FROM_EMAIL', 'votre-email@gmail.com');
define('SMTP_FROM_NAME', 'Prof-IT');
```

**Gmail** : Générer un mot de passe d'application :
1. Compte Google → Sécurité
2. Validation en deux étapes (activer si nécessaire)
3. Mots de passe des applications → Générer

### Logs Apache

**Activer les logs** dans `httpd.conf` :

```apache
ErrorLog "logs/error.log"
CustomLog "logs/access.log" common
```

**Consulter** :
```bash
tail -f c:\xampp\apache\logs\error.log
```

---

## Déploiement en Production

### Checklist Sécurité

- [ ] **HTTPS activé** (certificat SSL via Let's Encrypt)
- [ ] **Credentials en variables d'environnement** (.env)
- [ ] **display_errors = Off** dans `php.ini`
- [ ] **Mots de passe par défaut changés**
- [ ] **Permissions fichiers** : 0644 pour PHP, 0755 pour dossiers
- [ ] **Firewall** : Bloquer tous les ports sauf 443 (HTTPS)
- [ ] **Backups BDD** : Quotidiens automatiques
- [ ] **session.cookie_secure = 1** (HTTPS uniquement)
- [ ] **CSP headers** configurés (Content-Security-Policy)

### Configuration VPS/Serveur

**Stack recommandée** :
- **OS** : Ubuntu 22.04 LTS
- **Serveur Web** : Nginx ou Apache
- **PHP** : 8.2 (via PHP-FPM)
- **BDD** : MySQL 8.0 ou MariaDB 10.6

**Installation rapide (Ubuntu)** :

```bash
# Mise à jour système
sudo apt update && sudo apt upgrade -y

# Installer LAMP
sudo apt install apache2 mysql-server php8.2 php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd -y

# Activer mod_rewrite
sudo a2enmod rewrite

# Redémarrer Apache
sudo systemctl restart apache2

# Sécuriser MySQL
sudo mysql_secure_installation
```

### HTTPS avec Let's Encrypt

```bash
# Installer Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtenir certificat SSL
sudo certbot --apache -d prof-it.fr -d www.prof-it.fr

# Renouvellement automatique (cron)
sudo certbot renew --dry-run
```

### Optimisations Production

**php.ini** :
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
```

**Apache** (.htaccess à la racine) :
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protection fichiers sensibles
<FilesMatch "(config\.php|\.env)$">
    Deny from all
</FilesMatch>

# Headers sécurité
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

---

## Support

**Problèmes courants** : Consultez [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

**Contact** : support@prof-it.fr

---

**Dernière mise à jour** : Janvier 2025

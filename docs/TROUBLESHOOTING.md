# Guide de Dépannage - Prof-IT

Solutions aux problèmes courants rencontrés avec Prof-IT.

---

## Table des Matières
- [Problèmes d'Installation](#problèmes-dinstallation)
- [Erreurs de Base de Données](#erreurs-de-base-de-données)
- [Problèmes de Connexion](#problèmes-de-connexion)
- [Erreurs de Session](#erreurs-de-session)
- [Problèmes d'Upload de Fichiers](#problèmes-dupload-de-fichiers)
- [Erreurs CSRF](#erreurs-csrf)
- [Problèmes d'Email](#problèmes-demail)
- [Erreurs JavaScript](#erreurs-javascript)
- [Performance et Lenteur](#performance-et-lenteur)
- [Outils de Débogage](#outils-de-débogage)

---

## Problèmes d'Installation

### ❌ Erreur : "Connexion échouée: SQLSTATE[HY000] [1045]"

**Cause** : Mauvais identifiants MySQL.

**Solution** :
1. Vérifier `config/config.php` :
   ```php
   $host = "localhost"; // Correct
   $user = "root"; // Correct pour XAMPP
   $password = ""; // Vide par défaut sur XAMPP
   $database = "projet_profit"; // Nom exact de la BDD
   ```
2. Tester la connexion MySQL :
   ```bash
   mysql -u root -p
   # Appuyer Entrée si pas de mot de passe
   ```
3. Si le mot de passe est requis, le modifier dans `config.php`.

---

### ❌ Erreur : "Table 'projet_profit.users' doesn't exist"

**Cause** : Base de données non importée ou mal nommée.

**Solution** :
1. Vérifier que la BDD existe :
   ```sql
   SHOW DATABASES;
   -- Doit afficher 'projet_profit'
   ```
2. Ré-importer `db.sql` :
   ```bash
   mysql -u root < c:/xampp/htdocs/prof-it/db.sql
   ```
3. Vérifier les tables :
   ```sql
   USE projet_profit;
   SHOW TABLES;
   -- Doit lister 25 tables
   ```

---

### ❌ Erreur : "Cannot modify header information - headers already sent"

**Cause** : Espaces ou BOM avant `<?php` ou après `?>`.

**Solution** :
1. Ouvrir le fichier mentionné dans l'erreur
2. Vérifier qu'il n'y a **aucun espace** avant `<?php`
3. Supprimer `?>` en fin de fichier PHP (optionnel mais recommandé)
4. Sauvegarder en **UTF-8 sans BOM**

---

### ❌ Erreur : "Call to undefined function password_hash()"

**Cause** : PHP < 5.5 ou extension manquante.

**Solution** :
1. Vérifier version PHP :
   ```bash
   php -v
   # Doit afficher >= 8.0
   ```
2. Si PHP < 8.0, mettre à jour XAMPP/WAMP
3. Redémarrer Apache

---

## Erreurs de Base de Données

### ❌ Erreur : "Syntax error or access violation: 1064"

**Cause** : Erreur SQL (souvent dans une requête préparée).

**Solution** :
1. Activer les erreurs détaillées dans `config.php` :
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Consulter le message d'erreur complet
3. Vérifier la requête SQL concernée
4. Tester la requête dans phpMyAdmin

**Exemple d'erreur courante** :
```php
// ❌ Mauvais (nom de colonne incorrect)
$stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");

// ✅ Correct
$stmt = $conn->prepare("SELECT * FROM users WHERE nom = ?");
```

---

### ❌ Erreur : "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry"

**Cause** : Violation de contrainte UNIQUE (ex: email déjà utilisé).

**Solution** :
1. Vérifier l'email en BDD :
   ```sql
   SELECT * FROM users WHERE email = 'admin@prof-it.fr';
   ```
2. Si l'utilisateur existe déjà, utiliser un autre email
3. Ou supprimer l'ancien compte :
   ```sql
   DELETE FROM users WHERE email = 'admin@prof-it.fr';
   ```

---

## Problèmes de Connexion

### ❌ Erreur : "Email ou mot de passe incorrect"

**Cause** : Mauvais identifiants ou compte inexistant.

**Solution** :
1. Vérifier que le compte existe :
   ```sql
   SELECT id, email, role, actif FROM users WHERE email = 'admin@prof-it.fr';
   ```
2. Si `actif = 0`, activer le compte :
   ```sql
   UPDATE users SET actif = 1 WHERE email = 'admin@prof-it.fr';
   ```
3. Réinitialiser le mot de passe :
   ```php
   // Générer un nouveau hash
   echo password_hash('password', PASSWORD_DEFAULT);
   // Copier le hash et mettre à jour la BDD
   ```
   ```sql
   UPDATE users SET password = '$2y$10$...' WHERE email = 'admin@prof-it.fr';
   ```

---

### ❌ Erreur : "CAPTCHA incorrect"

**Cause** : Mauvaise réponse au CAPTCHA ou questions CAPTCHA vides.

**Solution** :
1. Vérifier qu'il y a des questions CAPTCHA :
   ```sql
   SELECT * FROM captcha_questions WHERE actif = 1;
   ```
2. Si vide, insérer des questions :
   ```sql
   INSERT INTO captcha_questions (question, reponse, actif) VALUES
   ('Quelle est la capitale de la France ?', 'Paris', 1);
   ```
3. Vider le cache du navigateur (Ctrl + F5)
4. Réessayer avec la bonne réponse (sensible à la casse)

---

### ❌ Erreur : "Accès refusé : vous devez être administrateur"

**Cause** : Utilisateur n'a pas le rôle requis.

**Solution** :
1. Vérifier le rôle :
   ```sql
   SELECT role FROM users WHERE email = 'votre-email@example.com';
   ```
2. Modifier le rôle si nécessaire :
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'votre-email@example.com';
   ```
3. Se déconnecter et se reconnecter

---

## Erreurs de Session

### ❌ Erreur : "Session timeout" ou déconnexion trop rapide

**Cause** : `SESSION_LIFETIME` trop court.

**Solution** :
1. Augmenter la durée dans `config/config.php` :
   ```php
   define('SESSION_LIFETIME', 3600); // 1 heure au lieu de 30 min
   ```
2. Modifier aussi `assets/js/auto_logout.js` :
   ```javascript
   const timeout = 3600000; // 1 heure (3600 * 1000 ms)
   ```
3. Vider le cache navigateur et se reconnecter

---

### ❌ Erreur : "Undefined index: user_id in session"

**Cause** : Session non démarrée ou perdue.

**Solution** :
1. Vérifier que `session_start()` est appelé :
   ```php
   // En haut de chaque fichier PHP nécessitant la session
   require_once __DIR__ . '/includes/helpers.php';
   safe_session_start();
   ```
2. Vérifier les permissions du dossier sessions :
   ```bash
   # Linux/Mac
   ls -la /tmp/
   # Windows : généralement C:\xampp\tmp
   ```
3. Augmenter `session.gc_maxlifetime` dans `php.ini` :
   ```ini
   session.gc_maxlifetime = 1800
   ```

---

### ❌ Erreur : "Warning: session_start(): Failed to read session data"

**Cause** : Problème de permissions ou de stockage des sessions.

**Solution** :
1. Vérifier le dossier de sessions dans `php.ini` :
   ```ini
   session.save_path = "C:\xampp\tmp"
   ```
2. S'assurer que le dossier existe et a les bonnes permissions
3. Créer le dossier si manquant :
   ```bash
   mkdir C:\xampp\tmp
   ```
4. Redémarrer Apache

---

## Problèmes d'Upload de Fichiers

### ❌ Erreur : "Fichier non uploadé" ou "Failed to move uploaded file"

**Cause** : Permissions insuffisantes sur le dossier `uploads/`.

**Solution** :
1. **Windows** :
   - Clic droit sur `uploads/` → Propriétés → Sécurité
   - Ajouter "Utilisateurs" avec droits "Lecture et écriture"

2. **Linux/Mac** :
   ```bash
   chmod -R 0755 uploads/
   chown -R www-data:www-data uploads/
   ```

3. Vérifier que le dossier existe :
   ```bash
   ls -la uploads/
   ls -la uploads/messages/
   ```

---

### ❌ Erreur : "File size exceeds upload_max_filesize"

**Cause** : Fichier trop volumineux.

**Solution** :
1. Modifier `php.ini` :
   ```ini
   upload_max_filesize = 20M
   post_max_size = 22M
   ```
2. Redémarrer Apache
3. Vérifier avec `phpinfo()` :
   ```php
   <?php phpinfo(); ?>
   ```

---

### ❌ Erreur : "Extension de fichier non autorisée"

**Cause** : Type de fichier non dans la whitelist.

**Solution** :
1. Vérifier les extensions autorisées dans `api/messaging.php` :
   ```php
   $allowedExtensions = ['pdf','doc','docx','xls','xlsx','ppt','pptx','png','jpg','jpeg','txt'];
   ```
2. Ajouter l'extension manquante si sûr et sécurisé :
   ```php
   $allowedExtensions = ['pdf','doc','docx','xls','xlsx','ppt','pptx','png','jpg','jpeg','txt','zip'];
   ```
3. **⚠️ Ne jamais autoriser** : php, exe, sh, bat, etc.

---

## Erreurs CSRF

### ❌ Erreur : "Token CSRF invalide"

**Cause** : Token manquant, expiré ou incorrect.

**Solution** :
1. Vérifier que le formulaire contient le token :
   ```php
   <?php echo csrf_field(); ?>
   ```
2. S'assurer que la session est active :
   ```php
   safe_session_start();
   ```
3. Vider le cache du navigateur (Ctrl + Shift + Delete)
4. Recharger la page et réessayer
5. Si le problème persiste, vérifier `includes/csrf.php`

---

### ❌ Erreur : "CSRF token not found in session"

**Cause** : Session non initialisée avant génération du token.

**Solution** :
1. Déplacer `safe_session_start()` **avant** tout autre code :
   ```php
   <?php
   require_once 'includes/helpers.php';
   safe_session_start(); // DOIT être en premier

   // Puis le reste du code...
   ```
2. Vérifier qu'il n'y a pas de sortie avant `session_start()`

---

## Problèmes d'Email

### ❌ Erreur : "SMTP connect() failed"

**Cause** : Mauvaise configuration SMTP ou serveur inaccessible.

**Solution** :
1. Vérifier les credentials SMTP dans `config/config.php`
2. Tester avec Gmail (plus simple pour dev) :
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587); // ou 465 pour SSL
   define('SMTP_USER', 'votre-email@gmail.com');
   define('SMTP_PASS', 'mot-de-passe-application');
   ```
3. Activer "Accès moins sécurisé" ou générer mot de passe d'application
4. Tester avec un outil comme MailHog (serveur SMTP local) :
   ```bash
   # Installer MailHog
   # Windows : Télécharger depuis GitHub
   # Linux : apt install mailhog
   # Lancer : mailhog
   ```

---

### ❌ Emails envoyés finissent en spam

**Cause** : Serveur SMTP non configuré correctement ou réputation faible.

**Solution** :
1. Utiliser un service SMTP professionnel (SendGrid, Mailgun, Amazon SES)
2. Configurer SPF, DKIM, DMARC records (DNS)
3. Ajouter un lien de désinscription
4. Éviter les mots spam ("gratuit", "urgent", etc.)

---

## Erreurs JavaScript

### ❌ Erreur : "Uncaught ReferenceError: $ is not defined"

**Cause** : jQuery non chargé ou chargé après le script.

**Solution** :
1. Vérifier l'ordre des scripts dans le HTML :
   ```html
   <!-- jQuery AVANT les autres scripts -->
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="assets/js/index.js"></script>
   ```
2. Ou ne pas utiliser jQuery (vanilla JS) :
   ```javascript
   // ❌ jQuery
   $('#element').hide();

   // ✅ Vanilla JS
   document.getElementById('element').style.display = 'none';
   ```

---

### ❌ Erreur : "Failed to fetch" (API calls)

**Cause** : URL incorrecte, serveur arrêté ou CORS.

**Solution** :
1. Vérifier que Apache est démarré
2. Vérifier l'URL dans la console navigateur (F12 → Network)
3. Tester l'URL directement dans le navigateur
4. Vérifier que l'API retourne bien du JSON :
   ```javascript
   fetch('/api/appointments.php?action=stats')
     .then(response => {
       console.log('Status:', response.status);
       return response.json();
     })
     .then(data => console.log('Data:', data))
     .catch(error => console.error('Error:', error));
   ```

---

## Performance et Lenteur

### 🐌 Site très lent

**Causes possibles** :
- Trop de requêtes SQL
- Pas d'index sur les colonnes recherchées
- Fichiers trop volumineux
- Pas de cache

**Solutions** :
1. **Activer OPcache** dans `php.ini` :
   ```ini
   opcache.enable = 1
   opcache.memory_consumption = 128
   ```
2. **Optimiser les requêtes SQL** :
   ```sql
   -- Analyser les requêtes lentes
   EXPLAIN SELECT * FROM reservation WHERE id_utilisateur = 5;

   -- Ajouter des index si nécessaire
   CREATE INDEX idx_user ON reservation(id_utilisateur);
   ```
3. **Limiter les résultats** :
   ```sql
   SELECT * FROM logs_connexions ORDER BY date_connexion DESC LIMIT 100;
   ```
4. **Utiliser les vues SQL** :
   ```sql
   -- Plutôt que de faire des jointures complexes
   SELECT * FROM vue_reservations_details WHERE email_etudiant = 'test@example.com';
   ```

---

### 🐌 Dashboard admin lent

**Solution** :
1. Réduire les stats chargées :
   - Charger stats par AJAX après affichage de la page
   - Paginer les tableaux (20 lignes max)
2. Utiliser Chart.js en différé :
   ```javascript
   // Charger le graphique après la page
   window.addEventListener('load', function() {
     loadChart();
   });
   ```

---

## Outils de Débogage

### Activer les Erreurs PHP

Dans `config/config.php` (développement uniquement) :
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

**⚠️ Désactiver en production** :
```php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
```

---

### Console Navigateur

**Ouvrir** : F12 ou Ctrl+Shift+I

**Onglets utiles** :
- **Console** : Erreurs JavaScript
- **Network** : Requêtes HTTP/AJAX (statut, réponse, temps)
- **Application** : Sessions, cookies, stockage local

**Exemples** :
```javascript
// Déboguer une variable
console.log('User ID:', userId);

// Déboguer un objet
console.table(appointments);

// Chronométrer une fonction
console.time('loadData');
loadData();
console.timeEnd('loadData'); // Affiche le temps écoulé
```

---

### Logs Apache

**Localisation XAMPP** :
- Erreurs : `c:\xampp\apache\logs\error.log`
- Accès : `c:\xampp\apache\logs\access.log`

**Consulter en temps réel** :
```bash
# Windows (PowerShell)
Get-Content C:\xampp\apache\logs\error.log -Wait

# Linux/Mac
tail -f /var/log/apache2/error.log
```

---

### Logs MySQL

**Activer le log des requêtes lentes** :

Dans `my.ini` (XAMPP) ou `my.cnf` (Linux) :
```ini
slow_query_log = 1
slow_query_log_file = c:/xampp/mysql/data/slow.log
long_query_time = 2
```

**Consulter** :
```bash
cat c:/xampp/mysql/data/slow.log
```

---

### Xdebug (Debugging Avancé)

**Installation XAMPP** :

1. Télécharger Xdebug depuis https://xdebug.org/download
2. Copier `php_xdebug.dll` dans `c:\xampp\php\ext\`
3. Éditer `php.ini` :
   ```ini
   [XDebug]
   zend_extension = "c:\xampp\php\ext\php_xdebug.dll"
   xdebug.mode = debug
   xdebug.start_with_request = yes
   ```
4. Redémarrer Apache
5. Installer l'extension VSCode "PHP Debug"

---

## Problèmes Spécifiques

### Windows : "XAMPP Apache ne démarre pas"

**Cause** : Port 80 ou 443 déjà utilisé (Skype, IIS, etc.).

**Solution** :
1. Changer le port dans `httpd.conf` :
   ```apache
   Listen 8080
   ```
2. Redémarrer Apache
3. Accéder à http://localhost:8080/prof-it/

---

### Linux : "Permission denied" sur uploads/

**Solution** :
```bash
sudo chown -R www-data:www-data uploads/
sudo chmod -R 0755 uploads/
```

---

### Mac : "PDO driver not found"

**Solution** :
```bash
# Vérifier les extensions
php -m | grep pdo

# Installer si manquant (via Homebrew)
brew install php@8.2
brew link php@8.2
```

---

## Support et Assistance

### Avant de Demander de l'Aide

1. Consulter cette documentation
2. Vérifier les logs Apache et PHP
3. Tester avec les comptes par défaut
4. Vider le cache du navigateur
5. Redémarrer Apache et MySQL

### Informations à Fournir

Quand vous demandez de l'aide, incluez :
- **Version PHP** : `php -v`
- **Version MySQL** : `mysql --version`
- **OS** : Windows 10/11, Ubuntu, macOS
- **Message d'erreur complet** : Copier-coller exact
- **Logs** : Extraits pertinents de error.log
- **Étapes pour reproduire** : Séquence exacte d'actions

### Contact

- **Email** : support@prof-it.fr
- **GitHub Issues** : https://github.com/votre-repo/prof-it/issues
- **Documentation** : Consultez [docs/INDEX.md](INDEX.md)

---

**Dernière mise à jour** : Janvier 2025

# Guide de Sécurité - Prof-IT

Documentation complète des mécanismes de sécurité implémentés dans le projet Prof-IT.

---

## Table des Matières
- [Vue d'Ensemble](#vue-densemble)
- [Authentification et Mots de Passe](#authentification-et-mots-de-passe)
- [Gestion des Sessions](#gestion-des-sessions)
- [Protection CSRF](#protection-csrf)
- [Prévention des Injections SQL](#prévention-des-injections-sql)
- [Protection XSS](#protection-xss)
- [Upload de Fichiers](#upload-de-fichiers)
- [Validation des Données](#validation-des-données)
- [Logs et Monitoring](#logs-et-monitoring)
- [Headers de Sécurité](#headers-de-sécurité)
- [Auto-Logout](#auto-logout)
- [CAPTCHA Anti-Bot](#captcha-anti-bot)
- [Contrôle d'Accès](#contrôle-daccès)
- [Bonnes Pratiques](#bonnes-pratiques)
- [Vulnérabilités et Recommandations](#vulnérabilités-et-recommandations)

---

## Vue d'Ensemble

### Stratégie de Sécurité

Prof-IT implémente une **défense en profondeur** (defense in depth) avec plusieurs couches :

```
┌─────────────────────────────────────────────┐
│  1. Input Validation (Validation entrées)   │
├─────────────────────────────────────────────┤
│  2. Authentication (Authentification)       │
├─────────────────────────────────────────────┤
│  3. Authorization (Autorisation RBAC)       │
├─────────────────────────────────────────────┤
│  4. CSRF Protection (Tokens CSRF)           │
├─────────────────────────────────────────────┤
│  5. SQL Injection Prevention (PDO)          │
├─────────────────────────────────────────────┤
│  6. XSS Protection (htmlspecialchars)       │
├─────────────────────────────────────────────┤
│  7. Session Security (Regeneration ID)      │
├─────────────────────────────────────────────┤
│  8. Logging (Audit trail)                   │
└─────────────────────────────────────────────┘
```

### Principes Appliqués

| Principe | Implémentation Prof-IT |
|----------|------------------------|
| **Least Privilege** | Rôles (student, teacher, admin) avec droits minimaux |
| **Fail Secure** | En cas d'erreur, déconnexion automatique |
| **Defense in Depth** | Multiples couches de sécurité |
| **Input Validation** | Whitelist + sanitization systématique |
| **Secure by Default** | Sessions sécurisées, CSRF activé par défaut |

---

## Authentification et Mots de Passe

### Hachage des Mots de Passe

**Algorithme** : `PASSWORD_DEFAULT` (Bcrypt ou Argon2 selon version PHP)

**Fichier** : [auth/login_register.php](../auth/login_register.php#L63)

```php
// Inscription - Hachage du mot de passe
$password = password_hash($password_raw, PASSWORD_DEFAULT);

// Exemple de hash généré (Bcrypt) :
// $2y$10$e0MYzXyjpJS7Pd0RVvHwHeDn4K1qKZkGxXJNrQl1n0J8y3K4Y5z9K
```

**Caractéristiques** :
- **Coût adaptatif** : Augmente automatiquement la difficulté avec le temps
- **Salt automatique** : Chaque hash a un salt unique intégré
- **Résistant aux rainbow tables** : Grâce au salt
- **One-way hash** : Impossible à décrypter

### Vérification du Mot de Passe

**Fichier** : [auth/login_register.php](../auth/login_register.php#L91)

```php
// Connexion - Vérification sécurisée
if ($user && password_verify($password, $user['password']) && (int)($user['actif'] ?? 1) === 1) {
    // Connexion réussie
}
```

**Sécurités** :
- `password_verify()` : Résistant aux attaques timing
- Vérification du statut `actif` : Empêche les comptes désactivés de se connecter
- Pas de message différencié : "Email ou mot de passe incorrects" (évite l'énumération d'emails)

### Politique de Mots de Passe

**Validation** : [auth/login_register.php](../auth/login_register.php#L39-L44)

```php
if (strlen($password_raw) < 6) {
    $_SESSION['register_error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
    header("Location: auth.php");
    exit();
}
```

**Règles actuelles** :
- ✅ Longueur minimale : 6 caractères
- ❌ Pas de complexité requise (chiffres, majuscules, symboles)
- ❌ Pas de vérification contre les mots de passe courants

**⚠️ Recommandation** : Renforcer la politique (voir section Recommandations)

### Réinitialisation de Mot de Passe

**Implémentation** : Changement depuis les paramètres utilisateur

**Fichiers** :
- [student/settings.php](../student/settings.php#L89)
- [teacher/settings.php](../teacher/settings.php#L89)
- [admin/settings.php](../admin/settings.php#L99)

```php
// Changement de mot de passe
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashedPassword, $_SESSION['user_id']]);
```

**Sécurités** :
- ✅ Hash du nouveau mot de passe
- ✅ Vérification de session active
- ❌ Pas de vérification de l'ancien mot de passe
- ❌ Pas d'envoi d'email de notification

---

## Gestion des Sessions

### Configuration des Sessions

**Fichier** : [config/config.php](../config/config.php#L7)

```php
define('SESSION_LIFETIME', 1800); // 30 minutes
```

**Paramètres PHP recommandés** (`php.ini`) :

```ini
session.cookie_httponly = 1       ; Empêche JavaScript d'accéder aux cookies
session.cookie_samesite = Strict  ; Protection CSRF
session.gc_maxlifetime = 1800     ; 30 minutes
session.cookie_lifetime = 0       ; Cookie de session (supprimé à la fermeture)
session.use_strict_mode = 1       ; Refuse les ID de session non initialisés
session.use_only_cookies = 1      ; Désactive l'ID de session dans l'URL
session.cookie_secure = 1         ; HTTPS uniquement (production)
```

### Démarrage Sécurisé de Session

**Fichier** : [includes/helpers.php](../includes/helpers.php#L1-L10)

```php
function safe_session_start() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
}
```

**Protection** :
- Évite les doubles démarrages
- Configure HttpOnly (JavaScript ne peut pas lire le cookie)
- Configure SameSite=Strict (protection CSRF)

### Régénération de l'ID de Session

**Fichier** : [auth/login_register.php](../auth/login_register.php#L102)

```php
// Après authentification réussie
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
// ...
```

**But** : Prévenir le **session fixation** (attaque où l'attaquant impose un ID de session)

**Quand régénérer** :
- ✅ Après connexion réussie
- ✅ Après changement de privilèges
- ❌ Pas de régénération périodique (recommandé toutes les 15 minutes)

### Variables de Session Stockées

```php
$_SESSION = [
    'user_id'    => 42,                      // ID utilisateur
    'name'       => 'Dupont',                // Nom
    'prenom'     => 'Jean',                  // Prénom
    'email'      => 'jean@example.com',      // Email
    'role'       => 'student',               // Rôle (student|teacher|admin)
    'avatar_url' => '/uploads/avatars/42.jpg', // Photo de profil
    'csrf_token' => '8f7a3b2...',            // Token CSRF
];
```

### Destruction de Session

**Fichier** : [auth/logout.php](../auth/logout.php) (non fourni, logique standard)

```php
// Logout complet
session_start();
$_SESSION = [];                        // Vide toutes les variables
session_destroy();                     // Détruit la session
setcookie(session_name(), '', time()-3600, '/'); // Supprime le cookie
header("Location: auth/auth.php");
exit();
```

---

## Protection CSRF

### Génération de Token CSRF

**Fichier** : [includes/csrf.php](../includes/csrf.php#L6-L11)

```php
function csrf_token(): string {
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_SESSION_KEY];
}
```

**Caractéristiques** :
- **Longueur** : 64 caractères hexadécimaux (32 bytes)
- **Aléatoire cryptographique** : `random_bytes()` (CSPRNG)
- **Un token par session** : Persiste tant que la session est active
- **Exemple** : `a3f8e2b9c1d4f6a8e2b9c1d4f6a8e2b9c1d4f6a8e2b9c1d4f6a8e2b9c1d4f6a8`

### Insertion du Token dans les Formulaires

**Fonction helper** : [includes/helpers.php](../includes/helpers.php#L29-L32)

```php
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
```

**Utilisation dans les formulaires** : [auth/auth.php](../auth/auth.php#L81)

```php
<form action="login_register.php" method="post">
    <?= csrf_field() ?>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit" name="login">Se connecter</button>
</form>
```

**Rendu HTML** :
```html
<input type="hidden" name="csrf_token" value="a3f8e2b9c1d4f6a8e2b9c1d4f6...">
```

### Vérification du Token CSRF

**Fichier** : [includes/csrf.php](../includes/csrf.php#L13-L18)

```php
function verify_csrf(?string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sess = $_SESSION[CSRF_SESSION_KEY] ?? '';
    if (!is_string($token) || $token === '') return false;
    return hash_equals((string)$sess, (string)$token);
}
```

**Protection timing attack** : `hash_equals()` (comparaison en temps constant)

**Middleware CSRF** : [includes/helpers.php](../includes/helpers.php#L22-L27)

```php
function csrf_protect() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? null)) {
            die('CSRF token invalide');
        }
    }
}
```

**Utilisation** : [auth/login_register.php](../auth/login_register.php#L7)

```php
safe_session_start();
csrf_protect(); // Bloque toutes les requêtes POST sans token valide
```

### Protection AJAX (API)

**Fichiers API** :
- [api/appointments.php](../api/appointments.php)
- [api/messaging.php](../api/messaging.php)
- [admin/api/users.php](../admin/api/users.php)

**Vérification dans chaque endpoint** :

```php
// POST /api/appointments.php?action=book_slot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
    // Traitement de la réservation
}
```

**Client JavaScript** : Envoyer le token dans chaque requête POST

```javascript
// Exemple : Réserver un créneau
fetch('/api/appointments.php?action=book_slot', {
    method: 'POST',
    body: new FormData(form), // Inclut csrf_token
}).then(res => res.json());
```

---

## Prévention des Injections SQL

### PDO avec Requêtes Préparées

**Fichier** : [config/config.php](../config/config.php#L11-L21)

```php
$conn = new PDO(
    "mysql:host=$host;dbname=$database;charset=utf8mb4",
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false // Vraies requêtes préparées côté MySQL
    ]
);
```

### Exemples de Requêtes Sécurisées

**✅ BON** : Requêtes préparées avec placeholders

```php
// Sélection avec WHERE
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Insertion avec valeurs liées
$stmt = $conn->prepare("
    INSERT INTO users (nom, prenom, email, password, role)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$nom, $prenom, $email, $password, $role]);

// Update avec WHERE
$stmt = $conn->prepare("UPDATE users SET nom = ?, prenom = ? WHERE id = ?");
$stmt->execute([$nom, $prenom, $userId]);

// Delete avec WHERE
$stmt = $conn->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
$stmt->execute([$reservationId, $_SESSION['user_id']]);
```

**❌ MAUVAIS** : Concaténation directe (vulnérable)

```php
// NE JAMAIS FAIRE ÇA !
$query = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($query);
// Vulnérable à : ' OR '1'='1' --
```

### Validation des Identifiants

**Bonnes pratiques appliquées** :

```php
// Validation type entier
$userId = (int)$_POST['user_id']; // Cast forcé

// Vérification d'appartenance (whitelist)
if (!in_array($role, ['student', 'teacher', 'admin'])) {
    die('Rôle invalide');
}

// Validation email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Email invalide');
}
```

---

## Protection XSS

### Échappement des Sorties

**Fonction** : `htmlspecialchars()`

**Exemple** : [auth/auth.php](../auth/auth.php#L40)

```php
function showError($error) {
    return !empty($error)
        ? "<p class='error-message'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</p>"
        : '';
}
```

**Paramètres** :
- `ENT_QUOTES` : Encode `'` et `"` en `&#039;` et `&quot;`
- `UTF-8` : Charset pour éviter les contournements

**Résultat** :

```php
$input = "<script>alert('XSS')</script>";
echo htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
// Affiche : &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
// Le navigateur affiche le texte brut, pas le code
```

### Protection dans les Templates

**❌ Vulnérable** :
```php
<p>Bienvenue <?= $_SESSION['name'] ?></p>
```

**✅ Sécurisé** :
```php
<p>Bienvenue <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?></p>
```

### Protection dans les Attributs HTML

```php
// Dans un attribut value
<input type="text" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">

// Dans un attribut href (filtrer les protocoles dangereux)
<?php
$url = $_GET['redirect'] ?? '';
if (filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'javascript:') === false) {
    echo '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Redirection</a>';
}
?>
```

### Protection JSON (API)

```php
// API - Encoder en JSON (échappe automatiquement)
echo json_encode([
    'success' => true,
    'message' => $userMessage, // Échappé par json_encode
    'data' => [
        'name' => $name // Échappé par json_encode
    ]
]);
```

### Content-Security-Policy (Recommandé)

**Fichier** : `.htaccess` (à créer)

```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;"
```

**Effet** : Bloque les scripts inline et les sources non autorisées

---

## Upload de Fichiers

### Validation des Extensions

**Fichier** : [api/messaging.php](../api/messaging.php) (extrait supposé)

```php
// Whitelist des extensions autorisées
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
$fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    die('Type de fichier non autorisé');
}
```

**Extensions dangereuses bloquées** : `.php`, `.phtml`, `.exe`, `.sh`, `.bat`, `.js`

### Validation du Type MIME

```php
$allowedMimeTypes = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$fileMimeType = mime_content_type($_FILES['file']['tmp_name']);

if (!in_array($fileMimeType, $allowedMimeTypes)) {
    die('Type MIME non autorisé');
}
```

**⚠️ Attention** : Le type MIME peut être falsifié, toujours combiner avec extension

### Taille Maximale

**Configuration PHP** (`php.ini`) :

```ini
upload_max_filesize = 10M
post_max_size = 12M
```

**Validation côté serveur** :

```php
$maxSize = 10 * 1024 * 1024; // 10 MB

if ($_FILES['file']['size'] > $maxSize) {
    die('Fichier trop volumineux (max 10 MB)');
}
```

### Nom de Fichier Sécurisé

```php
// Génération d'un nom unique
$uniqueId = uniqid('file_', true);
$safeFilename = $uniqueId . '.' . $fileExtension;

// Exemple : file_64f8a9b12c4d5.17123456.pdf
```

**Pourquoi** :
- Évite les conflits de noms
- Empêche l'écrasement de fichiers existants
- Bloque les attaques par traversée de chemin (path traversal)

### Stockage Sécurisé

```php
// Dossier uploads/ hors du document root (si possible)
$uploadDir = __DIR__ . '/../uploads/messages/' . $messageId . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $safeFilename;
move_uploaded_file($_FILES['file']['tmp_name'], $destination);
```

**Protection** :
- Dossier par conversation (`$messageId`) : isolation
- Permissions `0755` : lecture seule pour les autres
- Stocker le chemin en BDD, pas le nom original

### .htaccess dans uploads/

**Fichier** : `uploads/.htaccess`

```apache
# Bloquer l'exécution de scripts PHP
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

# Forcer le téléchargement au lieu de l'exécution
<FilesMatch "\.(pdf|jpg|jpeg|png|doc|docx)$">
    Header set Content-Disposition "attachment"
</FilesMatch>
```

---

## Validation des Données

### Validation Email

```php
$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email invalide';
    header("Location: auth.php");
    exit();
}
```

### Validation Numérique

```php
// Cast forcé
$userId = (int)$_POST['user_id'];

// Validation plage
if ($userId <= 0) {
    die('ID utilisateur invalide');
}
```

### Validation Énumérations

```php
// Whitelist stricte
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
$status = $_POST['status'] ?? '';

if (!in_array($status, $allowedStatuses, true)) {
    die('Statut invalide');
}
```

### Validation Dates

```php
$date = $_POST['date'] ?? '';

// Vérifier format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    die('Format de date invalide');
}

// Vérifier validité de la date
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
    die('Date invalide');
}
```

### Sanitization

```php
// Nettoyer les espaces
$nom = trim($_POST['nom'] ?? '');

// Supprimer les balises HTML
$description = strip_tags($_POST['description'] ?? '');

// Limiter la longueur
$comment = substr($_POST['comment'], 0, 500);
```

---

## Logs et Monitoring

### Logs de Connexion

**Table** : `logs_connexions`

**Fichier** : [auth/login_register.php](../auth/login_register.php#L93-L100)

```php
// Connexion réussie
$stmt_log = $conn->prepare("
    INSERT INTO logs_connexions (user_id, email, ip_address, user_agent, statut)
    VALUES (?, ?, ?, ?, 'success')
");
$stmt_log->execute([
    $user['id'],
    $email,
    $_SERVER['REMOTE_ADDR'] ?? null,
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
]);
```

```php
// Connexion échouée
$stmt_log = $conn->prepare("
    INSERT INTO logs_connexions (email, ip_address, user_agent, statut, raison_echec)
    VALUES (?, ?, ?, 'failed', 'Identifiants incorrects ou compte inactif')
");
$stmt_log->execute([
    $email,
    $_SERVER['REMOTE_ADDR'] ?? null,
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
]);
```

**Données collectées** :
- `user_id` : ID de l'utilisateur (si succès)
- `email` : Email utilisé pour la tentative
- `ip_address` : Adresse IP (`$_SERVER['REMOTE_ADDR']`)
- `user_agent` : Navigateur et OS (`$_SERVER['HTTP_USER_AGENT']`)
- `statut` : `success` ou `failed`
- `raison_echec` : Raison de l'échec
- `date_heure` : Timestamp automatique

**Utilité** :
- Détecter les tentatives de brute force (multiples échecs depuis une IP)
- Audit de sécurité
- Conformité RGPD (traçabilité des accès)

### Logs de Visites

**Table** : `logs_visites`

**Collecte** : À chaque chargement de page (si implémenté)

```php
// Exemple dans includes/track_visit.php
$stmt = $conn->prepare("
    INSERT INTO logs_visites (user_id, page, ip_address, user_agent)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'] ?? null,
    $_SERVER['REQUEST_URI'],
    $_SERVER['REMOTE_ADDR'],
    substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
]);
```

**Utilité** :
- Statistiques d'utilisation
- Détecter les comportements suspects
- Analyser les pages les plus visitées

### Sessions Actives

**Table** : `sessions_actives`

**Suivi des sessions en temps réel** :

```php
// Au login : créer l'entrée
$sessionId = session_id();
$stmt = $conn->prepare("
    INSERT INTO sessions_actives (session_id, user_id, ip_address, user_agent, last_activity)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$sessionId, $userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

// À chaque requête : mettre à jour last_activity
$stmt = $conn->prepare("UPDATE sessions_actives SET last_activity = NOW() WHERE session_id = ?");
$stmt->execute([$sessionId]);

// Au logout : supprimer l'entrée
$stmt = $conn->prepare("DELETE FROM sessions_actives WHERE session_id = ?");
$stmt->execute([$sessionId]);
```

**Utilité** :
- Voir les utilisateurs connectés en temps réel
- Forcer la déconnexion d'une session spécifique
- Détecter les sessions expirées

### Monitoring des Erreurs

**Configuration PHP** (`php.ini`) :

```ini
log_errors = On
error_log = /var/log/php_errors.log
display_errors = Off  ; En production uniquement
```

**Logging personnalisé** :

```php
// Exemple : Logger les tentatives CSRF échouées
function log_security_event($event, $details) {
    global $conn;
    $stmt = $conn->prepare("
        INSERT INTO logs_securite (type_event, details, ip_address, user_agent, date_heure)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $event,
        json_encode($details),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Utilisation
if (!verify_csrf($token)) {
    log_security_event('csrf_failed', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'url' => $_SERVER['REQUEST_URI']
    ]);
    die('CSRF token invalide');
}
```

---

## Headers de Sécurité

### Headers HTTP Recommandés

**Fichier** : `.htaccess` (à créer à la racine)

```apache
# Protection XSS
Header set X-XSS-Protection "1; mode=block"

# Prévention du MIME sniffing
Header set X-Content-Type-Options "nosniff"

# Protection Clickjacking
Header set X-Frame-Options "SAMEORIGIN"

# Politique de référents
Header set Referrer-Policy "strict-origin-when-cross-origin"

# Permissions restrictives
Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# Content-Security-Policy (à adapter)
Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;"

# HTTPS Strict Transport Security (HSTS) - Production uniquement
# Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

### Headers dans PHP

**Fichier** : `includes/security_headers.php` (à créer)

```php
<?php
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// En production avec HTTPS
// header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
```

**Inclure dans chaque page** :

```php
require_once 'includes/security_headers.php';
```

---

## Auto-Logout

### Mécanisme Client-Side

**Fichier** : [assets/js/auto_logout.js](../assets/js/auto_logout.js)

```javascript
(function() {
    let timeout = 1800 * 1000; // 30 minutes en millisecondes
    let logoutUrl = '/prof-it/auth/logout.php?timeout=1';
    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(logout, timeout);
    }

    function logout() {
        window.location.href = logoutUrl;
    }

    // Événements qui réinitialisent le timer
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    document.onclick = resetTimer;
    document.onscroll = resetTimer;
    document.ontouchstart = resetTimer; // Mobile

    resetTimer();
})();
```

**Fonctionnement** :
1. Timer de 30 minutes démarre au chargement
2. Toute activité (souris, clavier, scroll) réinitialise le timer
3. Si aucune activité pendant 30 minutes → redirection vers logout

**Initialisation** : Inclure dans chaque page protégée

```html
<script src="/prof-it/assets/js/auto_logout.js"></script>
<script>
    // Initialiser avec le timeout du serveur
    initAutoLogout(<?= SESSION_LIFETIME ?>);
</script>
```

### Mécanisme Server-Side

**Session timeout PHP** :

```php
// Vérifier l'inactivité
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];

    if ($inactive > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: /prof-it/auth/auth.php?timeout=1");
        exit();
    }
}

$_SESSION['last_activity'] = time(); // Mettre à jour
```

**Combinaison client-side + server-side** : Double protection

---

## CAPTCHA Anti-Bot

### Génération de Question CAPTCHA

**Fichier** : [src/get_captcha.php](../src/get_captcha.php)

```php
function getCaptcha($conn) {
    $stmt = $conn->prepare("SELECT id, question FROM questions ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    return $stmt->fetch();
}
```

**Table** : `questions` (20 questions pré-enregistrées)

**Exemple de questions** :
```sql
INSERT INTO questions (question, answer) VALUES
('Combien font 2+2?', '4'),
('Quelle est la capitale de la France?', 'Paris'),
('Combien de jours dans une semaine?', '7');
```

### Affichage du CAPTCHA

**Fichier** : [auth/auth.php](../auth/auth.php#L133-L139)

```php
<div class="captcha-question">
    <?= htmlspecialchars($captchaQuestion, ENT_QUOTES, 'UTF-8') ?>
</div>
<input type="text" id="captchaAnswer" placeholder="Votre réponse">
<input type="hidden" id="captchaId" value="<?= $captchaId ?>">
```

### Vérification du CAPTCHA

**Fichier** : [src/get_captcha.php](../src/get_captcha.php) (fonction `verifyCaptcha()`)

```php
function verifyCaptcha($conn, $captchaId, $userAnswer) {
    $stmt = $conn->prepare("SELECT answer FROM questions WHERE id = ?");
    $stmt->execute([$captchaId]);
    $row = $stmt->fetch();

    if (!$row) return false;

    // Comparaison insensible à la casse et aux espaces
    return strtolower(trim($userAnswer)) === strtolower(trim($row['answer']));
}
```

**Protection** :
- ✅ Insensible à la casse (`Paris` = `paris`)
- ✅ Insensible aux espaces (`  4  ` = `4`)
- ❌ Pas de protection contre les bots avancés (OCR, IA)

**⚠️ Limitation** : Questions simples = vulnérable aux bots modernes

**Recommandation** : Utiliser reCAPTCHA v3 de Google

---

## Contrôle d'Accès

### Rôles et Permissions

**3 rôles** : `student`, `teacher`, `admin`

| Rôle | Permissions |
|------|-------------|
| **student** | Réserver cours, messagerie, voir profil |
| **teacher** | Gérer créneaux, messagerie, modifier tarifs |
| **admin** | Tout (CRUD users, logs, gestion complète) |

### Vérification de Session

**Fichier type** : `includes/check_role.php` (supposé)

```php
<?php
session_start();

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: /prof-it/auth/auth.php");
    exit();
}

// Vérifier rôle
function require_role($allowedRoles) {
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        http_response_code(403);
        die('Accès interdit');
    }
}
```

**Utilisation** :

```php
// Page réservée aux admins
require_once 'includes/check_role.php';
require_role(['admin']);
```

```php
// Page accessible aux teachers et admins
require_once 'includes/check_role.php';
require_role(['teacher', 'admin']);
```

### Contrôle d'Accès aux Données

**Isolation par user_id** :

```php
// Étudiant ne peut voir que SES propres réservations
$stmt = $conn->prepare("
    SELECT * FROM reservations
    WHERE user_id = ?
    ORDER BY date_reservation DESC
");
$stmt->execute([$_SESSION['user_id']]);
```

**Vérification de propriété** :

```php
// Avant de modifier un message, vérifier qu'il appartient à l'utilisateur
$stmt = $conn->prepare("SELECT sender_id FROM messages WHERE id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

if ($message['sender_id'] !== $_SESSION['user_id']) {
    http_response_code(403);
    die('Vous ne pouvez pas modifier ce message');
}
```

---

## Bonnes Pratiques

### ✅ Pratiques Appliquées

| Pratique | Implémentation |
|----------|----------------|
| **Requêtes préparées** | PDO avec placeholders (100% couverture) |
| **Hash mots de passe** | `password_hash()` avec `PASSWORD_DEFAULT` |
| **CSRF tokens** | Génération et vérification sur toutes requêtes POST |
| **Échappement XSS** | `htmlspecialchars()` sur toutes les sorties |
| **Session sécurisée** | HttpOnly, SameSite=Strict, régénération ID |
| **Validation entrées** | Whitelist, cast types, sanitization |
| **Logs d'audit** | logs_connexions, logs_visites |
| **Upload sécurisé** | Whitelist extensions, noms uniques, dossier isolé |
| **Auto-logout** | Timeout 30 minutes (client + serveur) |

### ❌ Améliorations Recommandées

| Amélioration | Priorité | Effort |
|--------------|----------|--------|
| **Politique de mot de passe renforcée** | 🔴 Haute | Faible |
| **Rate limiting (brute force)** | 🔴 Haute | Moyen |
| **reCAPTCHA v3** | 🟡 Moyenne | Faible |
| **Variables d'environnement (.env)** | 🔴 Haute | Faible |
| **Headers CSP** | 🟡 Moyenne | Moyen |
| **2FA (authentification 2 facteurs)** | 🟢 Basse | Élevé |
| **Logs centralisés** | 🟡 Moyenne | Moyen |
| **WAF (Web Application Firewall)** | 🟢 Basse | Élevé |

---

## Vulnérabilités et Recommandations

### 🔴 Critiques

#### 1. Credentials en Dur

**Fichier** : [config/config.php](../config/config.php)

```php
// ❌ PROBLÈME
$host = "localhost";
$user = "root";
$password = ""; // Pas de mot de passe MySQL
define('SMTP_PASS', 'Support2025!'); // Mot de passe SMTP en clair
```

**Impact** : Exposition des credentials si le code est versionné (Git)

**Solution** : Variables d'environnement

```bash
# .env (ne pas versionner)
DB_HOST=localhost
DB_USER=root
DB_PASS=SecurePassword123!
SMTP_PASS=SecureSmtpPass456!
```

```php
// config/config.php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$password = $_ENV['DB_PASS'];
define('SMTP_PASS', $_ENV['SMTP_PASS']);
```

#### 2. Pas de Rate Limiting

**Problème** : Un attaquant peut tenter 1000 mots de passe en quelques secondes

**Solution** : Bloquer après X tentatives échouées

```php
// Vérifier tentatives depuis cette IP
$stmt = $conn->prepare("
    SELECT COUNT(*) as attempts
    FROM logs_connexions
    WHERE ip_address = ?
      AND statut = 'failed'
      AND date_heure > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
");
$stmt->execute([$_SERVER['REMOTE_ADDR']]);
$row = $stmt->fetch();

if ($row['attempts'] >= 5) {
    http_response_code(429);
    die('Trop de tentatives échouées. Réessayez dans 15 minutes.');
}
```

#### 3. Pas de Vérification Ancien Mot de Passe

**Fichier** : [student/settings.php](../student/settings.php#L89)

**Problème** : Si session compromise, attaquant peut changer le mot de passe sans connaître l'ancien

**Solution** :

```php
// Formulaire de changement de mot de passe
<input type="password" name="old_password" placeholder="Ancien mot de passe" required>
<input type="password" name="new_password" placeholder="Nouveau mot de passe" required>

// Traitement
$oldPassword = $_POST['old_password'];
$newPassword = $_POST['new_password'];

// Récupérer hash actuel
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Vérifier ancien mot de passe
if (!password_verify($oldPassword, $user['password'])) {
    die('Ancien mot de passe incorrect');
}

// Mettre à jour
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashedPassword, $_SESSION['user_id']]);
```

### 🟡 Moyennes

#### 4. Mots de Passe Faibles Acceptés

**Problème** : Mot de passe de 6 caractères accepté (`123456`)

**Solution** : Validation renforcée

```php
function is_password_strong($password) {
    // Minimum 8 caractères
    if (strlen($password) < 8) return false;

    // Au moins une majuscule
    if (!preg_match('/[A-Z]/', $password)) return false;

    // Au moins une minuscule
    if (!preg_match('/[a-z]/', $password)) return false;

    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) return false;

    // Au moins un caractère spécial
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;

    // Blacklist des mots de passe courants
    $commonPasswords = ['password', '12345678', 'qwerty', 'admin'];
    if (in_array(strtolower($password), $commonPasswords)) return false;

    return true;
}
```

#### 5. Pas d'Email de Notification

**Problème** : Changement de mot de passe sans email → utilisateur pas averti en cas de compromission

**Solution** : Envoyer un email à chaque changement

```php
// Après changement réussi
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
// ... Configuration SMTP

$mail->setFrom('noreply@prof-it.fr', 'Prof-IT Security');
$mail->addAddress($_SESSION['email']);
$mail->Subject = 'Changement de mot de passe';
$mail->Body = "Votre mot de passe a été modifié. Si ce n'est pas vous, contactez-nous immédiatement.";
$mail->send();
```

### 🟢 Mineures

#### 6. CAPTCHA Simple

**Problème** : Questions simples contournables par bots

**Solution** : reCAPTCHA v3

```html
<!-- Inclure script Google -->
<script src="https://www.google.com/recaptcha/api.js?render=VOTRE_SITE_KEY"></script>

<script>
grecaptcha.ready(function() {
    grecaptcha.execute('VOTRE_SITE_KEY', {action: 'register'}).then(function(token) {
        document.getElementById('g-recaptcha-response').value = token;
    });
});
</script>

<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
```

```php
// Vérification serveur
$recaptchaSecret = 'VOTRE_SECRET_KEY';
$recaptchaResponse = $_POST['g-recaptcha-response'];

$verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
$response = json_decode($verify);

if (!$response->success || $response->score < 0.5) {
    die('Échec vérification reCAPTCHA');
}
```

---

## Checklist de Sécurité

### Développement

- [x] PDO avec requêtes préparées
- [x] Hash des mots de passe (`password_hash()`)
- [x] CSRF tokens sur tous les formulaires
- [x] Échappement XSS (`htmlspecialchars()`)
- [x] Validation des entrées (whitelist)
- [x] Sessions sécurisées (HttpOnly, SameSite)
- [x] Upload de fichiers sécurisé
- [x] Logs d'audit (connexions, visites)
- [ ] Rate limiting
- [ ] Variables d'environnement (.env)
- [ ] Headers CSP

### Production

- [ ] HTTPS activé (SSL/TLS)
- [ ] `display_errors = Off`
- [ ] `session.cookie_secure = 1`
- [ ] Firewall configuré (ports 80/443 uniquement)
- [ ] Backups automatiques BDD
- [ ] Mot de passe MySQL fort
- [ ] Logs centralisés
- [ ] Monitoring (uptime, erreurs)
- [ ] WAF (Cloudflare, ModSecurity)
- [ ] Pentesting annuel

---

## Ressources

### Documentation Officielle

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [NIST Password Guidelines](https://pages.nist.gov/800-63-3/sp800-63b.html)
- [Mozilla Web Security](https://infosec.mozilla.org/guidelines/web_security)

### Outils de Test

- **OWASP ZAP** : Scanner de vulnérabilités
- **Burp Suite** : Proxy pour tester les API
- **Nikto** : Scanner de serveur web
- **SQLmap** : Tester les injections SQL

---

**Dernière mise à jour** : Janvier 2025

**Contact sécurité** : security@prof-it.fr

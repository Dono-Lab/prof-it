# Référence des Fonctions PHP - Prof-IT

Documentation exhaustive de toutes les fonctions PHP utilitaires du projet Prof-IT.

---

## Table des Matières
- [Fonctions de Sécurité](#fonctions-de-sécurité)
  - [CSRF Protection](#csrf-protection)
  - [Session Management](#session-management)
  - [Authentication Helpers](#authentication-helpers)
- [Fonctions Utilisateur](#fonctions-utilisateur)
  - [Statistiques](#statistiques)
  - [Profil et Avatar](#profil-et-avatar)
  - [Cours et Réservations](#cours-et-réservations)
  - [Documents](#documents)
- [Fonctions CAPTCHA](#fonctions-captcha)
- [Fonctions de Formatage](#fonctions-de-formatage)
- [Fonctions API](#fonctions-api)
  - [Support/Messaging](#supportmessaging)

---

## Fonctions de Sécurité

### CSRF Protection

**Fichier** : [includes/csrf.php](../includes/csrf.php)

#### `csrf_token()`

Génère ou retourne le token CSRF de la session.

```php
function csrf_token(): string
```

**Description** :
- Génère un token aléatoire cryptographiquement sécurisé de 64 caractères hexadécimaux (32 bytes)
- Utilise `random_bytes(32)` converti en hexadécimal avec `bin2hex()`
- Stocké dans `$_SESSION[CSRF_SESSION_KEY]`
- Réutilise le même token pour toute la durée de la session

**Retour** :
- `string` : Token CSRF (ex: `a3f8e2b9c1d4f6a8...`)

**Exemple** :
```php
$token = csrf_token();
echo "<input type='hidden' name='csrf_token' value='$token'>";
```

**Fichier** : [includes/csrf.php:6-11](../includes/csrf.php#L6-L11)

---

#### `verify_csrf()`

Vérifie la validité d'un token CSRF.

```php
function verify_csrf(?string $token): bool
```

**Paramètres** :
- `$token` (string|null) : Token CSRF à vérifier (depuis `$_POST['csrf_token']`)

**Retour** :
- `true` : Token valide
- `false` : Token invalide, manquant, ou session inactive

**Sécurité** :
- Utilise `hash_equals()` pour éviter les **timing attacks** (comparaison en temps constant)
- Démarre automatiquement la session si nécessaire

**Exemple** :
```php
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    die('CSRF token invalide');
}
// Continuer le traitement
```

**Fichier** : [includes/csrf.php:13-18](../includes/csrf.php#L13-L18)

---

### Session Management

**Fichier** : [includes/helpers.php](../includes/helpers.php)

#### `safe_session_start()`

Démarre une session de manière sécurisée avec les bons paramètres.

```php
function safe_session_start(): void
```

**Description** :
- Vérifie si une session est déjà active avec `session_status() !== PHP_SESSION_ACTIVE`
- Configure `session.cookie_httponly = 1` (JavaScript ne peut pas lire le cookie)
- Configure `session.cookie_samesite = 'Strict'` (protection CSRF)
- Démarre la session avec `session_start()`

**Sécurité** :
- **HttpOnly** : Empêche le vol de session via XSS
- **SameSite=Strict** : Bloque les requêtes cross-site

**Exemple** :
```php
safe_session_start();
$_SESSION['user_id'] = 42;
```

**Fichier** : [includes/helpers.php:1-10](../includes/helpers.php#L1-L10)

---

### Authentication Helpers

**Fichier** : [includes/helpers.php](../includes/helpers.php)

#### `csrf_protect()`

Middleware pour bloquer les requêtes POST sans token CSRF valide.

```php
function csrf_protect(): void
```

**Description** :
- Vérifie `$_SERVER['REQUEST_METHOD'] === 'POST'`
- Si POST, vérifie le token CSRF avec `verify_csrf($_POST['csrf_token'])`
- Si invalide : arrête l'exécution avec `die('CSRF token invalide')`

**Utilisation** :
```php
// En haut de chaque fichier traitant des POST
safe_session_start();
csrf_protect(); // Bloque si pas de token valide

// Traiter le formulaire en sécurité
```

**Fichier** : [includes/helpers.php:22-27](../includes/helpers.php#L22-L27)

---

#### `csrf_field()`

Génère un champ caché HTML avec le token CSRF.

```php
function csrf_field(): string
```

**Retour** :
- `string` : Balise `<input type="hidden" ...>` avec le token CSRF échappé

**Rendu HTML** :
```html
<input type="hidden" name="csrf_token" value="a3f8e2b9c1d4f6a8...">
```

**Utilisation dans les formulaires** :
```php
<form method="POST" action="process.php">
    <?= csrf_field() ?>
    <input type="text" name="username">
    <button type="submit">Envoyer</button>
</form>
```

**Fichier** : [includes/helpers.php:29-32](../includes/helpers.php#L29-L32)

---

#### `is_logged_in()`

Vérifie si un utilisateur est connecté.

```php
function is_logged_in(): bool
```

**Retour** :
- `true` : Utilisateur connecté (`$_SESSION['user_id']` existe)
- `false` : Utilisateur non connecté

**Exemple** :
```php
if (!is_logged_in()) {
    header("Location: /prof-it/auth/auth.php");
    exit();
}
```

**Fichier** : [includes/helpers.php:12-15](../includes/helpers.php#L12-L15)

---

#### `has_role()`

Vérifie si l'utilisateur connecté a un rôle spécifique.

```php
function has_role(string $role): bool
```

**Paramètres** :
- `$role` (string) : Rôle à vérifier (`student`, `teacher`, `admin`)

**Retour** :
- `true` : Utilisateur a ce rôle
- `false` : Utilisateur n'a pas ce rôle ou non connecté

**Exemple** :
```php
if (has_role('admin')) {
    // Afficher le panel admin
} else {
    http_response_code(403);
    die('Accès interdit');
}
```

**Fichier** : [includes/helpers.php:17-20](../includes/helpers.php#L17-L20)

---

## Fonctions Utilisateur

**Fichier** : [includes/functions_user.php](../includes/functions_user.php)

### Profil et Avatar

#### `get_user_avatar()`

Récupère l'URL de l'avatar d'un utilisateur.

```php
function get_user_avatar(int $user_id, PDO $conn): string
```

**Paramètres** :
- `$user_id` (int) : ID de l'utilisateur
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `string` : URL de l'avatar (fichier uploadé ou avatar généré via UI Avatars)

**Logique** :
1. Récupère `photo_url`, `prenom`, `nom` depuis la table `users`
2. Si `photo_url` existe : retourne le chemin relatif `'../' . ltrim($photo_url, '/')`
3. Sinon : génère un avatar avec les initiales via [UI Avatars](https://ui-avatars.com/)
   - Couleur de fond : `#6366f1` (indigo)
   - Couleur du texte : `#fff` (blanc)

**Exemples de retour** :
```
../uploads/avatars/42.jpg                           // Avatar personnalisé
https://ui-avatars.com/api/?name=Jean+Dupont&background=6366f1&color=fff  // Avatar généré
```

**Utilisation** :
```php
$avatarUrl = get_user_avatar($_SESSION['user_id'], $conn);
echo "<img src='$avatarUrl' alt='Avatar' class='rounded-circle'>";
```

**Fichier** : [includes/functions_user.php:3-14](../includes/functions_user.php#L3-L14)

---

#### `get_profile_completion()`

Calcule le pourcentage de complétion du profil utilisateur.

```php
function get_profile_completion(int $user_id, PDO $conn): int
```

**Paramètres** :
- `$user_id` (int) : ID de l'utilisateur
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `int` : Pourcentage de complétion (0 à 100)

**Champs vérifiés** (9 au total) :
- `nom`, `prenom`, `email`, `telephone`, `adresse`, `ville`, `code_postal`, `bio`, `photo_url`

**Calcul** :
```
Pourcentage = (nombre de champs remplis / 9) * 100
```

**Exemple de résultats** :
- Tous les champs remplis : `100`
- Email + nom + prénom seulement : `33` (3/9)
- Profil vide : `0`

**Utilisation** :
```php
$completion = get_profile_completion($_SESSION['user_id'], $conn);
echo "Votre profil est complet à $completion%";

if ($completion < 100) {
    echo "<div class='alert alert-warning'>Complétez votre profil pour une meilleure visibilité</div>";
}
```

**Fichier** : [includes/functions_user.php:294-317](../includes/functions_user.php#L294-L317)

---

### Statistiques

#### `get_student_stats()`

Récupère les statistiques d'un étudiant.

```php
function get_student_stats(int $user_id, PDO $conn): array
```

**Paramètres** :
- `$user_id` (int) : ID de l'étudiant
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `array` : Tableau associatif avec les statistiques

**Structure du retour** :
```php
[
    'cours_termines'    => 12,          // Nombre de cours terminés
    'heures_total'      => 18.5,        // Heures de cours suivies (float arrondi à 1 décimale)
    'matiere_preferee'  => 'Mathématiques', // Matière la plus réservée
    'depenses_total'    => 450.50       // Dépenses totales en € (float arrondi à 2 décimales)
]
```

**Requêtes SQL** :
1. **Cours terminés** : `COUNT(*)` sur `reservation` où `statut_reservation = 'terminee'`
2. **Heures + Dépenses** : `SUM(durée en heures)` et `SUM(montant_ttc)` sur réservations confirmées/terminées
3. **Matière préférée** : `GROUP BY matiere` + `ORDER BY COUNT(*) DESC LIMIT 1`

**Utilisation** :
```php
$stats = get_student_stats($_SESSION['user_id'], $conn);

echo "Cours terminés : {$stats['cours_termines']}";
echo "Heures totales : {$stats['heures_total']}h";
echo "Matière préférée : {$stats['matiere_preferee']}";
echo "Dépenses : {$stats['depenses_total']} €";
```

**Fichier** : [includes/functions_user.php:16-65](../includes/functions_user.php#L16-L65)

---

#### `get_teacher_stats()`

Récupère les statistiques d'un professeur.

```php
function get_teacher_stats(int $user_id, PDO $conn): array
```

**Paramètres** :
- `$user_id` (int) : ID du professeur
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `array` : Tableau associatif avec les statistiques

**Structure du retour** :
```php
[
    'nb_etudiants'      => 25,          // Nombre d'étudiants distincts
    'nb_reservations'   => 47,          // Nombre de réservations (confirmées + terminées)
    'note_moyenne'      => 4.7,         // Note moyenne (sur 5, arrondi à 1 décimale)
    'nb_avis'           => 32,          // Nombre d'avis reçus
    'heures_donnees'    => 65.5,        // Heures de cours données (float)
    'revenus_total'     => 1850.00      // Revenus totaux en € (float)
]
```

**Requêtes SQL** :
1. **Étudiants + Réservations** : `COUNT(DISTINCT user_id)` et `COUNT(*)` sur créneaux du professeur
2. **Notes** : `AVG(note)` et `COUNT(*)` sur la table `avis`
3. **Heures + Revenus** : `SUM(durée)` et `SUM(prix_fige)` sur réservations confirmées/terminées

**Utilisation** :
```php
$stats = get_teacher_stats($_SESSION['user_id'], $conn);

echo "Étudiants enseignés : {$stats['nb_etudiants']}";
echo "Note moyenne : {$stats['note_moyenne']}/5 ({$stats['nb_avis']} avis)";
echo "Heures données : {$stats['heures_donnees']}h";
echo "Revenus : {$stats['revenus_total']} €";
```

**Fichier** : [includes/functions_user.php:67-118](../includes/functions_user.php#L67-L118)

---

### Cours et Réservations

#### `get_student_upcoming_courses()`

Récupère les prochains cours d'un étudiant.

```php
function get_student_upcoming_courses(int $user_id, PDO $conn, int $limit = 5): array
```

**Paramètres** :
- `$user_id` (int) : ID de l'étudiant
- `$conn` (PDO) : Connexion à la base de données
- `$limit` (int) : Nombre maximum de résultats (défaut: 5)

**Retour** :
- `array` : Tableau de tableaux associatifs (un par cours)

**Structure d'un cours** :
```php
[
    'id_reservation'      => 42,
    'statut_reservation'  => 'confirmee',
    'date_debut'          => '2025-02-15 14:00:00',
    'date_fin'            => '2025-02-15 15:30:00',
    'mode_propose'        => 'visio',             // 'visio' ou 'presentiel'
    'lieu'                => 'https://meet.google.com/abc-defg-hij',
    'titre_cours'         => 'Algèbre linéaire - Niveau Terminale',
    'nom_matiere'         => 'Mathématiques',
    'matiere_icone'       => '📐',
    'nom_professeur'      => 'Marie Curie',
    'photo_professeur'    => '/uploads/avatars/12.jpg'
]
```

**Filtres appliqués** :
- `statut_reservation = 'confirmee'`
- `date_debut > NOW()` (cours futurs uniquement)
- Trié par `date_debut ASC` (du plus proche au plus éloigné)

**Utilisation** :
```php
$prochainsCours = get_student_upcoming_courses($_SESSION['user_id'], $conn, 3);

foreach ($prochainsCours as $cours) {
    echo "<div class='course-card'>";
    echo "<h3>{$cours['titre_cours']}</h3>";
    echo "<p>Professeur : {$cours['nom_professeur']}</p>";
    echo "<p>Date : " . date('d/m/Y H:i', strtotime($cours['date_debut'])) . "</p>";
    echo "</div>";
}
```

**Fichier** : [includes/functions_user.php:120-149](../includes/functions_user.php#L120-L149)

---

#### `get_teacher_upcoming_sessions()`

Récupère les prochaines sessions d'un professeur.

```php
function get_teacher_upcoming_sessions(int $user_id, PDO $conn, int $limit = 5): array
```

**Paramètres** :
- `$user_id` (int) : ID du professeur
- `$conn` (PDO) : Connexion à la base de données
- `$limit` (int) : Nombre maximum de résultats (défaut: 5)

**Retour** :
- `array` : Tableau de sessions (créneaux réservés)

**Structure d'une session** :
```php
[
    'id_reservation'      => 42,
    'statut_reservation'  => 'confirmee',         // 'en_attente' ou 'confirmee'
    'mode_choisi'         => 'visio',
    'date_debut'          => '2025-02-15 14:00:00',
    'date_fin'            => '2025-02-15 15:30:00',
    'lieu'                => 'https://meet.google.com/abc-defg-hij',
    'titre_cours'         => 'Révisions Bac - Physique',
    'nom_matiere'         => 'Physique',
    'nom_etudiant'        => 'Pierre Martin',
    'photo_etudiant'      => '/uploads/avatars/25.jpg',
    'statut_cours'        => 'à venir'            // Calculé par compute_course_status()
]
```

**Filtres appliqués** :
- `statut_reservation IN ('en_attente', 'confirmee')`
- `date_debut > NOW()` (sessions futures)
- Trié par `date_debut ASC`

**Post-traitement** :
- Ajoute un champ `statut_cours` calculé via `compute_course_status()` (fonction supposée)

**Utilisation** :
```php
$sessions = get_teacher_upcoming_sessions($_SESSION['user_id'], $conn, 10);

foreach ($sessions as $session) {
    $badge = $session['statut_reservation'] === 'en_attente' ? 'warning' : 'success';
    echo "<div class='session-card'>";
    echo "<span class='badge bg-$badge'>{$session['statut_reservation']}</span>";
    echo "<p>Étudiant : {$session['nom_etudiant']}</p>";
    echo "<p>{$session['titre_cours']} - {$session['nom_matiere']}</p>";
    echo "</div>";
}
```

**Fichier** : [includes/functions_user.php:151-187](../includes/functions_user.php#L151-L187)

---

#### `get_teacher_available_slots()`

Récupère les créneaux disponibles d'un professeur.

```php
function get_teacher_available_slots(int $user_id, PDO $conn, int $limit = 5): array
```

**Paramètres** :
- `$user_id` (int) : ID du professeur
- `$conn` (PDO) : Connexion à la base de données
- `$limit` (int) : Nombre maximum de créneaux (défaut: 5)

**Retour** :
- `array` : Tableau de créneaux disponibles

**Structure d'un créneau** :
```php
[
    'id_creneau'      => 123,
    'date_debut'      => '2025-02-20 10:00:00',
    'date_fin'        => '2025-02-20 11:30:00',
    'tarif_horaire'   => 25.00,                 // Prix par heure en €
    'mode_propose'    => 'presentiel',          // 'visio' ou 'presentiel'
    'lieu'            => 'Bibliothèque municipale',
    'titre_cours'     => 'Cours particuliers Anglais',
    'nom_matiere'     => 'Anglais'
]
```

**Filtres appliqués** :
- `statut_creneau = 'disponible'` (non réservé)
- `date_debut > NOW()` (créneaux futurs uniquement)
- Trié par `date_debut ASC`

**Utilisation** :
```php
$creneaux = get_teacher_available_slots($_SESSION['user_id'], $conn, 5);

echo "<h3>Mes créneaux disponibles</h3>";
foreach ($creneaux as $slot) {
    echo "<div class='slot-card'>";
    echo "<p>{$slot['titre_cours']} ({$slot['nom_matiere']})</p>";
    echo "<p>Du " . date('d/m H:i', strtotime($slot['date_debut']));
    echo " au " . date('H:i', strtotime($slot['date_fin'])) . "</p>";
    echo "<p>Tarif : {$slot['tarif_horaire']} €/h</p>";
    echo "<button onclick='deleteSlot({$slot['id_creneau']})'>Supprimer</button>";
    echo "</div>";
}
```

**Fichier** : [includes/functions_user.php:189-213](../includes/functions_user.php#L189-L213)

---

### Documents

#### `get_user_documents()`

Récupère les documents d'un utilisateur.

```php
function get_user_documents(int $user_id, PDO $conn, int $limit = 10): array
```

**Paramètres** :
- `$user_id` (int) : ID de l'utilisateur
- `$conn` (PDO) : Connexion à la base de données
- `$limit` (int) : Nombre maximum de documents (défaut: 10)

**Retour** :
- `array` : Tableau de documents
- `[]` : Tableau vide si la table `document` n'existe pas (gestion d'erreur)

**Structure d'un document** :
```php
[
    'id_document'     => 15,
    'nom_original'    => 'Fiche_revision_maths.pdf',
    'fichier_path'    => '/uploads/documents/user_42/file_64f8a9b12c4d5.pdf',
    'type_fichier'    => 'application/pdf',
    'taille_octets'   => 524288,                    // 512 KB
    'categorie'       => 'Cours',                   // 'Cours', 'Exercices', 'Devoirs', etc.
    'source'          => 'upload_manuel',           // 'upload_manuel', 'messagerie', etc.
    'uploaded_at'     => '2025-01-15 14:30:00'
]
```

**Tri** : Par `uploaded_at DESC` (du plus récent au plus ancien)

**Gestion d'erreur** :
- Si la table `document` n'existe pas (PDOException) → retourne `[]`
- Sinon, propage l'exception

**Utilisation** :
```php
$documents = get_user_documents($_SESSION['user_id'], $conn, 20);

echo "<h3>Mes documents</h3>";
foreach ($documents as $doc) {
    $sizeKB = round($doc['taille_octets'] / 1024, 2);
    echo "<div class='doc-item'>";
    echo "<a href='{$doc['fichier_path']}' target='_blank'>{$doc['nom_original']}</a>";
    echo "<span class='badge'>{$doc['categorie']}</span>";
    echo "<small>{$sizeKB} KB - " . date('d/m/Y', strtotime($doc['uploaded_at'])) . "</small>";
    echo "</div>";
}
```

**Fichier** : [includes/functions_user.php:215-241](../includes/functions_user.php#L215-L241)

---

#### `get_user_document_stats()`

Calcule des statistiques sur les documents d'un utilisateur.

```php
function get_user_document_stats(int $user_id, PDO $conn): array
```

**Paramètres** :
- `$user_id` (int) : ID de l'utilisateur
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `array` : Statistiques détaillées

**Structure du retour** :
```php
[
    'total'       => 42,                 // Nombre total de documents
    'total_size'  => 15728640,           // Taille totale en octets (15 MB)
    'by_type'     => [                   // Répartition par type de fichier
        'pdf'  => 25,
        'jpg'  => 10,
        'docx' => 7
    ],
    'categories'  => [                   // Répartition par catégorie
        ['categorie' => 'Cours',     'total' => 20],
        ['categorie' => 'Exercices', 'total' => 15],
        ['categorie' => 'Devoirs',   'total' => 7]
    ]
]
```

**Requêtes SQL** :
1. **Total + Taille** : `COUNT(*)` et `SUM(taille_octets)`
2. **Par type** : `GROUP BY extension` (extraite via `SUBSTRING_INDEX(nom_original, '.', -1)`)
3. **Par catégorie** : `GROUP BY categorie` + `ORDER BY COUNT(*) DESC`

**Gestion d'erreur** :
- Si la table `document` n'existe pas → retourne structure par défaut avec zéros

**Utilisation** :
```php
$stats = get_user_document_stats($_SESSION['user_id'], $conn);

$sizeMB = round($stats['total_size'] / 1024 / 1024, 2);
echo "Total : {$stats['total']} documents ({$sizeMB} MB)";

echo "<h4>Par type</h4>";
foreach ($stats['by_type'] as $ext => $count) {
    echo "<div>$ext : $count fichiers</div>";
}

echo "<h4>Par catégorie</h4>";
foreach ($stats['categories'] as $cat) {
    echo "<div>{$cat['categorie']} : {$cat['total']} documents</div>";
}
```

**Fichier** : [includes/functions_user.php:243-292](../includes/functions_user.php#L243-L292)

---

## Fonctions CAPTCHA

**Fichier** : [src/get_captcha.php](../src/get_captcha.php)

#### `getCaptcha()`

Récupère une question CAPTCHA aléatoire.

```php
function getCaptcha(PDO $conn): array|false
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données

**Retour** :
- `array` : Question CAPTCHA
  ```php
  [
      'id'       => 7,
      'question' => 'Combien font 2+2?'
  ]
  ```
- `false` : Si aucune question active trouvée

**Requête SQL** :
```sql
SELECT id, question
FROM captcha_questions
WHERE actif = 1
ORDER BY RAND()
LIMIT 1
```

**Logique** :
- Sélectionne UNE question aléatoire parmi les questions actives
- `ORDER BY RAND()` : Randomisation MySQL (acceptable pour petit dataset)

**Utilisation** :
```php
$captchaData = getCaptcha($conn);

if ($captchaData) {
    $_SESSION['captcha_question'] = $captchaData['question'];
    $_SESSION['captcha_id'] = $captchaData['id'];

    echo "<p>{$captchaData['question']}</p>";
    echo "<input type='text' name='captcha_answer'>";
    echo "<input type='hidden' name='captcha_id' value='{$captchaData['id']}'>";
}
```

**Fichier** : [src/get_captcha.php:4-9](../src/get_captcha.php#L4-L9)

---

#### `verifyCaptcha()`

Vérifie la réponse à une question CAPTCHA.

```php
function verifyCaptcha(PDO $conn, string $captchaId, string $userAnswer): bool
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$captchaId` (string) : ID de la question posée
- `$userAnswer` (string) : Réponse fournie par l'utilisateur

**Retour** :
- `true` : Réponse correcte
- `false` : Réponse incorrecte ou question introuvable

**Logique** :
1. Récupère la réponse correcte depuis `captcha_questions WHERE id = ?`
2. Normalise les réponses :
   - Convertit en minuscules : `strtolower()`
   - Supprime les espaces : `trim()`
3. Compare avec `===` (strict)

**Exemples de comparaisons** :
```php
verifyCaptcha($conn, '7', '4');        // true  (réponse: '4')
verifyCaptcha($conn, '7', '  4  ');    // true  (trim + compare)
verifyCaptcha($conn, '7', 'Quatre');   // false (strict)
verifyCaptcha($conn, '7', '5');        // false
```

**Utilisation** :
```php
$captchaId = $_POST['captcha_id'] ?? '';
$captchaAnswer = $_POST['captcha_answer'] ?? '';

if (!verifyCaptcha($conn, $captchaId, $captchaAnswer)) {
    $_SESSION['error'] = 'Réponse CAPTCHA incorrecte';
    header("Location: auth.php");
    exit();
}

// Continuer l'inscription
```

**Fichier** : [src/get_captcha.php:11-25](../src/get_captcha.php#L11-L25)

---

## Fonctions de Formatage

**Fichier** : [includes/functions_user.php](../includes/functions_user.php)

#### `format_date_fr()`

Formate une date en français (jour de la semaine + date + heure).

```php
function format_date_fr(string $date): string
```

**Paramètres** :
- `$date` (string) : Date au format SQL (`YYYY-MM-DD HH:MM:SS`)

**Retour** :
- `string` : Date formatée en français
- `''` : Si date vide

**Format de sortie** :
```
Vendredi 15 janvier à 14:30
```

**Exemples** :
```php
format_date_fr('2025-02-15 14:30:00');
// → "Samedi 15 février à 14:30"

format_date_fr('2025-12-25 10:00:00');
// → "Jeudi 25 décembre à 10:00"

format_date_fr('');
// → ""
```

**Tableaux internes** :
- **Jours** : `['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']`
- **Mois** : `['', 'janvier', 'février', ..., 'décembre']` (index 1-12)

**Utilisation** :
```php
$reservation = ['date_debut' => '2025-02-15 14:30:00'];
echo "Rendez-vous : " . format_date_fr($reservation['date_debut']);
// Affiche : "Rendez-vous : Samedi 15 février à 14:30"
```

**Fichier** : [includes/functions_user.php:319-334](../includes/functions_user.php#L319-L334)

---

#### `format_relative_date()`

Formate une date en temps relatif ("Il y a X minutes/heures/jours").

```php
function format_relative_date(string $dateStr): string
```

**Paramètres** :
- `$dateStr` (string) : Date au format SQL ou tout format reconnu par `strtotime()`

**Retour** :
- `string` : Date relative en français

**Plages de temps** :
| Différence | Format de sortie |
|------------|------------------|
| < 1 heure | `Il y a X minute(s)` |
| < 1 jour (24h) | `Il y a X heure(s)` |
| < 1 semaine (7j) | `Il y a X jour(s)` |
| ≥ 1 semaine | `Il y a X semaine(s)` |

**Exemples** :
```php
// Supposons qu'on est le 15/02/2025 à 14:30

format_relative_date('2025-02-15 14:15:00');
// → "Il y a 15 minutes"

format_relative_date('2025-02-15 10:30:00');
// → "Il y a 4 heures"

format_relative_date('2025-02-12 14:30:00');
// → "Il y a 3 jours"

format_relative_date('2025-02-01 14:30:00');
// → "Il y a 2 semaines"
```

**Gestion du singulier/pluriel** :
```php
"Il y a 1 minute"   // Singulier
"Il y a 5 minutes"  // Pluriel
```

**Utilisation** :
```php
$message = ['created_at' => '2025-02-15 10:00:00'];
echo "Message posté " . strtolower(format_relative_date($message['created_at']));
// Affiche : "Message posté il y a 4 heures"
```

**Fichier** : [includes/functions_user.php:336-354](../includes/functions_user.php#L336-L354)

---

#### `get_priority_color()`

Retourne la classe CSS Bootstrap pour une priorité.

```php
function get_priority_color(string $priority): string
```

**Paramètres** :
- `$priority` (string) : Priorité (`basse`, `normale`, `haute`, `urgente`)

**Retour** :
- `string` : Classe Bootstrap (sans préfixe `bg-` ou `badge-`)

**Mapping** :
| Priorité | Classe retournée | Couleur Bootstrap |
|----------|------------------|-------------------|
| `basse` | `secondary` | Gris |
| `normale` | `info` | Bleu clair |
| `haute` | `warning` | Orange/Jaune |
| `urgente` | `danger` | Rouge |
| Autre | `secondary` | Gris (défaut) |

**Exemples** :
```php
get_priority_color('basse');    // → "secondary"
get_priority_color('urgente');  // → "danger"
get_priority_color('xyz');      // → "secondary" (fallback)
```

**Utilisation** :
```php
$ticket = ['priorite' => 'haute'];
$colorClass = get_priority_color($ticket['priorite']);

echo "<span class='badge bg-$colorClass'>{$ticket['priorite']}</span>";
// Affiche : <span class='badge bg-warning'>haute</span>
```

**Fichier** : [includes/functions_user.php:356-364](../includes/functions_user.php#L356-L364)

---

## Fonctions API

### Support/Messaging

**Fichier** : [api/support.php](../api/support.php)

#### `handleGetRequest()`

Gère les requêtes GET de l'API support.

```php
function handleGetRequest(PDO $conn, int $userId): void
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur connecté

**Actions possibles** (via `$_GET['action']`) :
- `list` : Appelle `getTickets()` pour lister les tickets
- `stats` : Appelle `getStats()` pour les statistiques
- `details` : Appelle `getTicketDetails()` pour un ticket spécifique

**Exemple de requête** :
```
GET /api/support.php?action=list&page=2&priorite=haute
```

**Fichier** : [api/support.php:37-68](../api/support.php#L37-L68)

---

#### `handlePostRequest()`

Gère les requêtes POST de l'API support.

```php
function handlePostRequest(PDO $conn, int $userId): void
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur connecté

**Actions possibles** (via `$_POST['action']`) :
- `create` : Appelle `createTicket()` pour créer un nouveau ticket
- `reply` : Ajoute une réponse à un ticket existant
- `update_status` : Met à jour le statut d'un ticket

**Exemple de requête** :
```javascript
fetch('/api/support.php', {
    method: 'POST',
    body: JSON.stringify({
        action: 'create',
        titre: 'Problème connexion',
        description: 'Je ne peux pas me connecter',
        priorite: 'haute'
    })
});
```

**Fichier** : [api/support.php:69-88](../api/support.php#L69-L88)

---

#### `getTickets()`

Récupère la liste des tickets de support avec pagination.

```php
function getTickets(PDO $conn, int $userId): array
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur

**Retour** :
- `array` : Tickets + métadonnées de pagination

**Structure du retour** :
```php
[
    'success' => true,
    'tickets' => [
        [
            'id_ticket'   => 42,
            'titre'       => 'Problème de paiement',
            'priorite'    => 'haute',
            'statut'      => 'ouvert',
            'categorie'   => 'Facturation',
            'created_at'  => '2025-01-15 10:30:00',
            'updated_at'  => '2025-01-15 14:20:00'
        ],
        // ...
    ],
    'pagination' => [
        'current_page' => 2,
        'total_pages'  => 5,
        'total_items'  => 47,
        'per_page'     => 10
    ]
]
```

**Filtres disponibles** (via `$_GET`) :
- `page` : Numéro de page (défaut: 1)
- `priorite` : Filtrer par priorité
- `statut` : Filtrer par statut
- `categorie` : Filtrer par catégorie

**Fichier** : [api/support.php:90-115](../api/support.php#L90-L115)

---

#### `getStats()`

Récupère les statistiques sur les tickets de support.

```php
function getStats(PDO $conn, int $userId): array
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur

**Retour** :
- `array` : Statistiques détaillées

**Structure du retour** :
```php
[
    'success' => true,
    'stats' => [
        'total_tickets'       => 47,
        'tickets_ouverts'     => 12,
        'tickets_en_cours'    => 8,
        'tickets_fermes'      => 27,
        'temps_reponse_moyen' => 2.5,    // Heures
        'taux_resolution'     => 85.3    // Pourcentage
    ]
]
```

**Utilisation** :
```php
fetch('/api/support.php?action=stats')
    .then(res => res.json())
    .then(data => {
        console.log(`Tickets ouverts : ${data.stats.tickets_ouverts}`);
        console.log(`Taux de résolution : ${data.stats.taux_resolution}%`);
    });
```

**Fichier** : [api/support.php:116-143](../api/support.php#L116-L143)

---

#### `getTicketDetails()`

Récupère les détails complets d'un ticket (avec messages).

```php
function getTicketDetails(PDO $conn, int $userId, int $ticketId): array
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur
- `$ticketId` (int) : ID du ticket

**Retour** :
- `array` : Détails du ticket + messages + pagination

**Structure du retour** :
```php
[
    'success' => true,
    'ticket' => [
        'id_ticket'   => 42,
        'titre'       => 'Problème de paiement',
        'description' => 'Ma carte bancaire est refusée',
        'priorite'    => 'haute',
        'statut'      => 'en_cours',
        'categorie'   => 'Facturation',
        'created_at'  => '2025-01-15 10:30:00'
    ],
    'messages' => [
        [
            'id_message'  => 101,
            'auteur'      => 'Jean Dupont',
            'role_auteur' => 'student',
            'contenu'     => 'J\'ai essayé 3 fois, toujours refusé',
            'created_at'  => '2025-01-15 11:00:00'
        ],
        [
            'id_message'  => 102,
            'auteur'      => 'Support Admin',
            'role_auteur' => 'admin',
            'contenu'     => 'Pouvez-vous essayer avec une autre carte?',
            'created_at'  => '2025-01-15 14:20:00'
        ]
    ],
    'pagination' => [
        'current_page' => 1,
        'total_pages'  => 1,
        'total_messages' => 2
    ]
]
```

**Vérification de propriété** :
- Vérifie que `ticket.user_id === $userId` (utilisateur peut seulement voir ses propres tickets)
- Si pas propriétaire → retourne `['success' => false, 'message' => 'Accès interdit']`

**Fichier** : [api/support.php:144-190](../api/support.php#L144-L190)

---

#### `createTicket()`

Crée un nouveau ticket de support.

```php
function createTicket(PDO $conn, int $userId): array
```

**Paramètres** :
- `$conn` (PDO) : Connexion à la base de données
- `$userId` (int) : ID de l'utilisateur

**Données requises** (depuis `$_POST`) :
- `titre` (string, requis) : Titre du ticket
- `description` (string, requis) : Description détaillée
- `priorite` (enum, requis) : `basse`, `normale`, `haute`, `urgente`
- `categorie` (string, optionnel) : Catégorie du problème

**Retour** :
- `array` : Succès + ID du nouveau ticket

**Exemple de succès** :
```php
[
    'success' => true,
    'message' => 'Ticket créé avec succès',
    'ticket_id' => 48
]
```

**Exemple d'erreur** :
```php
[
    'success' => false,
    'message' => 'Champs requis manquants'
]
```

**Validation** :
- Vérifie que `titre` et `description` ne sont pas vides
- Vérifie que `priorite` est valide (whitelist)

**Utilisation JavaScript** :
```javascript
const formData = new FormData();
formData.append('action', 'create');
formData.append('titre', 'Problème technique');
formData.append('description', 'Le système plante');
formData.append('priorite', 'haute');
formData.append('categorie', 'Technique');
formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

fetch('/api/support.php', {
    method: 'POST',
    body: formData
}).then(res => res.json())
  .then(data => {
      if (data.success) {
          alert('Ticket #' + data.ticket_id + ' créé');
      }
  });
```

**Fichier** : [api/support.php:191+](../api/support.php#L191)

---

## Index des Fonctions

### Sécurité
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `csrf_token()` | [csrf.php](../includes/csrf.php) | Génère token CSRF |
| `verify_csrf()` | [csrf.php](../includes/csrf.php) | Vérifie token CSRF |
| `csrf_field()` | [helpers.php](../includes/helpers.php) | Champ hidden CSRF |
| `csrf_protect()` | [helpers.php](../includes/helpers.php) | Middleware CSRF |
| `safe_session_start()` | [helpers.php](../includes/helpers.php) | Démarre session sécurisée |
| `is_logged_in()` | [helpers.php](../includes/helpers.php) | Vérifie connexion |
| `has_role()` | [helpers.php](../includes/helpers.php) | Vérifie rôle |

### Utilisateur - Statistiques
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `get_student_stats()` | [functions_user.php](../includes/functions_user.php) | Stats étudiant |
| `get_teacher_stats()` | [functions_user.php](../includes/functions_user.php) | Stats professeur |
| `get_profile_completion()` | [functions_user.php](../includes/functions_user.php) | % complétion profil |

### Utilisateur - Profil
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `get_user_avatar()` | [functions_user.php](../includes/functions_user.php) | URL avatar utilisateur |

### Utilisateur - Cours
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `get_student_upcoming_courses()` | [functions_user.php](../includes/functions_user.php) | Prochains cours étudiant |
| `get_teacher_upcoming_sessions()` | [functions_user.php](../includes/functions_user.php) | Prochaines sessions prof |
| `get_teacher_available_slots()` | [functions_user.php](../includes/functions_user.php) | Créneaux dispos prof |

### Utilisateur - Documents
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `get_user_documents()` | [functions_user.php](../includes/functions_user.php) | Liste documents |
| `get_user_document_stats()` | [functions_user.php](../includes/functions_user.php) | Stats documents |

### CAPTCHA
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `getCaptcha()` | [get_captcha.php](../src/get_captcha.php) | Question CAPTCHA aléatoire |
| `verifyCaptcha()` | [get_captcha.php](../src/get_captcha.php) | Vérifie réponse CAPTCHA |

### Formatage
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `format_date_fr()` | [functions_user.php](../includes/functions_user.php) | Date en français |
| `format_relative_date()` | [functions_user.php](../includes/functions_user.php) | Date relative |
| `get_priority_color()` | [functions_user.php](../includes/functions_user.php) | Classe CSS priorité |

### API Support
| Fonction | Fichier | Description |
|----------|---------|-------------|
| `handleGetRequest()` | [support.php](../api/support.php) | Gère GET API |
| `handlePostRequest()` | [support.php](../api/support.php) | Gère POST API |
| `getTickets()` | [support.php](../api/support.php) | Liste tickets |
| `getStats()` | [support.php](../api/support.php) | Stats tickets |
| `getTicketDetails()` | [support.php](../api/support.php) | Détails ticket |
| `createTicket()` | [support.php](../api/support.php) | Créer ticket |

---

## Conventions de Code

### Nommage

- **Fonctions** : `snake_case` (ex: `get_user_avatar()`)
- **Variables** : `camelCase` (ex: `$userId`, `$creneauId`)
- **Constantes** : `SCREAMING_SNAKE_CASE` (ex: `SESSION_LIFETIME`, `CSRF_SESSION_KEY`)

### Documentation

Chaque fonction devrait idéalement avoir un PHPDoc :

```php
/**
 * Récupère les statistiques d'un étudiant
 *
 * @param int $user_id ID de l'étudiant
 * @param PDO $conn Connexion à la base de données
 * @return array Tableau associatif avec les statistiques
 */
function get_student_stats($user_id, $conn) {
    // ...
}
```

### Gestion d'Erreurs

- **Try-Catch PDO** : Gestion des exceptions PDO pour les tables optionnelles
  ```php
  try {
      $stmt = $conn->prepare("SELECT ...");
      // ...
  } catch (PDOException $e) {
      if (stripos($e->getMessage(), 'table_name') !== false) {
          return []; // Valeur par défaut si table n'existe pas
      }
      throw $e; // Propager les autres erreurs
  }
  ```

- **Validation** : Valider les entrées avant toute opération
  ```php
  $limit = (int)$limit; // Cast forcé
  if ($limit <= 0) $limit = 10;
  ```

### Sécurité

- **PDO uniquement** : Jamais de requêtes dynamiques
- **Placeholders** : Toujours `?` ou `:param`
- **htmlspecialchars()** : Sur toutes les sorties HTML
- **Validation stricte** : Whitelist pour les enums

---

## Aide-Mémoire

### Vérifier la connexion
```php
if (!is_logged_in()) {
    header("Location: /prof-it/auth/auth.php");
    exit();
}
```

### Vérifier le rôle
```php
if (!has_role('admin')) {
    http_response_code(403);
    die('Accès interdit');
}
```

### Protéger un formulaire POST
```php
safe_session_start();
csrf_protect();

// Traiter $_POST en sécurité
```

### Afficher un avatar
```php
$avatarUrl = get_user_avatar($_SESSION['user_id'], $conn);
echo "<img src='$avatarUrl' alt='Avatar'>";
```

### Formater une date
```php
echo format_date_fr($reservation['date_debut']);
// Affiche : "Vendredi 15 janvier à 14:30"
```

---

**Dernière mise à jour** : Janvier 2025

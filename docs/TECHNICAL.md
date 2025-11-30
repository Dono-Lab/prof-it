# Documentation Technique Prof-IT

## 1. Sécurité

La sécurité est une priorité centrale du projet.

### Authentification & Mots de passe
-   Les mots de passe ne sont **jamais** stockés en clair.
-   Utilisation de l'algorithme **Argon2** ou **Bcrypt** via la fonction native PHP `password_hash()`.
-   Vérification via `password_verify()`.

### Protection CSRF (Cross-Site Request Forgery)
-   Un jeton (token) unique est généré à chaque session (`$_SESSION['csrf_token']`).
-   Ce token est inclus dans tous les formulaires (`<input type="hidden" name="csrf_token" ...>`).
-   À la soumission, le serveur vérifie que le token reçu correspond à celui de la session.

### Protection XSS (Cross-Site Scripting)
-   Toutes les données affichées dans le HTML provenant des utilisateurs sont échappées.
-   Utilisation de `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')` pour empêcher l'injection de scripts malveillants.

### Injections SQL
-   Utilisation systématique de **PDO (PHP Data Objects)**.
-   Toutes les requêtes SQL utilisent des **requêtes préparées** (`prepare()` + `execute()`).
-   Aucune concaténation directe de variables utilisateur dans les chaînes SQL.

## 2. API Interne

Le projet utilise une API interne légère pour les interactions dynamiques (AJAX).

-   **Localisation** : `admin/api/`, `student/api/`, etc.
-   **Format** : JSON.
-   **Exemple** : `GET /admin/api/live_users.php`
    -   **Réponse** :
        ```json
        {
            "success": true,
            "count": 12,
            "users": [...]
        }
        ```

## 3. Gestion des Fichiers

-   **Uploads** : Les fichiers (avatars, documents) sont stockés dans le dossier `uploads/`.
-   **Sécurité** :
    -   Vérification des extensions autorisées (.jpg, .png, .pdf, .docx).
    -   Vérification du type MIME.
    -   Renommage des fichiers pour éviter les conflits et les noms malveillants (utilisation de `uniqid()`).

## 4. Bibliothèques Externes

Le projet intègre des bibliothèques tierces via inclusion directe ou Composer (si activé).

-   **TCPDF** : Utilisé pour la génération de factures et de documents administratifs au format PDF.
    -   Emplacement : `includes/tcpdf/`
-   **PHPMailer** : Utilisé pour l'envoi fiable d'emails (confirmations, notifications).
    -   Emplacement : `includes/phpmailer/`
-   **FontAwesome** : Icônes vectorielles pour l'interface utilisateur (chargé via CDN ou local).

## 5. Algorithmes Clés

### Calcul des Statistiques
Les statistiques (dashboard) sont calculées à la volée via des requêtes SQL d'agrégation (`COUNT`, `SUM`, `AVG`) pour garantir que les données sont toujours à jour. Voir `includes/functions_user.php`.

### Gestion des Créneaux
L'algorithme de réservation vérifie la concurrence :
1.  Transaction SQL (si supporté par le moteur).
2.  Vérification que le créneau est `disponible`.
3.  Passage à `reserve`.
4.  Si échec (déjà pris), retour erreur à l'utilisateur.

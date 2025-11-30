# Guide de Tests - Prof-IT

Documentation complète des procédures de test manuelles et automatisées pour le projet Prof-IT.

---

## Table des Matières
- [Vue d'Ensemble](#vue-densemble)
- [Tests Manuels](#tests-manuels)
  - [Tests d'Authentification](#tests-dauthentification)
  - [Tests Étudiant](#tests-étudiant)
  - [Tests Professeur](#tests-professeur)
  - [Tests Admin](#tests-admin)
- [Tests de Sécurité](#tests-de-sécurité)
- [Tests d'API](#tests-dapi)
- [Tests de Performance](#tests-de-performance)
- [Tests de Compatibilité](#tests-de-compatibilité)
- [Checklist Pré-Déploiement](#checklist-pré-déploiement)

---

## Vue d'Ensemble

### Objectifs des Tests

| Type de Test | Objectif | Fréquence |
|--------------|----------|-----------|
| **Fonctionnels** | Vérifier que les fonctionnalités marchent comme prévu | À chaque modification |
| **Sécurité** | Détecter les vulnérabilités (CSRF, XSS, SQL injection) | Hebdomadaire |
| **Performance** | Mesurer les temps de réponse et charge | Mensuel |
| **Compatibilité** | Vérifier le fonctionnement sur différents navigateurs/OS | Avant chaque release |

### Environnements de Test

| Environnement | URL | Base de données | Objectif |
|---------------|-----|-----------------|----------|
| **Développement** | http://localhost/prof-it/ | `projet_profit_dev` | Tests rapides pendant le dev |
| **Staging** | https://staging.prof-it.fr/ | `projet_profit_staging` | Tests pré-production |
| **Production** | https://prof-it.fr/ | `projet_profit` | Environnement réel |

---

## Tests Manuels

### Tests d'Authentification

#### TC-AUTH-001 : Inscription Étudiant

**Prérequis** : Navigateur ouvert sur `/auth/auth.php`

**Étapes** :

1. ✅ Cliquer sur "S'inscrire"
   - **Attendu** : Formulaire d'inscription s'affiche

2. ✅ Remplir le formulaire :
   - Nom : `Martin`
   - Prénom : `Pierre`
   - Email : `pierre.martin.test@example.com`
   - Mot de passe : `Test1234!`
   - Téléphone : `0601020304`
   - Adresse : `10 Rue de la Paix`
   - Code postal : `75001`
   - Ville : `Paris`
   - Rôle : `Apprendre`

3. ✅ Cliquer sur "S'inscrire"
   - **Attendu** : Modal CAPTCHA s'ouvre avec une question

4. ✅ Répondre à la question CAPTCHA
   - Exemple : "Combien font 2+2?" → Répondre `4`
   - Cliquer "Valider"

5. ✅ Vérifier la redirection
   - **Attendu** : Redirection vers `/auth/auth.php` avec message "Compte créé avec succès ! Vous pouvez vous connecter."

6. ✅ Vérifier en base de données
   ```sql
   SELECT * FROM users WHERE email = 'pierre.martin.test@example.com';
   ```
   - **Attendu** : Utilisateur créé avec `role = 'student'`, `password` hashé

**Cas d'erreur à tester** :

- ❌ Email déjà utilisé → Message "Email déjà utilisé"
- ❌ Mot de passe < 6 caractères → Message "Le mot de passe doit contenir au moins 6 caractères"
- ❌ Email invalide (`test@test`) → Message "Email invalide"
- ❌ CAPTCHA incorrect → Message "La réponse de la question de sécurité est incorrecte"

---

#### TC-AUTH-002 : Connexion Étudiant

**Prérequis** : Compte étudiant existant

**Étapes** :

1. ✅ Ouvrir `/auth/auth.php`
2. ✅ Formulaire de connexion affiché par défaut
3. ✅ Saisir identifiants :
   - Email : `pierre.martin.test@example.com`
   - Mot de passe : `Test1234!`
4. ✅ Cliquer "Se connecter"
   - **Attendu** : Redirection vers `/student/student_page.php`
   - Session créée : `$_SESSION['user_id']`, `$_SESSION['role'] = 'student'`

5. ✅ Vérifier le dashboard :
   - Affichage des statistiques (cours terminés, heures, etc.)
   - Avatar affiché
   - Menu de navigation présent

6. ✅ Vérifier log de connexion :
   ```sql
   SELECT * FROM logs_connexions WHERE email = 'pierre.martin.test@example.com' ORDER BY date_heure DESC LIMIT 1;
   ```
   - **Attendu** : Entrée avec `statut = 'success'`, IP et user_agent enregistrés

**Cas d'erreur** :

- ❌ Email inexistant → "Email ou mot de passe incorrects"
- ❌ Mot de passe incorrect → "Email ou mot de passe incorrects" + log avec `statut = 'failed'`
- ❌ Compte inactif (`actif = 0`) → "Email ou mot de passe incorrects"

---

#### TC-AUTH-003 : Déconnexion

**Prérequis** : Utilisateur connecté

**Étapes** :

1. ✅ Cliquer sur le bouton "Déconnexion" (icône ou texte)
2. ✅ **Attendu** : Redirection vers `/auth/auth.php`
3. ✅ Vérifier session détruite :
   - Tenter d'accéder à `/student/student_page.php` directement
   - **Attendu** : Redirection vers `/auth/auth.php` (non authentifié)

4. ✅ Vérifier suppression session active :
   ```sql
   SELECT * FROM sessions_actives WHERE user_id = ?;
   ```
   - **Attendu** : Aucune entrée (session supprimée)

---

#### TC-AUTH-004 : Auto-Logout (Timeout)

**Prérequis** : Utilisateur connecté

**Étapes** :

1. ✅ Se connecter et accéder au dashboard
2. ✅ Ouvrir la console navigateur → Vérifier `Auto-logout initialized with timeout: 1800 seconds`
3. ⏱️ **Attendre 30 minutes sans activité** (ou modifier `auto_logout.js` pour test rapide : `timeout = 10 * 1000` → 10 secondes)
4. ✅ **Attendu** : Redirection automatique vers `/auth/logout.php?timeout=1`
5. ✅ Message affiché : "Session expirée par inactivité"

**Test de réinitialisation** :

1. ✅ Se connecter
2. ✅ Attendre 25 minutes
3. ✅ Bouger la souris / Cliquer quelque part
4. ✅ Attendre 25 minutes supplémentaires
5. ✅ **Attendu** : Pas de déconnexion (timer réinitialisé)

---

### Tests Étudiant

#### TC-STU-001 : Recherche de Professeur

**Prérequis** : Connecté en tant qu'étudiant

**Étapes** :

1. ✅ Accéder à `/student/find_prof.php`
2. ✅ Vérifier affichage :
   - Carte interactive (Leaflet.js) chargée
   - Liste des professeurs affichée
   - Filtres (matière, niveau, mode) visibles

3. ✅ Filtrer par matière :
   - Sélectionner "Mathématiques" dans le dropdown
   - **Attendu** : Seuls les professeurs enseignant les maths sont affichés

4. ✅ Cliquer sur un professeur :
   - **Attendu** : Affichage de ses créneaux disponibles

5. ✅ Vérifier carte :
   - Marqueurs des professeurs géolocalisés affichés
   - Popup au clic sur un marqueur avec nom, matières, note

---

#### TC-STU-002 : Réservation de Créneau

**Prérequis** : Connecté en tant qu'étudiant, professeur avec créneaux disponibles

**Étapes** :

1. ✅ Accéder à `/student/find_prof.php`
2. ✅ Sélectionner un professeur
3. ✅ Cliquer sur "Réserver" pour un créneau disponible
4. ✅ **Attendu** : Modal de confirmation ou soumission directe

5. ✅ Confirmer la réservation
   - **Attendu** : Message "Réservation effectuée ! En attente de confirmation du professeur"
   - Redirection ou mise à jour de l'affichage

6. ✅ Vérifier en base de données :
   ```sql
   SELECT * FROM reservation WHERE id_utilisateur = ? ORDER BY date_reservation DESC LIMIT 1;
   ```
   - **Attendu** : Réservation créée avec `statut_reservation = 'en_attente'`

7. ✅ Vérifier le créneau :
   ```sql
   SELECT statut_creneau FROM creneau WHERE id_creneau = ?;
   ```
   - **Attendu** : `statut_creneau = 'reserve'`

8. ✅ Vérifier notification professeur (si implémentée) :
   - Email envoyé au professeur
   - Ou notification dans son dashboard

**Cas d'erreur** :

- ❌ Créneau déjà réservé (double-booking) → "Ce créneau n'est plus disponible"
- ❌ Token CSRF invalide → "CSRF token invalide"

---

#### TC-STU-003 : Consultation des Réservations

**Prérequis** : Étudiant avec au moins 1 réservation

**Étapes** :

1. ✅ Accéder au dashboard étudiant (`/student/student_page.php`)
2. ✅ Section "Prochains cours" :
   - **Attendu** : Liste des réservations confirmées à venir
   - Affichage : Titre cours, matière, professeur, date/heure, mode

3. ✅ Accéder à `/student/reservations.php` (si existe)
4. ✅ Vérifier filtres :
   - Toutes
   - En attente
   - Confirmées
   - Terminées
   - Annulées

5. ✅ Tester le filtre "En attente" :
   - **Attendu** : Seules les réservations avec `statut_reservation = 'en_attente'` affichées

---

#### TC-STU-004 : Annulation de Réservation

**Prérequis** : Réservation existante (`en_attente` ou `confirmee`)

**Étapes** :

1. ✅ Accéder à la liste des réservations
2. ✅ Cliquer "Annuler" sur une réservation
3. ✅ **Attendu** : Modal de confirmation "Voulez-vous vraiment annuler cette réservation ?"
4. ✅ Confirmer l'annulation
   - **Attendu** : Message "Réservation annulée avec succès"
   - Statut mis à jour : `statut_reservation = 'annulee'`
   - Créneau redevient `statut_creneau = 'disponible'`

5. ✅ Vérifier en BDD :
   ```sql
   SELECT statut_reservation FROM reservation WHERE id_reservation = ?;
   -- Attendu: 'annulee'

   SELECT statut_creneau FROM creneau WHERE id_creneau = ?;
   -- Attendu: 'disponible'
   ```

---

### Tests Professeur

#### TC-TEACH-001 : Création d'Offre de Cours

**Prérequis** : Connecté en tant que professeur

**Étapes** :

1. ✅ Accéder à `/teacher/create_offer.php` (ou via dashboard)
2. ✅ Remplir le formulaire :
   - Titre : `Soutien scolaire Mathématiques - Terminale S`
   - Description : `Cours particuliers de mathématiques niveau Terminale S. Préparation au Bac.`
   - Niveau : `lycee`
   - Matières : Cocher `Mathématiques`

3. ✅ Soumettre le formulaire
   - **Attendu** : Message "Offre de cours créée avec succès"
   - Redirection vers le dashboard professeur

4. ✅ Vérifier en BDD :
   ```sql
   SELECT * FROM offre_cours WHERE id_utilisateur = ? ORDER BY created_at DESC LIMIT 1;
   -- Attendu: Offre créée

   SELECT * FROM couvrir WHERE id_offre = ?;
   -- Attendu: Lien avec matière Mathématiques
   ```

**Cas d'erreur** :

- ❌ Titre vide → "Le titre est requis"
- ❌ Aucune matière sélectionnée → "Veuillez sélectionner au moins une matière"

---

#### TC-TEACH-002 : Ajout de Créneaux

**Prérequis** : Professeur avec une offre de cours créée

**Étapes** :

1. ✅ Accéder à `/teacher/add_slots.php` (ou via dashboard)
2. ✅ Remplir le formulaire :
   - Offre : Sélectionner l'offre créée
   - Date début : `2025-03-01 14:00`
   - Date fin : `2025-03-01 15:30`
   - Tarif horaire : `25` €
   - Mode : `visio`
   - Lieu (URL) : `https://meet.google.com/abc-defg-hij`

3. ✅ Soumettre
   - **Attendu** : Message "Créneau ajouté avec succès"

4. ✅ Vérifier en BDD :
   ```sql
   SELECT * FROM creneau WHERE id_utilisateur = ? AND id_offre = ? ORDER BY created_at DESC LIMIT 1;
   -- Attendu: Créneau créé avec statut_creneau = 'disponible'
   ```

**Test de conflit** :

1. ✅ Créer un créneau : `2025-03-02 10:00 - 11:00`
2. ✅ Tenter de créer un créneau chevauchant : `2025-03-02 10:30 - 11:30`
   - **Attendu** : Erreur "Conflit de créneaux détecté"

**Validations** :

- ❌ `date_debut >= date_fin` → "La date de fin doit être après la date de début"
- ❌ `date_debut < NOW()` → "Impossible de créer un créneau dans le passé"
- ❌ `tarif_horaire <= 0` → "Le tarif doit être supérieur à zéro"

---

#### TC-TEACH-003 : Confirmation de Réservation

**Prérequis** : Réservation en attente faite par un étudiant

**Étapes** :

1. ✅ Accéder au dashboard professeur
2. ✅ Section "Réservations en attente" :
   - **Attendu** : Liste des réservations avec `statut_reservation = 'en_attente'`
   - Affichage : Nom étudiant, cours, date, mode

3. ✅ Cliquer "Confirmer" sur une réservation
4. ✅ **Attendu** : Modal de confirmation ou confirmation directe
5. ✅ Confirmer l'action
   - **Attendu** : Message "Réservation confirmée"
   - Statut mis à jour : `statut_reservation = 'confirmee'`
   - Email envoyé à l'étudiant (si implémenté)

6. ✅ Vérifier en BDD :
   ```sql
   SELECT statut_reservation, date_confirmation FROM reservation WHERE id_reservation = ?;
   -- Attendu: statut = 'confirmee', date_confirmation = NOW()
   ```

---

#### TC-TEACH-004 : Refus de Réservation

**Prérequis** : Réservation en attente

**Étapes** :

1. ✅ Cliquer "Refuser" sur une réservation en attente
2. ✅ **Attendu** : Modal "Voulez-vous vraiment refuser cette réservation ?"
3. ✅ Confirmer le refus
   - **Attendu** : Statut mis à jour : `statut_reservation = 'refusee'`
   - Créneau redevient `statut_creneau = 'disponible'`

4. ✅ Vérifier que le créneau est à nouveau réservable par d'autres étudiants

---

### Tests Admin

#### TC-ADMIN-001 : Gestion Utilisateurs - Création

**Prérequis** : Connecté en tant qu'admin

**Étapes** :

1. ✅ Accéder à `/admin/users.php`
2. ✅ Cliquer "Ajouter un utilisateur"
3. ✅ Remplir le formulaire :
   - Nom : `Dupuis`
   - Prénom : `Sophie`
   - Email : `sophie.dupuis@example.com`
   - Mot de passe : `Admin1234!`
   - Rôle : `teacher`
   - Statut : `Actif`

4. ✅ Soumettre
   - **Attendu** : Message "Utilisateur créé avec succès"
   - Utilisateur ajouté à la liste

5. ✅ Vérifier en BDD :
   ```sql
   SELECT * FROM users WHERE email = 'sophie.dupuis@example.com';
   -- Attendu: Utilisateur créé avec password hashé, role = 'teacher', actif = 1
   ```

---

#### TC-ADMIN-002 : Gestion Utilisateurs - Modification

**Prérequis** : Utilisateur existant

**Étapes** :

1. ✅ Accéder à `/admin/users.php`
2. ✅ Cliquer "Modifier" sur un utilisateur
3. ✅ Modifier le rôle : `student` → `teacher`
4. ✅ Sauvegarder
   - **Attendu** : Message "Utilisateur modifié avec succès"
   - Rôle mis à jour dans la liste

5. ✅ Vérifier en BDD :
   ```sql
   SELECT role FROM users WHERE id = ?;
   -- Attendu: role = 'teacher'
   ```

---

#### TC-ADMIN-003 : Gestion Utilisateurs - Désactivation

**Prérequis** : Utilisateur actif

**Étapes** :

1. ✅ Cliquer "Désactiver" sur un utilisateur actif
2. ✅ **Attendu** : Modal de confirmation
3. ✅ Confirmer
   - **Attendu** : Statut mis à jour : `actif = 0`
   - Badge "Inactif" affiché

4. ✅ Tenter de se connecter avec ce compte :
   - **Attendu** : Échec de connexion "Email ou mot de passe incorrects"
   - Log créé avec `statut = 'failed'`, `raison_echec = 'compte inactif'`

5. ✅ Vérifier session forcée (si utilisateur était connecté) :
   ```sql
   SELECT * FROM sessions_actives WHERE user_id = ?;
   -- Attendu: Session supprimée (force logout)
   ```

---

#### TC-ADMIN-004 : Consultation des Logs

**Prérequis** : Admin connecté

**Étapes** :

1. ✅ Accéder à `/admin/logs.php`
2. ✅ Onglet "Logs de connexions" :
   - **Attendu** : Tableau avec colonnes (Date, Utilisateur, Email, IP, User Agent, Statut)
   - Données triées par date décroissante

3. ✅ Filtrer par statut "Échecs" :
   - Sélectionner `statut = 'failed'` dans le filtre
   - **Attendu** : Seules les tentatives échouées affichées

4. ✅ Rechercher une IP spécifique :
   - Saisir une IP dans le champ de recherche
   - **Attendu** : Logs filtrés par cette IP

5. ✅ Vérifier mise en évidence des IPs suspectes :
   - IPs avec > 5 tentatives échouées en surbrillance rouge

6. ✅ Exporter en CSV (si implémenté) :
   - Cliquer "Exporter"
   - **Attendu** : Fichier `logs_connexions_2025-01-30.csv` téléchargé

---

#### TC-ADMIN-005 : Dashboard - Statistiques

**Prérequis** : Admin connecté, données en base

**Étapes** :

1. ✅ Accéder à `/admin/dashboard.php`
2. ✅ Vérifier les KPIs affichés :
   - Utilisateurs totaux : Compte correct
   - Réservations actives : Compte `statut IN ('en_attente', 'confirmee')`
   - Revenus du mois : Somme des `montant_ttc` des réservations de ce mois
   - Tickets support ouverts : Compte `statut = 'ouvert'`

3. ✅ Vérifier les graphiques Chart.js :
   - Graphique "Inscriptions par mois" affiché correctement
   - Graphique "Réservations par matière" (bar chart)
   - Graphique "Répartition des rôles" (pie chart)

4. ✅ Tester le responsive :
   - Réduire la fenêtre navigateur
   - **Attendu** : Graphiques s'adaptent (responsive: true)

---

## Tests de Sécurité

### SEC-001 : Test CSRF

**Objectif** : Vérifier que toutes les actions POST sont protégées par token CSRF

**Méthode** :

1. ✅ Ouvrir les DevTools → Onglet Réseau
2. ✅ Soumettre un formulaire légitime (ex: inscription)
3. ✅ Copier la requête POST (clic droit → Copy as cURL)
4. ✅ Supprimer le paramètre `csrf_token` de la requête
5. ✅ Rejouer la requête modifiée
   - **Attendu** : Réponse "CSRF token invalide" ou `die('CSRF token invalide')`

**Pages à tester** :
- `/auth/login_register.php` (login + register)
- `/api/appointments.php` (toutes actions)
- `/api/messaging.php`
- `/admin/api/users.php`

---

### SEC-002 : Test Injection SQL

**Objectif** : Vérifier que les requêtes préparées bloquent les injections SQL

**Méthode** :

1. ✅ Tenter une injection classique dans le login :
   - Email : `admin@prof-it.fr' OR '1'='1' --`
   - Mot de passe : `anything`
   - **Attendu** : Échec de connexion (PDO ne trouve aucun utilisateur avec cet email exact)

2. ✅ Tenter une injection dans la recherche :
   - Paramètre GET : `/student/find_prof.php?matiere=1' OR '1'='1`
   - **Attendu** : Requête préparée traite `"1' OR '1'='1"` comme une valeur littérale, pas comme du SQL

3. ✅ Vérifier logs d'erreurs :
   - Aucune erreur SQL ne doit apparaître (signe que l'injection a échoué proprement)

**Résultat attendu** : ✅ Aucune injection SQL réussie (100% requêtes préparées avec PDO)

---

### SEC-003 : Test XSS

**Objectif** : Vérifier que les sorties sont échappées avec `htmlspecialchars()`

**Méthode** :

1. ✅ Créer un utilisateur avec nom contenant du JavaScript :
   - Nom : `<script>alert('XSS')</script>`
   - Prénom : `Test`

2. ✅ Se connecter et accéder au dashboard
3. ✅ Vérifier l'affichage du nom :
   - **Attendu** : Code HTML affiché comme texte brut (échappé)
   - Source HTML : `&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;`
   - **Pas d'exécution** de JavaScript

4. ✅ Tester dans les messages :
   - Envoyer un message : `<img src=x onerror=alert('XSS')>`
   - **Attendu** : Message affiché comme texte, pas de popup

**Résultat attendu** : ✅ Aucune exécution de scripts malveillants

---

### SEC-004 : Test Brute Force

**Objectif** : Vérifier le logging des tentatives échouées (rate limiting pas implémenté)

**Méthode** :

1. ✅ Tenter 10 connexions avec un mauvais mot de passe :
   - Email : `admin@prof-it.fr`
   - Mot de passe : `wrong1`, `wrong2`, ..., `wrong10`

2. ✅ Vérifier les logs :
   ```sql
   SELECT COUNT(*) FROM logs_connexions
   WHERE email = 'admin@prof-it.fr'
     AND statut = 'failed'
     AND date_heure > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
   -- Attendu: 10 entrées
   ```

3. ✅ ⚠️ **Note** : Actuellement, aucun rate limiting n'est implémenté
   - Les 10 tentatives réussissent (retournent toutes "Email ou mot de passe incorrects")
   - **Recommandation** : Implémenter un blocage après 5 échecs

---

### SEC-005 : Test Session Fixation

**Objectif** : Vérifier que `session_regenerate_id()` est appelé après authentification

**Méthode** :

1. ✅ Ouvrir la page de connexion
2. ✅ Ouvrir les DevTools → Application → Cookies
3. ✅ Noter l'ID de session actuel (cookie `PHPSESSID`)
   - Exemple : `abc123def456`

4. ✅ Se connecter avec des identifiants valides
5. ✅ Vérifier le cookie `PHPSESSID` après connexion
   - **Attendu** : ID de session différent (régénéré)
   - Exemple : `xyz789ghi012`

**Résultat** : ✅ Session ID régénéré après login (protection contre session fixation)

---

## Tests d'API

### API-001 : Test Endpoint Appointments

**Endpoint** : `POST /api/appointments.php?action=book_slot`

**Payload** :
```json
{
    "csrf_token": "...",
    "creneau_id": 123
}
```

**Tests à effectuer** :

1. ✅ **Cas nominal** :
   - Créneau disponible
   - Utilisateur authentifié
   - Token CSRF valide
   - **Attendu** : `{"success": true, "reservation_id": 42}`

2. ❌ **Créneau inexistant** :
   - `creneau_id`: 99999
   - **Attendu** : `{"success": false, "message": "Créneau introuvable"}`

3. ❌ **Créneau déjà réservé** :
   - `creneau_id` avec `statut_creneau = 'reserve'`
   - **Attendu** : `{"success": false, "message": "Ce créneau n'est plus disponible"}`

4. ❌ **Utilisateur non authentifié** :
   - Aucune session active
   - **Attendu** : `{"success": false, "message": "Non authentifié"}`

5. ❌ **Token CSRF invalide** :
   - `csrf_token`: `fake_token`
   - **Attendu** : `{"success": false, "message": "Token CSRF invalide"}`

---

### API-002 : Test Endpoint Support

**Endpoint** : `POST /api/support.php?action=create`

**Payload** :
```json
{
    "csrf_token": "...",
    "titre": "Problème paiement",
    "description": "Ma carte bancaire est refusée",
    "priorite": "haute",
    "categorie": "Facturation"
}
```

**Tests** :

1. ✅ **Création réussie** :
   - **Attendu** : `{"success": true, "ticket_id": 48}`

2. ❌ **Titre vide** :
   - `titre`: `""`
   - **Attendu** : `{"success": false, "message": "Le titre est requis"}`

3. ❌ **Priorité invalide** :
   - `priorite`: `"super_urgente"`
   - **Attendu** : `{"success": false, "message": "Priorité invalide"}`

---

## Tests de Performance

### PERF-001 : Temps de Chargement des Pages

**Objectif** : Vérifier que les pages se chargent en < 2 secondes

**Méthode** :

1. ✅ Ouvrir Chrome DevTools → Onglet Performance
2. ✅ Enregistrer le chargement de la page
3. ✅ Analyser les métriques :
   - **FCP (First Contentful Paint)** : < 1.5s
   - **LCP (Largest Contentful Paint)** : < 2.5s
   - **TTI (Time to Interactive)** : < 3s

**Pages à tester** :
- `/public/home.php`
- `/student/student_page.php`
- `/teacher/teacher_page.php`
- `/admin/dashboard.php`

**Résultats attendus** :
| Page | FCP | LCP | TTI | Statut |
|------|-----|-----|-----|--------|
| home.php | 0.8s | 1.5s | 2.0s | ✅ |
| student_page.php | 1.2s | 2.0s | 2.8s | ✅ |
| admin/dashboard.php | 1.5s | 2.8s | 3.5s | ⚠️ Optimiser |

---

### PERF-002 : Performance des Requêtes SQL

**Objectif** : Identifier les requêtes lentes (> 100ms)

**Méthode** :

1. ✅ Activer le slow query log MySQL :
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 0.1; -- 100ms
   SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';
   ```

2. ✅ Effectuer des actions typiques (connexion, réservation, recherche professeurs)
3. ✅ Analyser le fichier de log :
   ```bash
   cat /var/log/mysql/slow-query.log
   ```

4. ✅ Identifier les requêtes lentes :
   - Requêtes sans index
   - Requêtes avec trop de JOINs
   - N+1 queries

**Actions correctives** :
- Ajouter des index sur les colonnes fréquemment filtrées
- Utiliser `EXPLAIN` pour analyser les requêtes

---

## Tests de Compatibilité

### COMP-001 : Tests Navigateurs

**Navigateurs à tester** :

| Navigateur | Version Minimale | Fonctionnalités à vérifier |
|------------|------------------|----------------------------|
| **Chrome** | 90+ | Toutes fonctionnalités |
| **Firefox** | 88+ | Toutes fonctionnalités |
| **Safari** | 14+ | Toutes fonctionnalités + Leaflet.js |
| **Edge** | 90+ | Toutes fonctionnalités |
| **Mobile Chrome** | 90+ | Responsive, touch events |
| **Mobile Safari** | 14+ | Responsive, touch events |

**Checklist par navigateur** :

- ✅ Connexion/Inscription fonctionnelle
- ✅ Dashboard affiché correctement
- ✅ Formulaires fonctionnels (validation HTML5)
- ✅ Modals Bootstrap s'ouvrent
- ✅ Graphiques Chart.js s'affichent
- ✅ Carte Leaflet.js fonctionne
- ✅ Auto-logout fonctionne
- ✅ Upload de fichiers fonctionne

---

### COMP-002 : Tests Responsive

**Résolutions à tester** :

| Appareil | Résolution | Orientation | Points de vigilance |
|----------|------------|-------------|---------------------|
| **iPhone SE** | 375x667 | Portrait | Menu hamburger, boutons accessibles |
| **iPad** | 768x1024 | Portrait | Grid 2 colonnes |
| **Desktop HD** | 1920x1080 | Landscape | Grid 4 colonnes |
| **Desktop 4K** | 3840x2160 | Landscape | Pas de pixelisation |

**Tests** :

1. ✅ Ouvrir Chrome DevTools → Mode responsive
2. ✅ Tester chaque résolution :
   - Navigation accessible
   - Texte lisible (taille de police adaptée)
   - Boutons cliquables (min 44x44px sur mobile)
   - Images ne débordent pas
   - Tableaux scrollables horizontalement sur mobile

---

## Checklist Pré-Déploiement

### ✅ Sécurité

- [ ] Credentials en variables d'environnement (.env)
- [ ] `display_errors = Off` dans php.ini
- [ ] `session.cookie_secure = 1` (HTTPS)
- [ ] Mots de passe par défaut changés
- [ ] Certificat SSL actif
- [ ] Firewall configuré (ports 443, 80)
- [ ] Headers de sécurité (CSP, X-Frame-Options, etc.)

### ✅ Base de Données

- [ ] Backup automatique configuré
- [ ] Mot de passe MySQL fort
- [ ] Utilisateur MySQL avec privilèges minimaux
- [ ] Indexes optimisés
- [ ] Données de test supprimées

### ✅ Fichiers

- [ ] Dossier `uploads/` avec permissions 0755
- [ ] `.env` dans `.gitignore`
- [ ] `.htaccess` avec protections
- [ ] Fichiers sensibles (config.php) non accessibles publiquement

### ✅ Performance

- [ ] OPcache activé
- [ ] Images optimisées (compression)
- [ ] CSS/JS minifiés
- [ ] Gzip/Brotli activé
- [ ] CDN configuré (Bootstrap, Font Awesome)

### ✅ Monitoring

- [ ] Logs d'erreurs activés (`log_errors = On`)
- [ ] Monitoring uptime (UptimeRobot, Pingdom)
- [ ] Alertes email en cas d'erreur critique
- [ ] Google Analytics ou équivalent

### ✅ Tests Finaux

- [ ] Tous les tests manuels passés
- [ ] Tests de sécurité validés (OWASP Top 10)
- [ ] Tests de performance OK (< 2s chargement)
- [ ] Tests navigateurs OK (Chrome, Firefox, Safari, Edge)
- [ ] Tests mobiles OK (iOS, Android)

---

## Outils de Test Recommandés

### Outils Gratuits

| Outil | Usage | Lien |
|-------|-------|------|
| **OWASP ZAP** | Scanner de vulnérabilités | https://www.zaproxy.org/ |
| **Lighthouse** | Performance, SEO, Accessibilité | Intégré Chrome DevTools |
| **GTmetrix** | Performance web | https://gtmetrix.com/ |
| **BrowserStack** (free tier) | Tests cross-browser | https://www.browserstack.com/ |
| **Postman** | Tests d'API | https://www.postman.com/ |

### Extensions Navigateur

- **Wappalyzer** : Détection des technologies utilisées
- **EditThisCookie** : Manipulation des cookies (tests session)
- **JSON Formatter** : Formatage des réponses API
- **Pesticide** : Visualiser les boîtes CSS (debugging layout)

---

## Rapports de Test

### Template de Rapport

```markdown
# Rapport de Test - Prof-IT

**Date** : 30/01/2025
**Testeur** : Jean Dupont
**Environnement** : Staging (https://staging.prof-it.fr/)
**Navigateur** : Chrome 120

## Tests Effectués

| ID Test | Nom | Statut | Commentaires |
|---------|-----|--------|--------------|
| TC-AUTH-001 | Inscription Étudiant | ✅ PASS | RAS |
| TC-AUTH-002 | Connexion Étudiant | ✅ PASS | RAS |
| TC-STU-002 | Réservation Créneau | ❌ FAIL | Erreur 500 lors de la réservation |
| SEC-001 | Test CSRF | ✅ PASS | Toutes requêtes protégées |

## Bugs Identifiés

### BUG-001 : Erreur 500 lors de la réservation de créneau

**Sévérité** : Critique
**Étapes de reproduction** :
1. Se connecter en tant qu'étudiant
2. Accéder à /student/find_prof.php
3. Cliquer "Réserver" sur un créneau disponible
4. → Erreur 500

**Erreur serveur** : `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'prix_fige' cannot be null`

**Solution proposée** : Récupérer `tarif_horaire` du créneau avant l'INSERT dans `reservation`

## Statistiques

- **Tests exécutés** : 25
- **Tests réussis** : 24 (96%)
- **Tests échoués** : 1 (4%)
- **Bugs critiques** : 1
- **Bugs mineurs** : 0

## Recommandations

1. Corriger BUG-001 avant déploiement en production
2. Ajouter rate limiting sur le login (SEC-004)
3. Optimiser dashboard admin (PERF-001)
```

---

**Dernière mise à jour** : Janvier 2025

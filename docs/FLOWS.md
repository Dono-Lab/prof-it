# Flux de Données - Prof-IT

Documentation exhaustive des flux de données et diagrammes de séquence du projet Prof-IT.

---

## Table des Matières
- [Flux d'Authentification](#flux-dauthentification)
  - [Inscription](#inscription)
  - [Connexion](#connexion)
  - [Déconnexion](#déconnexion)
- [Flux de Réservation](#flux-de-réservation)
  - [Recherche de Professeurs](#recherche-de-professeurs)
  - [Réservation de Créneau](#réservation-de-créneau)
  - [Confirmation/Annulation](#confirmationannulation)
- [Flux Professeur](#flux-professeur)
  - [Création d'Offre de Cours](#création-doffre-de-cours)
  - [Ajout de Créneaux](#ajout-de-créneaux)
  - [Gestion des Réservations](#gestion-des-réservations)
- [Flux de Messagerie](#flux-de-messagerie)
  - [Envoi de Message](#envoi-de-message)
  - [Réception et Lecture](#réception-et-lecture)
- [Flux de Support](#flux-de-support)
  - [Création de Ticket](#création-de-ticket)
  - [Traitement Admin](#traitement-admin)
- [Flux Admin](#flux-admin)
  - [Gestion Utilisateurs](#gestion-utilisateurs)
  - [Consultation des Logs](#consultation-des-logs)
- [Flux de Session](#flux-de-session)
  - [Timeout et Auto-Logout](#timeout-et-auto-logout)

---

## Flux d'Authentification

### Inscription

**Acteurs** : Visiteur → Système → Base de Données

```
┌─────────┐          ┌──────────────┐         ┌────────────────┐        ┌──────────┐
│Visiteur │          │  auth.php    │         │login_register  │        │   BDD    │
└────┬────┘          └──────┬───────┘         └───────┬────────┘        └────┬─────┘
     │                      │                         │                      │
     │ 1. GET /auth/auth.php│                         │                      │
     ├─────────────────────>│                         │                      │
     │                      │                         │                      │
     │                      │ 2. getCaptcha($conn)    │                      │
     │                      ├────────────────────────>│                      │
     │                      │                         │ 3. SELECT question   │
     │                      │                         │      FROM captcha    │
     │                      │                         ├─────────────────────>│
     │                      │                         │<─────────────────────┤
     │                      │<────────────────────────┤ 4. Question aléa.    │
     │                      │                         │                      │
     │ 5. Formulaire +      │                         │                      │
     │    CAPTCHA affiché   │                         │                      │
     │<─────────────────────┤                         │                      │
     │                      │                         │                      │
     │ 6. POST /login_register.php                    │                      │
     │    (nom, email, pwd, captcha_answer, csrf_token)                      │
     ├───────────────────────────────────────────────>│                      │
     │                      │                         │                      │
     │                      │      7. csrf_protect()  │                      │
     │                      │      verify_csrf($token)│                      │
     │                      │                         │                      │
     │                      │      8. verifyCaptcha() │                      │
     │                      │         ($captchaId, $answer)                  │
     │                      │                         ├─────────────────────>│
     │                      │                         │<─────────────────────┤
     │                      │                         │ 9. Réponse correcte? │
     │                      │                         │                      │
     │                      │      10. filter_var($email, VALIDATE_EMAIL)    │
     │                      │                         │                      │
     │                      │      11. SELECT email   │                      │
     │                      │          WHERE email=?  │                      │
     │                      │                         ├─────────────────────>│
     │                      │                         │<─────────────────────┤
     │                      │                         │ 12. Email existe?    │
     │                      │                         │     NON → Continuer  │
     │                      │                         │                      │
     │                      │      13. password_hash($pwd, PASSWORD_DEFAULT) │
     │                      │                         │                      │
     │                      │      14. INSERT INTO users                     │
     │                      │          (nom, email, password, role, ...)     │
     │                      │                         ├─────────────────────>│
     │                      │                         │<─────────────────────┤
     │                      │                         │ 15. ID créé          │
     │                      │                         │                      │
     │                      │      16. $_SESSION['success'] = 'Compte créé'  │
     │                      │                         │                      │
     │ 17. Redirect /auth.php?success                 │                      │
     │<───────────────────────────────────────────────┤                      │
     │                      │                         │                      │
     │ 18. Message succès   │                         │                      │
     │    "Vous pouvez vous connecter"                │                      │
     │<─────────────────────┤                         │                      │
     │                      │                         │                      │
```

**Étapes détaillées** :

1. **Affichage formulaire** : `GET /auth/auth.php`
   - Génère un token CSRF : `csrf_token()`
   - Récupère une question CAPTCHA : `getCaptcha($conn)`
   - Stocke en session : `$_SESSION['captcha_id']` et `$_SESSION['captcha_question']`

2. **Soumission formulaire** : `POST /auth/login_register.php` avec `$_POST['register']`
   - **Validation CSRF** : `csrf_protect()` → vérifie `$_POST['csrf_token']`
   - **Validation CAPTCHA** : `verifyCaptcha($conn, $captchaId, $answer)` → compare réponse
   - **Validation email** : `filter_var($email, FILTER_VALIDATE_EMAIL)`
   - **Vérification unicité** : `SELECT email WHERE email = ?` → doit être vide
   - **Hachage mot de passe** : `password_hash($password, PASSWORD_DEFAULT)`
   - **Insertion** : `INSERT INTO users (nom, prenom, email, password, role, ...)`
   - **Redirection** : `header("Location: auth.php")` avec message de succès

3. **Gestion d'erreurs** :
   - CAPTCHA incorrect → `$_SESSION['register_error']` + `$_SESSION['active_form'] = 'register'`
   - Email invalide → Message d'erreur
   - Email déjà utilisé → Message d'erreur
   - Mot de passe < 6 caractères → Message d'erreur

---

### Connexion

**Acteurs** : Utilisateur → Système → BDD

```
┌─────────┐          ┌──────────────┐         ┌────────────────┐        ┌──────────┐
│Utilisat.│          │  auth.php    │         │login_register  │        │   BDD    │
└────┬────┘          └──────┬───────┘         └───────┬────────┘        └────┬─────┘
     │                      │                         │                      │
     │ 1. POST /login_register.php                    │                      │
     │    (email, password, csrf_token)               │                      │
     ├───────────────────────────────────────────────>│                      │
     │                      │                         │                      │
     │                      │      2. csrf_protect()  │                      │
     │                      │                         │                      │
     │                      │      3. SELECT * FROM users WHERE email = ?    │
     │                      │                         ├─────────────────────>│
     │                      │                         │<─────────────────────┤
     │                      │                         │ 4. User trouvé?      │
     │                      │                         │                      │
     │                      │      5. password_verify($pwd, $user['password'])
     │                      │         ET actif = 1?   │                      │
     │                      │                         │                      │
     │                      │      6. INSERT logs_connexions                 │
     │                      │         (user_id, statut='success')            │
     │                      │                         ├─────────────────────>│
     │                      │                         │                      │
     │                      │      7. session_regenerate_id(true)            │
     │                      │                         │                      │
     │                      │      8. $_SESSION = [   │                      │
     │                      │           'user_id' => $user['id'],            │
     │                      │           'email' => $user['email'],           │
     │                      │           'role' => $user['role'], ...         │
     │                      │         ]               │                      │
     │                      │                         │                      │
     │                      │      9. if role == 'admin'                     │
     │                      │            Redirect /admin/dashboard.php       │
     │                      │         elseif role == 'teacher'               │
     │                      │            Redirect /teacher/teacher_page.php  │
     │                      │         else                                   │
     │                      │            Redirect /student/student_page.php  │
     │                      │                         │                      │
     │ 10. Redirect vers dashboard selon rôle         │                      │
     │<───────────────────────────────────────────────┤                      │
     │                      │                         │                      │
```

**Étapes détaillées** :

1. **Soumission** : `POST /auth/login_register.php` avec `$_POST['login']`
2. **Protection CSRF** : Vérification du token
3. **Recherche utilisateur** : `SELECT * FROM users WHERE email = ?`
4. **Vérification mot de passe** : `password_verify($password, $user['password'])`
5. **Vérification statut actif** : `(int)$user['actif'] === 1`
6. **Logging** : Insertion dans `logs_connexions` :
   - Succès : `statut = 'success'`, `user_id`, `ip_address`, `user_agent`
   - Échec : `statut = 'failed'`, `raison_echec`, `email` (sans user_id)
7. **Régénération session ID** : `session_regenerate_id(true)` → Prévention session fixation
8. **Stockage session** : Variables `user_id`, `name`, `prenom`, `email`, `role`, `avatar_url`
9. **Redirection selon rôle** :
   - `admin` → `/admin/dashboard.php`
   - `teacher` → `/teacher/teacher_page.php`
   - `student` → `/student/student_page.php`

**Gestion d'erreurs** :
- Identifiants incorrects ou compte inactif → Log échec + message "Email ou mot de passe incorrects"
- Pas de différenciation email/mot de passe → Évite l'énumération d'emails

---

### Déconnexion

**Acteurs** : Utilisateur → Système

```
┌─────────┐          ┌──────────────┐         ┌──────────┐
│Utilisat.│          │  logout.php  │         │   BDD    │
└────┬────┘          └──────┬───────┘         └────┬─────┘
     │                      │                      │
     │ 1. GET /auth/logout.php                     │
     ├─────────────────────>│                      │
     │                      │                      │
     │                      │ 2. session_start()   │
     │                      │                      │
     │                      │ 3. DELETE FROM sessions_actives
     │                      │    WHERE session_id = ?
     │                      ├─────────────────────>│
     │                      │                      │
     │                      │ 4. $_SESSION = []    │
     │                      │    (vide toutes vars)│
     │                      │                      │
     │                      │ 5. session_destroy() │
     │                      │                      │
     │                      │ 6. setcookie(session_name(), '', time()-3600)
     │                      │    (supprime cookie) │
     │                      │                      │
     │ 7. Redirect /auth/auth.php                  │
     │<─────────────────────┤                      │
     │                      │                      │
```

**Étapes détaillées** :
1. **Démarrage session** : `session_start()`
2. **Suppression session active** : `DELETE FROM sessions_actives WHERE session_id = ?`
3. **Vidage variables** : `$_SESSION = []`
4. **Destruction session** : `session_destroy()`
5. **Suppression cookie** : `setcookie(session_name(), '', time()-3600, '/')`
6. **Redirection** : `header("Location: /prof-it/auth/auth.php")`

**Cas spécial - Auto-logout (timeout)** :
- Paramètre `?timeout=1` dans l'URL
- Affiche un message "Session expirée par inactivité"

---

## Flux de Réservation

### Recherche de Professeurs

**Acteurs** : Étudiant → Système → BDD

```
┌─────────┐          ┌──────────────┐         ┌──────────┐
│Étudiant │          │ student/     │         │   BDD    │
│         │          │ find_prof.php│         │          │
└────┬────┘          └──────┬───────┘         └────┬─────┘
     │                      │                      │
     │ 1. GET /student/find_prof.php               │
     ├─────────────────────>│                      │
     │                      │                      │
     │                      │ 2. SELECT DISTINCT users
     │                      │    JOIN offre_cours  │
     │                      │    WHERE role='teacher'
     │                      │    AND actif=1       │
     │                      ├─────────────────────>│
     │                      │<─────────────────────┤
     │                      │ 3. Liste professeurs │
     │                      │                      │
     │ 4. Affiche carte     │                      │
     │    avec Leaflet.js   │                      │
     │<─────────────────────┤                      │
     │                      │                      │
     │ 5. Clic sur prof /   │                      │
     │    Filtre par matière│                      │
     ├─────────────────────>│                      │
     │                      │                      │
     │                      │ 6. SELECT offre_cours│
     │                      │    JOIN couvrir      │
     │                      │    WHERE id_utilisateur=?
     │                      │    AND id_matiere=?  │
     │                      ├─────────────────────>│
     │                      │<─────────────────────┤
     │                      │ 7. Offres filtrées   │
     │                      │                      │
     │ 8. Liste offres +    │                      │
     │    créneaux dispos   │                      │
     │<─────────────────────┤                      │
     │                      │                      │
```

**Fichier** : [student/find_prof.php](../student/find_prof.php)

**Filtres possibles** :
- **Matière** : `$_GET['matiere']` → Filtre sur `couvrir.id_matiere`
- **Niveau** : `$_GET['niveau']` → Filtre sur `offre_cours.niveau`
- **Mode** : `$_GET['mode']` → Filtre sur `creneau.mode_propose` (visio/presentiel)
- **Localisation** : Géolocalisation via Leaflet.js

---

### Réservation de Créneau

**Acteurs** : Étudiant → API → BDD → Professeur (notification)

```
┌─────────┐     ┌────────────────┐     ┌──────────┐     ┌────────────┐
│Étudiant │     │ api/           │     │   BDD    │     │ Professeur │
│         │     │ appointments   │     │          │     │            │
└────┬────┘     └────────┬───────┘     └────┬─────┘     └─────┬──────┘
     │                   │                   │                 │
     │ 1. POST /api/appointments.php         │                 │
     │    action=book_slot                   │                 │
     │    creneau_id=123                     │                 │
     │    csrf_token=...                     │                 │
     ├──────────────────>│                   │                 │
     │                   │                   │                 │
     │                   │ 2. verify_csrf()  │                 │
     │                   │                   │                 │
     │                   │ 3. is_logged_in() │                 │
     │                   │                   │                 │
     │                   │ 4. SELECT * FROM creneau            │
     │                   │    WHERE id_creneau=?               │
     │                   │    AND statut='disponible'          │
     │                   ├──────────────────>│                 │
     │                   │<──────────────────┤                 │
     │                   │ 5. Créneau existe?│                 │
     │                   │                   │                 │
     │                   │ 6. BEGIN TRANSACTION                │
     │                   ├──────────────────>│                 │
     │                   │                   │                 │
     │                   │ 7. UPDATE creneau │                 │
     │                   │    SET statut='reserve'             │
     │                   │    WHERE id_creneau=?               │
     │                   │    AND statut='disponible'          │
     │                   ├──────────────────>│                 │
     │                   │                   │                 │
     │                   │ 8. INSERT reservation               │
     │                   │    (id_creneau, id_utilisateur,     │
     │                   │     statut='en_attente',            │
     │                   │     prix_fige=...,                  │
     │                   │     montant_ttc=...)                │
     │                   ├──────────────────>│                 │
     │                   │<──────────────────┤                 │
     │                   │ 9. ID réservation │                 │
     │                   │                   │                 │
     │                   │ 10. COMMIT        │                 │
     │                   ├──────────────────>│                 │
     │                   │                   │                 │
     │                   │ 11. (Optionnel) Email notification  │
     │                   │     au professeur │                 │
     │                   ├────────────────────────────────────>│
     │                   │                   │                 │
     │ 12. JSON response │                   │                 │
     │     {success:true,│                   │                 │
     │      reservation_id:42}               │                 │
     │<──────────────────┤                   │                 │
     │                   │                   │                 │
```

**Fichier** : [api/appointments.php](../api/appointments.php)

**Données calculées automatiquement** :
- `prix_fige` : Prix du créneau au moment de la réservation (depuis `creneau.tarif_horaire`)
- `montant_ttc` : Calculé via GENERATED COLUMN : `prix_fige * (1 + taux_tva / 100)`
- `date_reservation` : `NOW()`

**Validations** :
1. ✅ Token CSRF valide
2. ✅ Utilisateur connecté (`$_SESSION['user_id']`)
3. ✅ Créneau existe et `statut = 'disponible'`
4. ✅ Pas de conflit de réservation (double booking)
5. ✅ Étudiant ne peut pas réserver son propre créneau (si prof et étudiant)

**Transaction SQL** :
- Garantit atomicité : UPDATE + INSERT ensemble
- Si échec INSERT → ROLLBACK (créneau reste disponible)

---

### Confirmation/Annulation

**Acteurs** : Professeur → API → BDD → Étudiant (notification)

```
┌──────────┐     ┌────────────────┐     ┌──────────┐     ┌─────────┐
│Professeur│     │ api/           │     │   BDD    │     │Étudiant │
└────┬─────┘     │ appointments   │     └────┬─────┘     └────┬────┘
     │           └────────┬───────┘          │                │
     │                    │                  │                │
     │ 1. POST /api/appointments.php         │                │
     │    action=confirm_booking              │                │
     │    reservation_id=42                   │                │
     ├───────────────────>│                  │                │
     │                    │                  │                │
     │                    │ 2. verify_csrf() │                │
     │                    │                  │                │
     │                    │ 3. SELECT reservation r           │
     │                    │    JOIN creneau c ON ...          │
     │                    │    WHERE r.id_reservation=?       │
     │                    │    AND c.id_utilisateur=? (prof)  │
     │                    ├─────────────────>│                │
     │                    │<─────────────────┤                │
     │                    │ 4. Vérifie propriété              │
     │                    │                  │                │
     │                    │ 5. UPDATE reservation             │
     │                    │    SET statut='confirmee',        │
     │                    │        date_confirmation=NOW()    │
     │                    │    WHERE id_reservation=?         │
     │                    ├─────────────────>│                │
     │                    │                  │                │
     │                    │ 6. Email notification étudiant    │
     │                    ├──────────────────────────────────>│
     │                    │                  │                │
     │ 7. JSON {success:true}                │                │
     │<───────────────────┤                  │                │
     │                    │                  │                │
```

**Actions possibles** :
| Action | Endpoint | Transition de statut |
|--------|----------|----------------------|
| **Confirmer** | `action=confirm_booking` | `en_attente` → `confirmee` |
| **Annuler (prof)** | `action=cancel_booking` | `en_attente/confirmee` → `annulee` |
| **Annuler (étudiant)** | `action=cancel_booking` | `en_attente/confirmee` → `annulee` |
| **Terminer** | `action=complete_booking` | `confirmee` → `terminee` |

**Règles métier** :
- Étudiant peut annuler jusqu'à X heures avant (ex: 24h)
- Professeur peut annuler à tout moment mais doit notifier
- Annulation après délai → pénalité possible (flag `penalite` dans BDD)

---

## Flux Professeur

### Création d'Offre de Cours

**Acteurs** : Professeur → Système → BDD

```
┌──────────┐     ┌────────────────┐     ┌──────────┐
│Professeur│     │ teacher/       │     │   BDD    │
│          │     │ create_offer   │     │          │
└────┬─────┘     └────────┬───────┘     └────┬─────┘
     │                    │                  │
     │ 1. POST /teacher/create_offer.php     │
     │    (titre, desc, niveau, matiere_ids[])
     ├───────────────────>│                  │
     │                    │                  │
     │                    │ 2. csrf_protect()│
     │                    │                  │
     │                    │ 3. Validation inputs
     │                    │    - titre non vide
     │                    │    - niveau in whitelist
     │                    │    - matiere_ids array
     │                    │                  │
     │                    │ 4. BEGIN TRANSACTION
     │                    ├─────────────────>│
     │                    │                  │
     │                    │ 5. INSERT offre_cours
     │                    │    (titre, description,
     │                    │     niveau, id_utilisateur)
     │                    ├─────────────────>│
     │                    │<─────────────────┤
     │                    │ 6. ID offre créée│
     │                    │                  │
     │                    │ 7. LOOP matiere_ids:
     │                    │    INSERT couvrir
     │                    │    (id_offre, id_matiere)
     │                    ├─────────────────>│
     │                    │                  │
     │                    │ 8. COMMIT        │
     │                    ├─────────────────>│
     │                    │                  │
     │ 9. Redirect /teacher/teacher_page.php │
     │    avec message succès                │
     │<───────────────────┤                  │
     │                    │                  │
```

**Fichier** : [teacher/create_offer.php](../teacher/create_offer.php) (supposé)

**Champs du formulaire** :
- `titre` : Titre de l'offre (ex: "Soutien scolaire Maths Terminale")
- `description` : Description détaillée
- `niveau` : Niveau scolaire (`college`, `lycee`, `superieur`)
- `matiere_ids[]` : Array d'IDs de matières (multi-sélection)

**Relation N:M** :
- Table `offre_cours` : Stocke l'offre
- Table `couvrir` : Lie offre ↔ matières (relation N:M)

---

### Ajout de Créneaux

**Acteurs** : Professeur → Système → BDD

```
┌──────────┐     ┌────────────────┐     ┌──────────┐
│Professeur│     │ teacher/       │     │   BDD    │
│          │     │ add_slots      │     │          │
└────┬─────┘     └────────┬───────┘     └────┬─────┘
     │                    │                  │
     │ 1. POST /teacher/add_slots.php        │
     │    (id_offre, date_debut, date_fin,   │
     │     tarif_horaire, mode, lieu)        │
     ├───────────────────>│                  │
     │                    │                  │
     │                    │ 2. Validation    │
     │                    │    - date_debut < date_fin
     │                    │    - date_debut > NOW()
     │                    │    - tarif > 0   │
     │                    │    - mode in ['visio','presentiel']
     │                    │                  │
     │                    │ 3. Vérif conflits│
     │                    │    SELECT COUNT(*) FROM creneau
     │                    │    WHERE id_utilisateur=?
     │                    │    AND statut!='annule'
     │                    │    AND (date_debut BETWEEN ... OR ...)
     │                    ├─────────────────>│
     │                    │<─────────────────┤
     │                    │ 4. Conflit? NON  │
     │                    │                  │
     │                    │ 5. INSERT creneau│
     │                    │    (id_offre, id_utilisateur,
     │                    │     date_debut, date_fin,
     │                    │     tarif_horaire, mode_propose,
     │                    │     lieu, statut='disponible')
     │                    ├─────────────────>│
     │                    │                  │
     │ 6. JSON {success:true, creneau_id:...}│
     │<───────────────────┤                  │
     │                    │                  │
```

**Fichier** : [teacher/add_slots.php](../teacher/add_slots.php) (supposé)

**Validations anti-conflits** :
```sql
SELECT COUNT(*) FROM creneau
WHERE id_utilisateur = ?
  AND statut != 'annule'
  AND (
      (date_debut BETWEEN ? AND ?) OR
      (date_fin BETWEEN ? AND ?) OR
      (? BETWEEN date_debut AND date_fin)
  )
```

Si `COUNT(*) > 0` → Erreur "Conflit de créneaux"

---

### Gestion des Réservations

**Acteurs** : Professeur → Interface

```
Professeur accède à /teacher/teacher_page.php

┌──────────────────────────────────────────────┐
│         Dashboard Professeur                 │
├──────────────────────────────────────────────┤
│                                              │
│  [Onglet: Réservations en attente]           │
│                                              │
│  ┌────────────────────────────────────────┐  │
│  │ Réservation #42                        │  │
│  │ Étudiant: Jean Dupont                  │  │
│  │ Cours: Maths - Algèbre                 │  │
│  │ Date: 15/02/2025 14:00-15:30           │  │
│  │ Mode: Visio                            │  │
│  │                                        │  │
│  │ [Confirmer]  [Refuser]                 │  │
│  └────────────────────────────────────────┘  │
│                                              │
│  [Onglet: Cours confirmés]                   │
│  [Onglet: Historique]                        │
│                                              │
└──────────────────────────────────────────────┘
```

**Actions** :
- **Confirmer** : `POST /api/appointments.php?action=confirm_booking`
  - Statut `en_attente` → `confirmee`
  - Email envoyé à l'étudiant
- **Refuser** : `POST /api/appointments.php?action=reject_booking`
  - Statut `en_attente` → `refusee`
  - Créneau redevient `disponible`
- **Annuler** : `POST /api/appointments.php?action=cancel_booking`
  - Statut `confirmee` → `annulee`
  - Notification étudiant

---

## Flux de Messagerie

### Envoi de Message

**Acteurs** : Utilisateur → API → BDD → Destinataire

```
┌─────────┐     ┌────────────────┐     ┌──────────┐     ┌─────────────┐
│Expéditeur│    │ api/messaging  │     │   BDD    │     │Destinataire │
└────┬────┘     └────────┬───────┘     └────┬─────┘     └──────┬──────┘
     │                   │                   │                  │
     │ 1. POST /api/messaging.php            │                  │
     │    action=send_message                │                  │
     │    conversation_id=7                  │                  │
     │    message=...                        │                  │
     │    file=... (optionnel)               │                  │
     ├──────────────────>│                   │                  │
     │                   │                   │                  │
     │                   │ 2. verify_csrf()  │                  │
     │                   │                   │                  │
     │                   │ 3. SELECT conversation               │
     │                   │    WHERE id_conversation=?           │
     │                   │    AND (user1_id=? OR user2_id=?)    │
     │                   ├──────────────────>│                  │
     │                   │<──────────────────┤                  │
     │                   │ 4. Vérifie appartenance              │
     │                   │                   │                  │
     │                   │ 5. IF file uploaded:                 │
     │                   │    - Validate extension (pdf,jpg...) │
     │                   │    - Check size < 10MB               │
     │                   │    - Generate unique name            │
     │                   │    - move_uploaded_file()            │
     │                   │                   │                  │
     │                   │ 6. INSERT message │                  │
     │                   │    (id_conversation, sender_id,      │
     │                   │     contenu, fichier_path,           │
     │                   │     date_envoi=NOW())                │
     │                   ├──────────────────>│                  │
     │                   │                   │                  │
     │                   │ 7. UPDATE conversation               │
     │                   │    SET last_message_at=NOW()         │
     │                   ├──────────────────>│                  │
     │                   │                   │                  │
     │                   │ 8. (Optionnel) Push notification     │
     │                   ├─────────────────────────────────────>│
     │                   │                   │                  │
     │ 9. JSON {success:true, message_id:...}│                  │
     │<──────────────────┤                   │                  │
     │                   │                   │                  │
```

**Fichier** : [api/messaging.php](../api/messaging.php)

**Upload de fichier** :
- Extensions autorisées : `['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']`
- Taille max : 10 MB
- Stockage : `uploads/messages/{conversation_id}/file_{uniqid}.ext`
- Chemin stocké dans `messages.fichier_path`

---

### Réception et Lecture

**Acteurs** : Utilisateur → Interface → API → BDD

```
┌─────────┐     ┌────────────────┐     ┌──────────┐
│Utilisat.│     │ Messagerie     │     │   BDD    │
│         │     │ Interface      │     │          │
└────┬────┘     └────────┬───────┘     └────┬─────┘
     │                   │                  │
     │ 1. Ouvre /student/messaging.php      │
     ├──────────────────>│                  │
     │                   │                  │
     │                   │ 2. GET /api/messaging.php
     │                   │    action=get_conversations
     │                   ├─────────────────>│
     │                   │                  │
     │                   │ 3. SELECT conversation c
     │                   │    LEFT JOIN message m
     │                   │    WHERE c.user1_id=? OR c.user2_id=?
     │                   │    GROUP BY c.id_conversation
     │                   │    ORDER BY c.last_message_at DESC
     │                   │<─────────────────┤
     │                   │                  │
     │ 4. Liste conversations affichée      │
     │    (avec badge "nouveaux messages")  │
     │<──────────────────┤                  │
     │                   │                  │
     │ 5. Clic sur conversation #7          │
     ├──────────────────>│                  │
     │                   │                  │
     │                   │ 6. GET /api/messaging.php
     │                   │    action=get_messages
     │                   │    conversation_id=7
     │                   ├─────────────────>│
     │                   │                  │
     │                   │ 7. SELECT * FROM message
     │                   │    WHERE id_conversation=7
     │                   │    ORDER BY date_envoi ASC
     │                   │<─────────────────┤
     │                   │                  │
     │                   │ 8. UPDATE message
     │                   │    SET lu=1, date_lecture=NOW()
     │                   │    WHERE id_conversation=7
     │                   │    AND destinataire_id=?
     │                   │    AND lu=0
     │                   ├─────────────────>│
     │                   │                  │
     │ 9. Messages affichés                 │
     │<──────────────────┤                  │
     │                   │                  │
     │ 10. Auto-refresh toutes les 5s       │
     │     (AJAX polling)│                  │
     │<─────────────────>│                  │
     │                   │                  │
```

**Fichier** : [student/messaging.php](../student/messaging.php), [api/messaging.php](../api/messaging.php)

**Auto-refresh JavaScript** :
```javascript
setInterval(() => {
    fetch(`/api/messaging.php?action=get_new_messages&conversation_id=${currentConvId}&since=${lastMessageId}`)
        .then(res => res.json())
        .then(data => {
            if (data.new_messages.length > 0) {
                appendMessages(data.new_messages);
            }
        });
}, 5000); // Toutes les 5 secondes
```

---

## Flux de Support

### Création de Ticket

**Acteurs** : Utilisateur → API → BDD → Admin

```
┌─────────┐     ┌────────────────┐     ┌──────────┐     ┌───────┐
│Utilisat.│     │ api/support    │     │   BDD    │     │ Admin │
└────┬────┘     └────────┬───────┘     └────┬─────┘     └───┬───┘
     │                   │                  │               │
     │ 1. POST /api/support.php             │               │
     │    action=create                     │               │
     │    titre="Problème paiement"         │               │
     │    description="..."                 │               │
     │    priorite="haute"                  │               │
     │    categorie="Facturation"           │               │
     ├──────────────────>│                  │               │
     │                   │                  │               │
     │                   │ 2. Validation    │               │
     │                   │    - titre non vide              │
     │                   │    - priorite in whitelist       │
     │                   │                  │               │
     │                   │ 3. INSERT ticket │               │
     │                   │    (user_id, titre, description, │
     │                   │     priorite, statut='ouvert',   │
     │                   │     categorie, created_at=NOW()) │
     │                   ├─────────────────>│               │
     │                   │<─────────────────┤               │
     │                   │ 4. ID ticket créé│               │
     │                   │                  │               │
     │                   │ 5. (Optionnel) Email admin       │
     │                   ├────────────────────────────────>│
     │                   │                  │               │
     │ 6. JSON {success:true, ticket_id:48} │               │
     │<──────────────────┤                  │               │
     │                   │                  │               │
```

**Fichier** : [api/support.php](../api/support.php)

**États possibles** :
- `ouvert` : Ticket créé, en attente de traitement
- `en_cours` : Admin a commencé le traitement
- `resolu` : Problème résolu
- `ferme` : Ticket fermé (avec ou sans résolution)

**Priorités** :
- `basse`, `normale`, `haute`, `urgente`

---

### Traitement Admin

**Acteurs** : Admin → Interface → API → BDD

```
┌───────┐     ┌────────────────┐     ┌──────────┐
│ Admin │     │ admin/         │     │   BDD    │
│       │     │ messaging.php  │     │          │
└───┬───┘     └────────┬───────┘     └────┬─────┘
    │                  │                  │
    │ 1. GET /admin/messaging.php         │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 2. GET /api/support.php
    │                  │    action=list   │
    │                  │    statut=ouvert │
    │                  ├─────────────────>│
    │                  │                  │
    │                  │ 3. SELECT * FROM tickets
    │                  │    WHERE statut='ouvert'
    │                  │    ORDER BY priorite DESC,
    │                  │             created_at ASC
    │                  │<─────────────────┤
    │                  │                  │
    │ 4. Liste tickets affichée           │
    │    (triés par priorité)             │
    │<─────────────────┤                  │
    │                  │                  │
    │ 5. Clic "Voir détails" ticket #48   │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 6. GET /api/support.php
    │                  │    action=details│
    │                  │    ticket_id=48  │
    │                  ├─────────────────>│
    │                  │                  │
    │                  │ 7. SELECT ticket + messages
    │                  │<─────────────────┤
    │                  │                  │
    │ 8. Détails + historique affichés    │
    │<─────────────────┤                  │
    │                  │                  │
    │ 9. POST réponse  │                  │
    │    "Voici la solution..."           │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 10. POST /api/support.php
    │                  │     action=reply │
    │                  │     ticket_id=48 │
    │                  │     message=...  │
    │                  ├─────────────────>│
    │                  │                  │
    │                  │ 11. INSERT ticket_message
    │                  │     (ticket_id, admin_id,
    │                  │      message, created_at)
    │                  │                  │
    │                  │ 12. UPDATE ticket
    │                  │     SET statut='en_cours',
    │                  │         updated_at=NOW()
    │                  ├─────────────────>│
    │                  │                  │
    │ 13. JSON {success:true}             │
    │<─────────────────┤                  │
    │                  │                  │
```

**Fichier** : [admin/messaging.php](../admin/messaging.php), [api/support.php](../api/support.php)

**Actions admin** :
- **Répondre** : Ajoute un message au ticket
- **Changer priorité** : `UPDATE ticket SET priorite = ?`
- **Changer statut** : `UPDATE ticket SET statut = ?`
- **Assigner** : `UPDATE ticket SET assigned_to = ?` (si multi-admin)
- **Fermer** : `UPDATE ticket SET statut = 'ferme', resolved_at = NOW()`

---

## Flux Admin

### Gestion Utilisateurs

**Acteurs** : Admin → API → BDD

```
┌───────┐     ┌────────────────┐     ┌──────────┐
│ Admin │     │ admin/api/     │     │   BDD    │
│       │     │ users.php      │     │          │
└───┬───┘     └────────┬───────┘     └────┬─────┘
    │                  │                  │
    │ 1. GET /admin/users.php             │
    │    (Dashboard utilisateurs)         │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 2. SELECT * FROM users
    │                  │    ORDER BY created_at DESC
    │                  ├─────────────────>│
    │                  │<─────────────────┤
    │                  │                  │
    │ 3. Liste utilisateurs affichée      │
    │<─────────────────┤                  │
    │                  │                  │
    │ 4. Clic "Modifier" user #25         │
    ├─────────────────>│                  │
    │                  │                  │
    │ 5. Modal édition ouverte            │
    │<─────────────────┤                  │
    │                  │                  │
    │ 6. POST /admin/api/users.php        │
    │    action=update │                  │
    │    user_id=25    │                  │
    │    actif=0       │                  │
    │    (désactiver compte)              │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 7. verify_csrf() │
    │                  │                  │
    │                  │ 8. has_role('admin')
    │                  │                  │
    │                  │ 9. UPDATE users  │
    │                  │    SET actif=0   │
    │                  │    WHERE id=25   │
    │                  ├─────────────────>│
    │                  │                  │
    │                  │ 10. DELETE FROM sessions_actives
    │                  │     WHERE user_id=25
    │                  │     (force logout)
    │                  ├─────────────────>│
    │                  │                  │
    │ 11. JSON {success:true}             │
    │<─────────────────┤                  │
    │                  │                  │
```

**Fichier** : [admin/api/users.php](../admin/api/users.php), [admin/users.php](../admin/users.php)

**Actions CRUD** :
| Action | Méthode | Endpoint | Description |
|--------|---------|----------|-------------|
| **Lister** | GET | `/admin/api/users.php?action=list` | Récupère tous les users |
| **Créer** | POST | `/admin/api/users.php?action=create` | Ajoute un utilisateur |
| **Modifier** | POST | `/admin/api/users.php?action=update` | Modifie un utilisateur |
| **Supprimer** | POST | `/admin/api/users.php?action=delete` | Supprime un utilisateur |
| **Activer/Désactiver** | POST | `/admin/api/users.php?action=toggle_active` | Change statut `actif` |

---

### Consultation des Logs

**Acteurs** : Admin → Interface → BDD

```
┌───────┐     ┌────────────────┐     ┌──────────┐
│ Admin │     │ admin/logs.php │     │   BDD    │
└───┬───┘     └────────┬───────┘     └────┬─────┘
    │                  │                  │
    │ 1. GET /admin/logs.php              │
    │    ?type=connexions                 │
    │    &page=1                          │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 2. SELECT * FROM logs_connexions
    │                  │    ORDER BY date_heure DESC
    │                  │    LIMIT 50 OFFSET 0
    │                  ├─────────────────>│
    │                  │<─────────────────┤
    │                  │                  │
    │ 3. Tableau logs affiché             │
    │    Colonnes: Date, User, IP, Statut │
    │<─────────────────┤                  │
    │                  │                  │
    │ 4. Filtre "statut=failed"           │
    ├─────────────────>│                  │
    │                  │                  │
    │                  │ 5. SELECT * FROM logs_connexions
    │                  │    WHERE statut='failed'
    │                  │    ORDER BY date_heure DESC
    │                  ├─────────────────>│
    │                  │<─────────────────┤
    │                  │                  │
    │ 6. Tentatives échouées affichées    │
    │    (détection brute force)          │
    │<─────────────────┤                  │
    │                  │                  │
```

**Fichier** : [admin/logs.php](../admin/logs.php)

**Types de logs** :
- **Connexions** : `logs_connexions` (success/failed, IP, user_agent)
- **Visites** : `logs_visites` (pages visitées, durée)
- **Modifications** : `logs_modifications` (CRUD sur tables sensibles)

**Filtres disponibles** :
- Date (plage)
- Statut (success/failed)
- Utilisateur (ID ou email)
- IP address
- Type d'action

---

## Flux de Session

### Timeout et Auto-Logout

**Acteurs** : Client (JavaScript) → Serveur

```
┌─────────────┐     ┌────────────────┐     ┌──────────┐
│ Navigateur  │     │ auto_logout.js │     │ Serveur  │
│ (Utilisat.) │     │                │     │          │
└──────┬──────┘     └────────┬───────┘     └────┬─────┘
       │                     │                  │
       │ 1. Page chargée     │                  │
       │    (student_page.php)                  │
       ├────────────────────>│                  │
       │                     │                  │
       │                     │ 2. init(1800)    │
       │                     │    Timer 30min   │
       │                     │    démarré       │
       │                     │                  │
       │ 3. Activité utilisateur                │
       │    (souris, clavier, scroll, ...)      │
       ├────────────────────>│                  │
       │                     │                  │
       │                     │ 4. resetTimer()  │
       │                     │    Réinitialise  │
       │                     │    à 30 min      │
       │                     │                  │
       │ ... 30 minutes sans activité ...       │
       │                     │                  │
       │                     │ 5. setTimeout()  │
       │                     │    expiré        │
       │                     │                  │
       │                     │ 6. logout()      │
       │                     │    window.location.href =
       │                     │    '/prof-it/auth/logout.php?timeout=1'
       ├────────────────────────────────────────>│
       │                     │                  │
       │                     │                  │ 7. session_destroy()
       │                     │                  │
       │ 8. Redirect /auth/auth.php             │
       │    avec message "Session expirée"      │
       │<───────────────────────────────────────┤
       │                     │                  │
```

**Fichier** : [assets/js/auto_logout.js](../assets/js/auto_logout.js)

**Événements écoutés** :
- `onload`, `onmousemove`, `onkeypress`, `onclick`, `onscroll`, `ontouchstart`
- Chaque événement appelle `resetTimer()`

**Timeout configurable** :
```javascript
// Initialisation avec timeout serveur
initAutoLogout(<?= SESSION_LIFETIME ?>);
```

**Synchronisation client-serveur** :
- **Client** : Timer JavaScript (30 min)
- **Serveur** : Vérification `$_SESSION['last_activity']` (30 min)

Si désynchronisation (ex: client modifié) → Serveur bloque quand même

---

## Diagramme Complet - Cycle de Vie d'une Réservation

```
┌─────────────┐
│   ÉTUDIANT  │
│  Recherche  │
│  professeur │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Trouve    │
│   créneau   │
│ disponible  │
└──────┬──────┘
       │
       ▼
┌─────────────┐         ┌──────────────┐
│   Réserve   │────────>│  CRÉNEAU     │
│   créneau   │         │  statut:     │
└──────┬──────┘         │  'reserve'   │
       │                └──────────────┘
       │
       ▼
┌─────────────┐         ┌──────────────┐
│ RÉSERVATION │         │  PROFESSEUR  │
│ statut:     │────────>│  Reçoit      │
│ 'en_attente'│         │  notification│
└──────┬──────┘         └──────┬───────┘
       │                       │
       │                       │ Décision
       │                       ▼
       │              ┌────────────────┐
       │              │   Confirme?    │
       │              └────┬───────┬───┘
       │                   │ OUI   │ NON
       │                   ▼       ▼
       │          ┌────────────┐  ┌────────────┐
       │          │ CONFIRMÉE  │  │  REFUSÉE   │
       │          └─────┬──────┘  └─────┬──────┘
       │                │                │
       │                │                ▼
       │                │         ┌────────────┐
       │                │         │  Créneau   │
       │                │         │  redevient │
       │                │         │ 'disponible'│
       │                │         └────────────┘
       │                │
       │                ▼
       │         ┌────────────┐
       │         │  Email     │
       │         │  étudiant  │
       │         └─────┬──────┘
       │               │
       │               ▼
       │         ┌────────────┐
       │         │  Cours a   │
       │         │   lieu     │
       │         └─────┬──────┘
       │               │
       │               ▼
       │         ┌────────────┐
       │         │  TERMINÉE  │
       │         └─────┬──────┘
       │               │
       │               ▼
       │         ┌────────────┐
       │         │  Étudiant  │
       │         │  laisse    │
       │         │   avis     │
       │         └────────────┘
       │
       │ (Peut annuler avant)
       ▼
┌─────────────┐
│  ANNULÉE    │
└─────────────┘
```

**États possibles** : `en_attente`, `confirmee`, `refusee`, `annulee`, `terminee`

---

## Résumé des Patterns

### Pattern Request-Response (API)

Toutes les API suivent ce pattern :

```php
// 1. Vérification session
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// 2. Protection CSRF
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    echo json_encode(['success' => false, 'message' => 'CSRF invalide']);
    exit;
}

// 3. Validation entrées
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// 4. Traitement métier
try {
    $stmt = $conn->prepare("...");
    $stmt->execute([...]);
    echo json_encode(['success' => true, 'data' => ...]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
```

### Pattern MVC (simplifié)

- **Model** : Requêtes SQL (PDO) dans les fichiers API
- **View** : Templates PHP avec échappement (`htmlspecialchars()`)
- **Controller** : Logique métier dans les pages PHP

---

**Dernière mise à jour** : Janvier 2025

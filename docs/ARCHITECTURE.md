# Architecture du Projet Prof-IT

## 1. Vue d'Ensemble

**Prof-IT** est une plateforme web de gestion pédagogique conçue pour faciliter les interactions entre trois types d'utilisateurs : les **Étudiants**, les **Professeurs**, et les **Administrateurs**.

Le projet repose sur une architecture web classique **LAMP/WAMP** (Linux/Windows, Apache, MySQL, PHP), privilégiant le PHP natif (Vanilla) pour une performance optimale et une maîtrise totale du code.

## 2. Architecture Technique

Le projet suit une architecture de type **Page Controller** (Contrôleur de Page), où chaque fichier PHP accessible via l'URL agit à la fois comme contrôleur (traitement logique) et vue (génération HTML).

### Structure en Couches

1.  **Couche Présentation (View)** :
    - HTML5 / CSS3 (Bootstrap 5 + CSS personnalisé).
    - JavaScript (Vanilla) pour l'interactivité côté client (AJAX, DOM manipulation).
    - Les fichiers PHP dans `student/`, `teacher/`, `admin/` génèrent le HTML.

2.  **Couche Logique (Controller)** :
    - La logique métier est traitée au début des fichiers PHP (vérification des formulaires, appels aux fonctions).
    - Les fonctions réutilisables sont centralisées dans le dossier `includes/` (ex: `functions_user.php`, `helpers.php`).
    - Le routage est géré soit par `index.php` (point d'entrée), soit par accès direct aux fichiers.

3.  **Couche Données (Model)** :
    - Base de données relationnelle **MySQL / MariaDB**.
    - Accès aux données via **PDO** (PHP Data Objects) pour la sécurité et l'abstraction.
    - Pas d'ORM lourd ; les requêtes SQL sont optimisées et exécutées directement.

## 3. Base de Données

Le schéma de base de données est conçu pour garantir l'intégrité et la cohérence des données.

### Tables Principales

-   **`users`** : Table centrale stockant tous les utilisateurs. Le champ `role` ('student', 'teacher', 'admin') définit les permissions.
-   **`roles`** & **`affecter`** : Gestion avancée des rôles (si nécessaire pour extension future).
-   **`offre_cours`** : Catalogue des cours proposés (ex: Soutien Maths Lycée).
-   **`matiere`** : Liste des matières (Maths, Anglais, etc.).
-   **`creneau`** : Disponibilités horaires définies par les professeurs.
-   **`reservation`** : Lien entre un étudiant et un créneau. Contient le statut ('en_attente', 'confirmee', etc.).
-   **`message`** & **`conversation`** : Système de messagerie interne.
-   **`document`** : Gestion des fichiers partagés.

### Relations Clés

-   Un **Professeur** crée des **Créneaux** liés à une **Offre de cours**.
-   Un **Étudiant** effectue une **Réservation** sur un **Créneau**.
-   Une **Conversation** est liée à une **Réservation** (contexte).

## 4. Flux de Données (Data Flow)

### Exemple : Réservation d'un cours

1.  **Client (Navigateur)** : L'étudiant clique sur "Réserver" sur la page `student_page.php`.
2.  **Requête HTTP** : Une requête POST est envoyée à `student/api/book_slot.php` (ou traitement dans la même page).
3.  **Sécurité** :
    - Vérification de la session PHP (utilisateur connecté ?).
    - Vérification du Token CSRF (protection anti-falsification).
4.  **Traitement (PHP)** :
    - Vérification de la disponibilité du créneau en BDD.
    - Insertion d'une nouvelle ligne dans la table `reservation`.
    - Mise à jour du statut du créneau (`reserve`).
5.  **Réponse** :
    - Redirection ou réponse JSON (succès/erreur).
    - Mise à jour de l'interface utilisateur.

## 5. Schéma de la Base de Données

### 5.1 Diagramme ERD (Entity-Relationship Diagram)

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         SCHÉMA COMPLET DE LA BDD                                │
└─────────────────────────────────────────────────────────────────────────────────┘

┌────────────────────┐         ┌──────────────────┐         ┌──────────────────┐
│      users         │    ╔════│   affecter       │════╗    │     roles        │
├────────────────────┤    ║    ├──────────────────┤    ║    ├──────────────────┤
│ id (PK)            │────╢    │ id (PK)          │    ╠────│ id (PK)          │
│ nom                │    ║    │ id_utilisateur   │    ║    │ code_role (UQ)   │
│ prenom             │    ║    │ id_role          │    ║    │ nom_role         │
│ email (UQ)         │    ║    │ date_affectation │    ║    │ description      │
│ password           │    ║    └──────────────────┘    ║    └──────────────────┘
│ role (ENUM)        │    ║    Relation N:M            ║
│ telephone          │    ║    (users ↔ roles)         ║
│ adresse            │    ╚════════════════════════════╝
│ ville              │
│ code_postal        │    ┌───────────────────┐
│ bio                │    │   sessions_       │
│ photo_url          │────│   actives         │
│ actif              │    ├───────────────────┤
│ email_verifie      │    │ id (PK)           │
│ created_at         │    │ user_id (FK)      │
│ updated_at         │    │ session_php_id(UQ)│
└────────────────────┘    │ ip_address        │
         │                │ user_agent        │
         │                │ current_url       │
         │                │ derniere_activite │
         │                └───────────────────┘
         │
         │                ┌───────────────────┐
         │────────────────│ logs_connexions   │
         │                ├───────────────────┤
         │                │ id (PK)           │
         │                │ user_id (FK)      │
         │                │ email             │
         │                │ statut (ENUM)     │
         │                │ raison_echec      │
         │                └───────────────────┘
         │
         │                ┌───────────────────┐
         │────────────────│ logs_visites      │
         │                ├───────────────────┤
         │                │ id (PK)           │
         │                │ user_id (FK)      │
         │                │ page_url          │
         │                │ duree_visite      │
         │                └───────────────────┘
         │
         ├───────────────────────────────────────────────────┐
         │                                                   │
         ▼                                                   ▼
┌──────────────────┐                              ┌──────────────────┐
│   enseigner      │──────────┐                   │    creneau       │
├──────────────────┤          │                   ├──────────────────┤
│ id (PK)          │          │                   │ id_creneau (PK)  │
│ id_utilisateur   │          │                   │ id_utilisateur   │─┐
│ id_offre         │──┐       │                   │ id_offre (FK)    │ │
│ date_debut       │  │       │                   │ date_debut       │ │
│ date_fin         │  │       │                   │ date_fin         │ │
│ actif            │  │       │                   │ tarif_horaire    │ │
└──────────────────┘  │       │                   │ mode_propose(SET)│ │
   Relation N:M       │       │                   │ lieu             │ │
   (users ↔ offres)   │       │                   │ statut_creneau   │ │
                      │       │                   └──────────────────┘ │
                      │       │                            │           │
                      │       │                            │           │
                      ▼       ▼                            ▼           │
         ┌──────────────────────────┐           ┌──────────────────┐  │
         │    offre_cours           │           │   reservation    │  │
         ├──────────────────────────┤           ├──────────────────┤  │
         │ id_offre (PK)            │           │ id_reservation   │  │
         │ titre                    │           │ id_utilisateur   │──┘
         │ description              │           │ id_creneau (FK)  │
         │ niveau (ENUM)            │           │ statut_reservation│
         │ tarif_horaire_defaut     │           │ mode_choisi      │
         │ duree_seance_defaut      │           │ prix_fige        │
         │ actif                    │           │ tva              │
         └──────────────────────────┘           │ montant_ttc      │
                  │                              │ notes            │
                  │                              │ date_annulation  │
                  │                              └──────────────────┘
                  │                                      │  │  │
                  │                                      │  │  │
         ┌────────┴──────┐                              │  │  │
         │               │                              │  │  │
         ▼               ▼                              │  │  │
┌──────────────┐  ┌──────────────┐                     │  │  │
│   couvrir    │  │   matiere    │                     │  │  │
├──────────────┤  ├──────────────┤                     │  │  │
│ id (PK)      │  │ id_matiere   │                     │  │  │
│ id_offre     │──│ nom_matiere  │                     │  │  │
│ id_matiere   │  │ description  │                     │  │  │
└──────────────┘  │ icone        │                     │  │  │
   Relation N:M   │ actif        │                     │  │  │
   (offres ↔      └──────────────┘                     │  │  │
    matieres)                                          │  │  │
                                                       │  │  │
                  ┌────────────────────────────────────┘  │  │
                  │                                       │  │
                  ▼                                       │  │
         ┌──────────────────┐                            │  │
         │  conversation    │                            │  │
         ├──────────────────┤                            │  │
         │ id_conversation  │                            │  │
         │ id_reservation   │                            │  │
         │ cree_le          │                            │  │
         │ derniere_activite│                            │  │
         │ archivee         │                            │  │
         └──────────────────┘                            │  │
                  │                                      │  │
                  │                                      │  │
                  ▼                                      │  │
         ┌──────────────────┐                            │  │
         │     message      │                            │  │
         ├──────────────────┤                            │  │
         │ id_message (PK)  │                            │  │
         │ id_conversation  │                            │  │
         │ id_utilisateur   │                            │  │
         │ contenu          │                            │  │
         │ date_envoi       │                            │  │
         │ lu               │                            │  │
         │ date_lecture     │                            │  │
         │ fichier_joint    │                            │  │
         └──────────────────┘                            │  │
                  │                                      │  │
                  │                                      │  │
         ┌────────┴─────┐                                │  │
         │              │                                │  │
         ▼              ▼                                │  │
┌─────────────────┐  ┌──────────────────┐               │  │
│ message_reaction│  │    document      │               │  │
├─────────────────┤  ├──────────────────┤               │  │
│ id_reaction     │  │ id_document (PK) │               │  │
│ id_message      │  │ id_utilisateur   │               │  │
│ id_utilisateur  │  │ id_message (FK)  │               │  │
│ type_reaction   │  │ nom_original     │               │  │
└─────────────────┘  │ fichier_path     │               │  │
                     │ type_fichier     │               │  │
                     │ source (ENUM)    │               │  │
                     └──────────────────┘               │  │
                                                        │  │
         ┌──────────────────────────────────────────────┘  │
         │                                                 │
         ▼                                                 ▼
┌──────────────────┐                            ┌──────────────────┐
│      avis        │                            │     facture      │
├──────────────────┤                            ├──────────────────┤
│ id_avis (PK)     │                            │ id_facture (PK)  │
│ id_reservation   │                            │ id_reservation   │
│ id_utilisateur   │                            │ numero_facture   │
│ note (1-5)       │                            │ montant_ht       │
│ commentaire      │                            │ montant_tva      │
│ verifie          │                            │ montant_ttc      │
│ date_avis        │                            │ statut_facture   │
└──────────────────┘                            └──────────────────┘
                                                         │
                                                         │
                                                         ▼
                                               ┌──────────────────┐
                                               │    paiement      │
                                               ├──────────────────┤
                                               │ id_paiement (PK) │
                                               │ id_reservation   │
                                               │ fournisseur(ENUM)│
                                               │ montant          │
                                               │ statut_paiement  │
                                               │ date_paiement    │
                                               └──────────────────┘

┌──────────────────────┐          ┌────────────────────┐
│  ticket_support      │          │  message_ticket    │
├──────────────────────┤          ├────────────────────┤
│ id_ticket (PK)       │──────────│ id_message_ticket  │
│ id_utilisateur (FK)  │          │ id_ticket (FK)     │
│ sujet                │          │ id_utilisateur(FK) │
│ categorie (ENUM)     │          │ contenu            │
│ statut_ticket (ENUM) │          │ est_admin          │
│ priorite (ENUM)      │          └────────────────────┘
│ cree_le              │
│ ferme_le             │
└──────────────────────┘

┌──────────────────────┐          ┌────────────────────┐
│  newsletter          │          │ newsletter_envoi   │
├──────────────────────┤          ├────────────────────┤
│ id (PK)              │          │ id_envoi (PK)      │
│ email (UQ)           │          │ sujet              │
│ prenom               │          │ contenu            │
│ actif                │          │ nb_destinataires   │
│ date_inscription     │          │ envoye_par (FK)    │
│ token_desinscription │          └────────────────────┘
└──────────────────────┘

┌──────────────────────┐
│  captcha_questions   │
├──────────────────────┤
│ id (PK)              │
│ question             │
│ reponse              │
│ actif                │
└──────────────────────┘
```

### 5.2 Tables Principales et Relations

#### Groupe Utilisateurs & Authentification
- **users** (1) ↔ (N) **sessions_actives** : Un utilisateur peut avoir plusieurs sessions actives
- **users** (1) ↔ (N) **logs_connexions** : Logs de toutes les tentatives de connexion
- **users** (1) ↔ (N) **logs_visites** : Tracking des pages visitées
- **users** (N) ↔ (M) **roles** via **affecter** : Système de rôles avancé (future extension)

#### Groupe Cours & Enseignement
- **users** (teacher) (N) ↔ (M) **offre_cours** via **enseigner** : Professeurs enseignent des offres
- **offre_cours** (N) ↔ (M) **matiere** via **couvrir** : Offres couvrent plusieurs matières
- **users** (teacher) (1) ↔ (N) **creneau** : Professeur crée des créneaux de disponibilité
- **offre_cours** (1) ↔ (N) **creneau** : Chaque créneau est lié à une offre

#### Groupe Réservation & Paiement
- **users** (student) (1) ↔ (N) **reservation** : Étudiant réserve des créneaux
- **creneau** (1) ↔ (N) **reservation** : Créneaux peuvent être réservés (mais généralement 1:1)
- **reservation** (1) ↔ (1) **conversation** : Chaque réservation génère une conversation
- **reservation** (1) ↔ (0..1) **facture** : Facture générée pour réservation confirmée
- **reservation** (1) ↔ (0..N) **paiement** : Historique des paiements (initial + remboursements)
- **reservation** (1) ↔ (0..1) **avis** : Un seul avis par réservation (contrainte UNIQUE)

#### Groupe Messagerie
- **conversation** (1) ↔ (N) **message** : Échange de messages dans conversations
- **users** (1) ↔ (N) **message** : Auteur du message
- **message** (1) ↔ (0..N) **message_reaction** : Réactions aux messages (like, love, etc.)
- **message** (1) ↔ (0..1) **document** : Fichier joint au message

#### Groupe Support
- **users** (1) ↔ (N) **ticket_support** : Utilisateur crée des tickets
- **ticket_support** (1) ↔ (N) **message_ticket** : Messages du ticket (utilisateur + admin)

#### Groupe Newsletter
- **newsletter** : Table indépendante (emails inscrits à la newsletter)
- **newsletter_envoi** : Historique des campagnes envoyées

#### Groupe Sécurité
- **captcha_questions** : Table indépendante (questions de sécurité)

### 5.3 Contraintes d'Intégrité Référentielle

Toutes les clés étrangères utilisent `ON DELETE CASCADE` ou `ON DELETE SET NULL` :

| Table | Colonne FK | Référence | Action ON DELETE |
|-------|-----------|-----------|------------------|
| affecter | id_utilisateur | users(id) | CASCADE |
| affecter | id_role | roles(id) | CASCADE |
| enseigner | id_utilisateur | users(id) | CASCADE |
| enseigner | id_offre | offre_cours(id_offre) | CASCADE |
| couvrir | id_offre | offre_cours(id_offre) | CASCADE |
| couvrir | id_matiere | matiere(id_matiere) | CASCADE |
| creneau | id_utilisateur | users(id) | CASCADE |
| creneau | id_offre | offre_cours(id_offre) | CASCADE |
| reservation | id_utilisateur | users(id) | CASCADE |
| reservation | id_creneau | creneau(id_creneau) | CASCADE |
| facture | id_reservation | reservation(id_reservation) | CASCADE |
| paiement | id_reservation | reservation(id_reservation) | CASCADE |
| avis | id_reservation | reservation(id_reservation) | CASCADE |
| avis | id_utilisateur | users(id) | CASCADE |
| conversation | id_reservation | reservation(id_reservation) | CASCADE |
| message | id_conversation | conversation(id_conversation) | CASCADE |
| message | id_utilisateur | users(id) | CASCADE |
| document | id_utilisateur | users(id) | CASCADE |
| document | id_message | message(id_message) | SET NULL |
| message_reaction | id_message | message(id_message) | CASCADE |
| message_reaction | id_utilisateur | users(id) | CASCADE |
| ticket_support | id_utilisateur | users(id) | CASCADE |
| message_ticket | id_ticket | ticket_support(id_ticket) | CASCADE |
| message_ticket | id_utilisateur | users(id) | CASCADE |
| sessions_actives | user_id | users(id) | CASCADE |
| logs_connexions | user_id | users(id) | SET NULL |
| logs_visites | user_id | users(id) | SET NULL |

**Signification** :
- **CASCADE** : Si l'entité parente est supprimée, toutes les entités enfants sont supprimées automatiquement
- **SET NULL** : Si l'entité parente est supprimée, la FK est mise à NULL (pour conserver l'historique)

### 5.4 Index de Performance

Tous les index créés pour optimiser les requêtes :

| Table | Index | Colonnes | Utilité |
|-------|-------|----------|---------|
| users | idx_email | email | Recherche rapide par email (login) |
| users | idx_role | role | Filtrage par rôle (admin, teacher, student) |
| users | idx_actif | actif | Filtrage utilisateurs actifs |
| matiere | idx_actif | actif | Filtrage matières actives |
| offre_cours | idx_actif | actif | Filtrage offres actives |
| offre_cours | idx_tarif | tarif_horaire_defaut | Recherche par tarif |
| creneau | idx_professeur | id_utilisateur | Créneaux d'un professeur |
| creneau | idx_dates | date_debut, date_fin | Recherche par plage de dates |
| creneau | idx_statut | statut_creneau | Filtrage créneaux disponibles |
| reservation | idx_etudiant | id_utilisateur | Réservations d'un étudiant |
| reservation | idx_statut | statut_reservation | Filtrage par statut |
| reservation | idx_date | date_reservation | Tri chronologique |
| facture | idx_numero | numero_facture | Recherche par numéro |
| facture | idx_statut | statut_facture | Filtrage factures payées |
| paiement | idx_statut | statut_paiement | Filtrage paiements réussis |
| paiement | idx_fournisseur | fournisseur | Stats par fournisseur |
| avis | idx_note | note | Filtrage/tri par note |
| conversation | idx_derniere_activite | derniere_activite | Tri conversations actives |
| message | idx_conversation | id_conversation | Messages d'une conversation |
| message | idx_lu | lu | Messages non lus |
| ticket_support | idx_statut | statut_ticket | Filtrage tickets ouverts |
| ticket_support | idx_priorite | priorite | Tri par priorité |
| sessions_actives | idx_user | user_id | Sessions d'un utilisateur |
| sessions_actives | idx_activite | derniere_activite | Purge sessions inactives |
| logs_connexions | idx_statut | statut | Filtrage tentatives échouées |
| logs_connexions | idx_ip | ip_address | Détection bruteforce |
| newsletter | idx_actif | actif | Destinataires actifs |

### 5.5 Contraintes UNIQUE

Contraintes garantissant l'unicité :

| Table | Contrainte | Colonnes | Signification |
|-------|-----------|----------|---------------|
| users | PRIMARY | email | Un seul compte par email |
| roles | PRIMARY | code_role | Codes rôles uniques |
| matiere | PRIMARY | nom_matiere | Noms matières uniques |
| affecter | unique_user_role | id_utilisateur, id_role | Un utilisateur ne peut avoir un rôle qu'une fois |
| enseigner | unique_user_offre | id_utilisateur, id_offre | Un professeur ne peut enseigner une offre qu'une fois |
| couvrir | unique_offre_matiere | id_offre, id_matiere | Une offre ne couvre une matière qu'une fois |
| avis | unique_reservation_avis | id_reservation | Un seul avis par réservation |
| message_reaction | unique_user_message_reaction | id_utilisateur, id_message | Une seule réaction par utilisateur/message |
| facture | PRIMARY | numero_facture | Numéros de facture uniques |
| sessions_actives | unique_session | session_php_id | Un seul enregistrement par session PHP |
| newsletter | PRIMARY | email | Un seul abonnement par email |

### 5.6 Vues SQL

#### vue_stats_professeurs
Statistiques agrégées pour chaque professeur :
```sql
SELECT
    u.id, u.nom, u.prenom, u.email,
    COUNT(DISTINCT r.id_reservation) as nb_reservations,
    AVG(a.note) as note_moyenne,
    COUNT(DISTINCT a.id_avis) as nb_avis,
    SUM(r.prix_fige) as revenu_total
FROM users u
LEFT JOIN creneau c ON u.id = c.id_utilisateur
LEFT JOIN reservation r ON c.id_creneau = r.id_creneau AND r.statut_reservation = 'terminee'
LEFT JOIN avis a ON r.id_reservation = a.id_reservation
WHERE u.role = 'teacher'
GROUP BY u.id;
```

**Utilisation** : Dashboard professeur, classement des meilleurs profs

#### vue_reservations_details
Vue complète des réservations avec tous les détails :
```sql
SELECT
    r.id_reservation, r.date_reservation, r.statut_reservation,
    r.mode_choisi, r.prix_fige, r.montant_ttc,
    etudiant.nom as nom_etudiant, etudiant.prenom as prenom_etudiant,
    professeur.nom as nom_professeur, professeur.prenom as prenom_professeur,
    c.date_debut, c.date_fin,
    o.titre as titre_cours,
    m.nom_matiere
FROM reservation r
INNER JOIN users etudiant ON r.id_utilisateur = etudiant.id
INNER JOIN creneau c ON r.id_creneau = c.id_creneau
INNER JOIN users professeur ON c.id_utilisateur = professeur.id
INNER JOIN offre_cours o ON c.id_offre = o.id_offre
LEFT JOIN couvrir co ON o.id_offre = co.id_offre
LEFT JOIN matiere m ON co.id_matiere = m.id_matiere;
```

**Utilisation** : Affichage complet des réservations (admin, exports, rapports)

### 5.7 Énumérations (ENUM et SET)

| Table | Colonne | Valeurs Possibles | Description |
|-------|---------|-------------------|-------------|
| users | role | student, teacher, admin | Rôle de l'utilisateur |
| offre_cours | niveau | primaire, college, lycee, superieur, professionnel, tous | Niveau ciblé |
| creneau | mode_propose | presentiel, visio, domicile (SET) | Modes proposés (combinables) |
| creneau | statut_creneau | disponible, reserve, termine, annule | Statut du créneau |
| reservation | statut_reservation | en_attente, confirmee, terminee, annulee | Cycle de vie réservation |
| reservation | mode_choisi | presentiel, visio | Mode choisi par l'étudiant |
| facture | statut_facture | en_attente, payee, annulee, remboursee | Statut paiement |
| paiement | fournisseur | stripe, paypal, virement, especes, cheque | Moyen de paiement |
| paiement | statut_paiement | en_attente, reussi, echoue, rembourse | Résultat paiement |
| document | source | upload, messaging | Origine du fichier |
| message_reaction | type_reaction | like, love, laugh, wow, sad, angry | Type de réaction |
| ticket_support | categorie | technique, paiement, compte, reservation, autre | Catégorie du ticket |
| ticket_support | statut_ticket | ouvert, en_cours, resolu, ferme | État du ticket |
| ticket_support | priorite | basse, normale, haute, urgente | Priorité du ticket |
| logs_connexions | statut | success, failed | Résultat connexion |

**Note** : Le type `SET` pour `mode_propose` permet de combiner plusieurs valeurs (ex: 'presentiel,visio')

### 5.8 Colonnes Générées (GENERATED)

| Table | Colonne | Formule | Type |
|-------|---------|---------|------|
| reservation | montant_ttc | `prix_fige * (1 + tva / 100)` | STORED |

**Avantages** :
- Calcul automatique du montant TTC
- Pas besoin de recalculer à chaque requête
- Garantit la cohérence des données

## 6. Architecture de Sécurité

### 6.1 Couche Session & Authentification

```
┌─────────────────────────────────────────────────────────┐
│              FLUX D'AUTHENTIFICATION                    │
└─────────────────────────────────────────────────────────┘

Utilisateur
    │
    │ 1. POST /auth/login_register.php
    ▼
┌─────────────────────────────────┐
│  Vérification CAPTCHA            │ ← captcha_questions
│  (question/réponse aléatoire)    │
└─────────────────┬───────────────┘
                  │
                  │ 2. Validé
                  ▼
┌─────────────────────────────────┐
│  Vérification Email en BDD       │ ← users.email
└─────────────────┬───────────────┘
                  │
                  │ 3. Existe
                  ▼
┌─────────────────────────────────┐
│  password_verify()               │ ← users.password (hash)
│  Compare hash stocké             │
└─────────────────┬───────────────┘
                  │
                  │ 4. Valide
                  ▼
┌─────────────────────────────────┐
│  session_regenerate_id(true)     │ ← Prévient fixation
└─────────────────┬───────────────┘
                  │
                  │ 5. Session créée
                  ▼
┌─────────────────────────────────┐
│  $_SESSION :                     │
│  - user_id                       │
│  - name, prenom, email           │
│  - role (student/teacher/admin)  │
│  - avatar_url                    │
│  - last_activity (timestamp)     │
└─────────────────┬───────────────┘
                  │
                  │ 6. Tracking
                  ▼
┌─────────────────────────────────┐
│  INSERT logs_connexions          │ ← Enregistre tentative
│  statut = 'success'              │
└─────────────────┬───────────────┘
                  │
                  │ 7. Session active
                  ▼
┌─────────────────────────────────┐
│  INSERT/UPDATE sessions_actives  │ ← Monitoring temps réel
│  derniere_activite = NOW()       │
└─────────────────────────────────┘
```

### 6.2 Protection CSRF

```
┌─────────────────────────────────────────────────────────┐
│              FLUX PROTECTION CSRF                       │
└─────────────────────────────────────────────────────────┘

1. Génération Token (includes/csrf.php)
   ┌────────────────────────────────────┐
   │ csrf_token()                        │
   │ → bin2hex(random_bytes(32))         │
   │ → 64 caractères hexadécimaux        │
   │ → Stocké en $_SESSION['csrf_token'] │
   └────────────────────────────────────┘

2. Insertion dans Formulaire (via csrf_field())
   ┌────────────────────────────────────┐
   │ <form method="POST">                │
   │   <input type="hidden"              │
   │          name="csrf_token"          │
   │          value="abc123...">         │
   │   ...                               │
   │ </form>                             │
   └────────────────────────────────────┘

3. Vérification lors de la soumission (verify_csrf())
   ┌────────────────────────────────────┐
   │ if ($_SERVER['REQUEST_METHOD']      │
   │     === 'POST') {                   │
   │   $token_recu = $_POST['csrf_token']│
   │   $token_session = $_SESSION[...]   │
   │   if (!hash_equals($token_session,  │
   │                    $token_recu)) {  │
   │     die('Token CSRF invalide');     │
   │   }                                 │
   │ }                                   │
   └────────────────────────────────────┘

4. Protection appliquée sur :
   - Tous les formulaires POST
   - Toutes les APIs modifiant des données
   - Actions sensibles (delete, update, create)
```

### 6.3 Timeout de Session

```
┌─────────────────────────────────────────────────────────┐
│          GESTION TIMEOUT SESSION (30 min)               │
└─────────────────────────────────────────────────────────┘

Constante : SESSION_LIFETIME = 1800 secondes (30 minutes)

Frontend (assets/js/auto_logout.js)
    │
    │ Événements : mousemove, keypress, click, scroll
    ▼
┌─────────────────────────────────┐
│  resetTimer()                    │
│  clearTimeout(timeout)           │
│  timeout = setTimeout(           │
│    logout, 1800000) // 30 min    │
└─────────────────────────────────┘
    │
    │ Si inactivité 30 min
    ▼
┌─────────────────────────────────┐
│  window.location =               │
│    '/auth/logout.php?timeout=1'  │
└─────────────────────────────────┘

Backend (includes/helpers.php → safe_session_start())
    │
    │ À chaque requête PHP
    ▼
┌─────────────────────────────────┐
│  if (isset($_SESSION['user_id']))│
│    $now = time()                 │
│    $last = $_SESSION[            │
│      'last_activity']            │
│    if ($now - $last >            │
│        SESSION_LIFETIME) {       │
│      session_destroy()           │
│      redirect → auth.php         │
│    }                             │
│    $_SESSION['last_activity']    │
│      = $now                      │
└─────────────────────────────────┘
```

### 6.4 Logs de Sécurité

| Type de Log | Table | Enregistré Lors de | Utilité |
|-------------|-------|-------------------|---------|
| **Connexions** | logs_connexions | Tentative login (success/failed) | Détection bruteforce, audit |
| **Visites** | logs_visites | Chargement de page | Analytics, parcours utilisateur |
| **Sessions** | sessions_actives | Activité utilisateur (< 5 min) | Monitoring temps réel |

**Analyse de sécurité possible** :
- Détection tentatives bruteforce : Requête sur `logs_connexions WHERE statut = 'failed' GROUP BY ip_address`
- Comptes compromis : Multiples connexions depuis IPs différentes
- Activité suspecte : Pages admin visitées par non-admins

## 7. Arborescence des Fichiers

```
prof-it/
├── admin/              # Module Administrateur
├── student/            # Module Étudiant
├── teacher/            # Module Professeur
├── auth/               # Authentification (Login/Register)
├── includes/           # Fonctions et bibliothèques partagées
├── config/             # Configuration (DB, Constantes)
├── assets/             # CSS, JS, Images publics
├── uploads/            # Stockage fichiers utilisateurs
└── index.php           # Routeur principal
```

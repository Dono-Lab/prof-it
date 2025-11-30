# Schéma de la Base de Données - Prof-IT

Ce document décrit en détail le schéma complet de la base de données `projet_profit` utilisée par la plateforme Prof-IT.

---

## Table des Matières
- [Vue d'Ensemble](#vue-densemble)
- [Tables Utilisateurs](#tables-utilisateurs)
- [Tables Cours & Enseignement](#tables-cours--enseignement)
- [Tables Réservation & Paiement](#tables-réservation--paiement)
- [Tables Messagerie](#tables-messagerie)
- [Tables Support](#tables-support)
- [Tables Système & Logs](#tables-système--logs)
- [Vues SQL](#vues-sql)
- [Données Initiales](#données-initiales)

---

## Vue d'Ensemble

**Nom de la BDD** : `projet_profit`
**Charset** : `utf8mb4`
**Collation** : `utf8mb4_unicode_ci`
**Moteur** : `InnoDB` (toutes les tables)
**Nombre de tables** : 25 tables + 2 vues SQL

### Configuration BDD

```sql
CREATE DATABASE projet_profit
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

**Connexion PDO** (depuis `config/config.php`) :
```php
$conn = new PDO(
    "mysql:host=localhost;dbname=projet_profit;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
```

---

## Tables Utilisateurs

### Table `users`
**Rôle** : Table centrale stockant tous les utilisateurs (étudiants, professeurs, administrateurs).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(100) | NOT NULL | Nom de famille |
| `prenom` | VARCHAR(100) | NOT NULL | Prénom |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email (login unique) |
| `password` | VARCHAR(255) | NOT NULL | Mot de passe hashé (Bcrypt/Argon2) |
| `role` | ENUM('student','teacher','admin') | NOT NULL, DEFAULT 'student' | Rôle de l'utilisateur |
| `telephone` | VARCHAR(20) | NULL | Numéro de téléphone |
| `adresse` | TEXT | NULL | Adresse postale |
| `ville` | VARCHAR(100) | NULL | Ville |
| `code_postal` | VARCHAR(10) | NULL | Code postal |
| `bio` | TEXT | NULL | Biographie (professeurs) |
| `photo_url` | VARCHAR(255) | NULL | URL de la photo de profil |
| `actif` | BOOLEAN | DEFAULT 1 | Compte actif (1) ou désactivé (0) |
| `email_verifie` | BOOLEAN | DEFAULT 0 | Email vérifié (future fonctionnalité) |
| `email_verification_token` | VARCHAR(64) | NULL | Token de vérification email |
| `date_derniere_connexion` | TIMESTAMP | NULL | Dernière connexion réussie |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création du compte |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Index** :
- `idx_email` sur `email` : Recherche rapide lors du login
- `idx_role` sur `role` : Filtrage par rôle (admin, teacher, student)
- `idx_actif` sur `actif` : Filtrage des comptes actifs

**Relations** :
- `users` (1) → (N) `sessions_actives`
- `users` (1) → (N) `logs_connexions`
- `users` (1) → (N) `logs_visites`
- `users` (1) → (N) `creneau` (si teacher)
- `users` (1) → (N) `reservation` (si student)
- `users` (1) → (N) `message`, `ticket_support`, `document`

**Exemple** :
```sql
SELECT id, nom, prenom, email, role FROM users WHERE role = 'teacher' AND actif = 1;
```

---

### Table `roles`
**Rôle** : Gestion avancée des rôles (future extension pour permissions granulaires).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `code_role` | VARCHAR(50) | NOT NULL, UNIQUE | Code du rôle (ADMIN, TEACHER, STUDENT) |
| `nom_role` | VARCHAR(100) | NOT NULL | Nom lisible du rôle |
| `description` | TEXT | NULL | Description du rôle |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Données initiales** :
- ADMIN : Administrateur (accès complet)
- TEACHER : Professeur (gestion cours)
- STUDENT : Étudiant (réservation cours)

---

### Table `affecter`
**Rôle** : Table de liaison Many-to-Many entre `users` et `roles` (pour système de rôles avancé).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Référence utilisateur |
| `id_role` | INT | NOT NULL, FK → roles(id) | Référence rôle |
| `date_affectation` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'affectation du rôle |

**Contraintes** :
- `UNIQUE KEY unique_user_role (id_utilisateur, id_role)` : Un utilisateur ne peut avoir le même rôle qu'une fois
- `ON DELETE CASCADE` : Suppression de l'affectation si utilisateur ou rôle supprimé

---

## Tables Cours & Enseignement

### Table `matiere`
**Rôle** : Liste des matières enseignées (Mathématiques, Français, etc.).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_matiere` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `nom_matiere` | VARCHAR(100) | NOT NULL, UNIQUE | Nom de la matière |
| `description` | TEXT | NULL | Description détaillée |
| `icone` | VARCHAR(50) | NULL | Classe FontAwesome (ex: fa-calculator) |
| `actif` | BOOLEAN | DEFAULT 1 | Matière active |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** : `idx_actif` sur `actif`

**Données initiales** : 10 matières (Mathématiques, Français, Anglais, Physique-Chimie, etc.)

---

### Table `offre_cours`
**Rôle** : Catalogue des offres de cours proposées (ex: "Soutien Maths Lycée").

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_offre` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `titre` | VARCHAR(200) | NOT NULL | Titre de l'offre |
| `description` | TEXT | NULL | Description détaillée |
| `niveau` | ENUM('primaire','college','lycee','superieur','professionnel','tous') | DEFAULT 'tous' | Niveau ciblé |
| `tarif_horaire_defaut` | DECIMAL(10,2) | NOT NULL | Tarif par défaut (€/heure) |
| `duree_seance_defaut` | INT | DEFAULT 60 | Durée par défaut (minutes) |
| `actif` | BOOLEAN | DEFAULT 1 | Offre active |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Index** :
- `idx_actif` sur `actif`
- `idx_tarif` sur `tarif_horaire_defaut`

---

### Table `enseigner`
**Rôle** : Table de liaison N:M entre `users` (teachers) et `offre_cours`.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Professeur |
| `id_offre` | INT | NOT NULL, FK → offre_cours(id_offre) | Offre de cours |
| `date_debut` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de début d'enseignement |
| `date_fin` | TIMESTAMP | NULL | Date de fin (si applicable) |
| `actif` | BOOLEAN | DEFAULT 1 | Relation active |

**Contraintes** :
- `UNIQUE KEY unique_user_offre (id_utilisateur, id_offre)`
- `ON DELETE CASCADE`

---

### Table `couvrir`
**Rôle** : Table de liaison N:M entre `offre_cours` et `matiere`.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_offre` | INT | NOT NULL, FK → offre_cours(id_offre) | Offre de cours |
| `id_matiere` | INT | NOT NULL, FK → matiere(id_matiere) | Matière enseignée |

**Contraintes** :
- `UNIQUE KEY unique_offre_matiere (id_offre, id_matiere)`
- `ON DELETE CASCADE`

**Exemple** : Une offre "Soutien Sciences" peut couvrir Maths, Physique, SVT.

---

### Table `creneau`
**Rôle** : Créneaux de disponibilité définis par les professeurs.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_creneau` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Professeur créateur |
| `id_offre` | INT | NOT NULL, FK → offre_cours(id_offre) | Offre associée |
| `date_debut` | DATETIME | NOT NULL | Date/heure de début |
| `date_fin` | DATETIME | NOT NULL | Date/heure de fin |
| `tarif_horaire` | DECIMAL(10,2) | NOT NULL | Tarif pour ce créneau (€/h) |
| `mode_propose` | SET('presentiel','visio','domicile') | DEFAULT 'presentiel,visio' | Modes proposés (combinables) |
| `lieu` | VARCHAR(255) | NULL | Lieu si mode présentiel |
| `statut_creneau` | ENUM('disponible','reserve','termine','annule') | DEFAULT 'disponible' | Statut actuel |
| `notes` | TEXT | NULL | Notes du professeur |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** :
- `idx_professeur` sur `id_utilisateur`
- `idx_dates` sur `(date_debut, date_fin)` : Recherche par plage
- `idx_statut` sur `statut_creneau`

**Contraintes** : `ON DELETE CASCADE`

**Exemple de mode_propose** : `'presentiel,visio'` permet les deux modes.

---

## Tables Réservation & Paiement

### Table `reservation`
**Rôle** : Réservations de créneaux par les étudiants.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_reservation` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Étudiant |
| `id_creneau` | INT | NOT NULL, FK → creneau(id_creneau) | Créneau réservé |
| `date_reservation` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de la réservation |
| `statut_reservation` | ENUM('en_attente','confirmee','terminee','annulee') | DEFAULT 'en_attente' | Cycle de vie |
| `mode_choisi` | ENUM('presentiel','visio') | NOT NULL | Mode choisi par l'étudiant |
| `prix_fige` | DECIMAL(10,2) | NOT NULL | Prix HT figé au moment de la réservation |
| `tva` | DECIMAL(5,2) | DEFAULT 20.00 | Taux de TVA (%) |
| `montant_ttc` | DECIMAL(10,2) | GENERATED ALWAYS AS (prix_fige * (1 + tva / 100)) STORED | Montant TTC (calculé automatiquement) |
| `notes` | TEXT | NULL | Notes de l'étudiant |
| `date_annulation` | TIMESTAMP | NULL | Date d'annulation (si applicable) |
| `raison_annulation` | TEXT | NULL | Raison de l'annulation |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Index** :
- `idx_etudiant` sur `id_utilisateur`
- `idx_statut` sur `statut_reservation`
- `idx_date` sur `date_reservation`

**Contraintes** : `ON DELETE CASCADE`

**Cycle de vie** :
1. `en_attente` : Réservation créée, attend validation professeur
2. `confirmee` : Professeur a validé
3. `terminee` : Cours terminé
4. `annulee` : Annulée par l'étudiant ou le professeur

**Colonne générée** : `montant_ttc` se calcule automatiquement : `prix_fige × 1.20` (si TVA 20%)

---

### Table `facture`
**Rôle** : Factures générées pour les réservations confirmées.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_facture` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_reservation` | INT | NOT NULL, FK → reservation(id_reservation) | Réservation associée |
| `numero_facture` | VARCHAR(50) | NOT NULL, UNIQUE | Numéro de facture (ex: FAC2025-001) |
| `date_emission` | DATE | NOT NULL | Date d'émission |
| `date_echeance` | DATE | NOT NULL | Date limite de paiement |
| `montant_ht` | DECIMAL(10,2) | NOT NULL | Montant hors taxes |
| `montant_tva` | DECIMAL(10,2) | NOT NULL | Montant de la TVA |
| `montant_ttc` | DECIMAL(10,2) | NOT NULL | Montant TTC |
| `statut_facture` | ENUM('en_attente','payee','annulee','remboursee') | DEFAULT 'en_attente' | Statut |
| `url_pdf` | VARCHAR(255) | NULL | Chemin du PDF généré (via TCPDF) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** :
- `idx_numero` sur `numero_facture`
- `idx_statut` sur `statut_facture`
- `idx_date_emission` sur `date_emission`

**Contraintes** : `ON DELETE CASCADE`

---

### Table `paiement`
**Rôle** : Historique des paiements et remboursements.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_paiement` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_reservation` | INT | NOT NULL, FK → reservation(id_reservation) | Réservation payée |
| `fournisseur` | ENUM('stripe','paypal','virement','especes','cheque') | NOT NULL | Moyen de paiement |
| `id_paiement_fournisseur` | VARCHAR(255) | NULL | ID transaction externe (Stripe, PayPal) |
| `montant` | DECIMAL(10,2) | NOT NULL | Montant payé |
| `devise` | VARCHAR(3) | DEFAULT 'EUR' | Devise (EUR, USD, etc.) |
| `statut_paiement` | ENUM('en_attente','reussi','echoue','rembourse') | DEFAULT 'en_attente' | Résultat paiement |
| `date_paiement` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date du paiement |
| `date_remboursement` | TIMESTAMP | NULL | Date du remboursement (si applicable) |
| `metadata` | JSON | NULL | Métadonnées additionnelles (JSON) |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** :
- `idx_statut` sur `statut_paiement`
- `idx_fournisseur` sur `fournisseur`
- `idx_date` sur `date_paiement`

**Contraintes** : `ON DELETE CASCADE`

**Note** : Le champ `metadata` (JSON) permet de stocker des données supplémentaires flexibles.

---

### Table `avis`
**Rôle** : Avis laissés par les étudiants après les cours terminés.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_avis` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_reservation` | INT | NOT NULL, FK → reservation(id_reservation) | Réservation notée |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Étudiant qui note |
| `note` | TINYINT | NOT NULL, CHECK (note BETWEEN 1 AND 5) | Note de 1 à 5 étoiles |
| `commentaire` | TEXT | NULL | Commentaire textuel |
| `verifie` | BOOLEAN | DEFAULT 0 | Avis modéré par admin |
| `date_avis` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de publication |
| `date_modification` | TIMESTAMP | NULL ON UPDATE CURRENT_TIMESTAMP | Dernière modification |

**Index** :
- `idx_note` sur `note`
- `idx_date` sur `date_avis`

**Contraintes** :
- `UNIQUE KEY unique_reservation_avis (id_reservation)` : Un seul avis par réservation
- `CHECK (note BETWEEN 1 AND 5)` : Note valide
- `ON DELETE CASCADE`

---

## Tables Messagerie

### Table `conversation`
**Rôle** : Conversations créées automatiquement lors des réservations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_conversation` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_reservation` | INT | NOT NULL, FK → reservation(id_reservation) | Réservation liée |
| `cree_le` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `derniere_activite` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière activité (message envoyé) |
| `archivee` | BOOLEAN | DEFAULT 0 | Conversation archivée |

**Index** : `idx_derniere_activite` sur `derniere_activite`

**Contraintes** : `ON DELETE CASCADE`

**Relation** : Chaque réservation crée automatiquement une conversation 1:1.

---

### Table `message`
**Rôle** : Messages échangés dans les conversations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_message` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_conversation` | INT | NOT NULL, FK → conversation(id_conversation) | Conversation parente |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Auteur du message |
| `contenu` | TEXT | NOT NULL | Contenu du message |
| `date_envoi` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'envoi |
| `lu` | BOOLEAN | DEFAULT 0 | Message lu |
| `date_lecture` | TIMESTAMP | NULL | Date de lecture |
| `supprime` | BOOLEAN | DEFAULT 0 | Message supprimé (soft delete) |
| `fichier_joint` | VARCHAR(255) | NULL | Chemin du fichier joint |

**Index** :
- `idx_conversation` sur `id_conversation`
- `idx_date` sur `date_envoi`
- `idx_lu` sur `lu`

**Contraintes** : `ON DELETE CASCADE`

---

### Table `document`
**Rôle** : Fichiers uploadés (documents partagés ou fichiers joints aux messages).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_document` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Propriétaire du document |
| `id_message` | INT | NULL, FK → message(id_message) | Message associé (si fichier joint) |
| `nom_original` | VARCHAR(255) | NOT NULL | Nom original du fichier |
| `fichier_path` | VARCHAR(255) | NOT NULL | Chemin de stockage |
| `type_fichier` | VARCHAR(100) | NULL | Type MIME (application/pdf, image/png, etc.) |
| `taille_octets` | INT | NULL | Taille en octets |
| `categorie` | VARCHAR(100) | NULL | Catégorie du document |
| `source` | ENUM('upload','messaging') | DEFAULT 'upload' | Origine du fichier |
| `uploaded_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'upload |

**Index** :
- `idx_utilisateur` sur `id_utilisateur`
- `idx_source` sur `source`
- `idx_uploaded_at` sur `uploaded_at`

**Contraintes** :
- `ON DELETE CASCADE` pour `id_utilisateur`
- `ON DELETE SET NULL` pour `id_message` (conserve le document si message supprimé)

---

### Table `message_reaction`
**Rôle** : Réactions aux messages (like, love, etc.).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_reaction` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_message` | INT | NOT NULL, FK → message(id_message) | Message réagi |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Utilisateur qui réagit |
| `type_reaction` | ENUM('like','love','laugh','wow','sad','angry') | NOT NULL | Type de réaction |
| `date_reaction` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de la réaction |

**Contraintes** :
- `UNIQUE KEY unique_user_message_reaction (id_utilisateur, id_message)` : Une seule réaction par utilisateur/message
- `ON DELETE CASCADE`

---

## Tables Support

### Table `ticket_support`
**Rôle** : Tickets de support créés par les utilisateurs.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_ticket` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Créateur du ticket |
| `sujet` | VARCHAR(255) | NOT NULL | Sujet du ticket |
| `categorie` | ENUM('technique','paiement','compte','reservation','autre') | DEFAULT 'autre' | Catégorie |
| `statut_ticket` | ENUM('ouvert','en_cours','resolu','ferme') | DEFAULT 'ouvert' | État du ticket |
| `priorite` | ENUM('basse','normale','haute','urgente') | DEFAULT 'normale' | Priorité |
| `cree_le` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `ferme_le` | TIMESTAMP | NULL | Date de fermeture |
| `dernier_message` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date du dernier message |

**Index** :
- `idx_statut` sur `statut_ticket`
- `idx_priorite` sur `priorite`
- `idx_date` sur `cree_le`

**Contraintes** : `ON DELETE CASCADE`

---

### Table `message_ticket`
**Rôle** : Messages échangés dans les tickets de support.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_message_ticket` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `id_ticket` | INT | NOT NULL, FK → ticket_support(id_ticket) | Ticket parent |
| `id_utilisateur` | INT | NOT NULL, FK → users(id) | Auteur du message |
| `contenu` | TEXT | NOT NULL | Contenu du message |
| `date_envoi` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'envoi |
| `fichier_joint` | VARCHAR(255) | NULL | Fichier joint (capture d'écran, log, etc.) |
| `est_admin` | BOOLEAN | DEFAULT 0 | Message envoyé par un admin |

**Index** :
- `idx_ticket` sur `id_ticket`
- `idx_date` sur `date_envoi`

**Contraintes** : `ON DELETE CASCADE`

**Note** : `est_admin` permet de distinguer les réponses de l'équipe support des messages utilisateur.

---

## Tables Système & Logs

### Table `captcha_questions`
**Rôle** : Questions CAPTCHA pour sécuriser l'inscription/connexion.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `question` | VARCHAR(255) | NOT NULL | Question CAPTCHA |
| `reponse` | VARCHAR(100) | NOT NULL | Réponse attendue (sensible à la casse) |
| `actif` | BOOLEAN | DEFAULT 1 | Question active |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** : `idx_actif` sur `actif`

**Données initiales** : 20 questions (capitale France, 5+3, couleurs, etc.)

**Exemple** :
- Question : "Quelle est la capitale de la France ?"
- Réponse : "Paris"

---

### Table `sessions_actives`
**Rôle** : Tracking des sessions utilisateurs actives (pour monitoring temps réel).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `user_id` | INT | NOT NULL, FK → users(id) | Utilisateur connecté |
| `session_php_id` | VARCHAR(128) | NOT NULL, UNIQUE | ID de session PHP |
| `ip_address` | VARCHAR(45) | NULL | Adresse IP (IPv4/IPv6) |
| `user_agent` | VARCHAR(255) | NULL | Navigateur/OS |
| `current_url` | VARCHAR(255) | NULL | Page actuelle |
| `derniere_activite` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Dernière activité |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index** :
- `idx_user` sur `user_id`
- `idx_activite` sur `derniere_activite`

**Contraintes** :
- `UNIQUE KEY unique_session (session_php_id)`
- `ON DELETE CASCADE`

**Utilisation** : Requête pour utilisateurs en ligne : `WHERE derniere_activite > NOW() - INTERVAL 5 MINUTE`

---

### Table `logs_connexions`
**Rôle** : Logs de toutes les tentatives de connexion (succès/échec).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `user_id` | INT | NULL, FK → users(id) | Utilisateur (NULL si échec) |
| `email` | VARCHAR(255) | NULL | Email utilisé pour connexion |
| `ip_address` | VARCHAR(45) | NULL | Adresse IP |
| `user_agent` | VARCHAR(255) | NULL | Navigateur/OS |
| `statut` | ENUM('success','failed') | NOT NULL | Résultat de la tentative |
| `raison_echec` | VARCHAR(255) | NULL | Raison de l'échec (mauvais mot de passe, compte inactif, etc.) |
| `date_connexion` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de la tentative |

**Index** :
- `idx_statut` sur `statut`
- `idx_date` sur `date_connexion`
- `idx_ip` sur `ip_address`

**Contraintes** : `ON DELETE SET NULL` (conserve les logs même si utilisateur supprimé)

**Analyse sécurité** :
```sql
-- Tentatives échouées par IP (détection bruteforce)
SELECT ip_address, COUNT(*) as tentatives
FROM logs_connexions
WHERE statut = 'failed' AND date_connexion > NOW() - INTERVAL 1 HOUR
GROUP BY ip_address
HAVING tentatives > 5;
```

---

### Table `logs_visites`
**Rôle** : Logs des pages visitées (analytics, parcours utilisateur).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `user_id` | INT | NULL, FK → users(id) | Utilisateur (NULL si non connecté) |
| `session_token` | VARCHAR(64) | NULL | Token de session (pour visiteurs anonymes) |
| `page_url` | VARCHAR(255) | NOT NULL | URL de la page visitée |
| `ip_address` | VARCHAR(45) | NULL | Adresse IP |
| `duree_visite` | INT | NULL | Durée de visite (secondes) |
| `date_visite` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de la visite |

**Index** :
- `idx_user` sur `user_id`
- `idx_date` sur `date_visite`
- `idx_page` sur `page_url`

**Contraintes** : `ON DELETE SET NULL`

**Analytics** :
```sql
-- Pages les plus visitées
SELECT page_url, COUNT(*) as visites
FROM logs_visites
GROUP BY page_url
ORDER BY visites DESC
LIMIT 10;
```

---

### Table `newsletter`
**Rôle** : Inscriptions à la newsletter.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email inscrit |
| `prenom` | VARCHAR(100) | NULL | Prénom (optionnel) |
| `actif` | BOOLEAN | DEFAULT 1 | Abonnement actif |
| `date_inscription` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'inscription |
| `date_desinscription` | TIMESTAMP | NULL | Date de désinscription |
| `token_desinscription` | VARCHAR(64) | UNIQUE | Token unique pour se désinscrire |

**Index** :
- `idx_actif` sur `actif`
- `idx_email` sur `email`

**Processus de désinscription** : Lien avec token → `UPDATE newsletter SET actif = 0, date_desinscription = NOW() WHERE token_desinscription = ?`

---

### Table `newsletter_envoi`
**Rôle** : Historique des campagnes newsletter envoyées.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_envoi` | INT | PK, AUTO_INCREMENT | Identifiant unique |
| `sujet` | VARCHAR(255) | NOT NULL | Sujet de l'email |
| `contenu` | TEXT | NOT NULL | Corps de l'email (HTML) |
| `nb_destinataires` | INT | DEFAULT 0 | Nombre de destinataires |
| `date_envoi` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'envoi |
| `envoye_par` | INT | NULL, FK → users(id) | Admin qui a envoyé |

**Contraintes** : `ON DELETE SET NULL`

---

## Vues SQL

### Vue `vue_stats_professeurs`
**Rôle** : Statistiques agrégées pour chaque professeur (réservations, avis, revenu).

```sql
CREATE VIEW vue_stats_professeurs AS
SELECT
    u.id,
    u.nom,
    u.prenom,
    u.email,
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

**Colonnes** :
- `id`, `nom`, `prenom`, `email` : Infos professeur
- `nb_reservations` : Nombre de réservations terminées
- `note_moyenne` : Moyenne des notes (1-5)
- `nb_avis` : Nombre d'avis reçus
- `revenu_total` : Somme des prix_fige (revenu HT total)

**Utilisation** :
```sql
-- Meilleurs professeurs
SELECT * FROM vue_stats_professeurs ORDER BY note_moyenne DESC LIMIT 10;
```

---

### Vue `vue_reservations_details`
**Rôle** : Vue complète des réservations avec tous les détails (étudiant, professeur, cours, matière).

```sql
CREATE VIEW vue_reservations_details AS
SELECT
    r.id_reservation,
    r.date_reservation,
    r.statut_reservation,
    r.mode_choisi,
    r.prix_fige,
    r.montant_ttc,
    etudiant.nom as nom_etudiant,
    etudiant.prenom as prenom_etudiant,
    etudiant.email as email_etudiant,
    professeur.nom as nom_professeur,
    professeur.prenom as prenom_professeur,
    c.date_debut,
    c.date_fin,
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

**Utilisation** :
```sql
-- Réservations d'un étudiant
SELECT * FROM vue_reservations_details WHERE email_etudiant = 'student@prof-it.fr';

-- Réservations à venir
SELECT * FROM vue_reservations_details WHERE date_debut > NOW() AND statut_reservation = 'confirmee';
```

---

## Données Initiales

### Rôles
```sql
INSERT INTO roles (code_role, nom_role, description) VALUES
('ADMIN', 'Administrateur', 'Accès complet à toutes les fonctionnalités'),
('TEACHER', 'Professeur', 'Peut créer des offres et gérer ses cours'),
('STUDENT', 'Étudiant', 'Peut rechercher et réserver des cours');
```

### Utilisateur Admin par Défaut
```sql
-- Email: admin@prof-it.fr
-- Mot de passe: password
INSERT INTO users (nom, prenom, email, password, role, actif, email_verifie) VALUES
('Admin', 'Super', 'admin@prof-it.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1);
```

### Matières (10)
```sql
INSERT INTO matiere (nom_matiere, description, icone) VALUES
('Mathématiques', 'Algèbre, géométrie, analyse', 'fa-calculator'),
('Français', 'Grammaire, littérature, expression écrite', 'fa-book'),
('Anglais', 'Langue anglaise tous niveaux', 'fa-language'),
('Physique-Chimie', 'Sciences physiques et chimie', 'fa-flask'),
('Histoire-Géographie', 'Histoire et géographie', 'fa-globe'),
('Informatique', 'Programmation, bureautique', 'fa-laptop-code'),
('SVT', 'Sciences de la vie et de la terre', 'fa-dna'),
('Philosophie', 'Philosophie et pensée critique', 'fa-brain'),
('Économie', 'Sciences économiques et sociales', 'fa-chart-line'),
('Espagnol', 'Langue espagnole', 'fa-language');
```

### Questions CAPTCHA (20)
Exemples :
- "Quelle est la capitale de la France ?" → "Paris"
- "Combien font 5 + 3 ?" → "8"
- "Combien de jours dans une semaine ?" → "7"
- "Quelle couleur obtient-on en mélangeant bleu et jaune ?" → "vert"
- ...

---

## Scripts de Vérification

### Vérification de la création
```sql
SELECT
    'Base de données PROF-IT créée avec succès!' as message,
    COUNT(*) as nb_tables
FROM information_schema.tables
WHERE table_schema = 'projet_profit';
```

### Liste des tables
```sql
SELECT
    table_name as 'Table',
    table_rows as 'Lignes'
FROM information_schema.tables
WHERE table_schema = 'projet_profit'
ORDER BY table_name;
```

---

**Dernière mise à jour** : Janvier 2025
**Version BDD** : 1.0
**Compatibilité** : MySQL 5.7+, MariaDB 10.3+

---

Pour plus d'informations, consultez :
- [ARCHITECTURE.md](ARCHITECTURE.md) - Architecture globale
- [API_REFERENCE.md](API_REFERENCE.md) - APIs utilisant ces tables
- [SECURITY.md](SECURITY.md) - Sécurité de la BDD

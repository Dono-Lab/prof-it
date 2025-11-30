# Référence API - Prof-IT

Documentation complète de toutes les APIs REST du projet Prof-IT.

---

## Table des Matières
- [Vue d'Ensemble](#vue-densemble)
- [API Appointments](#api-appointments) - Gestion des rendez-vous et créneaux
- [API Messaging](#api-messaging) - Système de messagerie
- [API Support](#api-support) - Tickets de support
- [API Admin - Users](#api-admin---users) - Gestion utilisateurs
- [API Admin - Stats](#api-admin---stats) - Statistiques
- [API Admin - Tickets](#api-admin---tickets) - Support admin
- [API Admin - CAPTCHA](#api-admin---captcha) - Questions CAPTCHA
- [API Admin - Logs](#api-admin---logs) - Journaux système
- [API Admin - Live Users](#api-admin---live-users) - Utilisateurs en ligne
- [Codes d'Erreur](#codes-derreur)

---

## Vue d'Ensemble

### Format des Réponses

Toutes les APIs retournent des réponses au format JSON :

**Succès** :
```json
{
  "success": true,
  "data": { ... },
  "message": "Opération réussie" // optionnel
}
```

**Erreur** :
```json
{
  "success": false,
  "error": "Message d'erreur descriptif"
}
```

### Authentification

La plupart des endpoints nécessitent une session PHP active avec les variables suivantes :
- `$_SESSION['user_id']` : ID de l'utilisateur connecté
- `$_SESSION['role']` : Rôle (student, teacher, admin)

### Protection CSRF

Tous les endpoints POST/PUT/DELETE nécessitent un token CSRF :
```json
{
  "csrf_token": "abc123...",
  // autres paramètres...
}
```

Le token est généré via `csrf_token()` et vérifié avec `verify_csrf()`.

---

## API Appointments

**Fichier** : `/api/appointments.php`
**Authentification** : Requise
**Rôles** : student, teacher

### GET - Available Slots

Récupère les créneaux disponibles.

**URL** : `GET /api/appointments.php?action=available_slots`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `action` | string | Oui | "available_slots" |
| `offre_id` | int | Non | Filtrer par offre de cours |

**Réponse** :
```json
{
  "success": true,
  "slots": [
    {
      "id_creneau": 1,
      "date_debut": "2025-02-01 14:00:00",
      "date_fin": "2025-02-01 16:00:00",
      "tarif_horaire": 25.00,
      "mode_propose": "presentiel,visio",
      "lieu": "Paris 15ème",
      "titre_cours": "Soutien Mathématiques Lycée",
      "nom_professeur": "Dupont",
      "prenom_professeur": "Jean",
      "photo_professeur": "https://..."
    }
  ]
}
```

**Logique** :
- Si **student** : Retourne tous les créneaux publics (statut = 'disponible', date_debut > NOW)
- Si **teacher** : Retourne ses propres créneaux

---

### GET - Upcoming Appointments

Récupère les rendez-vous à venir de l'utilisateur.

**URL** : `GET /api/appointments.php?action=upcoming_appointments`

**Réponse** :
```json
{
  "success": true,
  "appointments": [
    {
      "id_reservation": 5,
      "date_debut": "2025-02-05 10:00:00",
      "date_fin": "2025-02-05 12:00:00",
      "statut_reservation": "confirmee",
      "mode_choisi": "visio",
      "prix_fige": 50.00,
      "montant_ttc": 60.00,
      "titre_cours": "Cours d'Anglais",
      "nom_professeur": "Martin",
      "prenom_professeur": "Sophie",
      "nom_etudiant": "Durand",
      "prenom_etudiant": "Pierre",
      "statut_affichage": "a_venir" // calculé via compute_course_status()
    }
  ]
}
```

**Filtrage** : `statut_reservation IN ('confirmee', 'en_attente', 'en_cours')` AND `date_fin >= NOW()`

---

### GET - History Appointments

Récupère l'historique des cours passés (10 derniers).

**URL** : `GET /api/appointments.php?action=history_appointments`

**Réponse** : Même structure que `upcoming_appointments`, mais filtre sur :
- `statut_reservation = 'terminee'` OR `date_fin < NOW()`
- `LIMIT 10` triés par `date_reservation DESC`

---

### GET - Stats

Statistiques personnelles de l'utilisateur.

**URL** : `GET /api/appointments.php?action=stats`

**Réponse pour student** :
```json
{
  "success": true,
  "cours_month": 5,
  "total_heures": 12.5
}
```

**Réponse pour teacher** :
```json
{
  "success": true,
  "sessions_month": 8,
  "total_heures": 20.0
}
```

---

### GET - Teachers

Liste tous les professeurs actifs avec leurs offres.

**URL** : `GET /api/appointments.php?action=teachers`

**Réponse** :
```json
{
  "success": true,
  "teachers": [
    {
      "id": 3,
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean.dupont@prof-it.fr",
      "photo_url": "https://...",
      "bio": "Professeur de mathématiques...",
      "offres": [
        {
          "id_offre": 1,
          "titre": "Soutien Maths Lycée",
          "tarif_horaire_defaut": 30.00
        }
      ]
    }
  ]
}
```

---

### GET - Search Courses

Recherche de cours par titre ou matière.

**URL** : `GET /api/appointments.php?action=search_courses&query=maths`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `query` | string | Oui | Terme de recherche |
| `scope` | string | Non | "public" (défaut) ou "teacher" (mes cours) |

**Réponse** :
```json
{
  "success": true,
  "results": [
    {
      "id_offre": 1,
      "titre": "Soutien Mathématiques Lycée",
      "description": "...",
      "niveau": "lycee",
      "tarif_horaire_defaut": 25.00,
      "nom_professeur": "Dupont Jean"
    }
  ]
}
```

**Recherche** : `WHERE titre LIKE '%maths%' OR nom_matiere LIKE '%maths%'`

---

### POST - Book Slot

Réserver un créneau (étudiants uniquement).

**URL** : `POST /api/appointments.php?action=book_slot`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `creneau_id` | int | Oui | ID du créneau à réserver |
| `mode_choisi` | string | Oui | "presentiel" ou "visio" |

**Réponse succès** :
```json
{
  "success": true,
  "reservation_id": 12,
  "conversation_id": 8,
  "message": "Réservation effectuée avec succès"
}
```

**Validations** :
1. Créneau existe et statut = 'disponible'
2. `date_debut > NOW()` (créneau futur)
3. `mode_choisi` est dans `mode_propose` du créneau
4. Utilisateur a le rôle 'student'

**Actions** :
1. Calcul prix : `montant_HT = tarif_horaire × (date_fin - date_debut en heures)`
2. Calcul TTC : `montant_TTC = montant_HT × 1.20` (TVA 20%)
3. `INSERT INTO reservation` (statut: 'en_attente', prix_fige, tva, mode_choisi)
4. `INSERT INTO conversation` (id_reservation)
5. `UPDATE creneau SET statut_creneau = 'reserve'`

---

### POST - Create Slot

Créer un créneau de disponibilité (professeurs uniquement).

**URL** : `POST /api/appointments.php?action=create_slot`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `offre_id` | int | Oui | ID de l'offre de cours |
| `date_debut` | datetime | Oui | Date/heure début (ISO: Y-m-d H:i) |
| `date_fin` | datetime | Oui | Date/heure fin |
| `tarif_horaire` | decimal | Oui | Tarif €/heure |
| `mode_propose` | string | Oui | "presentiel", "visio" ou "presentiel,visio" |
| `lieu` | string | Non | Lieu si mode présentiel |

**Réponse** :
```json
{
  "success": true,
  "creneau_id": 15,
  "message": "Créneau créé avec succès"
}
```

**Validations** :
1. `date_debut < date_fin`
2. `date_debut > NOW()`
3. `tarif_horaire > 0`
4. `mode_propose` dans whitelist ('presentiel', 'visio', 'domicile')
5. Professeur possède l'offre (`enseigner`)

---

### POST - Update Status

Modifier le statut d'une réservation (professeurs uniquement).

**URL** : `POST /api/appointments.php?action=update_status`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `reservation_id` | int | Oui | ID réservation |
| `statut` | string | Oui | Nouveau statut |

**Statuts possibles** : `en_attente`, `confirmee`, `terminee`, `annulee`, `en_cours`

**Réponse** :
```json
{
  "success": true,
  "message": "Statut mis à jour : confirmee"
}
```

**Vérifications** :
- Le professeur est bien le créateur du créneau lié à la réservation
- Transitions valides (ex: en_attente → confirmee)

---

## API Messaging

**Fichier** : `/api/messaging.php`
**Authentification** : Requise
**Rôles** : student, teacher

### GET - Conversations

Liste des conversations de l'utilisateur.

**URL** : `GET /api/messaging.php?action=conversations`

**Réponse** :
```json
{
  "success": true,
  "conversations": [
    {
      "id_conversation": 3,
      "id_reservation": 5,
      "cree_le": "2025-01-15 10:00:00",
      "derniere_activite": "2025-01-20 14:30:00",
      "dernier_message": {
        "contenu": "D'accord pour demain !",
        "date_envoi": "2025-01-20 14:30:00",
        "auteur": "Jean Dupont"
      },
      "nb_non_lus": 2,
      "contact": {
        "nom": "Dupont",
        "prenom": "Jean",
        "photo_url": "https://...",
        "role": "teacher"
      },
      "cours": {
        "titre": "Soutien Maths",
        "date_debut": "2025-01-22 10:00:00"
      }
    }
  ]
}
```

**Logique** :
- Filtre : `archivee = 0`
- Jointures : reservation → creneau → offre_cours → users
- Contact = l'autre personne (si student → teacher, si teacher → student)

---

### GET - Messages

Messages d'une conversation spécifique.

**URL** : `GET /api/messaging.php?action=messages&conversation_id=3`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `conversation_id` | int | Oui | ID conversation |

**Réponse** :
```json
{
  "success": true,
  "messages": [
    {
      "id_message": 10,
      "contenu": "Bonjour, pouvez-vous m'aider en algèbre ?",
      "date_envoi": "2025-01-15 10:05:00",
      "lu": true,
      "date_lecture": "2025-01-15 10:10:00",
      "fichier_joint": null,
      "auteur": {
        "id": 2,
        "nom": "Durand",
        "prenom": "Pierre",
        "photo_url": "https://...",
        "role": "student"
      }
    },
    {
      "id_message": 11,
      "contenu": "Bien sûr ! Voici un document.",
      "date_envoi": "2025-01-15 10:12:00",
      "lu": true,
      "fichier_joint": "uploads/messages/3/msg_abc123_document.pdf",
      "auteur": {
        "id": 3,
        "nom": "Dupont",
        "prenom": "Jean",
        "role": "teacher"
      }
    }
  ]
}
```

**Vérification** : L'utilisateur doit être participant à la conversation (via reservation).

---

### POST - Send Message

Envoyer un message dans une conversation.

**URL** : `POST /api/messaging.php?action=send_message`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `conversation_id` | int | Oui | ID conversation |
| `contenu` | string | Oui | Texte du message |
| `fichier_joint` | file | Non | Fichier à joindre (upload) |

**Validations fichier** :
- Extensions autorisées : pdf, doc, docx, xls, xlsx, ppt, pptx, png, jpg, jpeg, txt
- Taille max : 10 MB (10 × 1024 × 1024)
- Sanitize nom : `preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $nom)`
- Nom final : `uniqid('msg_', true) . '_' . $nom_securise`

**Réponse** :
```json
{
  "success": true,
  "message_id": 20,
  "fichier_path": "uploads/messages/3/msg_123_document.pdf"
}
```

**Actions** :
1. Valide ownership de la conversation
2. Upload fichier si fourni → `uploads/messages/{conversation_id}/`
3. `INSERT INTO message` (contenu, fichier_joint, id_utilisateur, id_conversation)
4. Si fichier : `INSERT INTO document` (source: 'messaging')
5. `UPDATE conversation SET derniere_activite = NOW()`

---

### POST - Mark as Read

Marquer les messages comme lus.

**URL** : `POST /api/messaging.php?action=mark_as_read`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `conversation_id` | int | Oui | ID conversation |

**Réponse** :
```json
{
  "success": true,
  "messages_updated": 3
}
```

**Action** :
```sql
UPDATE message
SET lu = 1, date_lecture = NOW()
WHERE id_conversation = ? AND id_utilisateur != ? AND lu = 0
```

---

### POST - Delete Conversation

Supprimer une conversation (et ses fichiers).

**URL** : `POST /api/messaging.php?action=delete_conversation`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `conversation_id` | int | Oui | ID conversation |

**Réponse** :
```json
{
  "success": true,
  "message": "Conversation supprimée"
}
```

**Actions** :
1. Supprime fichiers physiques dans `uploads/messages/{id}/`
2. `DELETE FROM conversation` (CASCADE supprime messages, documents liés)

---

### POST - Submit Review

Laisser un avis après un cours terminé (étudiants uniquement).

**URL** : `POST /api/messaging.php?action=submit_review`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `conversation_id` | int | Oui | ID conversation |
| `note` | int | Oui | Note de 1 à 5 |
| `commentaire` | string | Non | Commentaire textuel |

**Validations** :
1. Note entre 1 et 5 (inclus)
2. Réservation liée à la conversation a `statut_reservation = 'terminee'`
3. Utilisateur est l'étudiant de la réservation
4. Pas d'avis existant (contrainte UNIQUE sur id_reservation)

**Réponse** :
```json
{
  "success": true,
  "avis_id": 7,
  "message": "Avis enregistré avec succès"
}
```

---

## API Support

**Fichier** : `/api/support.php`
**Authentification** : Requise
**Rôles** : Tous (student, teacher, admin)

### GET - Tickets

Liste des tickets de l'utilisateur.

**URL** : `GET /api/support.php?action=tickets`

**Réponse** :
```json
{
  "success": true,
  "tickets": [
    {
      "id_ticket": 5,
      "sujet": "Problème de paiement",
      "categorie": "paiement",
      "statut_ticket": "en_cours",
      "priorite": "haute",
      "cree_le": "2025-01-18 09:00:00",
      "dernier_message": "2025-01-19 14:30:00",
      "nb_messages": 3
    }
  ]
}
```

**Tri** : `ORDER BY cree_le DESC`

---

### GET - Stats

Statistiques des tickets de l'utilisateur.

**URL** : `GET /api/support.php?action=stats`

**Réponse** :
```json
{
  "success": true,
  "total": 10,
  "waiting": 2,
  "closed": 7,
  "open": 1
}
```

---

### GET - Ticket Details

Détails d'un ticket avec messages paginés.

**URL** : `GET /api/support.php?action=ticket_details&ticket_id=5&page=1`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `ticket_id` | int | Oui | ID du ticket |
| `page` | int | Non | Numéro de page (défaut: 1, 10 messages/page) |

**Réponse** :
```json
{
  "success": true,
  "ticket": {
    "id_ticket": 5,
    "sujet": "Problème de paiement",
    "categorie": "paiement",
    "statut_ticket": "en_cours",
    "priorite": "haute",
    "cree_le": "2025-01-18 09:00:00"
  },
  "messages": [
    {
      "id_message_ticket": 10,
      "contenu": "Ma carte bancaire est refusée",
      "date_envoi": "2025-01-18 09:00:00",
      "est_admin": false,
      "auteur": "Pierre Durand"
    },
    {
      "id_message_ticket": 11,
      "contenu": "Nous allons vérifier votre compte",
      "date_envoi": "2025-01-18 10:30:00",
      "est_admin": true,
      "auteur": "Support Prof-IT"
    }
  ],
  "pagination": {
    "page": 1,
    "total_pages": 1,
    "total_messages": 2
  }
}
```

---

### POST - Create Ticket

Créer un nouveau ticket de support.

**URL** : `POST /api/support.php?action=create_ticket`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `sujet` | string | Oui | Sujet du ticket |
| `categorie` | string | Oui | Catégorie (technique, paiement, compte, reservation, autre) |
| `priorite` | string | Oui | Priorité (basse, normale, haute, urgente) |
| `description` | string | Oui | Description (min 20 caractères) |

**Validations** :
- `description` : minimum 20 caractères
- `categorie` : dans whitelist
- `priorite` : dans whitelist

**Réponse** :
```json
{
  "success": true,
  "ticket_id": 12,
  "message": "Ticket créé avec succès"
}
```

**Actions** :
1. `INSERT INTO ticket_support` (statut: 'ouvert')
2. `INSERT INTO message_ticket` (message initial, est_admin: 0)

---

### POST - Reply Ticket

Répondre à un ticket.

**URL** : `POST /api/support.php?action=reply_ticket`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `ticket_id` | int | Oui | ID du ticket |
| `message` | string | Oui | Contenu réponse (min 3 caractères) |

**Réponse** :
```json
{
  "success": true,
  "message_id": 25
}
```

**Actions** :
1. `INSERT INTO message_ticket` (est_admin: 0 pour user, 1 pour admin)
2. Si ticket était 'resolu' ou 'ferme' → `UPDATE statut_ticket = 'en_cours'`
3. `UPDATE ticket_support SET dernier_message = NOW()`

---

## API Admin - Users

**Fichier** : `/admin/api/manage_user.php`
**Authentification** : Requise (admin uniquement)
**Protection** : `require_admin_api()`

### POST - Create User

Créer un nouvel utilisateur.

**URL** : `POST /admin/api/manage_user.php?action=create`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `nom` | string | Oui | Nom |
| `prenom` | string | Oui | Prénom |
| `email` | string | Oui | Email (unique) |
| `password` | string | Oui | Mot de passe (min 6 car.) |
| `role` | string | Oui | student, teacher ou admin |
| `telephone` | string | Non | Téléphone |
| `adresse` | string | Non | Adresse |
| `ville` | string | Non | Ville |
| `code_postal` | string | Non | Code postal |
| `actif` | boolean | Non | Actif (défaut: 1) |

**Validations** :
- Email valide : `filter_var($email, FILTER_VALIDATE_EMAIL)`
- Email unique en BDD
- Role dans whitelist : ['student', 'teacher', 'admin']
- Password hashé : `password_hash($password, PASSWORD_DEFAULT)`

**Réponse** :
```json
{
  "success": true,
  "user_id": 15,
  "message": "Utilisateur créé avec succès"
}
```

---

### POST - Update User

Modifier un utilisateur existant.

**URL** : `POST /admin/api/manage_user.php?action=update`

**Paramètres** : Mêmes que `create` + `id` (ID utilisateur)

**Spécificités** :
- `password` : optionnel (si fourni, sera hashé)
- Email unique : sauf si c'est le même utilisateur

**Réponse** :
```json
{
  "success": true,
  "message": "Utilisateur mis à jour"
}
```

---

### POST - Delete User

Supprimer un utilisateur.

**URL** : `POST /admin/api/manage_user.php?action=delete`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `id` | int | Oui | ID utilisateur |

**Vérification** : Ne peut pas supprimer son propre compte.

**Réponse** :
```json
{
  "success": true,
  "message": "Utilisateur supprimé"
}
```

**Cascade** : Supprime aussi créneaux, réservations, messages, etc. (ON DELETE CASCADE).

---

## API Admin - Stats

**Fichier** : `/admin/api/get_stats.php`
**Authentification** : Requise (admin uniquement)

### GET - Stats

Statistiques globales du dashboard.

**URL** : `GET /admin/api/get_stats.php?action=stats`

**Réponse** :
```json
{
  "success": true,
  "stats": {
    "total_users": 150,
    "students": 120,
    "teachers": 25,
    "admins": 5,
    "newsletter": 80,
    "visits_today": 45
  }
}
```

---

### GET - Recent Users

5 derniers utilisateurs inscrits.

**URL** : `GET /admin/api/get_stats.php?action=recent_users`

**Réponse** :
```json
{
  "success": true,
  "users": [
    {
      "id": 45,
      "nom": "Martin",
      "prenom": "Sophie",
      "email": "sophie.martin@example.com",
      "role": "student",
      "created_at": "2025-01-20 10:30:00"
    }
  ]
}
```

**Limite** : 5 utilisateurs, `ORDER BY created_at DESC`

---

### GET - Recent Logs

10 dernières connexions.

**URL** : `GET /admin/api/get_stats.php?action=recent_logs`

**Réponse** :
```json
{
  "success": true,
  "logs": [
    {
      "email": "admin@prof-it.fr",
      "statut": "success",
      "date_connexion": "2025-01-20 14:30:00",
      "ip_address": "192.168.1.100"
    }
  ]
}
```

**Limite** : 10 logs

---

### GET - Chart Inscriptions

Inscriptions des 12 derniers mois (pour Chart.js).

**URL** : `GET /admin/api/get_stats.php?action=chart_inscriptions`

**Réponse** :
```json
{
  "success": true,
  "chartData": {
    "labels": ["Fév 2024", "Mar 2024", ..., "Jan 2025"],
    "data": [5, 8, 12, 15, 20, 18, 22, 25, 30, 28, 35, 40]
  }
}
```

**Logique** :
- GROUP BY mois (DATE_FORMAT(created_at, '%Y-%m'))
- Remplit les mois manquants avec 0
- Retourne 12 derniers mois

---

### GET - Top Pages

Pages les plus visitées.

**URL** : `GET /admin/api/get_stats.php?action=top_pages&period=week`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `period` | string | Non | Période (day, week, month, all) - défaut: week |

**Réponse** :
```json
{
  "success": true,
  "pages": [
    {
      "page_url": "/student/student_page.php",
      "visites": 150
    },
    {
      "page_url": "/admin/dashboard.php",
      "visites": 80
    }
  ],
  "total_period": 450
}
```

**Filtrage** :
- `day` : `date_visite >= DATE_SUB(NOW(), INTERVAL 1 DAY)`
- `week` : `INTERVAL 7 DAY`
- `month` : `INTERVAL 30 DAY`
- `all` : Aucun filtre

---

## API Admin - Tickets

**Fichier** : `/admin/api/tickets.php`
**Authentification** : Requise (admin uniquement)

### GET - List Tickets

Liste des tickets avec filtres et pagination.

**URL** : `GET /admin/api/tickets.php?search=paiement&statut=ouvert&page=1`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `search` | string | Non | Recherche dans sujet |
| `statut` | string | Non | Filtrer par statut |
| `priorite` | string | Non | Filtrer par priorité |
| `categorie` | string | Non | Filtrer par catégorie |
| `page` | int | Non | Page (défaut: 1, 20 tickets/page) |

**Réponse** :
```json
{
  "success": true,
  "tickets": [ ... ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_tickets": 95,
    "per_page": 20
  }
}
```

---

### GET - Ticket Details

Détails avec messages paginés (admin).

**URL** : `GET /admin/api/tickets.php?action=details&ticket_id=5&page=1`

**Réponse** : Similaire à `/api/support.php?action=ticket_details`

---

### POST - Reply

Admin répond à un ticket.

**URL** : `POST /admin/api/tickets.php?action=reply`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `ticket_id` | int | Oui | ID ticket |
| `message` | string | Oui | Contenu |

**Réponse** :
```json
{
  "success": true,
  "message_id": 30
}
```

**Action** : `INSERT INTO message_ticket` avec `est_admin = 1`

---

### POST/PUT - Update

Modifier statut ou priorité d'un ticket.

**URL** : `POST /admin/api/tickets.php?action=update` (ou `PUT`)

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `ticket_id` | int | Oui | ID ticket |
| `statut` | string | Non | Nouveau statut |
| `priorite` | string | Non | Nouvelle priorité |

**Réponse** :
```json
{
  "success": true,
  "message": "Ticket mis à jour"
}
```

**Logique** :
- Si `statut = 'ferme'` → `UPDATE ferme_le = NOW()`

---

### DELETE - Delete Message

Supprimer un message de ticket.

**URL** : `DELETE /admin/api/tickets.php?action=delete` (ou `POST`)

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `message_id` | int | Oui | ID message à supprimer |

**Réponse** :
```json
{
  "success": true,
  "message": "Message supprimé"
}
```

---

## API Admin - CAPTCHA

**Fichier** : `/admin/api/manage_captcha.php`
**Authentification** : Requise (admin uniquement)

### POST - Create

Créer une question CAPTCHA.

**URL** : `POST /admin/api/manage_captcha.php?action=create`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `question` | string | Oui | Question |
| `reponse` | string | Oui | Réponse attendue |
| `actif` | boolean | Non | Active (défaut: 1) |

**Réponse** :
```json
{
  "success": true,
  "captcha_id": 25,
  "message": "Question CAPTCHA créée"
}
```

---

### POST - Update

Modifier une question CAPTCHA.

**URL** : `POST /admin/api/manage_captcha.php?action=update`

**Paramètres** : Mêmes que `create` + `id`

**Réponse** :
```json
{
  "success": true,
  "message": "Question mise à jour"
}
```

---

### POST - Delete

Supprimer une question CAPTCHA.

**URL** : `POST /admin/api/manage_captcha.php?action=delete`

**Paramètres** :
| Nom | Type | Requis | Description |
|-----|------|--------|-------------|
| `csrf_token` | string | Oui | Token CSRF |
| `id` | int | Oui | ID CAPTCHA |

**Réponse** :
```json
{
  "success": true,
  "message": "Question supprimée"
}
```

---

### GET - Get CAPTCHA

Récupérer toutes les questions CAPTCHA.

**Fichier** : `/admin/api/get_captcha.php`

**URL** : `GET /admin/api/get_captcha.php`

**Réponse** :
```json
{
  "success": true,
  "captcha": [
    {
      "id": 1,
      "question": "Quelle est la capitale de la France ?",
      "reponse": "Paris",
      "actif": true,
      "created_at": "2025-01-01 00:00:00"
    }
  ]
}
```

---

## API Admin - Logs

**Fichier** : `/admin/api/get_logs.php`
**Authentification** : Requise (admin uniquement)

### GET - Connexions

Logs des tentatives de connexion.

**URL** : `GET /admin/api/get_logs.php?type=connexions`

**Réponse** :
```json
{
  "success": true,
  "logs": [
    {
      "id": 100,
      "user_id": 5,
      "email": "student@prof-it.fr",
      "statut": "success",
      "ip_address": "192.168.1.50",
      "user_agent": "Mozilla/5.0...",
      "date_connexion": "2025-01-20 14:00:00",
      "raison_echec": null
    }
  ]
}
```

**Limite** : 500 derniers logs, `ORDER BY date_connexion DESC`

---

### GET - Visites

Logs des pages visitées.

**URL** : `GET /admin/api/get_logs.php?type=visites`

**Réponse** :
```json
{
  "success": true,
  "logs": [
    {
      "id": 200,
      "user_id": 3,
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean.dupont@prof-it.fr",
      "page_url": "/teacher/teacher_page.php",
      "date_visite": "2025-01-20 14:05:00",
      "duree_visite": 120
    }
  ]
}
```

**Limite** : 500 derniers logs

---

## API Admin - Live Users

**Fichier** : `/admin/api/get_live_user.php`
**Authentification** : Requise (admin uniquement)

### GET - Live Users

Utilisateurs connectés (activité < 5 min).

**URL** : `GET /admin/api/get_live_user.php`

**Réponse** :
```json
{
  "success": true,
  "users": [
    {
      "user_id": 5,
      "nom": "Durand",
      "prenom": "Pierre",
      "email": "pierre.durand@prof-it.fr",
      "role": "student",
      "current_url": "/student/student_page.php",
      "derniere_activite": "2025-01-20 14:58:00",
      "ip_address": "192.168.1.100"
    }
  ],
  "stats": {
    "total": 12,
    "students": 8,
    "teachers": 3,
    "admins": 1
  }
}
```

**Filtrage** : `WHERE derniere_activite > NOW() - INTERVAL 5 MINUTE`

---

## Codes d'Erreur

| Code HTTP | Description | Exemple de réponse |
|-----------|-------------|-------------------|
| **200** | Succès | `{"success": true, "data": {...}}` |
| **400** | Requête invalide | `{"success": false, "error": "Paramètre manquant : creneau_id"}` |
| **401** | Non authentifié | `{"success": false, "error": "Utilisateur non connecté"}` |
| **403** | Accès refusé | `{"success": false, "error": "Accès réservé aux admins"}` |
| **404** | Ressource non trouvée | `{"success": false, "error": "Créneau introuvable"}` |
| **500** | Erreur serveur | `{"success": false, "error": "Erreur base de données"}` |

### Messages d'Erreur Courants

**CSRF** :
```json
{
  "success": false,
  "error": "Token CSRF invalide"
}
```

**Validation** :
```json
{
  "success": false,
  "error": "Le champ email est invalide"
}
```

**Permissions** :
```json
{
  "success": false,
  "error": "Action réservée aux professeurs"
}
```

**Ressource** :
```json
{
  "success": false,
  "error": "Créneau déjà réservé"
}
```

---

## Exemples de Requêtes cURL

### Réserver un créneau

```bash
curl -X POST http://localhost/prof-it/api/appointments.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -b "PHPSESSID=abc123..." \
  -d "action=book_slot" \
  -d "csrf_token=xyz789..." \
  -d "creneau_id=5" \
  -d "mode_choisi=visio"
```

### Envoyer un message

```bash
curl -X POST http://localhost/prof-it/api/messaging.php \
  -H "Content-Type: multipart/form-data" \
  -b "PHPSESSID=abc123..." \
  -F "action=send_message" \
  -F "csrf_token=xyz789..." \
  -F "conversation_id=3" \
  -F "contenu=Bonjour professeur !" \
  -F "fichier_joint=@document.pdf"
```

### Créer un utilisateur (admin)

```bash
curl -X POST http://localhost/prof-it/admin/api/manage_user.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -b "PHPSESSID=abc123..." \
  -d "action=create" \
  -d "csrf_token=xyz789..." \
  -d "nom=Dupont" \
  -d "prenom=Marie" \
  -d "email=marie.dupont@example.com" \
  -d "password=SecurePass123" \
  -d "role=student"
```

---

**Dernière mise à jour** : Janvier 2025
**Version API** : 1.0

Pour plus d'informations :
- [DATABASE.md](DATABASE.md) - Schéma de données
- [SECURITY.md](SECURITY.md) - Sécurité des APIs
- [FLOWS.md](FLOWS.md) - Flux de données détaillés

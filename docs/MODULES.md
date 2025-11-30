# Modules du Projet Prof-IT

Ce document détaille le fonctionnement des différents modules de l'application.

## 1. Module Authentification (`auth/`)

Ce module gère l'accès à l'application.

-   **`login_register.php`** : Page unique gérant à la fois la connexion et l'inscription. Utilise des formulaires distincts.
-   **`auth.php`** : Script de traitement PHP qui reçoit les données POST de connexion/inscription.
    -   *Inscription* : Crée un nouvel utilisateur dans la table `users`, hache le mot de passe (`password_hash`), et attribue le rôle par défaut.
    -   *Connexion* : Vérifie l'email et le mot de passe (`password_verify`), initialise la session (`$_SESSION`).
-   **`logout.php`** : Détruit la session et redirige vers la page d'accueil.

## 2. Module Administrateur (`admin/`)

Accessible uniquement aux utilisateurs ayant le rôle `admin`.

-   **`dashboard.php`** : Tableau de bord principal. Affiche les statistiques en temps réel (KPIs) : nombre d'utilisateurs, réservations, revenus.
-   **`users.php`** : Gestion des utilisateurs (CRUD). Permet de voir la liste des inscrits, de modifier leurs informations ou de les supprimer.
-   **`settings.php`** : Configuration globale du site (si applicable) et gestion du profil admin.
-   **`logs.php`** : Visualisation des journaux d'activité (connexions, erreurs) pour la sécurité et le débogage.
-   **`api/`** : Dossier contenant des scripts PHP retournant du JSON, utilisés par les appels AJAX du dashboard (ex: mise à jour dynamique des graphiques).

## 3. Module Professeur (`teacher/`)

Espace dédié aux enseignants pour gérer leur activité.

-   **`teacher_page.php`** : Page d'accueil du professeur. Résumé des prochains cours.
-   **`rdv.php`** : Gestion des disponibilités. Le professeur définit ses créneaux horaires (Date, Heure, Durée, Tarif).
-   **`messagerie.php`** : Interface de chat avec les étudiants.
-   **`documents.php`** : Gestion des fichiers partagés avec les étudiants (supports de cours, exercices).
-   **`settings.php`** : Modification du profil (Bio, Photo, Spécialités).

## 4. Module Étudiant (`student/`)

Espace pour les élèves.

-   **`student_page.php`** : Dashboard étudiant. Affiche les cours à venir et les statistiques d'apprentissage.
-   **`rdv.php`** : Recherche et réservation de cours. L'étudiant voit les créneaux disponibles et peut réserver.
-   **`messagerie.php`** : Communication avec les professeurs.
-   **`documents.php`** : Accès aux documents partagés par les profs.
-   **`settings.php`** : Gestion du profil personnel.

## 5. Bibliothèques Partagées (`includes/`)

Ce dossier contient le code réutilisable par tous les modules.

-   **`functions_user.php`** : Contient des fonctions métiers essentielles.
    -   `get_user_avatar($id)` : Récupère l'URL de l'avatar ou en génère un par défaut.
    -   `get_student_stats($id)` : Calcule les stats pour le dashboard étudiant.
    -   `get_teacher_stats($id)` : Calcule les stats pour le dashboard prof.
    -   `get_upcoming_courses(...)` : Récupère les prochains cours.
-   **`db.php`** (ou dans `config/`) : Gestion de la connexion PDO à la base de données.
-   **`helpers.php`** : Fonctions utilitaires (formatage de dates, nettoyage de chaînes).
-   **`mailer.php`** : Wrapper pour PHPMailer pour l'envoi d'emails.

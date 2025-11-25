DROP DATABASE IF EXISTS projet_profit;

CREATE DATABASE projet_profit 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;
    
USE projet_profit;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL DEFAULT 'student',
    telephone VARCHAR(20) DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    ville VARCHAR(100) DEFAULT NULL,
    code_postal VARCHAR(10) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    actif BOOLEAN DEFAULT 1,
    email_verifie BOOLEAN DEFAULT 0,
    email_verification_token VARCHAR(64) DEFAULT NULL,
    date_derniere_connexion TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code_role VARCHAR(50) NOT NULL UNIQUE,
    nom_role VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE affecter (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_role INT NOT NULL,
    date_affectation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_role) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (id_utilisateur, id_role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matiere (
    id_matiere INT PRIMARY KEY AUTO_INCREMENT,
    nom_matiere VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icone VARCHAR(50) DEFAULT NULL,
    actif BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offre_cours (
    id_offre INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    niveau ENUM('primaire', 'college', 'lycee', 'superieur', 'professionnel', 'tous') DEFAULT 'tous',
    tarif_horaire_defaut DECIMAL(10, 2) NOT NULL,
    duree_seance_defaut INT DEFAULT 60 COMMENT 'Durée en minutes',
    actif BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_actif (actif),
    INDEX idx_tarif (tarif_horaire_defaut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enseigner (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_offre INT NOT NULL,
    date_debut TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fin TIMESTAMP NULL,
    actif BOOLEAN DEFAULT 1,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_offre) REFERENCES offre_cours(id_offre) ON DELETE CASCADE,
    UNIQUE KEY unique_user_offre (id_utilisateur, id_offre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE couvrir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_offre INT NOT NULL,
    id_matiere INT NOT NULL,
    FOREIGN KEY (id_offre) REFERENCES offre_cours(id_offre) ON DELETE CASCADE,
    FOREIGN KEY (id_matiere) REFERENCES matiere(id_matiere) ON DELETE CASCADE,
    UNIQUE KEY unique_offre_matiere (id_offre, id_matiere)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE creneau (
    id_creneau INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL COMMENT 'ID du professeur',
    id_offre INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    tarif_horaire DECIMAL(10, 2) NOT NULL,
    mode_propose ENUM('presentiel', 'visio', 'les_deux') DEFAULT 'les_deux',
    lieu VARCHAR(255) DEFAULT NULL COMMENT 'Pour mode présentiel',
    statut_creneau ENUM('disponible', 'reserve', 'termine', 'annule') DEFAULT 'disponible',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_offre) REFERENCES offre_cours(id_offre) ON DELETE CASCADE,
    INDEX idx_professeur (id_utilisateur),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_statut (statut_creneau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reservation (
    id_reservation INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL COMMENT "ID de l'étudiant",
    id_creneau INT NOT NULL,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut_reservation ENUM('en_attente', 'confirmee', 'terminee', 'annulee') DEFAULT 'en_attente',
    mode_choisi ENUM('presentiel', 'visio') NOT NULL,
    prix_fige DECIMAL(10, 2) NOT NULL COMMENT 'Prix fixé au moment de la réservation',
    tva DECIMAL(5, 2) DEFAULT 20.00,
    montant_ttc DECIMAL(10, 2) GENERATED ALWAYS AS (prix_fige * (1 + tva / 100)) STORED,
    notes TEXT,
    date_annulation TIMESTAMP NULL,
    raison_annulation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_creneau) REFERENCES creneau(id_creneau) ON DELETE CASCADE,
    INDEX idx_etudiant (id_utilisateur),
    INDEX idx_statut (statut_reservation),
    INDEX idx_date (date_reservation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE facture (
    id_facture INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    numero_facture VARCHAR(50) NOT NULL UNIQUE,
    date_emission DATE NOT NULL,
    date_echeance DATE NOT NULL,
    montant_ht DECIMAL(10, 2) NOT NULL,
    montant_tva DECIMAL(10, 2) NOT NULL,
    montant_ttc DECIMAL(10, 2) NOT NULL,
    statut_facture ENUM('en_attente', 'payee', 'annulee', 'remboursee') DEFAULT 'en_attente',
    url_pdf VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    INDEX idx_numero (numero_facture),
    INDEX idx_statut (statut_facture),
    INDEX idx_date_emission (date_emission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paiement (
    id_paiement INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    fournisseur ENUM('stripe', 'paypal', 'virement', 'especes', 'cheque') NOT NULL,
    id_paiement_fournisseur VARCHAR(255) DEFAULT NULL COMMENT 'ID transaction externe',
    montant DECIMAL(10, 2) NOT NULL,
    devise VARCHAR(3) DEFAULT 'EUR',
    statut_paiement ENUM('en_attente', 'reussi', 'echoue', 'rembourse') DEFAULT 'en_attente',
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_remboursement TIMESTAMP NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    INDEX idx_statut (statut_paiement),
    INDEX idx_fournisseur (fournisseur),
    INDEX idx_date (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avis (
    id_avis INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    id_utilisateur INT NOT NULL COMMENT "ID de l'étudiant qui note",
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    verifie BOOLEAN DEFAULT 0 COMMENT 'Avis modéré par admin',
    date_avis TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reservation_avis (id_reservation),
    INDEX idx_note (note),
    INDEX idx_date (date_avis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversation (
    id_conversation INT PRIMARY KEY AUTO_INCREMENT,
    id_reservation INT NOT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    derniere_activite TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archivee BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_reservation) REFERENCES reservation(id_reservation) ON DELETE CASCADE,
    INDEX idx_derniere_activite (derniere_activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message (
    id_message INT PRIMARY KEY AUTO_INCREMENT,
    id_conversation INT NOT NULL,
    id_utilisateur INT NOT NULL COMMENT 'Auteur du message',
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT 0,
    date_lecture TIMESTAMP NULL,
    supprime BOOLEAN DEFAULT 0,
    fichier_joint VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (id_conversation) REFERENCES conversation(id_conversation) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (id_conversation),
    INDEX idx_date (date_envoi),
    INDEX idx_lu (lu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_reaction (
    id_reaction INT PRIMARY KEY AUTO_INCREMENT,
    id_message INT NOT NULL,
    id_utilisateur INT NOT NULL,
    type_reaction ENUM('like', 'love', 'laugh', 'wow', 'sad', 'angry') NOT NULL,
    date_reaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_message) REFERENCES message(id_message) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_message_reaction (id_utilisateur, id_message)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_support (
    id_ticket INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    categorie ENUM('technique', 'paiement', 'compte', 'reservation', 'autre') DEFAULT 'autre',
    statut_ticket ENUM('ouvert', 'en_cours', 'resolu', 'ferme') DEFAULT 'ouvert',
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ferme_le TIMESTAMP NULL,
    dernier_message TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_statut (statut_ticket),
    INDEX idx_priorite (priorite),
    INDEX idx_date (cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_ticket (
    id_message_ticket INT PRIMARY KEY AUTO_INCREMENT,
    id_ticket INT NOT NULL,
    id_utilisateur INT NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fichier_joint VARCHAR(255) DEFAULT NULL,
    est_admin BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_ticket) REFERENCES ticket_support(id_ticket) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket (id_ticket),
    INDEX idx_date (date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE captcha_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question VARCHAR(255) NOT NULL,
    reponse VARCHAR(100) NOT NULL,
    actif BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions_actives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_php_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    current_url VARCHAR(255) DEFAULT NULL,
    derniere_activite TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (session_php_id),
    INDEX idx_user (user_id),
    INDEX idx_activite (derniere_activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_connexions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    email VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    statut ENUM('success', 'failed') NOT NULL,
    raison_echec VARCHAR(255) DEFAULT NULL,
    date_connexion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_date (date_connexion),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE logs_visites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_token VARCHAR(64),
    page_url VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    duree_visite INT DEFAULT NULL COMMENT 'Durée en secondes',
    date_visite TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_date (date_visite),
    INDEX idx_page (page_url)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    prenom VARCHAR(100) DEFAULT NULL,
    actif BOOLEAN DEFAULT 1,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_desinscription TIMESTAMP NULL,
    token_desinscription VARCHAR(64) UNIQUE,
    INDEX idx_actif (actif),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_envoi (
    id_envoi INT PRIMARY KEY AUTO_INCREMENT,
    sujet VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    nb_destinataires INT DEFAULT 0,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    envoye_par INT,
    FOREIGN KEY (envoye_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO roles (code_role, nom_role, description) VALUES
('ADMIN', 'Administrateur', 'Accès complet à toutes les fonctionnalités'),
('TEACHER', 'Professeur', 'Peut créer des offres et gérer ses cours'),
('STUDENT', 'Étudiant', 'Peut rechercher et réserver des cours');

-- compte administrateur par défaut
-- Email: admin@prof-it.fr
-- Mot de passe: password (à changer)
INSERT INTO users (nom, prenom, email, password, role, actif, email_verifie) VALUES
('Admin', 'Super', 'admin@prof-it.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1);


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

INSERT INTO captcha_questions (question, reponse) VALUES
('Quelle est la capitale de la France ?', 'Paris'),
('Combien font 5 + 3 ?', '8'),
('Quelle est la capitale de la Belgique ?', 'Bruxelles'),
('Combien de jours dans une semaine ?', '7'),
('Quelle couleur obtient-on en mélangeant bleu et jaune ?', 'vert'),
('Combien font 12 + 8 ?', '20'),
('Quelle est la capitale de l''Italie ?', 'Rome'),
('Combien de côtés a un triangle ?', '3'),
('Quelle est la capitale de l''Allemagne ?', 'Berlin'),
('Combien font 9 × 3 ?', '27'),
('Quel animal dit "miaou" ?', 'chat'),
('Combien de pattes a un chien ?', '4'),
('Quelle est la capitale du Portugal ?', 'Lisbonne'),
('Combien font 15 - 7 ?', '8'),
('De quelle couleur est le soleil ?', 'jaune'),
('Combien de doigts avons-nous ?', '10'),
('Quelle est la capitale de l''Espagne ?', 'Madrid'),
('Combien font 4 × 5 ?', '20'),
('Quel fruit est jaune et allongé ?', 'banane'),
('Combien de saisons dans une année ?', '4');
SELECT 
    'Base de données PROF-IT créée avec succès!' as message,
    COUNT(*) as nb_tables
FROM information_schema.tables
WHERE table_schema = 'projet_profit';
SELECT 
    table_name as 'Table',
    table_rows as 'Lignes'
FROM information_schema.tables
WHERE table_schema = 'projet_profit'
ORDER BY table_name;
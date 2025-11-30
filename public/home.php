<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
header('Content-Type: text/html; charset=UTF-8');

safe_session_start();

$userPrenom = '';
if (isset($_SESSION['user_id'])) {
    $userPrenom = $_SESSION['prenom'] ?? 'Utilisateur';
}

$isLogged = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? '';
$redirectBase = '../auth/auth.php';
if ($isLogged && $userRole === 'student') {
    $redirectBase = '../student/rdv.php';
} elseif ($isLogged && $userRole === 'teacher') {
    $redirectBase = '../teacher/rdv.php';
}

$action = $_GET['action'] ?? '';
if ($action === 'search_courses') {
    header('Content-Type: application/json; charset=UTF-8');
    $query = trim($_GET['query'] ?? '');
    $params = [];
    $filter = '';
    if ($query !== '') {
        $filter = " AND (o.titre LIKE ? OR m.nom_matiere LIKE ?) ";
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
    }
    try {
        $sql = "
            SELECT
                o.id_offre,
                o.titre,
                COALESCE(NULLIF(m.nom_matiere, ''), o.titre) AS nom_matiere,
                CONCAT(u.prenom, ' ', u.nom) AS nom_professeur,
                MIN(c.tarif_horaire) AS tarif_min,
                GROUP_CONCAT(DISTINCT c.mode_propose) AS modes,
                COUNT(c.id_creneau) AS total_slots
            FROM offre_cours o
            INNER JOIN enseigner e ON e.id_offre = o.id_offre AND e.actif = 1
            INNER JOIN users u ON e.id_utilisateur = u.id
            LEFT JOIN couvrir co ON o.id_offre = co.id_offre
            LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
            INNER JOIN creneau c ON c.id_offre = o.id_offre
                AND c.statut_creneau = 'disponible'
                AND c.date_debut > NOW()
            WHERE u.role = 'teacher'
            $filter
            GROUP BY o.id_offre
            ORDER BY total_slots DESC, o.titre ASC
            LIMIT 20
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la recherche.']);
    }
    exit;
}

try {
    $stmt = $conn->query("
        SELECT o.id_offre, o.titre, m.nom_matiere, u.prenom, u.nom, MIN(c.tarif_horaire) AS tarif_horaire, GROUP_CONCAT(DISTINCT c.mode_propose) AS modes, COUNT(c.id_creneau) AS total_slots
        FROM offre_cours o
        JOIN enseigner e ON o.id_offre = e.id_offre
        JOIN users u ON e.id_utilisateur = u.id
        LEFT JOIN couvrir co ON o.id_offre = co.id_offre
        LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
        JOIN creneau c ON o.id_offre = c.id_offre
        WHERE e.actif = 1 AND c.statut_creneau = 'disponible' AND c.date_debut > NOW()
        GROUP BY o.id_offre
        ORDER BY RAND()
        LIMIT 3
    ");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $courses = [];
}

try {
    $stmt = $conn->query("
        SELECT u.prenom, u.nom, m.nom_matiere, u.photo_url
        FROM users u
        JOIN enseigner e ON u.id = e.id_utilisateur
        JOIN offre_cours o ON e.id_offre = o.id_offre
        LEFT JOIN couvrir co ON o.id_offre = co.id_offre
        LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
        WHERE u.role = 'teacher' AND e.actif = 1
        GROUP BY u.id
        ORDER BY RAND()
        LIMIT 3
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $teachers = [];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prof-IT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="Content-Type" content="UTF-8">
    <meta name="Content-Language" content="fr">
    <meta name="Description"
        content="Prof-it est un espace ou les professeurs les plus compétent ont la chance de rencontrer leur éleves qui suivrons leur pas pour la démarche quotidienne d'étre de plus en plus sage.">
    <meta name="Keywords"
        content="cours, soutiens, tutorat, professeur, matiere, anglais, français, math, physique,ecole, etudiant,lycéens,collégiens,etude supérieur">
    <meta name="Subject" content="Cours particulier">
    <meta name="Revisit-After" content="30 days">
    <meta name="Robots" content="all">
    <meta name="Rating" content="general">
    <meta name="Distribution" content="global">
    <meta name="Category" content="software">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../assets/css/home.css">
</head>

<body>
    <header>
        <?php if ($userPrenom): ?>
        <div id="user-welcome"
            style="background: #7494ec; color: white; padding: 15px; text-align: center;">
            <div class="container">
                <span>Bienvenue <strong id="welcome-name"><?= htmlspecialchars($userPrenom) ?></strong> !</span>
                <a href="/auth/logout.php"
                    style="color: white; margin-left: 20px; text-decoration: underline;">Déconnexion</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <span>
                        <a href="#"><img id="logo" src="../assets/img/prof_it_logo_blanc.png"></a>
                    </span>
                </div>
                <?php if (!$userPrenom): ?>
                <div class="auth-buttons">
                    <a href="../auth/auth.php" class="btn btn-login">Connexion</a>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?= $_SESSION['role'] === 'teacher' ? '../teacher/dashboard.php' : '../student/dashboard.php' ?>" class="btn btn-login">Mon Espace</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-overlay">
                <h1>Trouvez le bon Prof, Au bon moment</h1>
                <p>Une plateforme de Study Dating pour vous</p>
            </div>
            <section class="search-section">
                <div class="search-container">
                    <h2 class="search-title">Que cherchez-vous ?</h2>
                    <div class="search-bar">
                        <input type="text" class="search-input" id="public-search-input" placeholder="Chercher un cours, un professeur...">
                        <button class="search-button" id="public-search-btn">Chercher</button>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <section class="search-results" id="public-search-results" style="display: none;">
        <div class="container" style="padding-top: 40px;">
            <h2 class="search-title">Résultats de votre recherche</h2>
            <div id="results-container" class="results-grid">
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container" style="padding-top: 80px;">
            <div class="section-title">
                <h2>Trouvez facilement un prof près de chez vous</h2>
                <section style="margin-top:20px;">
                    <div id="map"
                        style="height: 400px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);"></div>
                </section>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container" style="padding-top: 80px;">
            <div class="section-title">
                <h2>Nos Professeurs à la une</h2>
                <p>Découvrez quelques-uns de nos meilleurs enseignants</p>
            </div>
            <div class="testimonials-grid">
                <?php if (!empty($teachers)): ?>
                    <?php foreach ($teachers as $teacher): ?>
                    <div class="testimonial-card">
                        <p class="testimonial-text">"Passionné par l'enseignement, je suis là pour vous aider à réussir."</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="<?= !empty($teacher['photo_url']) ? '../' . htmlspecialchars($teacher['photo_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($teacher['prenom'] . '+' . $teacher['nom']) ?>" alt="<?= htmlspecialchars($teacher['prenom']) ?>">
                            </div>
                            <div class="author-info">
                                <h4><?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']) ?></h4>
                                <p>Professeur - <?= htmlspecialchars($teacher['nom_matiere'] ?? 'Général') ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="testimonial-card">
                        <p class="testimonial-text">"Prof-it est simple à l'utilisation, Je programme toutes mes séances facilement"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Marie Dupont">
                            </div>
                            <div class="author-info">
                                <h4>Marie Dupont</h4>
                                <p>Professeur - Anglais</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p class="testimonial-text">"J'ai trouvé de l'aide en seulement 5 minutes!! Merci Prof-it pour mon prof trop cool"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Pierre Martin">
                            </div>
                            <div class="author-info">
                                <h4>Pierre Martin</h4>
                                <p>Etudiant - BTS Technologie 2eme Année</p>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <p class="testimonial-text">"La meilleure chose que j'ai découvert. Je recommande vivement !"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sophie Leroy">
                            </div>
                            <div class="author-info">
                                <h4>Sophie Leroy</h4>
                                <p>Etudiant - Masters 1ere Année</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="newsletter">
        <div class="container" style="padding-top: 80px;">
            <h2>Restez informé</h2>
            <p>Abonnez-vous à notre newsletter pour recevoir nos actualités et conseils exclusifs.</p>
            <form class="newsletter-form" action="../handlers/newsletter_subscribe.php" method="POST">
                <input type="email" name="email" placeholder="Votre adresse email" required>
                <button type="submit">S'abonner</button>
            </form>
        </div>
    </section>

    <footer>
        <div class="container" style="padding-top: 80px;">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Prof-IT</h3>
                    <p>Nous aidons les étudiants à trouver un professeur selon leur besoin en quelques clics</p>
                </div>
                <div class="footer-column">
                    <h3>Liens rapides</h3>
                    <ul>
                        <li><a href="#">Trouver un prof</a></li>
                        <li><a href="#">Prendre rendez-vous</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">Prise de rendez-vous en ligne</a></li>
                        <li><a href="#">Factures</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact</h3>
                    <ul>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <a
                                href="https://www.google.fr/maps/place/242+Rue+du+Faubourg+Saint-Antoine,+75012+Paris/@48.8491595,2.3871218,17z/data=!3m1!4b1!4m6!3m5!1s0x47e6727347acc2f9:0xf05c1e2443d19a9e!8m2!3d48.8491595!4d2.3896967!16s%2Fg%2F11nnkxvhf5?entry=ttu&g_ep=EgoyMDI1MTExMS4wIKXMDSoASAFQAw%3D%3D">242
                                Rue Faubourg - 75012, Paris</a>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <a href="tel:+33762229724">+33 7 62 22 97 24</a>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:support@prof-it.fr">support@prof-it.fr</a>
                        </li>
                    </ul>
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=61581632294920"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/profi_t2025/"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 MonSite. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function() {
            const searchInput = document.getElementById('public-search-input');
            const searchBtn = document.getElementById('public-search-btn');
            const resultsSection = document.getElementById('public-search-results');
            const resultsContainer = document.getElementById('results-container');
            const isLogged = <?php echo $isLogged ? 'true' : 'false'; ?>;
            const userRole = "<?php echo htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8'); ?>";
            const redirectBase = "<?php echo htmlspecialchars($redirectBase, ENT_QUOTES, 'UTF-8'); ?>";

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function renderCourses(courses) {
                if (!courses || courses.length === 0) {
                    resultsContainer.innerHTML = '<p class="text-center text-muted mb-4">Aucun cours trouvé.</p>';
                    return;
                }
                resultsContainer.innerHTML = courses.map((course) => {
                    const price = course.tarif_min ? Number(course.tarif_min).toFixed(2) + ' €/h' : 'Tarif à définir';
                    const modes = (course.modes || '').split(',').filter(Boolean).map(m => m.trim()).join(', ');
                    const modesHtml = modes !== '' ? modes : 'Modes non spécifiés';
                    const bookingUrl = !isLogged ? '../auth/auth.php' : (userRole === 'student' ? redirectBase + '?offre_id=' + course.id_offre : redirectBase);
                    const bookingLabel = !isLogged ? 'Je réserve' : (userRole === 'student' ? 'Voir les créneaux' : 'Mon espace');
                    return `
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0 text-primary">${escapeHtml(course.titre || 'Cours')}</h5>
                                    <span class="badge bg-info text-dark">${escapeHtml(course.nom_matiere || '')}</span>
                                </div>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-user-circle me-1"></i> ${escapeHtml(course.nom_professeur || '')}
                                </p>
                                <p class="card-text fw-bold text-success mb-2">
                                    <i class="fas fa-tag me-1"></i> ${price}
                                </p>
                                <p class="card-text small text-secondary mb-3">
                                    <i class="fas fa-video me-1"></i> ${modesHtml}
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary">${course.total_slots || 0} créneau(x)</span>
                                <a href="${bookingUrl}" class="btn btn-sm btn-outline-primary">
                                    ${bookingLabel} <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            function doSearch() {
                const q = (searchInput.value || '').trim();
                resultsSection.style.display = 'block';
                resultsContainer.innerHTML = '<p class="text-center text-muted mb-4">Recherche en cours...</p>';
                fetch('home.php?action=search_courses&query=' + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            resultsContainer.innerHTML = '<p class="text-danger">Erreur lors de la recherche.</p>';
                            return;
                        }
                        renderCourses(data.courses || []);
                    })
                    .catch(() => {
                        resultsContainer.innerHTML = '<p class="text-danger">Erreur lors de la recherche.</p>';
                    });
            }

            searchBtn?.addEventListener('click', doSearch);
            searchInput?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doSearch();
                }
            });

            // carte simple
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L !== 'undefined' && document.getElementById('map')) {
                    var map = L.map('map').setView([48.8566, 2.3522], 12);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);
                }
            });
        })();
    </script>

</body>

</html>

<?php
require_once __DIR__ . '/bootstrap.php';

try {
    $action = $_GET['action'] ?? 'stats';

    switch ($action) {
        case 'stats':
            $stats = [];

            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $stats['total_users'] = $stmt->fetch()['total'];

            $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
            $stats['students'] = $stmt->fetch()['total'];

            $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
            $stats['teachers'] = $stmt->fetch()['total'];

            $stmt = $conn->query("SELECT COUNT(*) as total FROM newsletter WHERE actif = 1");
            $stats['newsletter'] = $stmt->fetch()['total'];

            $stmt = $conn->query("SELECT COUNT(DISTINCT session_token) as total FROM logs_visites WHERE DATE(date_visite) = CURDATE()");
            $stats['visits_today'] = $stmt->fetch()['total'];

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'recent_users':
            $stmt = $conn->prepare("SELECT nom, prenom, role, created_at 
                                    FROM users 
                                    ORDER BY created_at DESC 
                                    LIMIT 5");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'recent_logs':
            $stmt = $conn->prepare("SELECT email, statut, date_connexion 
                                    FROM logs_connexions 
                                    ORDER BY date_connexion DESC 
                                    LIMIT 10");
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'logs' => $logs]);
            break;

        case 'chart_inscriptions':
            $stmt = $conn->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as mois,
                    COUNT(*) as total
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY mois ASC
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = [];
            $data = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M Y', strtotime("-$i months"));

                $found = false;
                foreach ($results as $row) {
                    if ($row['mois'] === $date) {
                        $data[] = (int)$row['total'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data[] = 0;
                }
            }

            echo json_encode([
                'success' => true,
                'chartData' => [
                    'labels' => $labels,
                    'data' => $data
                ]
            ]);
            break;

            case 'top_pages':
            $period = $_GET['period'] ?? 'day';
            $whereClause = "";
            
            switch ($period) {
                case 'day':
                    $whereClause = "WHERE DATE(date_visite) = CURDATE()";
                    break;
                case 'week':
                    $whereClause = "WHERE date_visite >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case 'month':
                    $whereClause = "WHERE date_visite >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
                case 'all':
                default:
                    $whereClause = ""; 
                    break;
            }

            $sql = "SELECT page_url, COUNT(*) as total 
                    FROM logs_visites 
                    $whereClause 
                    GROUP BY page_url 
                    ORDER BY total DESC 
                    LIMIT 5";
            
            $stmt = $conn->query($sql);
            $pages = $stmt->fetchAll();

            $totalVisitesPeriode = 0;
            foreach($pages as $p) $totalVisitesPeriode += $p['total'];

            echo json_encode([
                'success' => true, 
                'pages' => $pages,
                'total_period' => $totalVisitesPeriode
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur', 'message' => $e->getMessage()]);
}

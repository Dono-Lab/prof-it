<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';

session_start();
if (!is_admin()) {
    die("Accès refusé");
}

$filename = "logs_connexions_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputs($output, "\xEF\xBB\xBF");

fputcsv($output, ['ID', 'Utilisateur (Email)', 'Statut', 'Date', 'Navigateur (User Agent)']);

try {
    $stmt = $conn->query("
        SELECT 
            id, 
            email, 
            statut, 
            date_connexion, 
            user_agent 
        FROM logs_connexions 
        ORDER BY date_connexion DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['user_agent'] = substr($row['user_agent'], 0, 50) . '...';
        fputcsv($output, $row);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Erreur lors de l\'export', $e->getMessage()]);
}

fclose($output);
exit();
?>
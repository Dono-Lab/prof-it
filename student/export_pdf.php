<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

safe_session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die('Accès interdit.');
}

$reservationId = 0;
if (isset($_GET['id'])) {
    $reservationId = (int)$_GET['id'];
}

if ($reservationId <= 0) {
    die('ID de réservation invalide.');
}

$userId = $_SESSION['user_id'];

$query = "
    SELECT 
        r.id_reservation,
        r.date_reservation,
        r.statut_reservation,
        r.mode_choisi,
        r.prix_fige,
        r.montant_ttc,
        c.date_debut,
        c.date_fin,
        o.titre as titre_cours,
        m.nom_matiere,
        p.nom as nom_prof,
        p.prenom as prenom_prof,
        p.email as email_prof
    FROM reservation r
    JOIN creneau c ON r.id_creneau = c.id_creneau
    JOIN offre_cours o ON c.id_offre = o.id_offre
    JOIN users p ON c.id_utilisateur = p.id
    LEFT JOIN couvrir cov ON o.id_offre = cov.id_offre
    LEFT JOIN matiere m ON cov.id_matiere = m.id_matiere
    WHERE r.id_reservation = :id AND r.id_utilisateur = :user_id
";

$stmt = $conn->prepare($query);
$stmt->execute([':id' => $reservationId, ':user_id' => $userId]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die('Réservation introuvable ou accès refusé.');
}

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Prof-IT');
$pdf->SetAuthor('Prof-IT');
$pdf->SetTitle('Reçu de réservation #' . $reservationId);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'PROF-IT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Reçu de réservation', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Informations du cours', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$dateDebut = date('d/m/Y H:i', strtotime($reservation['date_debut']));
$dateFin = date('H:i', strtotime($reservation['date_fin']));

$html = '
<table border="1" cellpadding="5">
    <tr>
        <td width="40%"><b>Matière / Cours</b></td>
        <td width="60%">' . htmlspecialchars($reservation['nom_matiere']) . ' - ' . htmlspecialchars($reservation['titre_cours']) . '</td>
    </tr>
    <tr>
        <td><b>Professeur</b></td>
        <td>' . htmlspecialchars($reservation['prenom_prof'] . ' ' . $reservation['nom_prof']) . '</td>
    </tr>
    <tr>
        <td><b>Date et Heure</b></td>
        <td>Le ' . $dateDebut . ' à ' . $dateFin . '</td>
    </tr>
    <tr>
        <td><b>Mode</b></td>
        <td>' . ucfirst($reservation['mode_choisi']) . '</td>
    </tr>
    <tr>
        <td><b>Statut</b></td>
        <td>' . ucfirst($reservation['statut_reservation']) . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Détails du paiement', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$htmlPrice = '
<table border="1" cellpadding="5">
    <tr>
        <td width="70%" align="right"><b>Prix HT</b></td>
        <td width="30%">' . number_format($reservation['prix_fige'], 2) . ' €</td>
    </tr>
    <tr>
        <td align="right"><b>TVA (20%)</b></td>
        <td>' . number_format($reservation['montant_ttc'] - $reservation['prix_fige'], 2) . ' €</td>
    </tr>
    <tr>
        <td align="right" style="background-color:#f0f0f0;"><b>TOTAL TTC</b></td>
        <td style="background-color:#f0f0f0;"><b>' . number_format($reservation['montant_ttc'], 2) . ' €</b></td>
    </tr>
</table>';

$pdf->writeHTML($htmlPrice, true, false, true, false, '');

$pdf->Ln(20);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Ce document vaut justificatif de réservation sur la plateforme Prof-IT.', 0, 1, 'C');
$pdf->Cell(0, 10, 'Généré le ' . date('d/m/Y à H:i'), 0, 1, 'C');

$pdf->Output('reservation_' . $reservationId . '.pdf', 'D');

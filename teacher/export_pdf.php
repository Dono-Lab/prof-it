<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/tcpdf/tcpdf.php';

safe_session_start();
require_role('teacher');

$reservationId = 0;
if (isset($_GET['id'])) {
    $reservationId = (int)$_GET['id'];
}

if ($reservationId <= 0) {
    die('ID de reservation invalide.');
}

$teacherId = (int)$_SESSION['user_id'];

$sql = "
    SELECT 
        r.id_reservation,
        r.date_reservation,
        r.statut_reservation,
        r.mode_choisi,
        r.prix_fige,
        r.tva,
        r.montant_ttc,
        c.date_debut,
        c.date_fin,
        c.lieu,
        o.titre AS titre_cours,
        m.nom_matiere,
        s.nom AS nom_etudiant,
        s.prenom AS prenom_etudiant,
        s.email AS email_etudiant
    FROM reservation r
    INNER JOIN creneau c ON r.id_creneau = c.id_creneau
    INNER JOIN offre_cours o ON c.id_offre = o.id_offre
    LEFT JOIN couvrir co ON o.id_offre = co.id_offre
    LEFT JOIN matiere m ON co.id_matiere = m.id_matiere
    INNER JOIN users s ON r.id_utilisateur = s.id
    WHERE r.id_reservation = :id
      AND c.id_utilisateur = :teacher_id
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $reservationId,
    ':teacher_id' => $teacherId
]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die('Reservation introuvable ou non autorisee.');
}

$prixHT = (float)$reservation['prix_fige'];
$tvaRate = (float)$reservation['tva'];
if ($tvaRate <= 0) {
    $tvaRate = 20.0;
}
$totalTTC = (float)$reservation['montant_ttc'];
if ($totalTTC <= 0) {
    $totalTTC = $prixHT * (1 + $tvaRate / 100);
}
$tvaAmount = $totalTTC - $prixHT;

$dateDebut = date('d/m/Y H:i', strtotime($reservation['date_debut']));
$dateFin = date('H:i', strtotime($reservation['date_fin']));

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Prof-IT');
$pdf->SetAuthor('Prof-IT');
$pdf->SetTitle('Recu reservation #' . $reservationId);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'PROF-IT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Recu de reservation', 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Informations du cours', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$htmlInfo = '
<table border="1" cellpadding="5">
    <tr>
        <td width="40%"><b>Matiere / Cours</b></td>
        <td width="60%">' . htmlspecialchars($reservation['nom_matiere']) . ' - ' . htmlspecialchars($reservation['titre_cours']) . '</td>
    </tr>
    <tr>
        <td><b>Etudiant</b></td>
        <td>' . htmlspecialchars($reservation['prenom_etudiant'] . ' ' . $reservation['nom_etudiant']) . '</td>
    </tr>
    <tr>
        <td><b>Date et Heure</b></td>
        <td>Le ' . $dateDebut . ' a ' . $dateFin . '</td>
    </tr>
    <tr>
        <td><b>Mode</b></td>
        <td>' . ucfirst($reservation['mode_choisi']) . '</td>
    </tr>
    <tr>
        <td><b>Statut</b></td>
        <td>' . ucfirst($reservation['statut_reservation']) . '</td>
    </tr>
    <tr>
        <td><b>Lieu</b></td>
        <td>' . htmlspecialchars($reservation['lieu']) . '</td>
    </tr>
</table>';

$pdf->writeHTML($htmlInfo, true, false, true, false, '');
$pdf->Ln(8);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Details de paiement', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$htmlPay = '
<table border="1" cellpadding="5">
    <tr>
        <td width="70%" align="right"><b>Prix HT</b></td>
        <td width="30%">' . number_format($prixHT, 2) . ' ?</td>
    </tr>
    <tr>
        <td align="right"><b>TVA (' . number_format($tvaRate, 2) . '%)</b></td>
        <td>' . number_format($tvaAmount, 2) . ' ?</td>
    </tr>
    <tr>
        <td align="right" style="background-color:#f0f0f0;"><b>Total TTC</b></td>
        <td style="background-color:#f0f0f0;"><b>' . number_format($totalTTC, 2) . ' ?</b></td>
    </tr>
</table>';

$pdf->writeHTML($htmlPay, true, false, true, false, '');
$pdf->Ln(12);

$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 8, 'Document genere le ' . date('d/m/Y a H:i'), 0, 1, 'C');
$pdf->Cell(0, 8, 'Ce recu confirme la reservation sur Prof-IT.', 0, 1, 'C');

$pdf->Output('reservation_' . $reservationId . '.pdf', 'D');

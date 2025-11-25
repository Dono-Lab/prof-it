<?php
session_start();
require_once 'includes/helpers.php';
if (!is_logged_in()) {
    header('Location: auth/auth.php');
    exit();
}

require_once 'includes/database.php';
$db = new Database();
$pdo = $db->getPDO();

require_once 'tcpdf/tcpdf.php';

class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Prof-IT', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('Prof-IT');
$pdf->SetAuthor('Prof-IT');
$pdf->SetTitle('Facture Prof-IT');
$pdf->SetSubject('Facture');

$pdf->AddPage();

$user_id = $_SESSION['user_id'];
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$appointments_stmt = $pdo->prepare("
    SELECT a.*, u.prenom as teacher_prenom, u.nom as teacher_nom 
    FROM appointments a 
    INNER JOIN users u ON a.teacher_id = u.id 
    WHERE a.student_id = ? 
    ORDER BY a.appointment_date DESC
");
$appointments_stmt->execute([$user_id]);
$appointments = $appointments_stmt->fetchAll();

$html = '
<h1>Facture - Prof-IT</h1>
<div style="border:1px solid #ddd; padding:15px; margin-bottom:20px;">
    <table>
        <tr>
            <td style="width:50%;">
                <strong>Étudiant:</strong><br>
                ' . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . '<br>
                ' . htmlspecialchars($user['email']) . '
            </td>
            <td style="width:50%;">
                <strong>Date de génération:</strong><br>
                ' . date('d/m/Y H:i') . '<br>
                Référence: INV-' . date('Ymd') . '-' . $user_id . '
            </td>
        </tr>
    </table>
</div>

<h2>Historique des cours</h2>
<table border="1" cellpadding="5" style="width:100%;">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Date</th>
            <th>Professeur</th>
            <th>Matière</th>
            <th>Durée</th>
            <th>Prix</th>
        </tr>
    </thead>
    <tbody>';

$total = 0;
foreach ($appointments as $appointment) {
    $price = 25;
    $total += $price;
    
    $html .= '
        <tr>
            <td>' . date('d/m/Y H:i', strtotime($appointment['appointment_date'])) . '</td>
            <td>' . htmlspecialchars($appointment['teacher_prenom'] . ' ' . $appointment['teacher_nom']) . '</td>
            <td>' . htmlspecialchars($appointment['subject'] ?? 'Non spécifié') . '</td>
            <td>1h</td>
            <td>' . $price . ' €</td>
        </tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr style="background-color:#f2f2f2;">
            <td colspan="4" style="text-align:right;"><strong>Total:</strong></td>
            <td><strong>' . $total . ' €</strong></td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:30px;">
    <p><strong>Conditions de paiement:</strong> Paiement à réception de facture</p>
    <p><strong>Moyens de paiement acceptés:</strong> Carte bancaire, Virement</p>
</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('facture-prof-it-' . date('Y-m-d') . '.pdf', 'D');
?>

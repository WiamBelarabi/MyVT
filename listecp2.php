<?php
require 'navbar.php'; 
ob_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Vérifiez si un fichier a été téléchargé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file_tmp = $_FILES['file']['tmp_name'];
    
    // Charger le fichier Excel
    $spreadsheet = IOFactory::load($file_tmp);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();
    
    // Organiser les données par salle
    $salles = [];
    foreach ($data as $row) {
        $numero = $row[1];  
        $cne = $row[2];    
        $nom = $row[3];    
        $prenom = $row[4];  
        $salle = $row[5];  

        if (!isset($salles[$salle])) {
            $salles[$salle] = [];
        }

        $salles[$salle][] = [
            'numero' => $numero,
            'cne' => $cne,
            'nom' => $nom,
            'prenom' => $prenom
        ];
    }
    class MyPDF extends TCPDF {
        public function Header() {
            $this->SetFont('dejavusans', '', 12); // this part is for the arabic text

            // HTML content for the header
            $html = '
                <table>
                    <tr>
                        <td style="font-size:12px;">Royaume du Maroc<br>Université Mohamed Premier<br>École Nationale des Sciences Appliquées<br>Oujda</td>
                        <td><img src="resources/ensao_logo.png" style="width: 200px; height: 99px;" /></td>
                        <td style="text-align:right ;font-size:12px;">المملكة المغربية<br>جامعة محمد الأول<br>المدرسة الوطنية للعلوم التطبيقية<br>وجدة</td>
                    </tr>
                </table>';        
            // Write the HTML content to the PDF
            $this->writeHTML($html, true, false, true, false, '');
        
            // Set the Y position to avoid overlap with the header content
            $this->SetY(50); // Adjust depending on your header height
        }
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $footerText = 'École Nationale des Sciences Appliquées - Complexe universitaire Al Qods, BP 669 - Oujda
            Tél : 05 36 50 54 70/71 - Fax : 05 36 50 54 72 - Email : administration.ensao@ump.ac.ma - Site web : ensao.ump.ma';
            $this->MultiCell(0, 10, $footerText, 0, 'C', 0, 1);
        }   
    }
    // Générer le PDF
    $pdf = new MyPDF();
    $pdf->SetMargins(10, 50, 10); // Left, TOP (increased), Right margins
    $pdf->SetHeaderMargin(10); 
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('MyVT');
    $pdf->SetTitle('Liste des étudiants CP2');
    
    foreach ($salles as $salle => $etudiants) {
        // Diviser les étudiants en deux colonnes
        $total = count($etudiants);
        $moitie = ceil($total / 2);
        $gauche = array_slice($etudiants, 0, $moitie);
        $droite = array_slice($etudiants, $moitie);
        $html ='<h4 style="text-align:center;">CP2</h4>
        <p style="text-align:center;font-size:13px;">Liste des étudiants-Salle ' . htmlspecialchars($salle) . '</p>
        <h4 style="text-align:center;">Filière : Cycle Préparatoire - Sciences et Techniques pour l\'ingénieur <br> Deuxième année</h4>';

        // Générer le tableau HTML avec deux colonnes
        $html .= '<table border="0" cellpadding="5" cellspacing="5" style="width:100%;">
                    <tr>
                        <td style="width:50%;">' . genererTableauHTML($gauche) . '</td>
                        <td style="width:50%;">' . genererTableauHTML($droite) . '</td>
                    </tr>
                  </table>';

        // Ajouter la page avec le tableau dans le PDF
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    // Afficher le PDF dans le navigateur
    $pdf->Output('liste_cp2.pdf', 'I');
} else {
    echo "";
}
ob_end_flush();

/**
 * Fonction pour générer un sous-tableau en HTML
 */
function genererTableauHTML($etudiants) {
    
    $html = '<table border="0.5" cellpadding="2" cellspacing="0" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background-color:rgb(22, 107, 185); font-size: 7px; color:white;">
                        <th style="width:9%; text-align:center;">N°</th>
                        <th style="width:20%;">CNE</th>
                        <th style="width:40%;">Nom</th>
                        <th style="width:31%;">Prénom</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($etudiants as $etudiant) {
        $html .= '<tr style="font-size:7px;">
                    <td style="text-align:center;">' . htmlspecialchars($etudiant['numero']) . '</td>
                    <td>' . htmlspecialchars($etudiant['cne']) . '</td>
                    <td>' . htmlspecialchars($etudiant['nom']) . '</td>
                    <td>' . htmlspecialchars($etudiant['prenom']) . '</td>
                  </tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importer un fichier Excel</title>
    <style>
        body{
            margin-left: 200px; /* Même largeur que la navbar */
            padding: 20px;
        }
    </style>
</head>

<body>
    <h1>Importer un fichier Excel</h1>
    <form action="listecp2.php" method="post" enctype="multipart/form-data">
        <label for="file">Choisissez un fichier Excel :</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls" required>
        <button type="submit">Générer PDF</button>
    </form>
</body>
</html>
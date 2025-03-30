<?php
    include("navbar.php");
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
        //organiser par cordinateur 
        $cordinateur=[];
        foreach ($data as $row) {
            // Make sure the row has enough columns
            if (count($row) < 25) continue; 
        
            $cord = trim($row[20] ?? ''); // Get coordinateur safely
            $date = trim($row[2] ?? '');
            $heure = trim($row[7] ?? '');
            $filiere = trim($row[2] ?? '');
            $salle = trim($row[5] ?? '');
        
            if (!empty($cord)) { // Ensure the key is valid
                $cordinateur[$cord][] = [
                    'date' => $date,
                    'heure' => $heure,
                    'filiere' => $filiere,
                    'salle' => $salle
                ];
            }
        }

    
    class MyPDF extends TCPDF {
        // Header
        public function Header() {
            $this->SetFont('dejavusans', '', 12); // Font for Arabic support
    
            $html = '
            <table>
                <tr>
                    <td style="font-size:12px;">Royaume du Maroc<br>Université Mohamed Premier<br>École Nationale des Sciences Appliquées<br>Oujda</td>
                    <td><img src="resources/ensao_logo.png" style="width: 150px; height: auto;" /></td>
                    <td style="text-align:right ;font-size:12px;">المملكة المغربية<br>جامعة محمد الأول<br>المدرسة الوطنية للعلوم التطبيقية<br>وجدة</td>
                </tr>
            </table>';
            
            $this->writeHTML($html, true, false, true, false, '');
            $this->SetY(50); // Move content below header
        }
    
        // Footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $footerText = 'École Nationale des Sciences Appliquées - Complexe universitaire Al Qods, BP 669 - Oujda
            Tél : 05 36 50 54 70/71 - Fax : 05 36 50 54 72 - Email : administration.ensao@ump.ac.ma - Site web : ensao.ump.ma';
            $this->MultiCell(0, 10, $footerText, 0, 'C', 0, 1);
        }
    }
    // ====== Generate PDF ======
    $pdf = new MyPDF();
    $pdf->SetMargins(10, 55, 10); // Set top margin to avoid header overlap
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetAuthor('MyVT');
    $pdf->SetTitle('Controle de présence');
    // Table Rows
    
    // Output PDF file
    $pdf->Output('controle_de_présence.pdf', 'I'); // 'I' for inline display, 'D' for download
}else{
    echo "";
}
ob_end_flush();
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
    <p>Importer un fichier Excel :</p><br>
    <form action="controle.php" method="post" enctype="multipart/form-data">
        <label for="file">Choisissez un fichier Excel :</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls" required><br>
        <button type="submit">Générer PDF</button>
    </form>
</body>
</html>
<?php
    include("navbar.php");
    ob_start();
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
        $file_tmp = $_FILES['file']['tmp_name'];
        
        // Charger le fichier Excel
        $spreadsheet = IOFactory::load($file_tmp);
        
        // Get the number of sheets in the workbook
        $sheetCount = $spreadsheet->getSheetCount();
        
        // Set the number of sheets you want to include (e.g., the first 10 sheets)
        $includeSheetCount = 10; // Change this value based on the number of sheets you want to include
        
        $cordinateur = [];

        // Loop through each sheet, process the first $includeSheetCount sheets
        for ($sheetIndex = 0; $sheetIndex < $includeSheetCount; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            
            // Skip this sheet if it's beyond the include range
            if ($sheetIndex >= $includeSheetCount) {
                continue; // Skip the current iteration and move to the next sheet
            }

            $data = $sheet->toArray();
            
            // Process data for the current sheet
            foreach ($data as $i => $row) {
                $cord = '';

                // cordinateur
                if ($i >= 4 && $i <= 10) {
                    $cord = trim($row[19] ?? '');
                }
                if ($i >= 11 && $i <= 16) {
                    $cord = trim($row[21] ?? '');
                }
                // date
                $date = trim($data[1][2] ?? '');
                // heure
                $heure = '';
                if ($i === 3) {
                    for ($j = 7; $j < 11; $j++) {
                        $heure .= ' ' . trim($row[$j] ?? '');
                    }
                }
                $filiere = trim($row[2] ?? '');
                $salle = trim($row[5] ?? '');

                if (!empty($cord)) {
                    $cordinateur[$cord][] = [
                        'date' => $date,
                        'heure' => $heure,
                        'filiere' => $filiere,
                        'salle' => $salle
                    ];
                }
            }
        }

        class MyPDF extends TCPDF {
            // Header
            public function Header() {
                $this->SetFont('dejavusans', '', 12);
                $html = '
                <table>
                    <tr>
                        <td style="font-size:12px; width:35%;">Royaume du Maroc<br>Université Mohamed Premier<br>École Nationale des Sciences Appliquées<br>Oujda</td>
                        <td style=" width:33%;"><img src="resources/ensao_logo.png" style="width: 150px; height: auto;" /></td>
                        <td style="text-align:right ; width:32%; font-size:12px;">المملكة المغربية<br>جامعة محمد الأول<br>المدرسة الوطنية للعلوم التطبيقية<br>وجدة</td>
                    </tr>
                </table>';
                
                $this->writeHTML($html, true, false, true, false, '');
                $this->SetY(50);
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
        $pdf->SetMargins(10, 50, 10); 
        $pdf->SetHeaderMargin(7); 
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetAuthor('MyVT');
        $pdf->SetTitle('contrôle de présence');

        foreach ($cordinateur as $cord => $entries) {
            $html = '<p style="text-align:center;"><br><strong>DE</strong><br>MONSIEUR LE DIRECTEUR<br>DE L\'ECOLE NATIONAL DES SCIENCES APPLIQUEES D\'OUJDA</p>
            <p style="text-align:center;"><strong>À<br>MONSIEUR/MADAME ' . htmlspecialchars($cord) .'</strong></p><br>
            <p><strong><br>Objet: </strong>contrôle de présence : Devoirs survéillés n°2 Semestre 1<br><br>Cher(e) collègue,<br>Je vous prie de bien vouloir participer au contrôle de présence lors des Devoirs survéillés n°2 Semestre 1, conformément au tableau ci-dessous:</p>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background-color:#166bb9; color:white;">
                    <th style="width:30%; text-align:center;">Date</th>
                    <th style="width:20%; text-align:center;">Heure</th>
                    <th style="width:30%; text-align:center;">Filière</th>
                    <th style="width:20%; text-align:center;">Salle</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($entries as $entry) {
                $html .= '<tr>
                            <td style="width:30%; text-align:center;">' . htmlspecialchars($entry['date']) . '</td>
                            <td style="width:20%; text-align:center;">' . htmlspecialchars($entry['heure']) . '</td>
                            <td style="width:30%; text-align:center;">' . htmlspecialchars($entry['filiere']) . '</td>
                            <td style="width:20%; text-align:center;">' . htmlspecialchars($entry['salle']) . '</td>
                        </tr>';
            }
            $html .= '</tbody></table>'; 
            
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        $pdf->Output('contrôle_de_présence.pdf', 'I');
    } else {
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
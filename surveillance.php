<?php
    ob_start();
    include("navbar.php");
    
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
        $today = date('d/m/Y'); 
        $file_tmp = $_FILES['file']['tmp_name'];
        
        // Charger le fichier Excel
        $spreadsheet = IOFactory::load($file_tmp);
        $sheetCount = $spreadsheet->getSheetCount();
        $includedSheets = 12; 
        
        $surveillant = [];
        $coordData = [];

        for ($sheetIndex = 0; $sheetIndex < $includedSheets; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();

            foreach ($data as $i => $row) {
                if ($i < 4) continue;

                $date = trim($data[0][2] ?? '') . ' ' . trim($data[1][2] ?? '');
                $filiere = trim($row[2] ?? '');

                for ($h = 7; $h <= 10; $h++) { 
                    $hourValue = trim($data[3][$h] ?? ''); 
                    $matiere = trim($row[$h] ?? '');

                    if (empty($matiere)) continue;

                    // ---------- GESTION DES COORDINATEURS ----------
                    $coordIndex = 15 + ($h - 7); // P=15, Q=16, R=17, S=18
                    $coord = trim($row[$coordIndex] ?? '');
                    if (!empty($coord)) {
                        $key = $date . '|' . $hourValue . '|' . $filiere . '|' . $matiere;

                        $coordData[$coord][$key]['date'] = $date;
                        $coordData[$coord][$key]['heure'] = $hourValue;
                        $coordData[$coord][$key]['matiere'] = $matiere;
                        $coordData[$coord][$key]['filiere'] = $filiere;
                        $coordData[$coord][$key]['mission'] = "Coordination";

                        $currentSalle = trim($row[5] ?? '');
                        if (!in_array($currentSalle, $coordData[$coord][$key]['salles'] ?? [])) {
                            $coordData[$coord][$key]['salles'][] = $currentSalle;
                        }
                    }

                    // ---------- GESTION DES SURVEILLANTS ----------
                    for ($col = 23; $col <= 30; $col++) { 
                        $surv = trim($row[$col] ?? '');
                        if (empty($surv)) continue;

                        $mission = "Surveillance";

                        $newEntry = [
                            'date' => $date,
                            'heure' => $hourValue,
                            'filiere' => $filiere,
                            'matiere' => $matiere,
                            'salle' => trim($row[5] ?? ''),
                            'mission' => $mission
                        ];

                        $found = false;
                        foreach ($surveillant[$surv] ?? [] as &$existing) {
                            if ($existing['date'] === $newEntry['date'] &&
                                $existing['heure'] === $newEntry['heure'] &&
                                $existing['filiere'] === $newEntry['filiere'] &&
                                $existing['matiere'] === $newEntry['matiere'] &&
                                $existing['mission'] === $newEntry['mission']) {
                                
                                if (!in_array($newEntry['salle'], explode(', ', $existing['salle']))) {
                                    $existing['salle'] .= ', ' . $newEntry['salle'];
                                }
                                $found = true;
                                break;
                            }
                        }
                        unset($existing);
                        if (!$found) {
                            $surveillant[$surv][] = $newEntry;
                        }
                    }
                }
            }
        }

    // ---------- AJOUTER LES COORDINATEURS DANS $surveillant ----------
    foreach ($coordData as $coordName => $entries) {
        foreach ($entries as $entry) {
            $surveillant[$coordName][] = [
                'date' => $entry['date'],
                'heure' => $entry['heure'],
                'filiere' => $entry['filiere'],
                'matiere' => $entry['matiere'],
                'salle' => implode(', ', $entry['salles']),
                'mission' => $entry['mission']
            ];
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
        // ====== Generer PDF ======
        $pdf = new MyPDF();
        $pdf->SetMargins(10, 50, 10); 
        $pdf->SetHeaderMargin(7); 
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetAuthor('MyVT');
        $pdf->SetTitle('surveillance et coordination');
        //tableau
        foreach($surveillant as $surv =>$entries){
            $html = '<p style="text-align:right;">Oujda le '. date('d/m/Y').'<br></p>
            <p style="text-align:center;"><br><strong>DE</strong><br>MONSIEUR LE DIRECTEUR<br>DE L\'ECOLE NATIONAL DES SCIENCES APPLIQUEES D\'OUJDA</p>
            <p style="text-align:center;"><strong>À<br>MONSIEUR/MADAME ' . htmlspecialchars($surv) .'</strong></p><br>
            <p><strong><br>Objet: </strong>Surveillance et coordination des Devoirs survéillés n°2 Semestre 1<br><br>Cher(e) collègue,<br>Je vous prie de bien vouloir participer à la coordination des Devoirs survéillés n°2 Semestre 1, conformément au tableau ci-dessous:</p>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background-color:#166bb9; color:white;">
                    <th style="width:18%; text-align:center;">Date</th>
                    <th style="width:15%; text-align:center;">Heure</th>
                    <th style="width:13%; text-align:center;">Filière</th>
                    <th style="width:25%; text-align:center;">Matière</th>
                    <th style="width:12%; text-align:center;">Salle(s)</th>
                    <th style="width:15%; text-align:center;">Mission</th>
                </tr>
            </thead>
            <tbody>';
            foreach ($entries as $entry) {
                $html .= '<tr>
                            <td style="width:18%; text-align:center;">' . htmlspecialchars($entry['date']) . '</td>
                            <td style="width:15%; text-align:center;">' . htmlspecialchars($entry['heure']) . '</td>
                            <td style="width:13%; text-align:center;">' . htmlspecialchars($entry['filiere']) . '</td>
                            <td style="width:25%; text-align:center;">' . htmlspecialchars($entry['matiere']) . '</td>
                            <td style="width:12%; text-align:center;">' . htmlspecialchars($entry['salle']) . '</td>
                            <td style="width:15%; text-align:center;">' . htmlspecialchars($entry['mission']) . '</td>
                        </tr>';
            }
            $html .= '</tbody></table>'; 
            
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        ob_end_clean();
        $pdf->Output('surveillance_et_coordination.pdf', 'I');
    }else {
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
    <form action="surveillance.php" method="post" enctype="multipart/form-data">
        <label for="file">Choisissez un fichier Excel :</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls" required><br>
        <button type="submit">Générer PDF</button>
    </form>
</body>
</html>
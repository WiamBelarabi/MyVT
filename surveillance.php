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
                        <td style="font-size:10px;width:40%;">Royaume du Maroc<br>Université Mohamed Premier<br>École Nationale des Sciences Appliquées<br>Oujda</td>
                        <td style="width:30%;"><img src="resources/ensao_logo.png" style="width: 120px; height: 61px;" /></td>
                        <td style="text-align:right ;font-size:11px; width:30%;">المملكة المغربية<br>جامعة محمد الأول<br>المدرسة الوطنية للعلوم التطبيقية<br>وجدة</td>
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
            <p style="text-align:center;font-size: 12px;"><br><strong>DE</strong><br>MONSIEUR LE DIRECTEUR<br>DE L\'ECOLE NATIONAL DES SCIENCES APPLIQUEES D\'OUJDA</p>
            <p style="text-align:center;font-size: 12px;"><strong>À<br>MONSIEUR/MADAME ' . htmlspecialchars($surv) .'</strong></p><br>
            <p style="font-size: 12px;"><strong><br>Objet: </strong>Surveillance et coordination des Devoirs survéillés n°2 Semestre 1<br><br>Cher(e) collègue,<br>Je vous prie de bien vouloir participer à la coordination des Devoirs survéillés n°2 Semestre 1, conformément au tableau ci-dessous:</p>
            <table  cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;border: 0.5px solid #7ba0eb;">
            <thead>
                <tr style="background-color:rgb(50, 112, 179); color:white;">
                    <th style="width:18%; text-align:center;border: 0.5px solid #7ba0eb;">Date</th>
                    <th style="width:15%; text-align:center;border: 0.5px solid #7ba0eb;">Heure</th>
                    <th style="width:13%; text-align:center;border: 0.5px solid #7ba0eb;">Filière</th>
                    <th style="width:25%; text-align:center;border: 0.5px solid #7ba0eb;">Matière</th>
                    <th style="width:12%; text-align:center;border: 0.5px solid #7ba0eb;">Salle(s)</th>
                    <th style="width:15%; text-align:center;border: 0.5px solid #7ba0eb;">Mission</th>
                </tr>
            </thead>
            <tbody>';
            foreach ($entries as $entry) {
                $html .= '<tr>
                            <td style="width:18%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['date']) . '</td>
                            <td style="width:15%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['heure']) . '</td>
                            <td style="width:13%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['filiere']) . '</td>
                            <td style="width:25%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['matiere']) . '</td>
                            <td style="width:12%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['salle']) . '</td>
                            <td style="width:15%; text-align:center;border: 0.5px solid #7ba0eb;">' . htmlspecialchars($entry['mission']) . '</td>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
           .black-bar {
            width: 100%;
            height: 2px;
            background-color: #000;
            margin: 20px 0;
            padding: 0;
            border: none;
        }
        :root {
            --primary-color: #166bb9;
            --secondary-color: #f8f9fa;
            --accent-color: #e3f2fd;
            --text-color: #333;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --hover-color: #0056b3;
        }
        
        body {
            margin-left: 200px; /* Même largeur que la navbar */
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .upload-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            background-color: var(--accent-color);
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        
        .upload-section:hover {
            border-color: var(--primary-color);
        }
        
        .file-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .file-input-wrapper {
            position: relative;
            margin: 20px 0;
            width: 100%;
            text-align: center;
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-label {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .file-label:hover {
            background-color: var(--hover-color);
        }
        
        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .submit-btn {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
            padding: 12px;
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #218838;
        }
        
        .submit-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .instructions {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            border-radius: 4px;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        
        .instructions ul {
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 10px;
            }
            
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            .upload-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Génération de surveillance et coordination</h1>
            <p>Importez un fichier Excel pour générer automatiquement les documents de surveillance et coordination</p>
        </div>
        
        <form action="surveillance.php" method="post" enctype="multipart/form-data" id="upload-form">
            <div class="upload-section" id="drop-area">
                <i class="fas fa-file-excel file-icon"></i>
                <p>Glissez-déposez votre fichier Excel ici ou cliquez pour sélectionner</p>
                
                <div class="file-input-wrapper">
                    <label for="file" class="file-label">
                        <i class="fas fa-upload"></i> Choisir un fichier
                    </label>
                    <input type="file" name="file" id="file" class="file-input" accept=".xlsx, .xls" required>
                    <div class="file-name" id="file-name">Aucun fichier sélectionné</div>
                </div>
            </div>
            
            <button type="submit" class="submit-btn" id="submit-btn" disabled>
                <i class="fas fa-file-pdf"></i> Générer PDF
            </button>
        </form>
        
        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li>Le fichier doit être au format Excel (.xlsx ou .xls)</li>
                <li>Assurez-vous que le fichier contient les informations de surveillance et coordination dans le format attendu</li>
            </ul>
        </div>
    </div>

    <script>
        // Script pour afficher le nom du fichier sélectionné et activer le bouton
        document.getElementById('file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucun fichier sélectionné';
            document.getElementById('file-name').textContent = fileName;
            
            // Activer le bouton si un fichier est sélectionné
            document.getElementById('submit-btn').disabled = !e.target.files[0];
        });
        
        // Fonctionnalité de glisser-déposer
        const dropArea = document.getElementById('drop-area');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
            dropArea.style.borderColor = 'var(--primary-color)';
            dropArea.style.backgroundColor = '#d1e7fc';
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
            dropArea.style.borderColor = 'var(--border-color)';
            dropArea.style.backgroundColor = 'var(--accent-color)';
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                document.getElementById('file').files = files;
                const event = new Event('change');
                document.getElementById('file').dispatchEvent(event);
            }
        }
    </script>
</body>
</html>
<?php
ob_start();
include("navbar.php");

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file_tmp = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($file_tmp);
    $sheetCount = $spreadsheet->getSheetCount(); 
    $includedSheets = 12; 

    
    // Tableau pour stocker les plannings par filière
    $planningByFiliere = [];

    // Processus de lecture de chaque feuille
    for ($sheetIndex = 0; $sheetIndex < $includedSheets; $sheetIndex++) {
        $sheet = $spreadsheet->getSheet($sheetIndex);
        $data = $sheet->toArray();

        foreach ($data as $i => $row) {
            if ($i < 4) continue; // Ignorer les premières lignes

            $date = trim($data[0][2] ?? '') . ' ' . trim($data[1][2] ?? '');
            $filiere = trim($row[2] ?? '');

            for ($h = 7; $h <= 10; $h++) {
                $hourValue = trim($data[3][$h] ?? '');
                $matiere = trim($row[$h] ?? '');

                if (empty($matiere)) continue; // Skip si matière est vide

                // Récupérer les coordinateurs de P à S
                $coordinateurs = [];
                $coordIndex = 15 + ($h - 7); // P=15, Q=16, R=17, S=18
                $coord = trim($row[$coordIndex] ?? '');    
                if (!empty($coord)) {
                    $coordinateurs[] = $coord;
                }
                

                // Salle
                $salle = trim($row[5] ?? '');
                if (!empty($salle)) {
                    // Ajouter à la liste de la filière
                    $key = $date . '|' . $hourValue . '|' . $matiere . '|' . $coord;
                    
                    if (!isset($planningByFiliere[$filiere][$key])) {
                        $planningByFiliere[$filiere][$key] = [
                            'date' => $date,
                            'heure' => $hourValue,
                            'matiere' => $matiere,
                            'coordinateur' => $coord,
                            'salles' => [] // tableau pour rassembler les salles
                        ];
                    }
                    $planningByFiliere[$filiere][$key]['salles'][] = $salle; // Ajouter la salle
                }
            }
        }
    }

    // Transformez les salles en chaîne de caractères séparées par des virgules
    foreach ($planningByFiliere as $filiere => &$entries) {
        foreach ($entries as &$entry) {
            $entry['salle'] = implode(', ', $entry['salles']); // Rassembler les salles
            unset($entry['salles']); // Enlever le tableau temporaire
        }
    }

    class MyPDF extends TCPDF {
        public function Header() {
            // Position initiale pour le contenu du header
            $this->SetY(10);
            $this->SetFont('dejavusans', '', 10); 
            // Ajout du QR code (Site ENSAO)
            $qrText = "http://ensao.ump.ma/fr/actualite/planning-des-devoirs-surveilles-mi-semestre-2-2024-2025";
            $style = array(
                'border' => 0,
                'vpadding' => 'auto',
                'hpadding' => 'auto',
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => false,
                'module_width' => 1,
                'module_height' => 1
            );
            // Position: 9mm from left, 31mm from top
            $this->write2DBarcode($qrText, 'QRCODE,L', 9, 30, 30, 40, $style);

            // Contenu du header aligné 
            $html = '
            <table cellpadding="0" cellspacing="0" style="width: 100%; ">
                    <tr>
                        <td style="font-size:9.4px; width:37%; vertical-align:top; line-height:1.5;">
                            Royaume du Maroc<br>
                            Université Mohamed Premier<br>
                            École Nationale des Sciences Appliquées<br>
                            Oujda
                        </td>
                        <td style="width:33%; text-align:left; vertical-align:middle;">
                            <img src="resources/ensao_logo.png"  style="width:140px;  display:block; margin:0 auto;">
                        </td>
                        <td style="text-align:right; font-size:11px; width:30%; vertical-align:top; direction:rtl; line-height:1.5;">
                            المملكة المغربية<br>
                            جامعة محمد الأول<br>
                            المدرسة الوطنية للعلوم التطبيقية<br>
                            وجدة
                        </td>
                    </tr>
                </table>';         
            $this->writeHTML($html, true, false, true, false, '');
            
            // Calcul de la position Y après le contenu du header
            $currentY = $this->GetY();
            
            // Barre effilée sous le texte (ajout de 6mm d'espace)
            $barY = $currentY - 6;
            $this->SetY($barY);
            
            $width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $xStart = $this->lMargin;
            $steps = 100;
            $maxThickness = 0.5;
            
            // Dessin de la barre effilée
            for ($i = 0; $i <= $steps; $i++) {
                $ratio = $i / $steps;
                $distanceFromCenter = abs($ratio - 0.5) * 2;
                $thickness = $maxThickness * (1 - pow($distanceFromCenter, 2));
                
                $x1 = $xStart + $width * ($i / $steps);
                $x2 = $xStart + $width * (($i + 1) / $steps);
                
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth($thickness);
                $this->Line($x1, $this->GetY(), $x2, $this->GetY());
            }
            
            // Réinitialiser l'épaisseur
            $this->SetLineWidth(0.2);
            
            // Position finale après la barre
            $this->SetY($barY + $maxThickness + 2);
        }

        public function Footer() { 
            $this->SetY(-20); // Ajusté pour laisser de l'espace pour la ligne et le texte
        
            // Position de la ligne très proche du texte
            $lineY = $this->GetY() + 2; // Position juste au-dessus du texte
            $width = $this->getPageWidth() - $this->lMargin - $this->rMargin;
            $xStart = $this->lMargin;
            $steps = 100;
            $maxThickness = 0.5;
        
            for ($i = 0; $i <= $steps; $i++) {
                $ratio = $i / $steps;
                $distanceFromCenter = abs($ratio - 0.5) * 2;
                $thickness = $maxThickness * (1 - pow($distanceFromCenter, 2));
        
                $x1 = $xStart + $width * ($i / $steps);
                $x2 = $xStart + $width * (($i + 1) / $steps);
        
                $this->SetDrawColor(0, 0, 0);
                $this->SetLineWidth($thickness);
                $this->Line($x1, $lineY, $x2, $lineY);
            }
        
            // Réinitialiser l'épaisseur
            $this->SetLineWidth(0.2);
        
            // Positionnement du texte juste sous la ligne
            $this->SetY($lineY + 1); // 1 mm sous la ligne
            $this->SetFont('helvetica', '', 8);
            $footerText = 'École Nationale des Sciences Appliquées - Complexe universitaire Al Qods, BP 669 - Oujda
             Tél : 05 36 50 54 70/71 - Fax : 05 36 50 54 72 - Email : administration.ensao@ump.ac.ma - Site web : ensao.ump.ma';
            $this->MultiCell(0, 10, $footerText, 0, 'C', 0, 1);
        }     
    }

    // ====== Generer PDF ======
    $pdf = new MyPDF();
    $pdf->SetMargins(10, 45, 10); 
    $pdf->SetHeaderMargin(7); 
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetAuthor('MyVT');
    $pdf->SetTitle('Planning Global');

    // Génération du PDF pour chaque filière
    foreach ($planningByFiliere as $filiere => $entries) {
        $pdf->AddPage();
    
        //  Filières ensao
        $nomsFilieres = [
            'STPI1' => 'Cycle Préparatoire - Sciences et Techniques pour l\'Ingénieur<br>Première année (STPI1)',
            'STPI2' => 'Cycle Préparatoire - Sciences et Techniques pour l\'Ingénieur<br>Deuxième année (STPI2)',
            'GINF1' => 'Cycle Ingénieur - Génie Informatique<br> Première année (GINF1)',
            'GINF2' => 'Cycle Ingénieur - Génie Informatique<br> Deuxième année (GINF2)',
            'GINF3' => 'Cycle Ingénieur - Génie Informatique <br>Troisième année (GINF3)',
            'GCIV1' => 'Cycle Ingénieur - Génie Civil <br> Première année (GCIV1)',
            'GCIV2' => 'Cycle Ingénieur - Génie Civil <br> Deuxième année (GCIV2)',
            'GCIV3' => 'Cycle Ingénieur - Génie Civil <br> Troisième année (GCIV3)',
            'GSEIR1'=> 'Cycle Ingénieur - Génie des Systèmes Electronique, Informatique et Réseaux <br> Première année (GSEIR1)',
            'GSEIR2'=> 'Cycle Ingénieur - Génie des Systèmes Electronique, Informatique et Réseaux <br> Deuxième année (GSEIR2)',
            'GSEIR3'=> 'Cycle Ingénieur - Génie des Systèmes Electronique, Informatique et Réseaux <br> Troisième année (GSEIR3)',
            'GIND1' => 'Cycle Ingénieur - Génie Industriel <br> Première année (GIND1)',
            'GIND2' => 'Cycle Ingénieur - Génie Industriel <br> Deuxième année (GIND2)',
            'GIND3' => 'Cycle Ingénieur - Génie Industriel <br> Troisième année (GIND3)',
            'GELC1' => 'Cycle Ingénieur - Génie Electrique <br> Première année (GELC1)',
            'GELC2' => 'Cycle Ingénieur - Génie Electrique <br> Deuxième année (GELC2)',
            'GELC3' => 'Cycle Ingénieur - Génie Electrique <br> Troisième année (GELC3)',
            'ITIRC1'=> 'Cycle Ingénieur - Ingénierie des Technologies de l\'information et Réseaux de Communication <br> Première année (ITIRC1)',
            'ITIRC2'=> 'Cycle Ingénieur - Ingénierie des Technologies de l\'information et Réseaux de Communication <br> Deuxième année (ITIRC2)',
            'ITIRC3'=> 'Cycle Ingénieur - Ingénierie des Technologies de l\'information et Réseaux de Communication <br> Troisième année (ITIRC3)',
            'IDSCC1'=> 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Première année (IDSCC1)',
            'IDSCC2'=> 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Deuxième année (IDSCC2)',
            'IDSCC3'=> 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Troisième année (IDSCC3)',
            'MGSI1' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Première année (MGSI1)',
            'MGSI2' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Deuxième année (MGSI2)',
            'MGSI3' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Troisième année (MGSI3)',
            'SICS1' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité <br> Première année  (SICS1)',
            'SICS2' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité <br> Deuxième année  (SICS2)',
            'SICS3' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité <br> Troisième année  (SICS3)',

        ];
    
        
        //Test:  Récupérer le nom complet ou utiliser le code si non trouvé
<<<<<<< HEAD
    $nomCompletFiliere = isset($nomsFilieres[$filiere]) ? $nomsFilieres[$filiere] : $filiere;
    
    // Test: si c'est un cycle d'ingénieur ou préparatoire
    $isCycleIngenieur = (strpos($filiere, 'STPI') !== 0); // Si ne commence pas par STPI, c'est un cycle ingénieur
    
    // Ajuster les largeurs des colonnes en fonction du cycle
    if ($isCycleIngenieur) {
        // Cycle ingénieur 
        $widthDate = '15%';
        $widthHeure = '13%';
        $widthMatiere = '35%'; 
        $widthCoordinateur = '27%';
        $widthSalle = '10%'; 
    } else {
        // Cycle préparatoire - garder les tailles originales
        $widthDate = '15%';
        $widthHeure = '13%';
        $widthMatiere = '28%';
        $widthCoordinateur = '25%';
        $widthSalle = '19%';
    }
    
    $html = '
    <p style="text-align:center;font-size:18px;">
        <strong>PLANNING <span style="color: #FF0000;">DÉFINITIF</span> DES EXAMENS<br>
        DS n° 2 - SEMESTRE 1, JANVIER 2025</strong>
    </p>
    <p style="text-align:center;font-size:12px;">
        <strong>Filière : ' . $nomCompletFiliere . '</strong>
    </p>';
=======
        $nomCompletFiliere = isset($nomsFilieres[$filiere]) ? $nomsFilieres[$filiere] : $filiere;
        
        $html = '
            <p style="text-align:center;font-size:18px;">
                <strong>PLANNING <span style="color: #FF0000;">DÉFINITIF</span> DES EXAMENS<br>';

        if (isset($_SESSION['session'])) {
            $html .= htmlspecialchars($_SESSION['session']);
        }

        $html .= '</strong></p>
        <p style="text-align:center;font-size:12px;">
            <strong>Filière : ' . $nomCompletFiliere . '</strong>
        </p>';
>>>>>>> 961693f4665798f8af91ae712bba80540bd793b6

    $html .= '<table cellpadding="5" cellspacing="0" style="border-collapse:collapse; border: 0.5px solid #7ba0eb; width:100%;">
        <thead>
            <tr style="background-color: #4472c4; color:white;">
                <th style="text-align:center; border: 0.5px solid #89a5d9; font-weight: bold; width: ' . $widthDate . ';">Date</th>
                <th style="text-align:center; border: 0.5px solid #89a5d9; font-weight: bold; width: ' . $widthHeure . ';">Heure</th>
                <th style="text-align:center; border: 0.5px solid #89a5d9; font-weight: bold; width: ' . $widthMatiere . ';">Matière</th>
                <th style="text-align:center; border: 0.5px solid #89a5d9; font-weight: bold; width: ' . $widthCoordinateur . ';">Coordinateur</th>
                <th style="text-align:center; border: 0.5px solid #89a5d9; font-weight: bold; width: ' . $widthSalle . ';">Salle(s)</th>
            </tr>
        </thead>
        <tbody>';

    // Group entries by date to calculate rowspans
    $groupedEntries = [];
    foreach ($entries as $entry) {
        $groupedEntries[$entry['date']][] = $entry;
    }

    $rowIndex = 0;
    foreach ($groupedEntries as $date => $dateEntries) {
        $first = true;
        
        foreach ($dateEntries as $entry) {
            $rowColor = ($rowIndex % 2 == 0) ? '#e6edf8' : '#e6edf8';
            
            $html .= '<tr style="background-color: ' . $rowColor . ';">';
            $rowIndex++;
            
            if ($first) {
                // Pour centrer la date par rapport aux lignes de la colonne heure
                //   if (count($dateEntries) == 2) {
                //     // Si exactement 2 lignes, on utilise une technique spéciale pour centrer
                //     $html .= '<td style="width:15%; text-align:center; border: 0.5px solid #89a5d9; vertical-align: middle; padding: 0; height: ' . $totalHeight . 'px;" rowspan="' . count($dateEntries) . '">
                //                 <div style="display: flex; align-items: center; justify-content: center; height: 100%;">' . htmlspecialchars($date) . '</div>
                //             </td>';
                // } 

                if (count($dateEntries) >= 2) {
                    // Si 2 lignes ou plus, on utilise la technique de centrage avec flexbox
                    $html .= '<td style="width:' . $widthDate . '; white-space: nowrap; text-align:center; border: 0.5px solid #89a5d9; vertical-align: middle; padding: 0;" rowspan="' . count($dateEntries) . '">
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">' . htmlspecialchars($date) . '</div>
                              </td>';
                } 
                else {
                    // Pour le cas d'une seule ligne, on utilise simplement vertical-align: middle
                    $html .= '<td style="width:' . $widthDate . '; text-align:center; border: 0.5px solid #89a5d9; vertical-align: middle; padding: 8px;" rowspan="' . count($dateEntries) . '">' . htmlspecialchars($date) . '</td>';
                }
                $first = false;
            }
            
            $html .= '<td style="width:' . $widthHeure . '; text-align:center; border: 0.5px solid #89a5d9; vertical-align:middle; ">' . htmlspecialchars($entry['heure']) . '</td>
                     <td style="width:' . $widthMatiere . '; text-align:center; border: 0.5px solid #89a5d9; vertical-align:middle;">' . htmlspecialchars($entry['matiere']) . '</td>
                     <td style="width:' . $widthCoordinateur . '; text-align:center; border: 0.5px solid #89a5d9; vertical-align:middle; ">' . htmlspecialchars($entry['coordinateur']) . '</td>
                     <td style="width:' . $widthSalle . '; text-align:center; border: 0.5px solid #89a5d9; vertical-align:middle; ">' . htmlspecialchars($entry['salle']) . '</td>
                  </tr>';
        }
    }

    $html .= '</tbody></table>';
$pdf->writeHTML($html, true, false, true, false, '');
    }

    ob_end_clean();
    $pdf->Output('planning_global.pdf', 'I');
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
        <h1>Génération du planning définitif</h1>
        <p>Importez un fichier Excel pour générer automatiquement les documents pour le planning global</p>
    </div>

    <form action="planning.php" method="post" enctype="multipart/form-data" id="upload-form" target="_blank">
        <div class="upload-section" id="drop-area">
            <i class="fas fa-file-excel file-icon"></i>
            <p>Glissez-déposez votre fichier Excel ici ou cliquez pour sélectionner un fichier contenant les données du planning global</p>
            
            <div class="file-input-wrapper">
                <label for="file" class="file-label">
                    <i class="fas fa-upload"></i> Choisir un fichier
                </label>
                <input type="file" name="file" id="file" class="file-input" accept=".xlsx, .xls" required>
                <div class="file-name" id="file-name">Aucun fichier sélectionné</div>
            </div>
        </div>
        
        <button type="submit" class="submit-btn" id="submit-btn" disabled>
            <i class="fas fa-file-pdf"></i> Générer les planning PDF
        </button>
    </form>

        
        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li>Le fichier doit être au format Excel (.xlsx ou .xls)</li>
                <li>Assurez-vous que les données de la liste CP1 sont bien structurées selon le format attendu</li>
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
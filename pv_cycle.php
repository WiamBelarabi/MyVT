<?php
ob_start();
include("navbar.php");
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

if($_SERVER['REQUEST_METHOD']== 'POST' && isset($_FILES['file1']) && isset($_FILES['file2'])){
    $file_tmp1= $_FILES['file1']['tmp_name']; //Planning global
    $file_tmp2= $_FILES['file2']['tmp_name']; //groupe cycles
    //partie 1 
    $spreadsheet1 = IOFactory::load($file_tmp1);
    $sheetCount = $spreadsheet1->getSheetCount();
    $includedSheets =12;

    $matieres = [];
    $salles=[];
    $filieres = [];

    for ($sheetIndex = 0; $sheetIndex< $includedSheets ;$sheetIndex++){
        $sheet1 = $spreadsheet1-> getSheet($sheetIndex);
        $data1 = $sheet1 -> toArray();
        foreach ($data1 as $i => $row){
            if ($i <4 ) continue;

            $filiere = trim ($row[2] ?? '');
            $date = trim($data1[0][2] ?? '') . ' ' . trim($data1[1][2] ?? '');
            $salle = trim($row[5] ?? '');

            // Initialize salle array if not set
            if (!isset($salles[$salle])) {
                $salles[$salle] = [];
            }

            for ($h = 7 ; $h <= 10 ; $h++){
                $heure = trim($data1[3][$h] ?? ''); 
                $nomMatiere = trim($row[$h] ?? '');

                if (empty($nomMatiere)) continue;

                    // Initialize arrays to hold coordinates and surveillants for the current hour
                $coord = '';
                $surv = '';

                // Extract coord from columns 15 to 18 for the current hour
                for ($c = 15; $c <= 18; $c++) {
                    $coordValue = trim($row[$c] ?? '');
                    if (!empty($coordValue)) {
                        $coord = $coordValue; // Take the first non-empty coord found
                        break; // Exit the loop once we have a value
                    }
                }

                // Extract surv from columns 23 to 30
                for ($col = 23; $col <= 30; $col++) {
                    $survValue = trim($row[$col] ?? '');
                    if (!empty($survValue)) {
                        $surv = $survValue; // Take the first non-empty surv found
                        break; // Exit the loop once we have a value
                    }
                }
                $filieres[$filiere][]=[
                    'filiere'=>$filiere,
                    'matieres'=>$matieres
                ];
                $matieres [$nomMatiere][] = [
                    'date' => $date,
                    'heure'=> $heure,
                    'nomMatiere' => $nomMatiere,
                    'coord' => $coord,
                    'surv' => $surv,
                    'salles' => $salle
                ];
            }
        }
    }
    //Partie 2
    $spreadsheet2 = IOFactory :: load($file_tmp2);
    $sheet2 = $spreadsheet2->getActiveSheet();
    $data2 = $sheet2->toArray();

    /*foreach($data2 as $i => $row){
        if ($i < 1 ) continue;
        $numero = $row[1];
        $cne = $row[2];    
        $nom = $row[3];    
        $prenom = $row[4];  
        $salle = $row[5];  
        $niv= $row[6];

        if(!isset($filieres[$niv])){
            $filieres[$niv] = [];
        }
        $filieres[$niv][]=[
            'numero' => $numero,
            'cne' => $cne,
            'nom' => $nom,
            'prenom' => $prenom,
            'niv'=>$niv,
            'matieres'=>$matieres
        ];
    }*/
    class MyPDF extends TCPDF {
        // Header
        public function Header() {
            // Position initiale pour le contenu du header
            $this->SetY(10);
            $this->SetFont('dejavusans', '', 10); 

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
            $this->SetY($lineY + 1); // 1mm/2 mm sous la ligne
            $this->SetFont('helvetica', '', 8);
            $footerText = 'École Nationale des Sciences Appliquées - Complexe universitaire Al Qods, BP 669 - Oujda
                Tél : 05 36 50 54 70/71 - Fax : 05 36 50 54 72 - Email : administration.ensao@ump.ac.ma - Site web : ensao.ump.ma';
            $this->MultiCell(0, 10, $footerText, 0, 'C', 0, 1);
        }  
    }
    print_r($filieres);
    //PDF
    /*
    $pdf = new MyPDF();
    $pdf -> setMargins(10,40,10);
    $pdf->SetHeaderMargin(7); 
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetAuthor('MyVT');
    $pdf->SetTitle('PV de Présence');
    ob_end_clean();*/

        foreach ($filieres as $filiere => $matieres) {
            
            $html = '<table cellpadding="2" cellspacing="0" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background-color: #4472c4; font-size: 7.5px;text-align:center; color:white;">
                                <th style="border: 0.5px solid #89a5d9;font-weight: bold;">Date</th>
                                <th style="border: 0.5px solid #89a5d9;font-weight: bold;">Heure</th>
                                <th style="border: 0.5px solid #89a5d9;font-weight: bold;">Matiére</th>
                                <th style="border: 0.5px solid #89a5d9;font-weight: bold;">Respo coordination</th>
                                <th style="border: 0.5px solid #89a5d9;font-weight: bold;">Salle</th>
                            </tr>
                        </thead>';
            /*foreach ($matieres as $nomMatiere => $details) {
                        $html .= '<tbody>
                                <tr style="font-size:6px;">
                                    <td style="text-align:center;border: 0.5px solid #89a5d9;">' . htmlspecialchars($details['date'] ) . '</td>
                                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($details['heure'] ) . '</td>
                                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($details['matiere'] ) . '</td>
                                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($details['coord'] ) . '</td>
                                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($details['salle']) . '</td>
                                </tr>';
            }*/
             $html .= '</tbody></table>';

            /*$total = count($etudiants);
            $moitie = ceil($total / 2);
            $gauche = array_slice($etudiants, 0, $moitie);
            $droite = array_slice($etudiants, $moitie);

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
                'IDSCC1' => 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Première année (IDSCC1)',
                'IDSCC2' => 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Deuxième année (IDSCC2)',
                'IDSCC3' => 'Cycle Ingénieur - Ingénierie Data Sciences et Cloud Computing <br> Troisième année (IDSCC3)',
                'MGSI1' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Première année (MGSI1)',
                'MGSI2' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Deuxième année (MGSI2)',
                'MGSI3' => 'Cycle Ingénieur - Management et Gouvernance des Systèmes d\'informations <br> Troisième année (MGSI3)',
                'SICS1' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité Première année <br> (SICS1)',
                'SICS2' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité Deuxième année <br> (SICS2)',
                'SICS3' => 'Cycle Ingénieur - Sécurité Informatique et Cyber Sécurité Troisième année <br> (SICS3)',

            ];

            //Test:  Récupérer le nom complet de la filière ou bien d'utiliser le code si non trouvé
            $nomCompletFiliere = isset($nomsFilieres[$niv]) ? $nomsFilieres[$niv] : $niv;
    
            // Construction du HTML avec le format demandé
            $html .= '<p style="text-align:center;font-size:12px;"> <strong>Filière : ' . $nomCompletFiliere . '</strong></p>';
            
            // Générer le tableau HTML avec deux colonnes
            $html .= '<table  border="0" cellpadding="5" cellspacing="5" style="width:100%;">
                        <tr>
                            <td style="width:50%;">' . genererTableauHTML($gauche) . '</td>
                            <td style="width:50%;">' . genererTableauHTML($droite) . '</td>
                        </tr>
                    </table>'; 
            */    
                
        }
           
        $pdf -> AddPage();
        $pdf->writeHTML( $html, true, false, true, false, '');

    //generer tableau d'etudiant
    $pdf->Output('pv_cycle.pdf', 'I');
    ob_end_clean();
} else {
    echo "";
}
ob_end_flush();
function genererTableauHTML($etudiants) {
    $html = '<table cellpadding="2" cellspacing="0" style="width:100%; border-collapse:collapse;">
                <thead >
                    <tr style="background-color: #4472c4; font-size: 7.5px;text-align:center; color:white; ">
                        <th style="width:9%;border: 0.5px solid #89a5d9;font-weight: bold;">N°</th>
                        <th style="width:19%;border: 0.5px solid #89a5d9;font-weight: bold;">CNE</th>
                        <th style="width:39%;border: 0.5px solid #89a5d9;font-weight: bold;">Nom</th>
                        <th style="width:33%;border: 0.5px solid #89a5d9;font-weight: bold;">Prénom</th>
                    </tr>
                </thead>
                <tbody >';

    foreach ($etudiants as $etudiant) {
        $html .= '<tr style="font-size:6px; ">
                    <td style="text-align:center;border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['numero']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['cne']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['nom']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['prenom']) . '</td>
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
    <title>Importer deux fichiers Excel</title>
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
            <h1>Génération de PV de présence</h1>
            <p>Importez deux fichiers Excel pour générer automatiquement les documents de PV</p>
        </div>

        <form action="pv_cycle.php" method="post" enctype="multipart/form-data" id="upload-form" target="_blank">
            <div class="upload-section">
                <i class="fas fa-file-excel file-icon"></i>
                <p>Glissez-déposez vos fichiers Excel ici ou cliquez pour sélectionner</p>

                <div class="file-input-wrapper">
                    <label for="file1" class="file-label">
                        <i class="fas fa-upload"></i> Choisir fichier 1
                    </label>
                    <input type="file" name="file1" id="file1" class="file-input" accept=".xlsx, .xls" required>
                    <div class="file-name" id="file1-name">Aucun fichier sélectionné</div>
                </div>
            </div>
            <div class="upload-section">
                <i class="fas fa-file-excel file-icon"></i>
                <p>Glissez-déposez vos fichiers Excel ici ou cliquez pour sélectionner</p>

                <div class="file-input-wrapper">
                    <label for="file2" class="file-label">
                        <i class="fas fa-upload"></i> Choisir fichier 2
                    </label>
                    <input type="file" name="file2" id="file2" class="file-input" accept=".xlsx, .xls" required>
                    <div class="file-name" id="file2-name">Aucun fichier sélectionné</div>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submit-btn" disabled>
                <i class="fas fa-file-pdf"></i> Générer PDF
            </button>
        </form>

        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li>Les fichiers doivent être au format Excel (.xlsx ou .xls)</li>
                <li>Assurez-vous que les fichiers contiennent les informations attendues</li>
            </ul>
        </div>
    </div>

    <script>
        function updateButtonState() {
            const file1 = document.getElementById('file1').files[0];
            const file2 = document.getElementById('file2').files[0];
            document.getElementById('submit-btn').disabled = !(file1 && file2);
        }

        document.getElementById('file1').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucun fichier sélectionné';
            document.getElementById('file1-name').textContent = fileName;
            updateButtonState();
        });

        document.getElementById('file2').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Aucun fichier sélectionné';
            document.getElementById('file2-name').textContent = fileName;
            updateButtonState();
        });

        
    </script>
</body>
</html>
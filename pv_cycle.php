<?php


    ob_start();


    include("navbar.php");
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    
    // Fonction pour générer le tableau des étudiants
    function genererTableauHTML($etudiants, $startIndex = 1) {
        $html = '<table cellpadding="2" cellspacing="0" style="width:100%; border-collapse:collapse; table-layout:auto; margin-bottom:10px;">
                    <thead>
                        <tr style="background-color: #4472c4; font-size: 8px; text-align:center; color:white;">
                            <th style="width:9%;border: 0.5px solid #89a5d9;font-weight: bold;">N°</th>
                            <th style="width:18%;border: 0.5px solid #89a5d9;font-weight: bold;">CNE</th>
                            <th style="width:31%;border: 0.5px solid #89a5d9;font-weight: bold;">Nom</th>
                            <th style="width:29%; border: 0.5px solid #89a5d9;font-weight: bold;">Prénom</th>
                            <th style="width:13%; border: 0.5px solid #89a5d9;font-weight: bold;">P/ABS</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($etudiants as $index => $etudiant) {
        // Utiliser startIndex comme point de départ pour la numérotation
        $lineNumber = $startIndex + $index;
        $html .= '<tr style="font-size:6.5px;">
                    <td style="text-align:center; border: 0.5px solid #89a5d9;">' . $lineNumber . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['cne']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['nom']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;">' . htmlspecialchars($etudiant['prenom']) . '</td>
                    <td style="border: 0.5px solid #89a5d9;"></td>
                  </tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}
    
    // Classe PDF
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

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file1']) && isset($_FILES['file2'])) {
        try {
            // Vider le tampon de sortie existant
            ob_clean();
            
            $file_tmp1 = $_FILES['file1']['tmp_name']; //fichier des coordinateur
            $file_tmp2 = $_FILES['file2']['tmp_name']; //liste d'etudiant
            
            // Vérifier si les fichiers existent
            if (!file_exists($file_tmp1) || !file_exists($file_tmp2)) {
                throw new Exception("Les fichiers téléchargés n'existent pas.");
            }
            
            //PARTIE 1
            $spreadsheet1 = IOFactory::load($file_tmp1);
            $sheetCount = $spreadsheet1->getSheetCount();
            $includedSheets = min(12, $sheetCount); // Éviter les erreurs si moins de 12 feuilles
            $surveillant = [];
            $coordData = [];

            $pv = []; // $pv[filiere][matiere][salle] = details

            for ($sheetIndex = 0; $sheetIndex < $includedSheets; $sheetIndex++) {
                $sheet = $spreadsheet1->getSheet($sheetIndex);
                $data = $sheet->toArray();

                foreach ($data as $i => $row) {
                    if ($i < 4) continue;
                    $f = trim($row[2] ?? '');
                    $date = trim($data[0][2] ?? '') . ' ' . trim($data[1][2] ?? '');
                    $salle = trim($row[5] ?? '');

                    for ($h = 7; $h <= 10; $h++) { 
                        $hourValue = trim($data[3][$h] ?? ''); 
                        $matiere = trim($row[$h] ?? '');

                        if (empty($matiere)) continue;

                        // ---------- GESTION DES COORDINATEURS ----------
                        $coordIndex = 15 + ($h - 7); // P=15, Q=16, R=17, S=18
                        $coord = trim($row[$coordIndex] ?? '');
                        $surveillants = [];
                        for ($col = 23; $col <= 30; $col++) {
                            $surv = trim($row[$col] ?? '');
                            if (!empty($surv)) $surveillants[] = $surv;
                        }

                        $pv[$f][$matiere][$salle] = [
                            'date'         => $date,
                            'heure'        => $hourValue,
                            'coord' => $coord,
                            'surveillants' => $surveillants,
                            'matiere'=>$matiere,
                            'salle'=>$salle
                        ];
            
                    }
                }
            }
            
            //partie 2
            $spreadsheet2 = IOFactory::load($file_tmp2);
            $sheet = $spreadsheet2->getActiveSheet();
            $data = $sheet->toArray();
            $filieres = [];
            foreach ($data as $i => $row) {
                if ($i < 1) continue;
                $numero = $row[1] ?? '';  
                $cne = $row[2] ?? '';    
                $nom = $row[3] ?? '';    
                $prenom = $row[4] ?? '';  
                $salle = $row[5] ?? '';  
                $filiere = $row[6] ?? '';

                if (empty($filiere)) continue;

                if (!isset($filieres[$filiere])) {
                    $filieres[$filiere] = [];
                }

                $filieres[$filiere][] = [
                    'numero' => $numero,
                    'cne' => $cne,
                    'nom' => $nom,
                    'prenom' => $prenom
                ];
            }
            
            
            // Créer le PDF
            $pdf = new MyPDF();
            $pdf->SetMargins(10, 40, 10); 
            $pdf->SetHeaderMargin(7); 
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetAuthor('MyVT');
            $pdf->SetTitle('PV de Présence');

            // Générer le contenu du PDF
            $pagesGenerated = 0;
            
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
            
           
            // Parcourir toutes les filières et tous les examens
            foreach ($pv as $filiere => $matieres) {
                // Vérifier si la filière existe dans les données d'étudiants
                if (!isset($filieres[$filiere]) || empty($filieres[$filiere])) continue;
                
                $etudiantsFiliere = $filieres[$filiere];
                
                // Récupérer le nom complet de la filière e
                $nomCompletFiliere = isset($nomsFilieres[$filiere]) ? $nomsFilieres[$filiere] : $filiere;
                
                // Parcourir toutes les matières pour cette filière
                foreach ($matieres as $matiere => $salles) {
                    // Parcourir toutes les salles pour cette matière
                    foreach ($salles as $salle => $infoExam) {
                        $pdf->AddPage();
                        $pagesGenerated++;
                        
         
                            $html = '<div style="text-align:center; font-size:14px; font-weight:bold;">
                        Filière : ' . $nomCompletFiliere . '<br><br>

                        <span style="font-size:12px; font-weight:normal;">
                            PV des Devoirs Surveillés (DS 1), Semestre 1 <br>
                        </span>
                    </div>';

                        
                        // Tableau des informations sur l'examen
                        $html .= '<table cellpadding="3" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:15px;">
                            <tr style="background-color: #4472c4; color:white; text-align:center; font-size:8px;">
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:20%;font-weight: bold;">Date</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:10%;font-weight: bold;">Heure</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:35%;font-weight: bold;">Matière</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:25%;font-weight: bold;">Responsable de Coordination</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:10%;font-weight: bold;">Salle</th>
                            </tr>
                            <tr style="text-align:center; font-size:8px;">
                                <td style=" text-align:center; border: 0.5px solid #7ba0eb;">' . htmlspecialchars($infoExam['date']) . '</td>
                                <td style=" text-align:center; border: 0.5px solid #7ba0eb;">' . htmlspecialchars($infoExam['heure']) . '</td>
                                <td style=" text-align:center; border: 0.5px solid #7ba0eb;">' . htmlspecialchars($infoExam['matiere']) . '</td>
                                <td style=" text-align:center; border: 0.5px solid #7ba0eb;">' . htmlspecialchars($infoExam['coord']) . '</td>
                                <td style=" text-align:center; border: 0.5px solid #7ba0eb;">' . htmlspecialchars($infoExam['salle']) . '</td>
                            </tr>
                        </table>';
                        $html .= '<p style="font-size:1px; line-height:3px;">&nbsp;</p>';


                        
                        // Tableau des surveillants
                        $html .= '<table cellpadding="3" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:15px;">
                            <tr style="background-color: #4472c4; color:white; text-align:center; font-size:8px;">
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:5%;font-weight: bold;">N°</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:30%;font-weight: bold;">Nom du Surveillant</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:15%;font-weight: bold;">Signature</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:25%;font-weight: bold;">Observations</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9; width:25%;font-weight: bold;">Nombre de copies rendues</th>
                            </tr>';

                        // Ajouter les lignes pour les surveillants (vides ou avec les données disponibles)
                        for ($i = 1; $i <= 2; $i++) {
                            $surveillant = isset($infoExam['surveillants'][$i-1]) ? htmlspecialchars($infoExam['surveillants'][$i-1]) : '';
                            
                            $html .= '<tr style="font-size:8px;">
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;">' . $i . '</td>
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;">' . $surveillant . '</td>
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;"></td>';
                            
                            // Ajouter les cellules fusionnées uniquement à la première ligne
                            if ($i == 1) {
                                $html .= '<td rowspan="2" style="text-align:center; border: 0.5px solid #7ba0eb; height:20px;"></td>
                                        <td rowspan="2" style="text-align:center; border: 0.5px solid #7ba0eb; height:20px;"></td>';
                            }
                            // Ne pas ajouter ces cellules pour la deuxième ligne
                            
                            $html .= '</tr>';
                        }
                        $html .= '</table>';
                        
                        // Saut de ligne entre les deux tableaux
                        $html .= '<p style="font-size:1px; line-height:3px;">&nbsp;</p>';


                        // Calculer le nombre d'étudiants
                        $nbEtudiants = count($etudiantsFiliere);
                        
                        // Tableau des statistiques
                        $html .= '<table cellpadding="3" cellspacing="0" style="width:100%; border-collapse:collapse; margin-bottom:10px;">
                            <tr style="background-color: #4472c4; color:white; text-align:center; font-size:8px;">
                                <th style="text-align:center; border: 0.5px solid #89a5d9;font-weight: bold; width:33%;">Nombre de convoqués</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9;font-weight: bold; width:33%;">Nombre de présents</th>
                                <th style="text-align:center; border: 0.5px solid #89a5d9;font-weight: bold;width:34%;">Nombre d\'absents</th>
                            </tr>
                            <tr style="text-align:center; font-size:8px;">
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;">' . $nbEtudiants . '</td>
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;"></td>
                                <td style="text-align:center; border: 0.5px solid #7ba0eb;"></td>
                            </tr>
                        </table>';
                        
                        // Diviser les étudiants en deux colonnes
                        $moitie = ceil($nbEtudiants / 2);
                        $gauche = array_slice($etudiantsFiliere, 0, $moitie);
                        $droite = array_slice($etudiantsFiliere, $moitie);
                        
                          // Générer le tableau HTML avec deux colonnes
                                $html .= '<table border="0" cellpadding="5" cellspacing="5" style="width:100%;">
                                            <tr>
                                            <td style="width:50%; vertical-align:top;">' . genererTableauHTML($gauche, 1) . '</td>
                                            <td style="width:50%; vertical-align:top;">' . genererTableauHTML($droite, $moitie + 1) . '</td>
                                            </tr>
                                        </table>';
                        
                        // Ajouter la page avec le tableau dans le PDF
                        $pdf->writeHTML($html, true, false, true, false, '');
                    }
                }
            }
            
            
            // Générer le PDF et l'envoyer au navigateur
            $pdf->Output('pv_cycle.pdf', 'I');
            
            // Terminer le script
            exit();
            
        } catch (Exception $e) {
            // En cas d'erreur, afficher un message
            ob_clean();
            echo "<h2>Erreur lors de la génération du PDF</h2>";
            echo "</div>";
        }
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

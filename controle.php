<?php
    include("navbar.php");
    ob_start();
    require 'vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
        $today = date('d/m/Y'); 
        $file_tmp = $_FILES['file']['tmp_name'];
        
        // Charger le fichier Excel
        $spreadsheet = IOFactory::load($file_tmp);
        
        //nbr de page du classeur
        $sheetCount = $spreadsheet->getSheetCount();
        
        // nombre de pages à utiliser
        $includedSheets = 12; 
        
        $controle = [];
        
        //initialiser chaque heure pour son controle
        $heureCols = [
            7 => 19, // H -> T
            8 => 20, // I -> U
            9 => 21, // J -> V
            10 => 22 // K -> W
        ];
        // boucle sur chaque page
        for ($sheetIndex = 0; $sheetIndex < $includedSheets; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $data = $sheet->toArray();
            //date 
            $date = trim($data[0][2] ?? '') . ' ' . trim($data[1][2] ?? '');
            // extrère les info
            foreach ($heureCols as $heureCol => $controlCol) {
                $heureValue = trim($data[3][$heureCol] ?? '');
        
                // lignes 5 à 17
                for ($i = 4; $i <= 43; $i++) {
                    $filiere = trim($data[$i][2] ?? '');
                    $salle = trim($data[$i][5] ?? '');
                    //controle
                    $contr = trim($data[$i][$controlCol] ?? '');
        
                    if (!empty($contr)) {
                        $controle[] = [
                            'date' => $date,
                            'heure' => $heureValue,
                            'filiere' => $filiere,
                            'salle' => $salle,
                            'controle' => $contr
                        ];
                    }
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
        //organiser selon le controleur 
        $grouped = [];
        foreach ($controle as $entry) {
            $grouped[$entry['controle']][] = $entry;
        }
        // ====== Generer PDF ======
        $pdf = new MyPDF();
        $pdf->SetMargins(10, 50, 10); 
        $pdf->SetHeaderMargin(7); 
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetAuthor('MyVT');
        $pdf->SetTitle('contrôle de présence');

        //tableau
        foreach ($grouped as $contr => $entries) {
            $html = '<p style="text-align:right;">Oujda le '. date('d/m/Y').'<br></p>
            <p style="text-align:center;"><br><strong>DE</strong><br>MONSIEUR LE DIRECTEUR<br>DE L\'ECOLE NATIONAL DES SCIENCES APPLIQUEES D\'OUJDA</p>
            <p style="text-align:center;"><strong>À<br>MONSIEUR/MADAME ' . htmlspecialchars($contr) .'</strong></p><br>
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
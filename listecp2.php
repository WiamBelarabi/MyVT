<?php
ob_start();

require 'navbar.php'; 
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
    foreach ($data as $i => $row) {
        if ($i < 1) continue;
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
    $pdf->SetHeaderMargin( 7); 
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
        <p style="text-align:center;font-size:13px;">Liste des étudiants - Salle ' . htmlspecialchars($salle) . '</p>
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
    ob_end_clean();
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
        <h1>Génération des listes CP2</h1>
        <p>Importez un fichier Excel pour générer automatiquement les documents pour la liste CP2</p>
    </div>

    <form action="listecp2.php" method="post" enctype="multipart/form-data" id="upload-form">
        <div class="upload-section" id="drop-area">
            <i class="fas fa-file-excel file-icon"></i>
            <p>Glissez-déposez votre fichier Excel ici ou cliquez pour sélectionner un fichier contenant les données des CP2</p>
            
            <div class="file-input-wrapper">
                <label for="file" class="file-label">
                    <i class="fas fa-upload"></i> Choisir un fichier
                </label>
                <input type="file" name="file" id="file" class="file-input" accept=".xlsx, .xls" required>
                <div class="file-name" id="file-name">Aucun fichier sélectionné</div>
            </div>
        </div>
        
        <button type="submit" class="submit-btn" id="submit-btn" disabled>
            <i class="fas fa-file-pdf"></i> Générer les listes PDF
        </button>
    </form>

        
        <div class="instructions">
            <h3>Instructions</h3>
            <ul>
                <li>Le fichier doit être au format Excel (.xlsx ou .xls)</li>
                <li>Assurez-vous que les données de la liste CP2 sont bien structurées selon le format attendu</li>
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
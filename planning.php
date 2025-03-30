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
        //organiser les données par niveau
        $niveau=[];
        foreach ($data as $row) {
            $niveau=$row[2];
            $salle=$row[5];
            
        }
    }
?>
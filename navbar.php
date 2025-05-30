<?php
session_start();

if (isset($_POST['session'])) {
    $_SESSION['session'] = $_POST['session'];
}
?>
<!DOCTYPE html> 
<html lang="fr">
<head>
    <title>MyVT</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="assets/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        #session {
            padding : 12px;
            border: 0.5;
            border-color :  #6e8efb; 
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
        }
        .session{
            display: flex;
            justify-content: center; /* Horizontal center */
            height: 100vh; 
        }
        #submit{
            background: linear-gradient(135deg, #6e8efb 0%, #a777e3 100%);
            padding : 12px;
            border: none; 
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="app-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>My VT</h1>
            <button class="toggle-btn" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="sidebar-content">
            <a href="accueil.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'accueil.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Accueil</span>
            </a>
            <a href="planning.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Planning global</span>
            </a>
            <a href="pv_cp.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i>
                <span>PV de présence CP</span>
            </a>
             <a href="pv_administration.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i>
                <span>PV de présence CP Administration</span>
            </a>
            <a href="pv_cycle.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>PV de présence Cycle</span>
            </a>
            <a href="recap.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Récap des DS</span>
            </a>
            <a href="listecp1.php" class="nav-item">
                <i class="fas fa-list-ol"></i>
                <span>Liste des CP1</span>
            </a>
            <a href="listecp2.php" class="nav-item">
                <i class="fas fa-list-ul"></i>
                <span>Liste des CP2</span>
            </a>
            <a href="listes_cycle.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                <span>Liste des cycles</span>
            </a>
            <a href="controle.php" class="nav-item">
                <i class="fas fa-tasks"></i>
                <span>Controle de présence</span>
            </a>
            <a href="surveillance.php" class="nav-item">
                <i class="fas fa-eye"></i>
                <span>Surveillance et Coordination</span>
            </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    </div>
  
    <?php if (basename($_SERVER['PHP_SELF']) == 'accueil.php'): ?>
<div class="dashboard-container">
    <div class="content-header">
        <h2>Bienvenue sur MyVT</h2>
        <div class="user-profile">
            <span class="user-name">Admin</span>
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
        </div>
    </div>

    <div class="content-body">
        <div class="welcome-card" id="dashboard">
            <h3>Tableau de bord</h3>
            <p>Veuillez préciser la session des examens pour commancer :</p>
            <div class="session">
            <form id="sessionForm">
                <select id="session" name="session" required>
                    <option value="DS1 Semestre1">DS1 Semestre 1</option>
                    <option value="DS1 Semestre2">DS1 Semestre 2</option>
                    <option value="DS2 Semestre1">DS2 Semestre 1</option>
                    <option value="DS2 Semestre2">DS2 Semestre 2</option>
                </select>
                <input type="submit" id="submit" value="Soumettre">
            </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.main-content').classList.toggle('expanded');
    });

     document.getElementById('sessionForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var formData = new FormData(this);

        fetch('navbar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log('Session saved:', data);
            // Optionally, handle the response data here
        })
        .catch(error => console.error('Error:', error));
    });
</script>
</body>
</html>

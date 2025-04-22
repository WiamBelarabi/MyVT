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
            <a href="importation.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Importer les fichiers</span>
            </a>
            <a href="planning.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Planning global</span>
            </a>
            <a href="pv_cp.php" class="nav-item">
                <i class="fas fa-clipboard-check"></i>
                <span>PV de présence CP</span>
            </a>
            <a href="pv_cycle.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span>PV de présence Cycle</span>
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
            <p>Sélectionnez une option dans le menu pour commencer</p>
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
</script>
</body>
</html>

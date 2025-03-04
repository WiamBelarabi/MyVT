<?php
session_start();
session_destroy(); // Détruit toutes les sessions
header("Location: index.php"); // Redirection vers la page de connexion
exit();
?>

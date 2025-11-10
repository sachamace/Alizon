<?php
    session_start();

    // Vérifie si l'utilisateur est connecté
    if (!isset($_SESSION['id'])) {
        header("Location: connexion.php");
        exit();
    }
?>

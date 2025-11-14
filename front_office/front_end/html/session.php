<?php
    session_start();
    // Vérifie si l'utilisateur est connecté
    if (!isset($_SESSION['login'])) {
        header("Location: seconnecter.php");
        exit();
    }
?>

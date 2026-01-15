<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Vérifie si l'utilisateur est connecté
    if (!isset($_SESSION['id']) || !isset($_SESSION['login'])) {
        header("Location: seconnecter.php");
        exit();
    }
?>

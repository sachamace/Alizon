<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Vérifier si l'utilisateur est connecté
    $isLogged = isset($_SESSION['login']);
?>

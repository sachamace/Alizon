<?php
    session_start();
    // Vérifier si l'utilisateur est connecté
    $isLogged = isset($_SESSION['login']);
?>

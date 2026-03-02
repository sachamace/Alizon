<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';
    $id_client_connecte = $_SESSION['id_client'];

    $stmt_a2f = $pdo->prepare("UPDATE compte_client SET codea2f = '' WHERE id_client = :id_client");
    $stmt_a2f->execute(['id_client' => $id_client_connecte]);

    header("Location: consulterProfilClient.php");
    exit();
?> 
<?php
    include 'config.php';

    header('Content-Type: application/json');

    $stmt = $pdo->query("
        SELECT cv.id_vendeur, cv.raison_sociale, av.adresse, av.latitude, av.longitude
        FROM compte_vendeur cv
        JOIN adresse_vendeur av ON cv.id_vendeur = av.id_vendeur
        WHERE av.latitude IS NOT NULL AND av.longitude IS NOT NULL
    ");

    echo json_encode($stmt->fetchAll());
?>
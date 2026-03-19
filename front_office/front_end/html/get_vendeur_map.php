<?php
    include 'config.php';

    header('Content-Type: application/json');

    $stmt = $pdo->query("
        SELECT 
            cv.id_vendeur,
            cv.raison_sociale,
            av.adresse,
            av.latitude,
            av.longitude,
            COUNT(p.id_produit) AS nb_produits
        FROM compte_vendeur cv
        JOIN adresse_vendeur av ON cv.id_vendeur = av.id_vendeur
        LEFT JOIN produit p ON p.id_vendeur = cv.id_vendeur AND p.est_actif = true
        WHERE av.latitude IS NOT NULL AND av.longitude IS NOT NULL
        GROUP BY cv.id_vendeur, cv.raison_sociale, av.adresse, av.latitude, av.longitude
    ");

    echo json_encode($stmt->fetchAll());
?>
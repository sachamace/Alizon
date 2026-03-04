<?php
    $id_vendeur_connecte = $_SESSION['vendeur_id'];

    $stmt_a2f = $pdo->prepare("UPDATE compte_vendeur SET codea2f = '' WHERE id_vendeur = :id_vendeur");
    $stmt_a2f->execute(['id_vendeur' => $id_vendeur_connecte]);

    header("Location: index.php?page=profil&type=consulter");
    exit();
?> 
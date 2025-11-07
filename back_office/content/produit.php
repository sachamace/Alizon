<?php
if (isset($_GET['id']) && isset($_GET['type'])) {
    $id = $_GET['id'];
    $type = $_GET['type'];

    $stmt = $pdo->prepare("SELECT * FROM produit WHERE id_produit= :id");

    $stmt->execute(['id' => $id]);

    $produit = $stmt->fetch();

    if (!$produit) {
        echo "<p>Produit introuvable.</p>";
        exit;
    }

    $stmtCat = $pdo->prepare("SELECT libelle FROM categorie WHERE id_categorie= :id_categorie");
    $stmtCat->execute(['id_categorie' => $produit['id_categorie']]);
    $categorie = $stmtCat->fetchColumn();

    include 'produit_consulter.php';
} ?>


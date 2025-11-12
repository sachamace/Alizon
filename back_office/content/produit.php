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

    $stmtCat = $pdo->prepare("SELECT libelle FROM categorie WHERE libelle= :libelle");
    $stmtCat->execute(['libelle' => $produit['categorie']]);
    $categorie = $stmtCat->fetchColumn();

    if ($_GET['type'] == "consulter") {

        if ($_GET['type'] == "consulter") {
            include 'produit_consulter.php';
        } else if ($_GET['type'] == 'modifier') {
            include 'produit_modifier.php';
        }
    }
} else if (isset($_GET['type']) && $_GET['type'] === 'creer') {
    $stmtCat = $pdo->query("SELECT libelle FROM categorie");
    $categorie = $stmtCat->fetchAll();
    include 'produit_creer.php';
} ?>
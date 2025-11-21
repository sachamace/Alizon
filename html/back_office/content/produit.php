<?php
if (isset($_GET['page']) && $_GET['page'] === 'produit') {
    if (isset($_GET['id']) && isset($_GET['type'])) {
        $id = $_GET['id'];
        $type = $_GET['type'];
        $id_vendeur_connecte = $_SESSION['vendeur_id'];

        $stmt = $pdo->prepare("SELECT * FROM produit WHERE id_produit= :id");
        $stmt->execute(['id' => $id]);
        $produit = $stmt->fetch();

        if (!$produit) {
            echo "<p>Produit introuvable.</p>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT libelle FROM categorie WHERE libelle= :libelle");
        $stmt->execute(['libelle' => $produit['categorie']]);
        $categorie = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM media_produit WHERE id_produit = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM media_produit WHERE id_produit = ?");
        $stmtCheck->execute([$id]);
        $imageCount = $stmtCheck->fetchColumn();

        if ($_GET['page'] == "produit" && $_GET['type'] == "consulter") {
            include 'produit_consulter.php';
        } else if ($_GET['page'] == "produit" && $_GET['type'] == 'modifier') {
            include 'produit_modifier.php';
        }
    } else if (isset($_GET['type']) && $_GET['type'] === 'creer') {
        $stmtCat = $pdo->query("SELECT libelle FROM categorie");
        $categorie = $stmtCat->fetchAll();
        include 'produit_creer.php';

    }
} ?>
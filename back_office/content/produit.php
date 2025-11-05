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
} ?>

<section>
    <h2>Informations du produit</h2>
    <article>
        <h3>Nom du produit</h3>
        <p><?php echo htmlentities($produit['nom_produit']) ?></p>
        <h3>Description</h3>
        <p><?php echo htmlentities($produit['description_produit']) ?></p>
        <h3>Catégorie</h3>
        <p><?php echo htmlentities($categorie ?: 'Aucune catégorie') ?></p>
    </article>
    <h2>Prix/Stock</h2>
    <article>
        <h3>Prix</h3>
        <p><?php echo htmlentities($produit['prix_unitaire_ht']) ?></p>
        <h3>Stock</h3>
        <p><?php echo htmlentities($produit['stock_disponible']) ?></p>
    </article>
    <article>
        <h3>Média</h3>
        <img src="" alt="">
    </article>
    <article>
        <h3>Visibilité</h3>
        <INPUT TYPE="radio" NAME= "visible" VALUE="visible" CHECKED> visible
        <INPUT TYPE="radio" NAME= "visible" VALUE="cache"> caché
    </article>
</section>
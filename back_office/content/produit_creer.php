<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $nom = $_POST['nom_produit'];
    $description = $_POST['description_produit'];
    $categorie = $_POST['categorie'];
    $prix_ttc = str_replace(',', '.', $_POST['prix_ttc_produit']);
    $prix_ht = str_replace(',', '.', $_POST['prix_unitaire_ht_produit']);
    $tva = str_replace(',', '.', $_POST['taux_tva_produit']);
    $stock = $_POST['stock_disponible_produit'];
    $actif = $_POST['visibilite'];

    if (!preg_match('/^\d+([.,]\d{1,2})?$/', $_POST['prix_ttc_produit'])) {
        $errors[] = "Le prix TTC doit contenir uniquement des chiffres et une virgule (ex : 12,99).";
    }

    if (!preg_match('/^\d+([.,]\d{1,2})?$/', $_POST['prix_unitaire_ht_produit'])) {
        $errors[] = "Le prix HT doit contenir uniquement des chiffres et une virgule (ex : 10,50).";
    }

    if (!preg_match('/^(0([.,]\d{1,2})?|1([.,]0{1,2})?)$/', $_POST['taux_tva_produit'])) {
        $errors[] = "La TVA doit être un nombre entre 0 et 1 (ex : 0,20).";
    }

    if (!preg_match('/^\d+$/', $stock)) {
        $errors[] = "Le stock doit être un nombre entier positif.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO produit (
            nom_produit, 
            description_produit, 
            prix_unitaire_ht, 
            taux_tva, 
            prix_ttc, 
            stock_disponible,
            est_actif,
            categorie
        ) VALUES (
            :nom,
            :description_prod,
            :prix_ht,
            :tva,
            :prix_ttc,
            :stock,
            :actif,
            :categorie
        )");

        $stmt->execute([
            'nom' => $nom,
            'description_prod' => $description,
            'categorie' => $categorie,
            'prix_ttc' => $prix_ttc,
            'prix_ht' => $prix_ht,
            'tva' => $tva,
            'stock' => $stock,
            'actif' => $actif
        ]);



        echo "<script>
            window.location.href = 'index.php?page=dashboard';
        </script>";
        exit;
    } else {
        echo "<ul style='color:red'>";
        foreach ($errors as $err)
            echo "<li>$err</li>";
        echo "</ul>";
    }
}
?>

<section class="produit-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <h2>Créer produit</h2>

        <div class="produit-content">
            <!-- Partie gauche : informations générales -->
            <div class="produit-infos">
                <h3>Informations générales</h3>
                <article>
                    <h3>Nom du produit</h3>
                    <input type="text" id="nom_produit" name="nom_produit"
                        value="">
                </article>
                <article>
                    <h3>Description</h3>
                    <input type="text" id="description_produit" name="description_produit"
                        value="">
                </article>
                <article>
                    <h3>Catégorie</h3>

                    <select name="categorie" id="select-categorie">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php
                        $stmtAllCat = $pdo->query("SELECT libelle FROM categorie");

                        foreach ($stmtAllCat as $cat) { ?>
                            <option value="<?php echo htmlentities($cat['libelle']) ?>">
                                <?php echo htmlentities($cat['libelle']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </article>
                <article>
                    <h3>Média</h3>

                    <div class="media-upload">
                        <label for="nouvelle_image">Ajouter une image :</label>
                        <input type="file" name="nouvelle_image[]" id="nouvelle_image" accept="image/*" multiple>
                    </div>
                </article>
            </div>

            <!-- Partie droite : prix, stock, TVA -->
            <div class="produit-prix-stock">
                <article>
                    <h3>Prix</h3>

                    <h4>Prix TTC</h4>
                    <input type="text" id="prix_ttc_produit" name="prix_ttc_produit"
                        value=""
                        pattern="^\d+([,]\d{1,2})?$" title="Uniquement chiffres et virgule (ex : 12,99)">

                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value=""
                        pattern="^\d+([,]\d{1,2})?$" title="Uniquement chiffres et virgule (ex : 10,50)">

                    <h4>TVA</h4>
                    <input type="text" id="taux_tva_produit" name="taux_tva_produit"
                        value=""
                        pattern="^(0([,]\d{1,2})?|1([,]0{1,2})?)$"
                        title="Uniquement un nombre entre 0 et 1 (ex : 0,20)">
                </article>

                <article>
                    <h3>Stock</h3>
                    <input type="text" id="stock_disponible_produit" name="stock_disponible_produit"
                        value="" pattern="^\d+$"
                        title="Uniquement chiffres entiers">
                </article>
            </div>
        </div>


        <div class="produit-actions">
            <input type="submit" name="confirmer" classe="confirmer" value="Confirmer">
            <a href="index.php?page=produit"
                class="annuler">Annuler</a>
        </div>
    </form>
</section>
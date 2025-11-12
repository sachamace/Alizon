<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $nom = $_POST['nom_produit'];
    $description = $_POST['description_produit'];
    $categorie = $_POST['categorie'];
    $prix_ht = str_replace(',', '.', $_POST['prix_unitaire_ht_produit']);
    $tva = str_replace(',', '.', $_POST['taux_tva_produit']);
    $stock = $_POST['stock_disponible_produit'];
    $actif = $_POST['visibilite'];

    if (!preg_match('/^\d+([.,]\d{1,2})?$/', $_POST['prix_unitaire_ht_produit'])) {
        $errors[] = "Le prix HT doit contenir uniquement des chiffres et une virgule (ex : 10,50).";
    }

    if (!preg_match('/^(100([\.,]0{1,2})?|[0-9]{1,2}([\.,]\d{1,2})?)$/', $_POST['taux_tva_produit'])) {
        $errors[] = "La TVA doit être un nombre entre 0 et 100 (ex : 20 ou 20,50).";
    }

    if (!preg_match('/^\d+$/', $stock)) {
        $errors[] = "Le stock doit être un nombre entier positif.";
    }

    if (empty($categorie)) {
        $errors[] = "Veuillez sélectionner une catégorie.";
    }

    if (!isset($_FILES['nouvelle_image']) || empty($_FILES['nouvelle_image']['name'][0])) {
        $errors[] = "Veuillez ajouter au moins une image pour le produit.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO produit (
            nom_produit, 
            description_produit, 
            prix_unitaire_ht, 
            taux_tva, 
            stock_disponible,
            est_actif,
            categorie
        ) VALUES (
            :nom,
            :description_prod,
            :prix_ht,
            :tva,
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

        $id_produit = $pdo->lastInsertId();

        $upload_dir = 'front_end/assets/images_produits/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['nouvelle_image']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['nouvelle_image']['error'][$index] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['nouvelle_image']['name'][$index]);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($fileExt, $allowed)) {
                    $errors[] = "Le format de l'image $fileName n'est pas autorisé.";
                    continue;
                }

                $newFileName = uniqid('prod_', true) . '.' . $fileExt;
                $destPath = $upload_dir . $newFileName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $stmtImg = $pdo->prepare("INSERT INTO media_produit (id_produit, chemin_image) VALUES (?, ?)");
                    $stmtImg->execute([$id_produit, $destPath]);
                } else {
                    $errors[] = "Erreur lors du transfert de l'image $fileName.";
                }
            }
        }


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
                        readonly>

                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value=""
                        pattern="^\d+([,]\d{1,2})?$" title="Uniquement chiffres et virgule (ex : 10,50)">

                    <h4>TVA (%)</h4>
                    <input type="text" id="taux_tva_produit" name="taux_tva_produit"
                        value=""
                        pattern="^(100([\.,]0{1,2})?|[0-9]{1,2}([\.,]\d{1,2})?)$"
                        title="Uniquement un nombre entre 0 et 100 (ex : 20 ou 5,5)">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const prixHT = document.getElementById('prix_unitaire_ht_produit');
    const tauxTVA = document.getElementById('taux_tva_produit');
    const prixTTC = document.getElementById('prix_ttc_produit');

    prixTTC.disabled = true;
    prixTTC.style.backgroundColor = '#e9ecef';
    prixTTC.style.cursor = 'not-allowed';

    function calculerPrixTTC() {
        let ht = parseFloat(prixHT.value.replace(',', '.').replace(/\s/g, ''));
        let tva = parseFloat(tauxTVA.value.replace(',', '.').replace(/\s/g, ''));

        if (!isNaN(ht) && !isNaN(tva) && ht >= 0 && tva >= 0 && tva <= 100) {
            // Diviser par 100 car c'est maintenant un pourcentage
            let ttc = ht * (1 + tva / 100);
            
            prixTTC.value = ttc.toFixed(2).replace('.', ',');
        } else {
            prixTTC.value = '';
        }
    }

    calculerPrixTTC();

    prixHT.addEventListener('input', calculerPrixTTC);
    tauxTVA.addEventListener('input', calculerPrixTTC);
});
</script>
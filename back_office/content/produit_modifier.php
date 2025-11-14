<?php
if (isset($_POST['delete_image'])) {
    $id_media = $_POST['delete_image'];

    $stmt = $pdo->prepare("SELECT chemin_image FROM media_produit WHERE id_media = ?");
    $stmt->execute([$id_media]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($media && file_exists($media['chemin_image'])) {
        unlink($media['chemin_image']);
    }

    $deleteStmt = $pdo->prepare("DELETE FROM media_produit WHERE id_media = ?");
    $deleteStmt->execute([$id_media]);
}


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

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE produit 
        SET nom_produit = :nom, 
            description_produit = :description_prod, 
            prix_unitaire_ht = :prix_ht, 
            taux_tva = :tva, 
            stock_disponible = :stock, 
            est_actif = :actif, 
            categorie = :categorie 
        WHERE id_produit = :id_produit");

        $stmt->execute([
            'id_produit' => $id,
            'nom' => $nom,
            'description_prod' => $description,
            'categorie' => $categorie,
            'prix_ht' => $prix_ht,
            'tva' => $tva,
            'stock' => $stock,
            'actif' => $actif
        ]);








        echo "<script>
            window.location.href = 'index.php?page=produit&id=$id&type=consulter';
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
        <h2>Modifier produit</h2>

        <div class="produit-content">
            <!-- Partie gauche : informations générales -->
            <div class="produit-infos">
                <h3>Informations générales</h3>
                <article>
                    <h3>Nom du produit</h3>
                    <input type="text" id="nom_produit" name="nom_produit"
                        value="<?php echo htmlentities($produit['nom_produit']) ?>">
                </article>
                <article>
                    <h3>Description</h3>
                    <input type="text" id="description_produit" name="description_produit"
                        value="<?php echo htmlentities($produit['description_produit']) ?>">
                </article>
                <article>
                    <h3>Catégorie</h3>

                    <select name="categorie" id="select-categorie">
                        <option value="<?php echo htmlentities($categorie) ?>"><?php echo htmlentities($categorie) ?>
                        </option>
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
                    <h3>Média (3 images maximum)</h3>

                    <div class="media-gallery" id="mediaGallery">
                        <?php if (!empty($images)) {
                            foreach ($images as $image) { ?>
                                <div class="media-item existing">
                                    <img src="<?php echo htmlentities($image['chemin_image']); ?>" alt="Image du produit"
                                        class="produit-image">
                                    <button type="button" class="delete-btn"
                                        onclick="deleteImage(<?php echo $image['id_media']; ?>)">Supprimer</button>
                                </div>
                            <?php }
                        } else { ?>
                            <p>Aucune image disponible pour ce produit.</p>
                        <?php } ?>
                    </div>
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

                    <h4>Prix TTC (calculé automatiquement)</h4>
                    <input type="text" id="prix_ttc_produit" name="prix_ttc_produit"
                        value="<?php echo htmlentities(number_format($produit['prix_ttc'], 2, ',', ' ')) ?>" readonly>

                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value="<?php echo htmlentities(number_format($produit['prix_unitaire_ht'], 2, ',', ' ')) ?>"
                        pattern="^\d+([,]\d{1,2})?$" title="Uniquement chiffres et virgule (ex : 10,50)">

                    <h4>TVA (%)</h4>
                    <input type="text" id="taux_tva_produit" name="taux_tva_produit"
                        value="<?php echo htmlentities(number_format($produit['taux_tva'], 2, ',', '')) ?>"
                        pattern="^(100([\.,]0{1,2})?|[0-9]{1,2}([\.,]\d{1,2})?)$"
                        title="Uniquement un nombre entre 0 et 100 (ex : 20 ou 5,5)">
                </article>

                <article>
                    <h3>Stock</h3>
                    <input type="text" id="stock_disponible_produit" name="stock_disponible_produit"
                        value="<?php echo htmlentities($produit['stock_disponible']) ?>" pattern="^\d+$"
                        title="Uniquement chiffres entiers">
                </article>

                <article>
                    <h3>Visibilité</h3>

                    <div class="visibility-option">
                        <input type="radio" id="visible" name="visibilite" value="1" <?php echo $produit['est_actif'] ? 'checked' : '' ?>>
                        <label for="visible">Visible</label>
                    </div>

                    <div class="visibility-option">
                        <input type="radio" id="cache" name="visibilite" value="0" <?php echo !$produit['est_actif'] ? 'checked' : '' ?>>
                        <label for="cache">Caché</label>
                    </div>
                </article>
            </div>
        </div>


        <div class="produit-actions">
            <input type="submit" name="confirmer" class="confirmer" value="Confirmer">
            <a href="index.php?page=produit&id=<?php echo htmlentities($id) ?>&type=consulter"
                class="annuler">Annuler</a>
        </div>
    </form>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
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

    document.getElementById("nouvelle_image").addEventListener("change", function (e) {
        const gallery = document.getElementById("mediaGallery");
        document.querySelectorAll(".media-item.preview").forEach(p => p.remove());

        Array.from(e.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function (event) {
                const div = document.createElement("div");
                div.classList.add("media-item", "preview");

                const img = document.createElement("img");
                img.src = event.target.result;
                img.classList.add("produit-image");

                const label = document.createElement("span");
                label.innerText = "Preview";
                label.classList.add("preview-label");

                div.appendChild(img);
                div.appendChild(label);
                gallery.appendChild(div);
            };

            reader.readAsDataURL(file);
        });
    });

    function deleteImage(id) {
        if (!confirm("Supprimer cette image ?")) return;

        fetch("delete_image.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "delete_image=" + id
        })
            .then(() => location.reload());
    }
</script>
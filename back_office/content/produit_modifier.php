<section class="produit-container">
    <form action="" method="POST">
        <h2>Fiche produit</h2>

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
                    <input type="text" id="categorie_produit" name="categorie_produit"
                        value="<?php echo htmlentities($categorie) ?>">
                </article>
                <article>
                    <h3>Média</h3>

                    <div class="media-gallery">
                        <?php
                        $imagesPath = "front_end/assets/images_produits/" . $produit['id_produit'];
                        if (is_dir($imagesPath)) {
                            $images = glob("$imagesPath/*.{jpg,jpeg,png,webp}", GLOB_BRACE);
                            foreach ($images as $img) {
                                echo "<img src='$img' alt='Image produit'>";
                            }
                        } else {
                            echo "<p>Aucune image pour ce produit.</p>";
                        }
                        ?>
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

                    <h4>Prix TTC</h4>
                    <input type="text" id="prix_ttc_produit" name="prix_ttc_produit"
                        value="<?php echo htmlentities(number_format($produit['prix_ttc'], 2, ',', ' ')) ?>€">

                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value="<?php echo htmlentities(number_format($produit['prix_unitaire_ht'], 2, ',', ' ')) ?>€">


                    <h4>TVA</h4>
                    <input type="text" id="taux_tva_produit" name="taux_tva_produit"
                        value="<?php echo htmlentities($produit['taux_tva'] * 100) ?>%">

                    <p></p>
                </article>

                <article>
                    <h3>Stock</h3>
                    <input type="text" id="stock_disponible_produit" name="stock_disponible_produit"
                        value="<?php echo htmlentities($produit['stock_disponible']) ?>">
                </article>

                <article>
                    <h3>Visibilité</h3>
                    <div class="visibility-option">
                        <input type="radio" id="visible" name="visibilite" <?php echo htmlentities($produit['est_actif']) ? 'checked' : '' ?>>
                        <label for="visible">Visible</label>
                    </div>

                    <div class="visibility-option">
                        <input type="radio" id="cache" name="visibilite" <?php echo htmlentities(!$produit['est_actif']) ? 'checked' : '' ?>>
                        <label for="cache">Caché</label>
                    </div>
                </article>
            </div>
        </div>


        <div class="produit-actions">
            <button class="confirmer">Confirmer</button>
            <a href="index.php?page=produit&id=<?php echo htmlentities($id) ?>&type=consulter" class="annuler">Annuler</a>
        </div>
    </form>
</section>
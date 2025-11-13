<?php 
    $stmt = $pdo->prepare("SELECT * FROM media_produit WHERE id_produit = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="produit-container">
    <h2>Fiche produit</h2>

    <div class="produit-content">
        <!-- Partie gauche : informations générales -->
        <div class="produit-infos">
            <h3>Informations générales</h3>
            <article>
                <h3>Nom du produit</h3>
                <p><?php echo htmlentities($produit['nom_produit']) ?></p>
            </article>
            <article>
                <h3>Description</h3>
                <p><?php echo htmlentities($produit['description_produit']) ?></p>
            </article>
            <article>
                <h3>Catégorie</h3>
                <p><?php echo htmlentities($categorie) ?></p>
            </article>
            <article>
                <h3>Média</h3>
                <?php if (!empty($images)){ ?>
                    <div class="produit-images">
                        <?php foreach ($images as $image){ ?>
                            <img src="<?php echo htmlentities($image['chemin_image']); ?>" alt="Image du produit" class="produit-image">
                        <?php } ?>
                    </div>
                <?php } else{ ?>
                    <p>Aucune image disponible pour ce produit.</p>
                <?php } ?>
            </article>
        </div>

        <!-- Partie droite : prix, stock, TVA -->
        <div class="produit-prix-stock">
            <article>
                <h3>Prix</h3>

                <h4>Prix TTC</h4>
                <p><?php echo htmlentities(number_format($produit['prix_ttc'], 2, ',', ' ')) ?> €</p>

                <h4>Prix HT</h4>
                <p><?php echo htmlentities(number_format($produit['prix_unitaire_ht'], 2, ',', ' ')) ?> €</p>

                <h4>TVA</h4>
                <p><?php echo htmlentities($produit['taux_tva']) ?>%</p>
            </article>

            <article>
                <h3>Stock</h3>
                <p><?php echo htmlentities($produit['stock_disponible']) ?></p>
            </article>

            <article>
                <h3>Visibilité</h3>
                <div class="visibility-option">
                    <input type="radio" id="visible" name="visibilite" <?php echo htmlentities($produit['est_actif']) ? 'checked' : '' ?> disabled>
                    <label for="visible">Visible</label>
                </div>

                <div class="visibility-option">
                    <input type="radio" id="cache" name="visibilite" <?php echo htmlentities(!$produit['est_actif']) ? 'checked' : '' ?> disabled>
                    <label for="cache">Caché</label>
                </div>
            </article>
        </div>
    </div>


    <div class="produit-actions">
        <a href="index.php?page=produit&id=<?php echo htmlentities($id) ?>&type=modifier" class="modifier">Modifier</a>
        <button class="supprimer">Supprimer</button>
    </div>
</section>
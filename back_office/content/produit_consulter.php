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
                <a href="?page=avis&id=<?= $produit['id_produit'] ?>">Voir les avis →</a>
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

                <?php if ($remise_active): ?>
                    <!-- Affichage avec remise active -->
                    <div class="prix-avec-remise">
                        <div class="remise-badge-consultation">
                            <?php if ($remise_active['type_remise'] === 'pourcentage'): ?>
                                -<?= number_format($remise_active['valeur_remise'], 0) ?>%
                            <?php else: ?>
                                -<?= number_format($remise_active['valeur_remise'], 2, ',', ' ') ?>€
                            <?php endif; ?>
                        </div>
                        <p class="remise-nom-consultation"><?= htmlentities($remise_active['nom_remise']) ?></p>
                    </div>

                    <h4>Prix TTC avec remise</h4>
                    <p class="prix-final-consultation"><?php echo number_format($prix_final, 2, ',', ' ') ?> €</p>
                    <p class="prix-original-consultation"><?php echo number_format($produit['prix_ttc'], 2, ',', ' ') ?> €</p>

                    <h4>Prix HT avec remise</h4>
                    <p class="prix-final-consultation"><?php echo number_format($prix_ht_final, 2, ',', ' ') ?> €</p>
                    <p class="prix-original-consultation"><?php echo number_format($produit['prix_unitaire_ht'], 2, ',', ' ') ?> €</p>

                    <?php if ($produit['poids_unite'] && $produit['unite_mesure']): ?>
                        <h4>Prix au <?= htmlentities($produit['unite_mesure']) ?> avec remise</h4>
                        <p class="prix-final-consultation"><?php echo number_format($prix_par_unite_final, 2, ',', ' ') ?> €/<?= htmlentities($produit['unite_mesure']) ?></p>
                        <p class="prix-original-consultation"><?php echo number_format($produit['prix_ttc_par_unite'], 2, ',', ' ') ?> €/<?= htmlentities($produit['unite_mesure']) ?></p>
                        <p style="font-size: 0.85em; color: #666; margin-top: 5px;">
                            (<?= htmlentities($produit['poids_unite']) ?> <?= htmlentities($produit['unite_mesure']) ?> par unité)
                        </p>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Affichage sans remise -->
                    <h4>Prix TTC</h4>
                    <p><?php echo number_format($produit['prix_ttc'], 2, ',', ' ') ?> €</p>

                    <h4>Prix HT</h4>
                    <p><?php echo number_format($produit['prix_unitaire_ht'], 2, ',', ' ') ?> €</p>

                    <?php if ($produit['poids_unite'] && $produit['unite_mesure']): ?>
                        <h4>Prix au <?= htmlentities($produit['unite_mesure']) ?></h4>
                        <p><?php echo number_format($produit['prix_ttc_par_unite'], 2, ',', ' ') ?> €/<?= htmlentities($produit['unite_mesure']) ?></p>
                        <p style="font-size: 0.85em; color: #666; margin-top: 5px;">
                            (<?= htmlentities($produit['poids_unite']) ?> <?= htmlentities($produit['unite_mesure']) ?> par unité)
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <h4>TVA</h4>
                <p><?php echo htmlentities($produit['taux_tva']) ?>%</p>
            </article>

            <?php if ($remise_active): ?>
                <article class="remise-info-consultation">
                    <h3>Remise active</h3>
                    <div class="remise-details">
                        <p><strong>Nom :</strong> <?= htmlentities($remise_active['nom_remise']) ?></p>
                        <p><strong>Valeur :</strong> 
                            <?php if ($remise_active['type_remise'] === 'pourcentage'): ?>
                                -<?= number_format($remise_active['valeur_remise'], 0) ?>%
                            <?php else: ?>
                                -<?= number_format($remise_active['valeur_remise'], 2, ',', ' ') ?>€
                            <?php endif; ?>
                        </p>
                        <p><strong>Période :</strong> 
                            <?php 
                            $debut = new DateTime($remise_active['date_debut']);
                            $fin = new DateTime($remise_active['date_fin']);
                            echo $debut->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
                            ?>
                        </p>
                        <a href="?page=remise&type=consulter&id=<?= $remise_active['id_remise'] ?>" class="voir-remise-link">
                            Voir la remise →
                        </a>
                    </div>
                </article>
            <?php endif; ?>

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
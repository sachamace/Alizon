<?php
include 'config.php';

// Récupérer l'ID du vendeur depuis la session
$id_vendeur_connecte = $_SESSION['vendeur_id'];

// Récupérer UNIQUEMENT les produits avec des remises actives
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id_produit, p.nom_produit, p.description_produit, 
               p.prix_unitaire_ht,
               ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc,
               p.stock_disponible, p.est_actif, p.seuil_alerte, p.categorie,
               (SELECT chemin_image FROM media_produit WHERE id_produit = p.id_produit LIMIT 1) AS image_path,
               r.id_remise, r.nom_remise, r.type_remise, r.valeur_remise
        FROM public.produit p
        LEFT JOIN public.taux_tva t ON p.id_taux_tva = t.id_taux_tva
        INNER JOIN public.remise r ON (
            (r.id_produit = p.id_produit OR r.id_produit IS NULL)
            AND r.id_vendeur = p.id_vendeur
            AND r.est_actif = true
            AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
        )
        WHERE p.id_vendeur = ?
        ORDER BY r.valeur_remise DESC, p.nom_produit
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $produits_promo = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des produits en promotion : " . $e->getMessage());
}
?>

<div class="produits-promo-header">
    <div>
        <h2>Produits en remise</h2>
        <p class="promo-count"><?= count($produits_promo) ?> produit<?= count($produits_promo) > 1 ? 's' : '' ?> en promotion actuellement</p>
    </div>
    <a href="?page=remise" class="btn-retour-hub">← Retour au menu</a>
</div>

<?php if (empty($produits_promo)): ?>
    <div class="aucun-produit-promo">
        <h3>Aucun produit en promotion</h3>
        <p>Vous n'avez actuellement aucune remise active sur vos produits.</p>
        <div class="empty-actions">
            <a href="?page=remise&type=creer" class="btn-creer-remise">
                Créer une remise
            </a>
            <a href="?page=remise&type=liste" class="btn-consulter-remises">
                Voir mes remises
            </a>
        </div>
    </div>
<?php else: ?>
    <section class="content">
        <?php foreach ($produits_promo as $produit):
            // Déterminer la classe CSS en fonction du statut et du stock
            $class_article = '';
            $statut_text = '';

            if (!$produit['est_actif']) {
                $class_article = 'inactif';
                $statut_text = 'Inactif';
            } elseif ($produit['stock_disponible'] <= 0) {
                $class_article = 'rupture-stock';
                $statut_text = 'Rupture de stock';
            } elseif ($produit['stock_disponible'] <= $produit['seuil_alerte']) {
                $class_article = 'stock-faible';
                $statut_text = 'Stock faible';
            } else {
                $class_article = 'stock-normal';
                $statut_text = 'Actif';
            }

            // Calculer le prix avec remise
            $prix_final = $produit['prix_ttc'];
            
            if ($produit['type_remise'] === 'pourcentage') {
                $prix_final = $produit['prix_ttc'] * (1 - $produit['valeur_remise'] / 100);
            } else {
                $prix_final = $produit['prix_ttc'] - $produit['valeur_remise'];
            }
            if ($prix_final < 0) $prix_final = 0;
            ?>
                <a href="?page=produit&id=<?= $produit['id_produit'] ?>&type=consulter">
                    <article class="<?= $class_article ?> has-remise">
                        <div class="statut-badge"><?= $statut_text ?></div>
                        
                        <div class="remise-badge">
                            <?php if ($produit['type_remise'] === 'pourcentage'): ?>
                                -<?= number_format($produit['valeur_remise'], 0) ?>%
                            <?php else: ?>
                                -<?= number_format($produit['valeur_remise'], 2, ',', ' ') ?>€
                            <?php endif; ?>
                        </div>
                        
                        <img src="<?= $produit['image_path'] ? htmlentities($produit['image_path']) : 'front_end/assets/images/template.jpg' ?>" 
                            alt="<?= htmlentities($produit['nom_produit']) ?>" 
                            width="350" height="225">
                        <h2 class="titre"><?= htmlentities($produit['nom_produit']) ?></h2>
                        <p class="description"><?= htmlentities($produit['description_produit']) ?></p>
                        <p class="description">Catégorie : <?= htmlentities($produit['categorie']) ?></p>
                        <p class="stock <?= $class_article ?>">
                            Stock : <?= $produit['stock_disponible'] ?> 
                            <?php if ($produit['stock_disponible'] <= $produit['seuil_alerte'] && $produit['stock_disponible'] > 0): ?>
                                <span class="alerte">(Seuil: <?= $produit['seuil_alerte'] ?>)</span>
                            <?php endif; ?>
                        </p>
                        
                        <div class="prix-container">
                            <p class="prix prix-original"><?= number_format($produit['prix_ttc'], 2, ',', ' ') ?>€</p>
                            <p class="prix prix-remise"><?= number_format($prix_final, 2, ',', ' ') ?>€</p>
                        </div>
                        <p class="remise-nom"><?= htmlentities($produit['nom_remise']) ?></p>
                    </article>
                </a>
            <?php endforeach; ?>
    </section>
<?php endif; ?>
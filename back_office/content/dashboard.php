<?php
include 'config.php';

// Récupérer l'ID du vendeur depuis la session
$id_vendeur_connecte = $_SESSION['vendeur_id'];

// Récupérer les informations du vendeur
try {
    $stmt_vendeur = $pdo->prepare("SELECT raison_sociale FROM public.compte_vendeur WHERE id_vendeur = ?");
    $stmt_vendeur->execute([$id_vendeur_connecte]);
    $info_vendeur = $stmt_vendeur->fetch();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des infos vendeur : " . $e->getMessage());
}

// Récupérer tous les produits du vendeur connecté avec les remises actives
try {
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.description_produit, 
               p.prix_unitaire_ht,
               ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc,
               p.stock_disponible, p.est_actif, p.seuil_alerte, p.categorie,
               (SELECT chemin_image FROM media_produit WHERE id_produit = p.id_produit LIMIT 1) AS image_path,
               -- Remise spécifique au produit
               r.id_remise, r.nom_remise, r.type_remise, r.valeur_remise, r.code_promo,
               r.categorie as remise_categorie
        FROM public.produit p
        LEFT JOIN public.taux_tva t ON p.id_taux_tva = t.id_taux_tva
        LEFT JOIN public.remise r ON (
            r.id_vendeur = p.id_vendeur
            AND r.est_actif = true
            AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
            AND (
                -- Cas 1: Remise sur CE produit spécifique (via id_produit)
                r.id_produit = p.id_produit
                -- Cas 2: Remise sur CE produit spécifique (via table remise_produit)
                OR EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise AND rp.id_produit = p.id_produit)
                -- Cas 3: Remise sur TOUS les produits (pas de produit spécifique, pas de catégorie)
                OR (r.id_produit IS NULL AND r.categorie IS NULL AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                -- Cas 4: Remise sur CATÉGORIE spécifique (pas de produit spécifique, catégorie correspond)
                OR (r.id_produit IS NULL AND r.categorie = p.categorie AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
            )
        )
        WHERE p.id_vendeur = ?
        ORDER BY p.est_actif DESC, p.id_produit
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des produits : " . $e->getMessage());
}
?>

<!-- Section de tous les produits -->
<section class="content">
   
    
    <a href="?page=produit&type=creer">
        <article class="creer_produit">
            <p>Créer un produit</p>
            <p>+</p>
        </article>
    </a>

    <?php foreach ($produits as $produit):
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

        // Calculer le prix avec remise si une remise est active
        $prix_final = $produit['prix_ttc'];
        $a_une_remise = false;
        
        if ($produit['id_remise']) {
            $a_une_remise = true;
            if ($produit['type_remise'] === 'pourcentage') {
                $prix_final = $produit['prix_ttc'] * (1 - $produit['valeur_remise'] / 100);
            } else {
                $prix_final = $produit['prix_ttc'] - $produit['valeur_remise'];
            }
            // S'assurer que le prix ne soit pas négatif
            if ($prix_final < 0) $prix_final = 0;
        }
        ?>
            <a href="?page=produit&id=<?= $produit['id_produit'] ?>&type=consulter">
                <article class="<?= $class_article ?> <?= $a_une_remise ? 'has-remise' : '' ?>">
                    <div class="statut-badge"><?= $statut_text ?></div>
                    
                    <?php if ($a_une_remise): ?>
                        <div class="remise-badge">
                            <?php if ($produit['type_remise'] === 'pourcentage'): ?>
                                -<?= number_format($produit['valeur_remise'], 0) ?>%
                            <?php else: ?>
                                -<?= number_format($produit['valeur_remise'], 2, ',', ' ') ?>€
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
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
                    
                    <?php if ($a_une_remise): ?>
                        <div class="prix-container">
                            <p class="prix prix-original"><?= number_format($produit['prix_ttc'], 2, ',', ' ') ?>€</p>
                            <p class="prix prix-remise"><?= number_format($prix_final, 2, ',', ' ') ?>€</p>
                        </div>
                        <?php if ($produit['nom_remise']): ?>
                            <p class="remise-nom"><?= htmlentities($produit['nom_remise']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="prix"><?= number_format($produit['prix_ttc'], 2, ',', ' ') ?>€</p>
                    <?php endif; ?>
                </article>
            </a>
        <?php endforeach; ?>
</section>
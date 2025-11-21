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

// Récupérer les produits du vendeur connecté
try {
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.description_produit, p.prix_ttc, 
               p.stock_disponible, p.est_actif, p.seuil_alerte, p.categorie,
               (SELECT chemin_image FROM media_produit WHERE id_produit = p.id_produit LIMIT 1) AS image_path
        FROM public.produit p
        WHERE p.id_vendeur = ?
        ORDER BY p.est_actif DESC, p.id_produit
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des produits : " . $e->getMessage());
}
?>

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
        ?>
            <a href="?page=produit&id=<?= $produit['id_produit'] ?>&type=consulter">
                <article class="<?= $class_article ?>">
                    <div class="statut-badge"><?= $statut_text ?></div>
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
                    <p class="prix"><?= number_format($produit['prix_ttc'], 2, ',', ' ') ?>€</p>
                </article>
            </a>
        <?php endforeach; ?>
</section>
<?php
// Récupérer toutes les promotions du vendeur
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               -- Calcul du statut
               CASE 
                   WHEN p.date_fin < CURRENT_DATE THEN 'Expirée'
                   WHEN p.date_debut > CURRENT_DATE THEN 'À venir'
                   WHEN p.est_actif = false THEN 'Inactive'
                   ELSE 'Active'
               END as statut_calcule,
               -- Nombre de produits associés
               COUNT(DISTINCT pp.id_produit) as nb_produits
        FROM promotion p
        LEFT JOIN promotion_produit pp ON p.id_promotion = pp.id_promotion
        WHERE p.id_vendeur = ?
        GROUP BY p.id_promotion
        ORDER BY p.ordre_affichage, p.date_debut DESC
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $promotions = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des promotions : " . $e->getMessage());
}
?>

<div class="promotion-liste-header">
    <h2>Liste des promotions</h2>
    <a href="?page=promotion" class="btn-retour">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
        </svg>
        Retour au menu
    </a>
</div>

<section class="content">
    <?php if (!empty($promotions)): ?>
        <?php foreach ($promotions as $promotion): 
            // Déterminer la classe CSS en fonction du statut
            $class_article = '';
            switch($promotion['statut_calcule']) {
                case 'Active':
                    $class_article = 'promotion-active';
                    break;
                case 'Inactive':
                    $class_article = 'promotion-inactive';
                    break;
                case 'Expirée':
                    $class_article = 'promotion-expiree';
                    break;
                case 'À venir':
                    $class_article = 'promotion-a-venir';
                    break;
            }
        ?>
            <a href="?page=promotion&type=consulter&id=<?= $promotion['id_promotion'] ?>" class="promotion-card-wrapper">
                <article class="promotion-article <?= $class_article ?>">
                    <div class="statut-badge"><?= htmlentities($promotion['statut_calcule']) ?></div>
                    
                    <div class="promotion-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8.5 1a.5.5 0 0 0-1 0v1.07A7.001 7.001 0 0 0 8 16a7 7 0 0 0 .5-13.93V1zm-1 2.157V5.5a.5.5 0 0 0 1 0V3.157A6.002 6.002 0 0 1 13.657 8H11.5a.5.5 0 0 0 0 1h2.157A6.002 6.002 0 0 1 8.5 13.843V11.5a.5.5 0 0 0-1 0v2.343A6.002 6.002 0 0 1 2.343 9H4.5a.5.5 0 0 0 0-1H2.343A6.002 6.002 0 0 1 7.5 3.157z"/>
                        </svg>
                    </div>
                    
                    <h3 class="promotion-nom">
                        <?= htmlentities($promotion['nom_promotion']) ?>
                    </h3>
                    
                    <?php if ($promotion['description']): ?>
                        <p class="promotion-description">
                            <?= htmlentities(mb_substr($promotion['description'], 0, 100)) ?>
                            <?= mb_strlen($promotion['description']) > 100 ? '...' : '' ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="promotion-produits-count">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                        </svg>
                        <strong><?= $promotion['nb_produits'] ?></strong> produit<?= $promotion['nb_produits'] > 1 ? 's' : '' ?> mis en avant
                    </div>
                    
                    <div class="promotion-dates">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M9.5 7h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5m3 0h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5M2 10.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5"/>
                            </svg>
                            <?= date('d/m/Y', strtotime($promotion['date_debut'])) ?>
                        </div>
                        <span>→</span>
                        <div>
                            <?= date('d/m/Y', strtotime($promotion['date_fin'])) ?>
                        </div>
                    </div>
                    
                    <div class="promotion-ordre">
                        Ordre d'affichage: <strong><?= $promotion['ordre_affichage'] ?></strong>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <article class="aucune-promotion">
            <h3>Aucune promotion créée</h3>
            <p>Vous n'avez pas encore créé de promotion. Les promotions permettent de mettre en avant certains produits sur votre boutique.</p>
        </article>
    <?php endif; ?>
</section>
<?php
// Récupérer toutes les remises du vendeur avec informations sur le type d'application
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               p.nom_produit,
               -- Calcul du statut
               CASE 
                   WHEN r.date_fin < CURRENT_DATE THEN 'Expirée'
                   WHEN r.date_debut > CURRENT_DATE THEN 'À venir'
                   WHEN r.est_actif = false THEN 'Inactive'
                   ELSE 'Active'
               END as statut_calcule,
               -- Information sur le type d'application
               CASE 
                   WHEN r.id_produit IS NOT NULL THEN 'Produit spécifique'
                   WHEN EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise) THEN 'Produits multiples'
                   WHEN r.categorie IS NOT NULL THEN 'Catégorie: ' || r.categorie
                   ELSE 'Tous les produits'
               END as type_application,
               -- Nombre de produits associés (si produits multiples)
               COALESCE(
                   (SELECT COUNT(*) FROM remise_produit rp WHERE rp.id_remise = r.id_remise),
                   0
               ) as nb_produits_associes
        FROM remise r
        LEFT JOIN produit p ON r.id_produit = p.id_produit
        WHERE r.id_vendeur = ?
        ORDER BY r.date_debut DESC, r.id_remise DESC
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $remises = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des remises : " . $e->getMessage());
}
?>

<div class="remise-liste-header">
    <h2>Liste des remises</h2>
    <a href="?page=remise" class="btn-retour">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
        </svg>
        Retour au menu
    </a>
</div>

<section class="content">
    <?php if (!empty($remises)): ?>
        <?php foreach ($remises as $remise): 
            // Déterminer la classe CSS en fonction du statut
            $class_article = '';
            switch($remise['statut_calcule']) {
                case 'Active':
                    $class_article = 'remise-active';
                    break;
                case 'Inactive':
                    $class_article = 'remise-inactive';
                    break;
                case 'Expirée':
                    $class_article = 'remise-expiree';
                    break;
                case 'À venir':
                    $class_article = 'remise-a-venir';
                    break;
            }
        ?>
            <a href="?page=remise&type=consulter&id=<?= $remise['id_remise'] ?>" class="remise-card-wrapper">
                <article class="remise-article <?= $class_article ?>">
                    <div class="statut-badge"><?= htmlentities($remise['statut_calcule']) ?></div>
                    
                    <div class="remise-valeur">
                        <?php if ($remise['type_remise'] === 'pourcentage'): ?>
                            -<?= number_format($remise['valeur_remise'], 0) ?>%
                        <?php else: ?>
                            -<?= number_format($remise['valeur_remise'], 2, ',', ' ') ?>€
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="remise-nom">
                        <?= htmlentities($remise['nom_remise'] ?: 'Remise sans nom') ?>
                    </h3>
                    
                    <?php if ($remise['code_promo']): ?>
                        <div class="remise-code">
                            Code: <strong><?= htmlentities($remise['code_promo']) ?></strong>
                        </div>
                    <?php endif; ?>
                    
<<<<<<< HEAD
=======
                    <div class="remise-produit">
                        <?php if ($remise['nom_produit']): ?>
                            Sur: <?= htmlentities($remise['nom_produit']) ?>
                        <?php else: ?>
                            Sur: Tous les produits
                        <?php endif; ?>
                    </div>
                    
>>>>>>> aef5c3a (remise cote back office)
                    <div class="remise-dates">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M4 .5a.5.5 0 0 0-1 0V1H2a2 2 0 0 0-2 2v1h16V3a2 2 0 0 0-2-2h-1V.5a.5.5 0 0 0-1 0V1H4zM16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2M9.5 7h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5m3 0h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5M2 10.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3.5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5"/>
                            </svg>
                            <?= date('d/m/Y', strtotime($remise['date_debut'])) ?>
                        </div>
                        <span>→</span>
                        <div>
                            <?= date('d/m/Y', strtotime($remise['date_fin'])) ?>
                        </div>
                    </div>
                    
                    <?php if ($remise['condition_min_achat'] > 0): ?>
                        <div class="remise-condition">
                            Achat min: <?= number_format($remise['condition_min_achat'], 2, ',', ' ') ?>€
                        </div>
                    <?php endif; ?>
<<<<<<< HEAD
                    
                    <!-- Pour les remises avec produits multiples, afficher un badge -->
                    <?php if ($remise['nb_produits_associes'] > 0): ?>
                        <div class="remise-produits-count">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                            </svg>
                            <?= $remise['nb_produits_associes'] ?> produit(s)
                        </div>
                    <?php endif; ?>
                </article>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <article class="aucune-promotion">
            <h3>Aucune remise créée</h3>
            <p>Vous n'avez pas encore créé de remise.</p>
        </article>
    <?php endif; ?>
</section>
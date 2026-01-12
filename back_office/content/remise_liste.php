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
    <a href="?page=remise&type=creer">
        <article class="creer_remise">
            <p>Créer une remise</p>
            <p>+</p>
        </article>
    </a>

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
                    
                    <div class="remise-produit">
                        <?php if ($remise['nom_produit']): ?>
                            Sur: <?= htmlentities($remise['nom_produit']) ?>
                        <?php else: ?>
                            Sur: Tous les produits
                        <?php endif; ?>
                    </div>
                    
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
                </article>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
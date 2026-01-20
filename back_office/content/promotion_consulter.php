<?php
// La promotion et ses produits sont déjà récupérés dans promotion.php
// Variables disponibles: $promotion, $produits_promotion

// Calculer la durée de la promotion
$date_debut_obj = new DateTime($promotion['date_debut']);
$date_fin_obj = new DateTime($promotion['date_fin']);
$duree_jours = $date_debut_obj->diff($date_fin_obj)->days;

// Déterminer le statut
$aujourd_hui = new DateTime();
$statut = '';
$badge_class = '';

if ($promotion['est_actif']) {
    if ($aujourd_hui < $date_debut_obj) {
        $statut = 'À venir';
        $badge_class = 'badge-a-venir';
    } elseif ($aujourd_hui > $date_fin_obj) {
        $statut = 'Expirée';
        $badge_class = 'badge-expire';
    } else {
        $statut = 'Active';
        $badge_class = 'badge-actif';
    }
} else {
    $statut = 'Inactive';
    $badge_class = 'badge-inactif';
}
?>

<section class="promotion-detail-container">
    <div class="promotion-detail-header">
        <h2>Détails de la promotion</h2>
        <div class="header-actions">
            <a href="?page=promotion&type=modifier&id=<?= $promotion['id_promotion'] ?>" class="btn-modifier">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                </svg>
                Modifier
            </a>
            <a href="?page=promotion" class="btn-retour">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                Retour
            </a>
        </div>
    </div>

    <div class="promotion-detail-content">
        <!-- Colonne gauche -->
        <div class="promotion-detail-left">
            <!-- Informations générales -->
            <div class="detail-section">
                <h3>Informations générales</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Nom</span>
                    <span class="detail-value"><strong><?= htmlentities($promotion['nom_promotion']) ?></strong></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Statut</span>
                    <span class="detail-value">
                        <span class="badge <?= $badge_class ?>"><?= $statut ?></span>
                    </span>
                </div>
                
                <?php if (!empty($promotion['description'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value" style="text-align: left;">
                        <?= nl2br(htmlentities($promotion['description'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Période de validité -->
            <div class="detail-section">
                <h3>Période de validité</h3>
                
                <div class="periode-timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">Date de début</div>
                            <div class="timeline-date"><?= date('d/m/Y', strtotime($promotion['date_debut'])) ?></div>
                        </div>
                    </div>
                    
                    <div class="timeline-separator"></div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">Date de fin</div>
                            <div class="timeline-date"><?= date('d/m/Y', strtotime($promotion['date_fin'])) ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="duree-info">
                    <strong>Durée :</strong> <?= $duree_jours ?> jour<?= $duree_jours > 1 ? 's' : '' ?>
                </div>
            </div>
        </div>

        <!-- Colonne droite -->
        <div class="promotion-detail-right">
            <!-- Produits de la promotion -->
            <div class="detail-section">
                <h3>Produits mis en avant (<?= count($produits_promotion) ?>)</h3>
                
                <?php if (!empty($produits_promotion)): ?>
                    <div class="produits-promotion-list">
                        <?php foreach ($produits_promotion as $produit): ?>
                            <div class="produit-promotion-item">
                                <?php if (!empty($produit['image_path'])): ?>
                                    <img src="<?= htmlentities($produit['image_path']) ?>" 
                                         alt="<?= htmlentities($produit['nom_produit']) ?>">
                                <?php else: ?>
                                    <img src="/images/produit-default.png" 
                                         alt="Image par défaut">
                                <?php endif; ?>
                                
                                <div class="produit-promotion-info">
                                    <h4><?= htmlentities($produit['nom_produit']) ?></h4>
                                    <p class="categorie"><?= htmlentities($produit['categorie'] ?? 'Non catégorisé') ?></p>
                                    <p class="prix"><?= number_format($produit['prix_ttc'], 2, ',', ' ') ?> € TTC</p>
                                    
                                    <?php if (!$produit['est_actif']): ?>
                                        <span class="badge-inactif-small">Inactif</span>
                                    <?php elseif ($produit['stock_disponible'] <= 0): ?>
                                        <span class="badge-rupture-small">Rupture de stock</span>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="?page=produit&type=consulter&id=<?= $produit['id_produit'] ?>" 
                                   class="voir-produit" 
                                   title="Voir le produit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data">Aucun produit dans cette promotion</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="promotion-actions">
        <button onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette promotion ?')) window.location.href='?page=promotion&type=supprimer&id=<?= $promotion['id_promotion'] ?>'" 
                class="btn-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
            </svg>
            Supprimer cette promotion
        </button>
    </div>
</section>
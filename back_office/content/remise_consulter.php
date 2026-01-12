<section class="remise-detail-container">
    <div class="remise-detail-header">
        <h2>Détails de la remise</h2>
        <div class="header-actions">
            <a href="?page=remise&type=modifier&id=<?= $remise['id_remise'] ?>" class="btn-modifier">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                </svg>
                Modifier
            </a>
            <a href="?page=remise" class="btn-retour">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                Retour au menu
            </a>
        </div>
    </div>

    <div class="remise-detail-content">
        <div class="remise-detail-left">
            <article class="detail-section">
                <h3>Informations générales</h3>
                <div class="detail-row">
                    <span class="detail-label">Nom</span>
                    <span class="detail-value"><?= htmlentities($remise['nom_remise'] ?: 'Sans nom') ?></span>
                </div>
                
                <?php if ($remise['description']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Description</span>
                        <span class="detail-value"><?= htmlentities($remise['description']) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Statut</span>
                    <span class="detail-value">
                        <?php
                        $now = date('Y-m-d');
                        if ($remise['date_fin'] < $now) {
                            echo '<span class="badge badge-expire">Expirée</span>';
                        } elseif ($remise['date_debut'] > $now) {
                            echo '<span class="badge badge-a-venir">À venir</span>';
                        } elseif (!$remise['est_actif']) {
                            echo '<span class="badge badge-inactif">Inactive</span>';
                        } else {
                            echo '<span class="badge badge-actif">Active</span>';
                        }
                        ?>
                    </span>
                </div>
            </article>

            <article class="detail-section">
                <h3>Valeur de la remise</h3>
                <div class="remise-valeur-display">
                    <?php if ($remise['type_remise'] === 'pourcentage'): ?>
                        <div class="valeur-principale">-<?= number_format($remise['valeur_remise'], 0) ?>%</div>
                        <div class="valeur-type">Remise en pourcentage</div>
                    <?php else: ?>
                        <div class="valeur-principale">-<?= number_format($remise['valeur_remise'], 2, ',', ' ') ?>€</div>
                        <div class="valeur-type">Remise fixe</div>
                    <?php endif; ?>
                </div>
                
                <?php if ($remise['condition_min_achat'] > 0): ?>
                    <div class="detail-row">
                        <span class="detail-label">Montant minimum d'achat</span>
                        <span class="detail-value"><?= number_format($remise['condition_min_achat'], 2, ',', ' ') ?>€</span>
                    </div>
                <?php endif; ?>
            </article>

            <article class="detail-section">
                <h3>Application</h3>
                <div class="detail-row">
                    <span class="detail-label">Produit concerné</span>
                    <span class="detail-value">
                        <?php if ($remise['nom_produit']): ?>
                            <?= htmlentities($remise['nom_produit']) ?>
                        <?php else: ?>
                            <em>Tous les produits</em>
                        <?php endif; ?>
                    </span>
                </div>
            </article>
        </div>

        <div class="remise-detail-right">
            <article class="detail-section">
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
                            <div class="timeline-label">Début</div>
                            <div class="timeline-date">
                                <?php
                                $date_debut = new DateTime($remise['date_debut']);
                                echo $date_debut->format('d/m/Y');
                                ?>
                            </div>
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
                            <div class="timeline-label">Fin</div>
                            <div class="timeline-date">
                                <?php
                                $date_fin = new DateTime($remise['date_fin']);
                                echo $date_fin->format('d/m/Y');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="duree-info">
                    <?php
                    $interval = $date_debut->diff($date_fin);
                    $jours = $interval->days;
                    echo "Durée: <strong>$jours jour" . ($jours > 1 ? 's' : '') . "</strong>";
                    ?>
                </div>
            </article>
        </div>
    </div>

    <!-- Bouton de suppression en bas de la page -->
    <div class="remise-actions">
        <button class="btn-danger supprimer" onclick="confirmerSuppression(<?= $remise['id_remise'] ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
            </svg>
            Supprimer la remise
        </button>
    </div>
</section>

<script>
function confirmerSuppression(idRemise) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette remise ? Cette action est irréversible.')) {
        window.location.href = 'remise_supprimer.php?id=' + idRemise;
    }
}
</script>
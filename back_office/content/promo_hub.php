<?php
include 'config.php';

// Récupérer l'ID du vendeur depuis la session
$id_vendeur_connecte = $_SESSION['vendeur_id'];

// Compter les remises actives
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_remises,
           SUM(CASE WHEN est_actif = true AND CURRENT_DATE BETWEEN date_debut AND date_fin THEN 1 ELSE 0 END) as remises_actives
    FROM remise 
    WHERE id_vendeur = ?
");
$stmt->execute([$id_vendeur_connecte]);
$stats_remises = $stmt->fetch();

// Compter les produits en promotion
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.id_produit) as produits_en_promo
    FROM produit p
    INNER JOIN remise r ON (
        (r.id_produit = p.id_produit OR r.id_produit IS NULL)
        AND r.id_vendeur = p.id_vendeur
        AND r.est_actif = true
        AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
    )
    WHERE p.id_vendeur = ?
");
$stmt->execute([$id_vendeur_connecte]);
$stats_produits = $stmt->fetch();
?>

<section class="promo-hub-container">
    <h2>Gestion des Promotions et Remises</h2>
    <p class="hub-description">Créez et gérez vos remises, consultez les produits en promotion</p>

    <div class="promo-hub-grid">
        
        <!-- Carte : Créer une remise -->
        <a href="?page=remise&type=creer" class="hub-card hub-card-active">
            <h3>Créer une remise</h3>
            <p>Appliquez des réductions sur vos produits</p>
            <div class="hub-card-stats">
                <span class="stat-badge stat-active"><?= $stats_remises['remises_actives'] ?> active<?= $stats_remises['remises_actives'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="hub-card-action">Créer →</div>
        </a>

        <!-- Carte : Créer une promotion (DÉSACTIVÉ) -->
        <div class="hub-card hub-card-disabled" title="Fonctionnalité à venir">
            <h3>Créer une promotion</h3>
            <p>Organisez des campagnes promotionnelles</p>
            <div class="hub-card-badge">Bientôt disponible</div>
        </div>

        <!-- Carte : Consulter les remises -->
        <a href="?page=remise&type=liste" class="hub-card hub-card-active">
            <h3>Consulter les remises</h3>
            <p>Gérez toutes vos remises existantes</p>
            <div class="hub-card-stats">
                <span class="stat-badge"><?= $stats_remises['total_remises'] ?> remise<?= $stats_remises['total_remises'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="hub-card-action">Voir →</div>
        </a>

        <!-- Carte : Produits en remise -->
        <a href="?page=produits_promo" class="hub-card hub-card-active hub-card-highlight">
            <h3>Produits en remise</h3>
            <p>Consultez tous les produits avec réductions actives</p>
            <div class="hub-card-stats">
                <span class="stat-badge stat-promo"><?= $stats_produits['produits_en_promo'] ?> produit<?= $stats_produits['produits_en_promo'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="hub-card-action">Voir →</div>
        </a>

    </div>
</section>
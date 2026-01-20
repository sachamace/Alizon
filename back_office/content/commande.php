<?php
$id_vendeur = $_SESSION['vendeur_id'];

// Requête : récupérer les commandes contenant mes produits
$stmt = $pdo->prepare("
    SELECT 
        c.id_commande,
        c.date_commande,
        c.statut,
        cl.prenom,
        cl.nom,
        a.ville,
        p.id_produit,
        p.nom_produit,
        (SELECT mp.chemin_image FROM media_produit mp WHERE mp.id_produit = p.id_produit LIMIT 1) AS image_produit,
        lc.quantite,
        lc.prix_unitaire_ttc,
        ROUND(CAST(lc.prix_unitaire_ttc * lc.quantite AS NUMERIC), 2) AS total_ligne_ttc
    FROM commande c
    JOIN compte_client cl ON c.id_client = cl.id_client
    LEFT JOIN adresse a ON c.id_adresse_livraison = a.id_adresse
    JOIN ligne_commande lc ON c.id_commande = lc.id_commande
    JOIN produit p ON lc.id_produit = p.id_produit
    WHERE p.id_vendeur = ?
    ORDER BY c.date_commande DESC, c.id_commande
");
$stmt->execute([$id_vendeur]);

// Regrouper par commande
$commandes = [];
while ($row = $stmt->fetch()) {
    $id = $row['id_commande'];
    if (!isset($commandes[$id])) {
        $commandes[$id] = [
            'id_commande' => $id,
            'date_commande' => $row['date_commande'],
            'statut' => $row['statut'],
            'client' => $row['prenom'] . ' ' . strtoupper(substr($row['nom'], 0, 1)) . '.',
            'ville' => $row['ville'],
            'produits' => [],
            'total' => 0
        ];
    }
    $commandes[$id]['produits'][] = [
        'nom' => $row['nom_produit'],
        'image' => $row['image_produit'],
        'quantite' => $row['quantite'],
        'prix_ttc' => $row['prix_unitaire_ttc'],
        'total' => $row['total_ligne_ttc']
    ];
    $commandes[$id]['total'] += $row['total_ligne_ttc'];
}
?>

<div class="recapitulatif-container">
    <h2>Récapitulatif des Commandes</h2>
    
    <?php if (empty($commandes)): ?>
        <p class="no-data">Aucune commande pour le moment.</p>
    <?php else: ?>
        <div class="commandes-list">
            <?php foreach ($commandes as $cmd): ?>
                <div class="commande-card">
                    <div class="commande-header">
                        <div class="commande-info">
                            <span class="commande-id">Commande #<?= $cmd['id_commande'] ?></span>
                            <span class="commande-date"><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></span>
                        </div>
                        <span class="commande-statut statut-<?= $cmd['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $cmd['statut'])) ?></span>
                    </div>
                    
                    <div class="commande-client">
                        <span>👤 <?= htmlspecialchars($cmd['client']) ?></span>
                        <?php if ($cmd['ville']): ?>
                            <span>📍 <?= htmlspecialchars($cmd['ville']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="commande-produits">
                        <?php foreach ($cmd['produits'] as $prod): ?>
                            <div class="produit-ligne">
                                <?php if ($prod['image']): ?>
                                    <img src="<?= htmlspecialchars($prod['image']) ?>" alt="">
                                <?php else: ?>
                                    <div class="produit-img-placeholder">📦</div>
                                <?php endif; ?>
                                <div class="produit-info">
                                    <span class="produit-nom"><?= htmlspecialchars($prod['nom']) ?></span>
                                    <span class="produit-qte">Qté: <?= $prod['quantite'] ?></span>
                                </div>
                                <span class="produit-prix"><?= number_format($prod['total'], 2, ',', ' ') ?> €</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="commande-footer">
                        <span class="total-label">Total :</span>
                        <span class="total-value"><?= number_format($cmd['total'], 2, ',', ' ') ?> € TTC</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
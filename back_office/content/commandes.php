<?php
$id_vendeur = $_SESSION['vendeur_id'];

// Fonction pour communiquer avec le serveur C
function appeler_serveur_c($message) {
    $host = "10.253.5.108";
    $port = 8080;
    $login = "alizon";
    $hash_md5 = "e54d588ca06c9f379b36d0b616421376";
    $message_complet = "$login;$hash_md5;$message";

    $socket = @fsockopen($host, $port, $errno, $errstr, 2);
    if (!$socket) {
        return null;
    }

    fwrite($socket, $message_complet);
    $response = fgets($socket, 4096);
    fclose($socket);
    
    return $response;
}

// 1. Récupérer les id_commande où le vendeur a des produits (via ligne_commande + produit)
$stmt = $pdo->prepare("
    SELECT DISTINCT lc.id_commande
    FROM ligne_commande lc
    JOIN produit p ON lc.id_produit = p.id_produit
    WHERE p.id_vendeur = ?
");
$stmt->execute([$id_vendeur]);
$ids_commandes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$commandes = [];

// 2. Pour chaque commande, appeler le serveur C pour avoir les infos
foreach ($ids_commandes as $id_commande) {
    
    // Appel au serveur C : CHECK;id
    $reponse = appeler_serveur_c("CHECK;$id_commande");
    
    if (!$reponse || $reponse === "NOT_FOUND") continue;
    
    // Format retour: bordereau;statut;etape;date_maj;details_etape;priorite|
    $data = explode(';', rtrim($reponse, '|'));
    
    if (count($data) < 5) continue;
    
    $statut = $data[1];
    
    // On ne prend que les commandes avec un statut (payées/traitées)
    if (empty($statut)) continue;
    
    // 3. Récupérer les infos client
    $stmt_client = $pdo->prepare("
        SELECT cl.prenom, cl.nom, a.ville
        FROM ligne_commande lc
        JOIN commande c ON lc.id_commande = c.id_commande
        JOIN compte_client cl ON c.id_client = cl.id_client
        LEFT JOIN adresse a ON c.id_adresse_livraison = a.id_adresse
        WHERE lc.id_commande = ?
        LIMIT 1
    ");
    $stmt_client->execute([$id_commande]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);
    
    // 4. Récupérer les produits du vendeur dans cette commande
    $stmt_produits = $pdo->prepare("
        SELECT p.nom_produit,
               (SELECT mp.chemin_image FROM media_produit mp WHERE mp.id_produit = p.id_produit LIMIT 1) AS image_produit,
               lc.quantite,
               lc.prix_unitaire_ttc,
               ROUND(CAST(lc.prix_unitaire_ttc * lc.quantite AS NUMERIC), 2) AS total_ligne_ttc
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        WHERE lc.id_commande = ? AND p.id_vendeur = ?
    ");
    $stmt_produits->execute([$id_commande, $id_vendeur]);
    $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le total
    $total = 0;
    foreach ($produits as $prod) {
        $total += $prod['total_ligne_ttc'];
    }
    
    $commandes[] = [
        'id_commande' => $id_commande,
        'bordereau' => $data[0],
        'statut' => $data[1],
        'etape' => $data[2],
        'date_maj' => $data[3],
        'details_etape' => $data[4],
        'client' => $client ? $client['prenom'] . ' ' . strtoupper(substr($client['nom'], 0, 1)) . '.' : 'Client',
        'ville' => $client['ville'] ?? '',
        'produits' => $produits,
        'total' => $total
    ];
}

// Trier par date_maj décroissante
usort($commandes, function($a, $b) {
    return strtotime($b['date_maj']) - strtotime($a['date_maj']);
});
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
                            <?php if ($cmd['date_maj']): ?>
                                <span class="commande-date"><?= date('d/m/Y H:i', strtotime($cmd['date_maj'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="commande-statut statut-<?= strtolower(str_replace(' ', '_', $cmd['statut'])) ?>">
                            <?= htmlspecialchars($cmd['statut']) ?>
                        </span>
                    </div>
                    
                    <div class="commande-client">
                        <span>👤 <?= htmlspecialchars($cmd['client']) ?></span>
                        <?php if ($cmd['ville']): ?>
                            <span>📍 <?= htmlspecialchars($cmd['ville']) ?></span>
                        <?php endif; ?>
                        <?php if ($cmd['bordereau']): ?>
                            <span>🚚 <?= htmlspecialchars($cmd['bordereau']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($cmd['details_etape']): ?>
                        <div class="commande-etape">
                            <span>📦 Étape <?= $cmd['etape'] ?> : <?= htmlspecialchars($cmd['details_etape']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="commande-produits">
                        <?php foreach ($cmd['produits'] as $prod): ?>
                            <div class="produit-ligne">
                                <?php if ($prod['image_produit']): ?>
                                    <img src="<?= htmlspecialchars($prod['image_produit']) ?>" alt="">
                                <?php else: ?>
                                    <div class="produit-img-placeholder">📦</div>
                                <?php endif; ?>
                                <div class="produit-info">
                                    <span class="produit-nom"><?= htmlspecialchars($prod['nom_produit']) ?></span>
                                    <span class="produit-qte">Qté: <?= $prod['quantite'] ?></span>
                                </div>
                                <span class="produit-prix"><?= number_format($prod['total_ligne_ttc'], 2, ',', ' ') ?> €</span>
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
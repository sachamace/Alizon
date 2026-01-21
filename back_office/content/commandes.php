<?php
$id_vendeur = $_SESSION['vendeur_id'];

// Ici on fait appelle a la fonction C
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

// On recupere l'id client/commande ou le vendeur a des commandes
$stmt = $pdo->prepare("
    SELECT DISTINCT lc.id_commande
    FROM ligne_commande lc
    JOIN produit p ON lc.id_produit = p.id_produit
    WHERE p.id_vendeur = ?
");
$stmt->execute([$id_vendeur]);
$ids_commandes = $stmt->fetchAll(PDO::FETCH_COLUMN);

$commandes = [];

// On appele le serveur C pour chaque commande afin d'avoir les infos
foreach ($ids_commandes as $id_commande) {
    
    // Appel au serveur C : CHECK;id
    $reponse = appeler_serveur_c("CHECK;$id_commande");
    
    if (!$reponse || $reponse === "NOT_FOUND") continue;
    
    // Format retour
    $data = explode(';', rtrim($reponse, '|'));
    
    if (count($data) < 9) continue;
    
    $statut = $data[4];
    
    // La commande doit être payé pour que ca marche 
    if (empty($statut)) continue;
    
    //  Récupérer les produits du vendeur dans cette commande
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
    
    // Calculer le total pour ce vendeur
    $total = 0;
    foreach ($produits as $prod) {
        $total += $prod['total_ligne_ttc'];
    }
    
    $commandes[] = [
        'id_commande' => $id_commande,
        'date_commande' => $data[0],
        'montant_ht' => $data[1],
        'montant_ttc' => $data[2],
        'bordereau' => $data[3],
        'statut' => $data[4],
        'etape' => $data[5],
        'date_maj' => $data[6],
        'details_etape' => $data[7],
        'priorite' => $data[8],
        'produits' => $produits,
        'total' => $total
    ];
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
                        <span class="commande-statut statut-<?= strtolower(str_replace(' ', '_', $cmd['statut'])) ?>">
                            <?= htmlspecialchars($cmd['statut']) ?>
                        </span>
                    </div>
                    
                    <div class="commande-client">
                        <?php if ($cmd['bordereau']): ?>
                            <span>Bordereau : <?= htmlspecialchars($cmd['bordereau']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                   
                    <div class="commande-produits">
                        <?php foreach ($cmd['produits'] as $prod): ?>
                            <div class="produit-ligne">
                                <?php if ($prod['image_produit']): ?>
                                    <img src="<?= htmlspecialchars($prod['image_produit']) ?>" alt="">
                                <?php else: ?>
                                    <div class="produit-img-placeholder"></div>
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
                        <span class="total-label">Votre part :</span>
                        <span class="total-value"><?= number_format($cmd['total'], 2, ',', ' ') ?> € TTC</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
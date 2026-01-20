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
    
    // Pour GET_LIST on lit tout
    if (strpos($message, "GET_LIST") === 0) {
        $response = "";
        while (!feof($socket)) {
            $response .= fread($socket, 4096);
        }
    } else {
        $response = fgets($socket, 4096);
    }
    
    fclose($socket);
    return $response;
}

// 1. Récupérer la liste des commandes via le serveur C
$raw_data = appeler_serveur_c("GET_LIST");

$commandes = [];

if (!empty($raw_data)) {
    // Format: "true/false|id;etape;statut;priorite|id;etape;statut;priorite|..."
    $parties = explode('|', $raw_data, 2);
    $reste = isset($parties[1]) ? $parties[1] : "";
    $rows = explode('|', $reste);
    
    foreach ($rows as $row) {
        if (empty($row)) continue;
        $cols = explode(';', $row);
        if (count($cols) >= 4) {
            $id_commande = $cols[0];
            $statut = $cols[2];
            
            // On ne prend que les commandes avec un statut (payées)
            if (empty($statut)) continue;
            
            // 2. Vérifier si cette commande contient des produits du vendeur
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as nb
                FROM ligne_commande lc
                JOIN produit p ON lc.id_produit = p.id_produit
                WHERE lc.id_commande = ? AND p.id_vendeur = ?
            ");
            $stmt_check->execute([$id_commande, $id_vendeur]);
            $check = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($check['nb'] == 0) continue; // Pas mes produits, on skip
            
            // 3. Appeler CHECK pour avoir les détails de la commande
            $reponse_check = appeler_serveur_c("CHECK;$id_commande");
            
            $bordereau = '';
            $details_etape = '';
            $etape = $cols[1];
            $date_maj = '';
            
            if ($reponse_check && $reponse_check !== "NOT_FOUND") {
                // Format: bordereau;statut;etape;date_maj;details_etape;priorite|
                $data = explode(';', rtrim($reponse_check, '|'));
                if (count($data) >= 5) {
                    $bordereau = $data[0];
                    $statut = $data[1];
                    $etape = $data[2];
                    $date_maj = $data[3];
                    $details_etape = $data[4];
                }
            }
            
            // 4. Récupérer les infos client depuis la BDD
            $stmt_client = $pdo->prepare("
                SELECT cl.prenom, cl.nom, a.ville
                FROM compte_client cl
                JOIN commande c ON c.id_client = cl.id_client
                LEFT JOIN adresse a ON c.id_adresse_livraison = a.id_adresse
                WHERE c.id_commande = ?
            ");
            $stmt_client->execute([$id_commande]);
            $client = $stmt_client->fetch(PDO::FETCH_ASSOC);
            
            // 5. Récupérer les produits du vendeur dans cette commande
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
                'date_maj' => $date_maj,
                'client' => $client ? $client['prenom'] . ' ' . strtoupper(substr($client['nom'], 0, 1)) . '.' : 'Client',
                'ville' => $client['ville'] ?? '',
                'bordereau' => $bordereau,
                'statut' => $statut,
                'etape' => $etape,
                'details_etape' => $details_etape,
                'produits' => $produits,
                'total' => $total
            ];
        }
    }
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
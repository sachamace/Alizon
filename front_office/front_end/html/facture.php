<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    die("Accès interdit - Vous devez être connecté");
}

// Récupérer l'ID de commande depuis l'URL
$id_commande = $_GET['id'] ?? null;

if (!$id_commande) {
    die("Aucune commande spécifiée");
}

$id_client = $_SESSION['id'];

try {
    // Vérifier que la commande appartient bien au client connecté
    $stmt_verif = $pdo->prepare("
        SELECT id_commande FROM commande 
        WHERE id_commande = :id_commande AND id_client = :id_client
    ");
    $stmt_verif->execute([
        ':id_commande' => $id_commande,
        ':id_client' => $id_client
    ]);
    
    if (!$stmt_verif->fetch()) {
        die("Commande introuvable ou accès non autorisé");
    }

    // Récupération des informations du client
    $stmt_client = $pdo->prepare("
        SELECT nom, prenom, adresse_mail, num_tel
        FROM compte_client
        WHERE id_client = :id_client
    ");
    $stmt_client->execute([':id_client' => $id_client]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);
    
    // Récupération de l'adresse de livraison
    $stmt_adresse = $pdo->prepare("
        SELECT a.adresse, a.code_postal, a.ville, a.pays, a.num_tel
        FROM commande c
        LEFT JOIN adresse a ON c.id_adresse_livraison = a.id_adresse
        WHERE c.id_commande = :id_commande
    ");
    $stmt_adresse->execute([':id_commande' => $id_commande]);
    $adresse_commande = $stmt_adresse->fetch(PDO::FETCH_ASSOC);
    
    // Si pas d'adresse dans la commande, prendre l'adresse par défaut du client
    if (!$adresse_commande || !$adresse_commande['adresse']) {
        $stmt_defaut = $pdo->prepare("
            SELECT adresse, code_postal, ville, pays, num_tel
            FROM adresse
            WHERE id_client = :id_client AND est_defaut = TRUE
            LIMIT 1
        ");
        $stmt_defaut->execute([':id_client' => $id_client]);
        $adresse_commande = $stmt_defaut->fetch(PDO::FETCH_ASSOC);
    }
    
    // Fusionner les données client et adresse
    if ($adresse_commande) {
        $client = array_merge($client, $adresse_commande);
    }

    if (!$client) {
        die("Client introuvable");
    }

    // Récupération de la commande
    $stmt_commande = $pdo->prepare("
        SELECT date_commande, montant_total_ht, montant_total_ttc
        FROM commande
        WHERE id_commande = :id_commande
    ");
    $stmt_commande->execute([':id_commande' => $id_commande]);
    $commande = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        die("Commande introuvable");
    }

    // RÉCUPÉRATION DES ARTICLES GROUPÉS PAR VENDEUR
    $stmt = $pdo->prepare("
        SELECT 
            lc.quantite, 
            p.nom_produit, 
            lc.prix_unitaire_ht,
            lc.prix_unitaire_ttc,
            tv.taux AS taux_tva,
            p.id_vendeur,
            cv.raison_sociale AS nom_vendeur,
            cv.adresse_mail AS mail_vendeur,
            cv.num_tel AS tel_vendeur,
            cv.num_siren AS siren,
            cv.statut_juridique AS stat_jur,
            ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(tv.taux, 0) / 100) AS NUMERIC), 2) AS prix_initial_ttc,
            r.id_remise,
            r.nom_remise,
            r.type_remise,
            r.valeur_remise
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        LEFT JOIN taux_tva tv ON p.id_taux_tva = tv.id_taux_tva
        LEFT JOIN compte_vendeur cv ON p.id_vendeur = cv.id_vendeur
        LEFT JOIN commande c ON lc.id_commande = c.id_commande
        LEFT JOIN remise r ON (
            r.id_vendeur = p.id_vendeur
            AND r.est_actif = true
            AND c.date_commande::date BETWEEN r.date_debut AND r.date_fin
            AND (
                r.id_produit = p.id_produit
                OR EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise AND rp.id_produit = p.id_produit)
                OR (r.id_produit IS NULL AND r.categorie IS NULL AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                OR (r.id_produit IS NULL AND r.categorie = p.categorie AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
            )
        )
        WHERE lc.id_commande = :id_commande
        ORDER BY p.id_vendeur, lc.id_ligne
    ");
    $stmt->execute([':id_commande' => $id_commande]);
    $tous_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tous_articles)) {
        die("Aucun article dans cette commande");
    }

    // GROUPER LES ARTICLES PAR VENDEUR
    $articles_par_vendeur = [];
    foreach ($tous_articles as $article) {
        $id_vendeur = $article['id_vendeur'];
        if (!isset($articles_par_vendeur[$id_vendeur])) {
            $articles_par_vendeur[$id_vendeur] = [
                'vendeur' => [
                    'raison_sociale' => $article['nom_vendeur'] ?? 'MarketPlace Bretonne',
                    'mail' => $article['mail_vendeur'] ?? 'contact@marketplace-bretonne.fr',
                    'tel' => $article['tel_vendeur'] ?? '02 99 00 00 00',
                    'num_srn' => $article['siren'] ?? 'N/A',
                    'statut' => $article['stat_jur'] ?? 'N/A',
                ],
                'articles' => []
            ];
        }
        $articles_par_vendeur[$id_vendeur]['articles'][] = $article;
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

$date_facture = date('d/m/Y', strtotime($commande['date_commande']));

// BOUCLE POUR GÉNÉRER UNE FACTURE PAR VENDEUR
$numero_facture_base = 1;
foreach ($articles_par_vendeur as $id_vendeur => $data_vendeur):
    $vendeur = $data_vendeur['vendeur'];
    $articles_commande = $data_vendeur['articles'];
    
    // Calcul des totaux pour CE vendeur
    $total_ht_vendeur = 0;
    $total_ttc_vendeur = 0;
    $total_initial_vendeur = 0;
    
    foreach ($articles_commande as $article) {
        $total_ht_vendeur += $article['prix_unitaire_ht'] * $article['quantite'];
        $total_ttc_vendeur += $article['prix_unitaire_ttc'] * $article['quantite'];
        $total_initial_vendeur += $article['prix_initial_ttc'] * $article['quantite'];
    }
    
    $total_tva_vendeur = $total_ttc_vendeur - $total_ht_vendeur;
    $total_economie_vendeur = $total_initial_vendeur - $total_ttc_vendeur;
    
    // Numéro de facture unique par vendeur
    $numero_facture = "FACT-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($id_commande, 5, '0', STR_PAD_LEFT) . "-V" . $numero_facture_base;
    $numero_facture_base++;
?>

<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Facture <?= htmlspecialchars($numero_facture) ?></title>
    <link rel="stylesheet" href="../assets/csss/style.css" media="all">
</head>
<body>
    <div class='header'>
        <div class='logo-section'>
            <img src="../assets/images/logo_Alizon.png" alt="Logo Alizon">
            <p>Votre marketplace de produits bretons</p>
            <p>contact@alizon.fr</p>
            <p>02 99 00 00 00</p>
            <p>Lannion, Bretagne, France</p>
        </div>
        <div class='facture-info'>
            <h2>FACTURE</h2>
            <div class='facture-numero'><?= htmlspecialchars($numero_facture) ?></div>
            <p class='facture-date'><strong>Date :</strong> <?= htmlspecialchars($date_facture) ?></p>
            <p class='commande-ref'>Commande #<?= $id_commande ?></p>
        </div>
    </div>
    
    <div class='addresses'>
        <div class='address-box'>
            <h3>Facturé à</h3>
            <p><strong><?= htmlspecialchars($client['prenom']) . " " . htmlspecialchars($client['nom']) ?></strong></p>
            <p><?= htmlspecialchars($client['adresse'] ?? 'Non renseignée') ?></p>
            <p><?= htmlspecialchars($client['code_postal'] ?? '') . " " . htmlspecialchars($client['ville'] ?? '') ?></p>
            <p><?= htmlspecialchars($client['pays'] ?? '') ?></p>
            <p><?= htmlspecialchars($client['adresse_mail']) ?></p>
            <p><?= htmlspecialchars($client['num_tel'] ?? 'Non renseigné') ?></p>
        </div>
        <div class='spacer'></div>
        <div class='address-box'>
            <h3>Vendeur</h3>
            <p><strong><?= htmlspecialchars($vendeur['raison_sociale']) ?></strong></p>
            <p>SIREN : <?= htmlspecialchars($vendeur['num_srn']) ?></p>
            <p>Status juridique : <?= htmlspecialchars($vendeur['statut']) ?></p>
            <p class='mt-10'><?= htmlspecialchars($vendeur['mail']) ?></p>
            <p><?= htmlspecialchars($vendeur['tel']) ?></p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class='text-center'>Qté</th>
                <th class='text-right'>Prix init. TTC</th>
                <th class='text-right'>Prix payé TTC</th>
                <th class='text-right'>Économie</th>
                <th class='text-center'>TVA</th>
                <th class='text-right'>Total initial</th>
                <th class='text-right'>Total payé</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles_commande as $article): ?>
                <?php 
                $total_ligne_initial = $article['prix_initial_ttc'] * $article['quantite'];
                $total_ligne_paye = $article['prix_unitaire_ttc'] * $article['quantite'];
                $economie_ligne = $total_ligne_initial - $total_ligne_paye;
                $taux_affichage = $article['taux_tva'] ?? 0;
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($article['nom_produit']) ?>
                        <?php if ($article['id_remise']): ?>
                            <span class='remise-badge'>
                                <?php if ($article['type_remise'] === 'pourcentage'): ?>
                                    -<?= number_format($article['valeur_remise'], 0) ?>%
                                <?php else: ?>
                                    -<?= number_format($article['valeur_remise'], 2) ?>€
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class='text-center'><?= htmlspecialchars($article['quantite']) ?></td>
                    <td class='text-right'><?= number_format($article['prix_initial_ttc'], 2, ',', ' ') ?> €</td>
                    <td class='text-right'><?= number_format($article['prix_unitaire_ttc'], 2, ',', ' ') ?> €</td>
                    <td class='text-right economie-positive'><?= $economie_ligne > 0 ? '-' : '' ?><?= number_format(abs($economie_ligne), 2, ',', ' ') ?> €</td>
                    <td class='text-center'><?= number_format($taux_affichage, 2, ',', ' ') ?>%</td>
                    <td class='text-right'><?= number_format($total_ligne_initial, 2, ',', ' ') ?> €</td>
                    <td class='text-right'><strong><?= number_format($total_ligne_paye, 2, ',', ' ') ?> €</strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class='totaux-ligne'>
                <td class='text-left'>TOTAUX</td>
                <td class="text-center"><?= array_sum(array_column($articles_commande, 'quantite')) ?></td>
                <td class='text-center'>-</td>
                <td class='text-center'>-</td>
                <td class='text-right economie-totale'>
                    <?= $total_economie_vendeur > 0 ? '-' : '' ?><?= number_format(abs($total_economie_vendeur), 2, ',', ' ') ?> €
                </td>
                <td class='text-center'>-</td>
                <td class='text-right'><?= number_format($total_initial_vendeur, 2, ',', ' ') ?> €</td>
                <td class='text-right'><?= number_format($total_ttc_vendeur, 2, ',', ' ') ?> €</td>
            </tr>
        </tfoot>
    </table>
    
    <div class='totals'>
        <table>
            <tr>
                <td>Total HT</td>
                <td class='text-right'><strong><?= number_format($total_ht_vendeur, 2, ',', ' ') ?> €</strong></td>
            </tr>
            <tr>
                <td>Total TVA</td>
                <td class='text-right'><strong><?= number_format($total_tva_vendeur, 2, ',', ' ') ?> €</strong></td>
            </tr>
            <?php if ($total_economie_vendeur > 0): ?>
            <tr class='economie-row'>
                <td><strong>Économie totale</strong></td>
                <td class='text-right'><strong>-<?= number_format($total_economie_vendeur, 2, ',', ' ') ?> €</strong></td>
            </tr>
            <?php endif; ?>
            <tr class='total-row'>
                <td><strong>TOTAL TTC</strong></td>
                <td class='text-right'><strong><?= number_format($total_ttc_vendeur, 2, ',', ' ') ?> €</strong></td>
            </tr>
        </table>
    </div>
    
    <div class='footer'>
        <p><strong>Conditions de paiement :</strong> Paiement comptant à réception</p>
        <p><strong>Mention légale :</strong> En cas de retard de paiement, une pénalité de 10% sera appliquée.</p>
        <p>Merci pour votre confiance !</p>
    </div>
    
    <?php if ($numero_facture_base <= count($articles_par_vendeur)): ?>
    <div class="page-break"></div>
    <?php endif; ?>
    
</body>
</html>

<?php endforeach; ?>

<script>
    window.onload = () => {
        window.print();
    }
</script>
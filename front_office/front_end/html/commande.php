<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';

    $id_client_connecte = $_SESSION['id'];
    
    try {
        // Récupération des commandes
        $stmt_commande = $pdo->prepare("
            SELECT id_commande, date_commande, montant_total_ht, montant_total_ttc, statut
            FROM commande
            WHERE id_client = :id_client
            ORDER BY date_commande DESC
        ");
        $stmt_commande->execute([':id_client' => $id_client_connecte]);
        $commandes = $stmt_commande->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        die("Erreur technique : " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes</title>
    <meta name="description" content="Consultez l'historique de vos commandes">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="stylesheet" href="../assets/csss/commandes.css">
</head>

<body class="body_profilClient">
    <header class="disabled">
        <?php include 'header.php'?>
    </header>

    <div class="compte__header disabled">
        <a href="compte.php">← Mon Profil</a>
    </div>

    <main class="commandes-container">
        <?php if (empty($commandes)): ?>
            <div class="commande-vide">
                <h3>Vous n'avez pas encore passé de commande</h3>
                <p>Découvrez nos produits bretons et passez votre première commande !</p>
                <a href="/index.php" class="btn btn-primary" style="margin-top: 1rem;">Découvrir la boutique</a>
            </div>
        <?php else: ?>
            <?php foreach ($commandes as $commande): 
                $numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);
                $statut_class = 'statut-' . strtolower(str_replace(' ', '-', $commande['statut']));

                // On garde cette requête pour afficher les miniatures d'aperçu
                $stmt_produits = $pdo->prepare("
                    SELECT lc.quantite, m.chemin_image, p.nom_produit
                    FROM ligne_commande lc
                    JOIN produit p ON lc.id_produit = p.id_produit
                    LEFT JOIN media_produit m ON p.id_produit = m.id_produit
                    WHERE lc.id_commande = :id_commande
                ");
                $stmt_produits->execute([':id_commande' => $commande['id_commande']]);
                $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

                // Calcul du nombre total d'articles pour le résumé
                $nb_articles = 0;
                foreach ($produits as $article) {
                    $nb_articles += $article['quantite'];
                }
            ?>
            
            <div class="commande-card">
                <div class="commande-header">
                    <div>
                        <div class="commande-numero"><?= htmlspecialchars($numero_commande) ?></div>
                        <p class="commande-date">
                            <?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?>
                        </p>
                    </div>
                    <span class="commande-statut <?= $statut_class ?>">
                        <?= htmlspecialchars($commande['statut']) ?>
                    </span>
                </div>

                <div class="commande-info">
                    <div>
                        <p><strong>Articles :</strong> <?= $nb_articles ?></p>
                        <p><strong>Total HT :</strong> <?= number_format($commande['montant_total_ht'], 2, ',', ' ') ?> €</p>
                    </div>
                    <div>
                        <p><strong>Total TTC :</strong> <?= number_format($commande['montant_total_ttc'], 2, ',', ' ') ?> €</p>
                    </div>
                </div>

                <div class="commande-produits">
                    <?php 
                    $count = 0;
                    foreach ($produits as $produit): 
                        if ($count >= 6) break; // On affiche max 4 miniatures
                        $count++;
                    ?>
                        <div class="produit-miniature">
                            <img src="<?= htmlspecialchars($produit['chemin_image'] ?? '/assets/images/default.png') ?>" 
                                 alt="<?= htmlspecialchars($produit['nom_produit']) ?>">
                            <?php if($produit['quantite'] > 1): ?>
                                <span style="font-size:0.8rem; font-weight:bold;">x<?= $produit['quantite'] ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($produits) > 4): ?>
                        <div class="produit-miniature" style="display: flex; align-items: center; justify-content: center; width:100px;">
                            <p style="font-size: 1.2rem; color: #999;">+<?= count($produits) - 4 ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="commande-actions">
                    <a href="facture.php?id=<?= $commande['id_commande'] ?>" class="btn btn-secondary" target="_blank">
                        Facture
                    </a>
                    <a href="detail_commande.php?id=<?= $commande['id_commande'] ?>" class="btn btn-primary">
                        Voir le détail
                    </a>
                </div>
            </div>

            <?php endforeach; ?>
        <?php endif; ?> 
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
</body>
</html>
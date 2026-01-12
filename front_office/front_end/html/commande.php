<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';

    $id_client_connecte = $_SESSION['id'];
    try {
        // Récupération des informations de la commande
        $stmt_commande = $pdo->prepare("
            SELECT id_commande, date_commande, montant_total_ht, montant_total_ttc, statut
            FROM commande
            WHERE id_client = :id_client
            ORDER BY date_commande DESC
        ");
        $stmt_commande->execute([':id_client' => $id_client_connecte]);
        $commandes = $stmt_commande->fetch(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        die("Erreur lors de la récupération des commandes : " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes</title>
    <meta name="description" content="Consultez l'historique de vos commandes">
    <meta name="keywords" content="MarketPlace, Shopping, Commandes, Historique" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <style>
        .commandes-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .commande-vide {
            text-align: center;
            padding: 3rem;
            background: #f9f9f9;
            border-radius: 10px;
            margin: 2rem 0;
        }

        .commande-vide h3 {
            color: #666;
            margin-bottom: 1rem;
        }

        .commande-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            transition: transform 0.2s;
        }

        .commande-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .commande-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 1rem;
        }

        .commande-numero {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
        }

        .commande-statut {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .statut-validee {
            background: #d4edda;
            color: #155724;
        }

        .statut-en-preparation {
            background: #fff3cd;
            color: #856404;
        }

        .statut-expediee {
            background: #d1ecf1;
            color: #0c5460;
        }

        .statut-livree {
            background: #c3e6cb;
            color: #155724;
        }

        .commande-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .commande-info p {
            margin: 0.3rem 0;
            color: #666;
        }

        .commande-info strong {
            color: #333;
        }

        .commande-produits {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding: 1rem 0;
            margin: 1rem 0;
        }

        .produit-miniature {
            flex: 0 0 120px;
            text-align: center;
        }

        .produit-miniature img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #f0f0f0;
        }

        .produit-miniature p {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .commande-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #ff6ce2;
            color: #000;
        }

        .btn-primary:hover {
            background: #ff5cd5;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #ff6ce2;
            color: #ff6ce2;
        }

        .btn-outline:hover {
            background: #ff6ce2;
            color: #000;
        }

        .voir-details {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .produits-details {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .produits-details.show {
            display: block;
        }

        .produit-detail-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .produit-detail-item:last-child {
            border-bottom: none;
        }

        .produit-detail-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 1rem;
        }

        .produit-detail-info {
            flex: 1;
        }

        .produit-detail-info h4 {
            margin: 0 0 0.3rem 0;
            font-size: 1rem;
        }

        .produit-detail-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .commande-info {
                grid-template-columns: 1fr;
            }

            .commande-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="body_profilClient">
    <header class = "disabled">
        <?php include 'header.php'?>
    </header>

    <div class="compte__header disabled">
        <a href="compte.php">← </a>Mon Profil
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
                // Générer le numéro de commande
                $numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);
                
                // Récupérer les produits de cette commande
                $stmt_produits = $pdo->prepare("
                    SELECT lc.quantite, lc.prix_unitaire_ttc,
                           p.nom_produit, p.id_produit,
                           m.chemin_image
                    FROM ligne_commande lc
                    JOIN produit p ON lc.id_produit = p.id_produit
                    LEFT JOIN media_produit m ON p.id_produit = m.id_produit
                    WHERE lc.id_commande = :id_commande
                ");
                $stmt_produits->execute([':id_commande' => $commande['id_commande']]);
                $produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

                $nb_articles = 0;
                $taxe = 0;

                foreach ($articles_commande as $article) {
                    $taxe += ($article['prix_unitaire_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
                    $nb_articles += $article['quantite'];
                }
                
                // Déterminer la classe CSS du statut
                $statut_class = 'statut-' . strtolower(str_replace(' ', '-', $commande['statut']));
            ?>
            
            <div class="commande-card">
                <div class="commande-header">
                    <div>
                        <div class="commande-numero"><?= htmlspecialchars($numero_commande) ?></div>
                        <p style="margin: 0.3rem 0; color: #666; font-size: 0.9rem;">
                            <?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?>
                        </p>
                    </div>
                    <span class="commande-statut <?= $statut_class ?>">
                        <?= htmlspecialchars($commande['statut']) ?>
                    </span>
                </div>

                <div class="commande-info">
                    <div>
                        <p><strong>Nombre d'articles :</strong> <?= $nb_articles ?></p>
                        <p><strong>Total HT :</strong> <?= number_format($commande['montant_total_ht'], 2, ',', ' ') ?> €</p>
                    </div>
                    <div>
                        <p><strong>Total TTC :</strong> <?= number_format($commande['montant_total_ttc'], 2, ',', ' ') ?> €</p>
                    </div>
                </div>

                <!-- Miniatures des produits (3 premiers) -->
                <div class="commande-produits">
                    <?php 
                    $count = 0;
                    foreach ($produits as $produit): 
                        if ($count >= 3) break;
                        $count++;
                    ?>
                        <div class="produit-miniature">
                            <img src="<?= htmlspecialchars($produit['chemin_image'] ?? '/assets/images/default.png') ?>" 
                                 alt="<?= htmlspecialchars($produit['nom_produit']) ?>">
                            <p>x<?= $produit['quantite'] ?></p>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($produits) > 3): ?>
                        <div class="produit-miniature" style="display: flex; align-items: center; justify-content: center;">
                            <p style="font-size: 1.5rem; color: #999;">+<?= count($produits) - 3 ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <a class="voir-details" onclick="toggleDetails(<?= $commande['id_commande'] ?>)">
                    Voir les détails ▼
                </a>

                <!-- Détails des produits (caché par défaut) -->
                <div class="produits-details" id="details-<?= $commande['id_commande'] ?>">
                    <h4 style="margin-top: 0;">Produits commandés :</h4>
                    <?php foreach ($produits as $produit): ?>
                        <div class="produit-detail-item">
                            <img src="<?= htmlspecialchars($produit['chemin_image'] ?? '/assets/images/default.png') ?>" 
                                 alt="<?= htmlspecialchars($produit['nom_produit']) ?>">
                            <div class="produit-detail-info">
                                <h4><?= htmlspecialchars($produit['nom_produit']) ?></h4>
                                <p>Quantité : <?= $produit['quantite'] ?> | 
                                   Prix unitaire : <?= number_format($produit['prix_unitaire_ttc'], 2, ',', ' ') ?> € | 
                                   Total : <?= number_format($produit['prix_unitaire_ttc'] * $produit['quantite'], 2, ',', ' ') ?> €</p>
                            </div>
                            <a href="produitdetail.php?article=<?= $produit['id_produit'] ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                Voir le produit
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="commande-actions">
                    <a href="facture.php?id=<?= $commande['id_commande'] ?>" class="btn btn-secondary" target="_blank">
                        Télécharger la facture
                    </a>
                    <a href="panier.php" class="btn btn-primary">
                        Recommander
                    </a>
                </div>
            </div>

            <?php endforeach; ?>
        <?php endif; ?> 
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
    <script>
        function toggleDetails(id) {
            const details = document.getElementById('details-' + id);
            const link = event.target;
            
            if (details.classList.contains('show')) {
                details.classList.remove('show');
                link.textContent = 'Voir les détails ▼';
            } else {
                details.classList.add('show');
                link.textContent = 'Masquer les détails ▲';
            }
        }
    </script>
</body>
</html>
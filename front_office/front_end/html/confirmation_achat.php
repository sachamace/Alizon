<?php
session_start();
require 'config.php';
include 'session.php';
include 'sessionindex.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Vérifier qu'une commande vient d'être passée
if (!isset($_SESSION['derniere_commande'])) {
    header("Location: index.php");
    exit();
}

$id_client_connecte = $_SESSION['id'];
$id_panier_commande = $_SESSION['derniere_commande']; // C'est l'ID du panier transformé en commande

try {
    // Récupération des informations client
    $stmt_client = $pdo->prepare("
        SELECT prenom, nom, adresse_mail 
        FROM compte_client 
        WHERE id_client = :id_client
    ");
    $stmt_client->execute([':id_client' => $id_client_connecte]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        die("Client introuvable");
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des infos client : " . $e->getMessage());
}

try {
    // Récupération des informations du panier/commande
    $stmt_commande = $pdo->prepare("
        SELECT date_commande, montant_total_ht, montant_total_ttc, nb_articles, statut_commande
        FROM panier
        WHERE id_panier = :id_panier AND id_client = :id_client
    ");
    $stmt_commande->execute([
        ':id_panier' => $id_panier_commande,
        ':id_client' => $id_client_connecte
    ]);
    $commande = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        die("Commande introuvable");
    }

    // Récupérer les articles de la commande
    $stmt_articles = $pdo->prepare("
        SELECT pp.quantite, 
               p.nom_produit, 
               p.prix_unitaire_ht,
               p.taux_tva,
               p.prix_ttc
        FROM panier_produit pp
        JOIN produit p ON pp.id_produit = p.id_produit
        WHERE pp.id_panier = :id_panier
    ");
    $stmt_articles->execute([':id_panier' => $id_panier_commande]);
    $articles_panier = $stmt_articles->fetchAll(PDO::FETCH_ASSOC);

    // Calculer la taxe totale
    $taxe = 0;
    foreach ($articles_panier as $article) {
        $taxe += ($article['prix_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
    }

} catch (PDOException $e) {
    die("Erreur lors de la récupération du panier : " . $e->getMessage());
}

// Générer le numéro de commande
$numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($id_panier_commande, 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée</title>
    <link rel="stylesheet" href="../assets/csss/style.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 100px auto;
            padding: 3rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-top: 200px;
        }

        .success-icon {
            font-size: 100px;
            color: #28a745;
            animation: successPulse 1s ease-in-out;
        }

        @keyframes successPulse {
            0% {
                transform: scale(0) rotate(0deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.3) rotate(180deg);
            }
            100% {
                transform: scale(1) rotate(360deg);
                opacity: 1;
            }
        }

        .confirmation-container h1 {
            color: #333;
            margin: 1.5rem 0;
            font-size: 2.2rem;
        }

        .confirmation-container > p {
            color: #666;
            margin: 1rem 0;
            font-size: 1.2rem;
            line-height: 1.6;
        }

        .client-info {
            background: rgba(255, 108, 226, 0.2);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 2rem 0;
            border-left: 4px solid #ff6ce2;
        }

        .client-info p {
            margin: 0.5rem 0;
            color: #333;
            font-size: 1.1rem;
        }

        .order-details {
            background: #f9f9f9;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
            text-align: left;
        }

        .order-details h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #ff6ce2;
            padding-bottom: 0.5rem;
        }

        .order-details p {
            margin: 1rem 0;
            display: flex;
            justify-content: space-between;
            font-size: 1.05rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-details p:last-child {
            border-bottom: none;
        }

        .order-details strong {
            color: #333;
            font-weight: 600;
        }

        .order-details .total {
            background: rgba(255, 108, 226, 0.2);
            margin: 1rem -2rem -2rem -2rem;
            padding: 1.5rem 2rem;
            border-radius: 0 0 12px 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .buttons-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1.2rem 2.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #ff6ce2;
            color: #000;
            box-shadow: 0 4px 15px rgba(108, 211, 255, 0.3);
        }

        .btn-primary:hover {
            background: #ff6ce2;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 211, 255, 0.4);
        }

        .btn-secondary {
            background: #333;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary:hover {
            background: #555;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 428px) {
            .confirmation-container {
                margin: 50px 1rem;
                padding: 2rem 1.5rem;
            }

            .success-icon {
                font-size: 70px;
            }

            .confirmation-container h1 {
                font-size: 1.7rem;
            }

            .confirmation-container > p {
                font-size: 1rem;
            }

            .buttons-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .order-details {
                padding: 1.5rem;
            }

            .order-details .total {
                margin: 1rem -1.5rem -1.5rem -1.5rem;
                padding: 1.2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php include 'header.php'; ?>
    </header>

    <main>
        <div class="confirmation-container">
            <div class="success-icon">✓</div>
            
            <h1>🎉 Commande confirmée !</h1>
            
            <p>Merci <?= htmlspecialchars($client['prenom']) ?> ! Votre commande a été validée avec succès.</p>

            <div class="client-info">
                <p><strong><?= htmlspecialchars($client['prenom']) ?> <?= htmlspecialchars($client['nom']) ?></strong></p>
                <p><?= htmlspecialchars($client['adresse_mail']) ?></p>
            </div>

            <div class="order-details">
                <h3>Détails de la commande</h3>
                <p>
                    <strong>Numéro de commande :</strong>
                    <span><?= htmlspecialchars($numero_commande) ?></span>
                </p>   
                <p>
                    <strong>Nombre d'articles :</strong>
                    <span><?= htmlspecialchars($commande['nb_articles']) ?></span>
                </p>
                <p>
                    <strong>Date :</strong>
                    <span><?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></span>
                </p>
                <p>
                    <strong>Statut :</strong>
                    <span><?= htmlspecialchars($commande['statut_commande']) ?></span>
                </p>
                <p>
                    <strong>Total HT :</strong>
                    <span><?= number_format($commande['montant_total_ht'], 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <strong>TVA :</strong>
                    <span><?= number_format($taxe, 2, ',', ' ') ?> €</span>
                </p>
                <p class="total">
                    <strong>Montant total TTC :</strong>
                    <span><?= number_format($commande['montant_total_ttc'], 2, ',', ' ') ?> €</span>
                </p>
            </div> 

            <div class="buttons-group">
                <a href="facture.php?id=<?= $id_panier_commande ?>" class="btn btn-primary" target="_blank">
                    📄 Télécharger la facture
                </a>
                <a href="index.php" class="btn btn-secondary">🏠 Retour à l'accueil</a>
            </div>
        </div>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'; ?>
    </footer>
</body>
</html>
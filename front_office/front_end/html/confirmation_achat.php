<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id_client']) || !isset($_SESSION['derniere_commande'])) {
    header("Location: index.php");
    exit();
}

$id_client = $_SESSION['id_client'];
$commande = $_SESSION['derniere_commande'];

// Récupération des informations client
$stmt_client = $pdo->prepare("
    SELECT prenom, nom, adresse_mail 
    FROM compte_client 
    WHERE id_client = :id_client
");
$stmt_client->execute([':id_client' => $id_client]);
$client = $stmt_client->fetch(PDO::FETCH_ASSOC);

// Générer le numéro de commande
$numero_commande = "CMD-" . date('Ymd') . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);
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
            background: #f0f9ff;
            padding: 1.5rem;
            border-radius: 12px;
            margin: 2rem 0;
            border-left: 4px solid #6CD3FF;
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
            border-bottom: 2px solid #6CD3FF;
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
            background: #6CD3FF;
            margin: 1rem -2rem -2rem -2rem;
            padding: 1.5rem 2rem;
            border-radius: 0 0 12px 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .email-confirmation {
            background: #e8f5e9;
            padding: 1.2rem;
            border-radius: 8px;
            margin: 2rem 0;
            color: #2e7d32;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
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
            background: #6CD3FF;
            color: #000;
            box-shadow: 0 4px 15px rgba(108, 211, 255, 0.3);
        }

        .btn-primary:hover {
            background: #5bc3ef;
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
                <p>👤 <strong><?= htmlspecialchars($client['prenom']) ?> <?= htmlspecialchars($client['nom']) ?></strong></p>
                <p>📧 <?= htmlspecialchars($client['adresse_mail']) ?></p>
            </div>

            <div class="order-details">
                <h3>📦 Détails de la commande</h3>
                <p>
                    <strong>Numéro de commande :</strong>
                    <span><?= $numero_commande ?></span>
                </p>
                <p>
                    <strong>Nombre d'articles :</strong>
                    <span><?= $commande['nb_articles'] ?></span>
                </p>
                <p>
                    <strong>Date :</strong>
                    <span><?= date('d/m/Y à H:i') ?></span>
                </p>
                <p class="total">
                    <strong>Montant total :</strong>
                    <span><?= number_format($commande['montant'], 2, ',', ' ') ?> €</span>
                </p>
            </div> 

            <div class="buttons-group">
                <a href="facture.php?id_commande=<?= $commande['id_commande'] ?>" class="btn btn-primary" target="_blank">
                    Télécharger la facture
                </a>
                <a href="index.php" class="btn btn-secondary">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'; ?>
    </footer>
</body>
</html>
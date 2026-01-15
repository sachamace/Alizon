<?php
session_start();
require 'config.php';
include 'session.php';
include 'sessionindex.php';

// Vérifier qu'une commande vient d'être passée
if (!isset($_SESSION['derniere_commande'])) {
    header("Location: index.php");
    exit();
}

$id_client_connecte = $_SESSION['id'];
$id_commande = $_SESSION['derniere_commande'];
$message_transporteur = "";

try {
    // --- PARTIE A : RÉCUPÉRATION DES DONNÉES DE LA COMMANDE
    // Informations client
    $stmt_client = $pdo->prepare("SELECT prenom, nom, adresse_mail FROM compte_client WHERE id_client = :id_client");
    $stmt_client->execute([':id_client' => $id_client_connecte]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client) die("Client introuvable");

    // Informations commande
    $stmt_commande = $pdo->prepare("SELECT id_commande, date_commande, montant_total_ht, montant_total_ttc, statut FROM commande WHERE id_commande = :id_commande AND id_client = :id_client");
    $stmt_commande->execute([':id_commande' => $id_commande, ':id_client' => $id_client_connecte]);
    $commande = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    if (!$commande) die("Commande introuvable");

    // Articles pour calcul de la taxe
    $stmt_lignes = $pdo->prepare("SELECT lc.quantite, p.nom_produit, lc.prix_unitaire_ht, lc.prix_unitaire_ttc FROM ligne_commande lc JOIN produit p ON lc.id_produit = p.id_produit WHERE lc.id_commande = :id_commande");
    $stmt_lignes->execute([':id_commande' => $id_commande]);
    $articles_commande = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

    $nb_articles = 0;
    $taxe = 0;
    foreach ($articles_commande as $article) {
        $taxe += ($article['prix_unitaire_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
        $nb_articles += $article['quantite'];
    }

    // --- PARTIE B : CRÉATION DU BORDEREAU (Communication Serveur C & PostgreSQL) ---
    
    $host = "10.253.5.108";
    $port = 5432;
    
    // 1. Ouvrir la connexion vers le serveur C 
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$socket) {
        throw new Exception("Le serveur C (bordereau) ne répond pas sur le port $port.");
    }

    // 3. Envoyer l'ID au serveur C
    fwrite($socket, $id_commande);

    // 4. Lire le bordereau TRK-XXXX généré par le C
    $reponse_c = trim(fgets($socket, 1024));
    
    // Table modifiée : systeme.commandes -> commande
    $query_max = "SELECT MAX(priorite) AS valeur_max FROM commande";
    $stmt_max = $pdo->prepare($query_max);
    $stmt_max->execute();
    
    $resultat = $stmt_max->fetch(PDO::FETCH_ASSOC);
    $max = $resultat['valeur_max'] ?? 0; // Gestion du cas si la table est vide

    $parts = explode('|', $reponse_c);
    $bordereau_recu = $parts[0] ?? ''; 
    $erreur_recue    = $parts[1] ?? '';

    // Logique de mise en attente (Algorithme conservé)
    if ($max != 0) {
        $query = "INSERT INTO commande (id_commande, id_client, montant_total_ht, montant_total_ttc, etape, bordereau, details_etape, statut, priorite) 
                  VALUES (:id, :id_client, :ht, :ttc, :etape, :bordereau, :details_etape, :statut, :priorite)";
        
        $stmtenvoie = $pdo->prepare($query);
        $stmtenvoie->execute([
            'id'            => $num_commande,
            'id_client'     => $id_client,
            'ht'            => $montant_ht,
            'ttc'           => $montant_ttc,
            'etape'         => 1,
            'bordereau'     => $bordereau_recu,
            'details_etape' => "Création d’un bordereau de livraison",
            'statut'        => "EN ATTENTE",
            'priorite'      => $max + 1
        ]);
        
        fclose($socket);
        throw new Exception("Désolé, le transporteur est complet. Votre commande est en attente.");
    }

    if (empty($reponse_c)) {
        fclose($socket);
        throw new Exception("Le serveur C a renvoyé une réponse vide.");
    }
    
    fclose($socket);

    // 5. CAS : INSERTION NORMALE (ENCOURS)
    $query = "INSERT INTO commande (id_commande, id_client, montant_total_ht, montant_total_ttc, etape, bordereau, details_etape, statut) 
              VALUES (:id, :id_client, :ht, :ttc, :etape, :bordereau, :details_etape, :statut)";
    
    $stmtenvoie = $pdo->prepare($query);
    $stmtenvoie->execute([
        'id'            => $num_commande,
        'id_client'     => $id_client,
        'ht'            => $montant_ht,
        'ttc'           => $montant_ttc,
        'etape'         => 1,
        'bordereau'     => $bordereau_recu,
        'details_etape' => "Création d’un bordereau de livraison",
        'statut'        => "ENCOURS"
    ]);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Générer le numéro de commande
$numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);
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
                    <span><?= htmlspecialchars($nb_articles) ?></span>
                </p>
                <p>
                    <strong>Date :</strong>
                    <span><?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></span>
                </p>
                <p>
                    <strong>Statut :</strong>
                    <span><?= htmlspecialchars($commande['statut']) ?></span>
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
                <a href="facture.php?id=<?= $commande['id_commande'] ?>" class="btn btn-primary" target="_blank">
                    Télécharger la facture
                </a>
                <a href="/index.php" class="btn btn-secondary">Retour à l'accueil</a>
            </div>
        </div>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'; ?>
    </footer>
</body>
</html>
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

try {
    // Récupération des données mises à jour par le serveur C (ou par défaut)
    // On récupère le statut, le bordereau, etc.
    $stmt_commande = $pdo->prepare("
        SELECT id_commande, date_commande, montant_total_ht, montant_total_ttc, 
               statut, bordereau, details_etape
        FROM commande 
        WHERE id_commande = :id_commande AND id_client = :id_client
    ");
    $stmt_commande->execute([':id_commande' => $id_commande, ':id_client' => $id_client_connecte]);
    $commande = $stmt_commande->fetch(PDO::FETCH_ASSOC);

    if (!$commande) die("Commande introuvable");

    // Récupération infos client
    $stmt_client = $pdo->prepare("SELECT prenom, nom, adresse_mail FROM compte_client WHERE id_client = :id_client");
    $stmt_client->execute([':id_client' => $id_client_connecte]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);

    // Calculs pour l'affichage (Taxe, nb articles)
    $stmt_lignes = $pdo->prepare("
        SELECT lc.quantite, lc.prix_unitaire_ht, lc.prix_unitaire_ttc 
        FROM ligne_commande lc 
        WHERE lc.id_commande = :id_commande
    ");
    $stmt_lignes->execute([':id_commande' => $id_commande]);
    $articles_commande = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

    $nb_articles = 0;
    $taxe = 0;
    foreach ($articles_commande as $article) {
        $taxe += ($article['prix_unitaire_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
        $nb_articles += $article['quantite'];
    }

} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

$numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);

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
</head>
<body>
    <header>
        <?php include 'header.php'; ?>
    </header>

    <main>
        <div class="confirmation-container">
            <div class="success-icon">✓</div>
            
            <h1>Commande confirmée !</h1>
            
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
    <script src="../assets/js/detailcommande.js"></script>
</body>
</html>
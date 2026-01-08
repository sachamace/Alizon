<?php
session_start();
require 'config.php';
require __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
    //Vérifier que la commande appartient bien au client connecté
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
        SELECT cc.nom, cc.prenom, cc.adresse_mail, cc.num_tel,
               a.adresse, a.code_postal, a.ville, a.pays
        FROM compte_client cc
        LEFT JOIN adresse a ON cc.id_client = a.id_client
        WHERE cc.id_client = :id_client
        ORDER BY a.id_adresse DESC
        LIMIT 1
    ");
    $stmt_client->execute([':id_client' => $id_client]);
    $client = $stmt_client->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        die("Client introuvable");
    }

    //Récupération de la commande
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

    //Récupération des articles de la commande avec taux TVA
    $stmt = $pdo->prepare("
        SELECT lc.quantite, 
               p.nom_produit, 
               lc.prix_unitaire_ht,
               lc.prix_unitaire_ttc,
               tv.taux AS taux_tva
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        LEFT JOIN taux_tva tv ON p.id_taux_tva = tv.id_taux_tva
        WHERE lc.id_commande = :id_commande
    ");
    $stmt->execute([':id_commande' => $id_commande]);
    $articles_commande = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //Impossible car paiement est déja inaccessible sans article mais SECURITE
    if (empty($articles_commande)) {
        die("Aucun article dans cette commande");
    }

    $total_ht = $commande['montant_total_ht'];
    $total_ttc = $commande['montant_total_ttc'];
    $total_tva = $total_ttc - $total_ht;

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

//Génération du numéro de la facture
$numero_facture = "FACT-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($id_commande, 5, '0', STR_PAD_LEFT);
$date_facture = date('d/m/Y', strtotime($commande['date_commande']));

//HTML de la facture
$html = "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <style>
        @page {
            margin: 20mm;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 3px solid #6CD3FF;
            padding-bottom: 20px;
        }
        
        .logo-section {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .logo-section h1 {
            color: #6CD3FF;
            font-size: 32px;
            margin: 0 0 10px 0;
        }
        
        .logo-section p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        
        .facture-info {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }
        
        .facture-info h2 {
            color: #333;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        
        .facture-numero {
            background: #6CD3FF;
            color: #000;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        
        .address-box {
            display: table-cell;
            width: 48%;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            vertical-align: top;
        }
        
        .address-box h3 {
            color: #6CD3FF;
            margin: 0 0 15px 0;
            font-size: 16px;
            border-bottom: 2px solid #6CD3FF;
            padding-bottom: 5px;
        }
        
        .address-box p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .spacer {
            display: table-cell;
            width: 4%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        table thead {
            background: #6CD3FF;
            color: #000;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }
        
        table tbody tr:hover {
            background: #f5f5f5;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            width: 50%;
            float: right;
            margin-top: 20px;
        }
        
        .totals table {
            margin-bottom: 0;
        }
        
        .totals td {
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .total-row {
            background: #6CD3FF !important;
            font-weight: bold;
            font-size: 14px !important;
        }
        
        .total-row td {
            padding: 12px !important;
            color: #000;
        }
        
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class='header'>
        <div class='logo-section'>
            <img src='/../images/logo_Alizon.png' alt='logo Alizon'>
            <p>Votre marketplace de produits bretons</p>
            <p>contact@marketplace-bretonne.fr</p>
            <p>02 99 00 00 00</p>
            <p>Rennes, Bretagne, France</p>
        </div>
        <div class='facture-info'>
            <h2>FACTURE</h2>
            <div class='facture-numero'>{$numero_facture}</div>
            <p style='margin-top: 15px;'><strong>Date :</strong> {$date_facture}</p>
        </div>
    </div>
    
    <div class='addresses'>
        <div class='address-box'>
            <h3>Facture a</h3>
            <p><strong>" . htmlspecialchars($client['prenom']) . " " . htmlspecialchars($client['nom']) . "</strong></p>
            <p>" . htmlspecialchars($client['adresse'] ?? 'Non renseignee') . "</p>
            <p>" . htmlspecialchars($client['code_postal'] ?? '') . " " . htmlspecialchars($client['ville'] ?? '') . "</p>
            <p>" . htmlspecialchars($client['pays'] ?? '') . "</p>
            <p style='margin-top: 10px;'>" . htmlspecialchars($client['adresse_mail']) . "</p>
            <p>" . htmlspecialchars($client['num_tel'] ?? 'Non renseigne') . "</p>
        </div>
        <div class='spacer'></div>
        <div class='address-box'>
            <h3>Vendeur</h3>
            <p><strong>MarketPlace Bretonne</strong></p>
            <p>SARL au capital de 10 000 euros</p>
            <p>SIRET : 123 456 789 00012</p>
            <p>TVA : FR 12 123456789</p>
            <p style='margin-top: 10px;'>1 Rue de la Bretagne</p>
            <p>35000 Rennes, France</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class='text-center'>Quantite</th>
                <th class='text-right'>Prix HT</th>
                <th class='text-center'>TVA</th>
                <th class='text-right'>Prix TTC</th>
                <th class='text-right'>Total TTC</th>
            </tr>
        </thead>
        <tbody>";

foreach ($articles_commande as $article) {
    $total_ligne = $article['prix_unitaire_ttc'] * $article['quantite'];
    $taux_affichage = $article['taux_tva'] ?? 0;
    $html .= "
            <tr>
                <td>" . htmlspecialchars($article['nom_produit']) . "</td>
                <td class='text-center'>{$article['quantite']}</td>
                <td class='text-right'>" . number_format($article['prix_unitaire_ht'], 2, ',', ' ') . " euros</td>
                <td class='text-center'>" . number_format($taux_affichage, 2, ',', ' ') . "%</td>
                <td class='text-right'>" . number_format($article['prix_unitaire_ttc'], 2, ',', ' ') . " euros</td>
                <td class='text-right'>" . number_format($total_ligne, 2, ',', ' ') . " euros</td>
            </tr>";
}

$html .= "
        </tbody>
    </table>
    
    <div class='totals'>
        <table>
            <tr>
                <td>Total HT</td>
                <td class='text-right'><strong>" . number_format($total_ht, 2, ',', ' ') . " euros</strong></td>
            </tr>
            <tr>
                <td>Total TVA</td>
                <td class='text-right'><strong>" . number_format($total_tva, 2, ',', ' ') . " euros</strong></td>
            </tr>
            <tr class='total-row'>
                <td><strong>TOTAL TTC</strong></td>
                <td class='text-right'><strong>" . number_format($total_ttc, 2, ',', ' ') . " euros</strong></td>
            </tr>
        </table>
    </div>
    
    <div class='footer'>
        <p><strong>Conditions de paiement :</strong> Paiement comptant a reception</p>
        <p><strong>Mention legale :</strong> En cas de retard de paiement, une penalite de 10% sera appliquee.</p>
        <p>Merci pour votre confiance !</p>
    </div>
</body>
</html>
";

// configuration et génération du PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Téléchargement du PDF
$nom_fichier = "facture_{$numero_facture}.pdf";
$dompdf->stream($nom_fichier, array("Attachment" => true));
?>
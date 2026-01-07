<?php
session_start();
require 'config.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_client'])) {
    die("Accès interdit - Vous devez être connecté");
}

$id_client = $_SESSION['id_client'];
$id_panier = $_SESSION['id_panier'];

// 📌 Récupération des informations du client
$stmt_client = $pdo->prepare("
    SELECT cc.nom, cc.prenom, cc.adresse_mail, cc.num_tel,
           a.adresse, a.code_postal, a.ville, a.pays
    FROM compte_client cc
    LEFT JOIN adresse a ON cc.id_client = a.id_client
    WHERE cc.id_client = :id_client
");
$stmt_client->execute([':id_client' => $id_client]);
$client = $stmt_client->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    die("Client introuvable");
}

// 📌 Récupération des articles du panier
$stmt = $pdo->prepare("
    SELECT pp.quantite, p.nom_produit, p.prix_unitaire_ht, p.taux_tva, p.prix_ttc
    FROM panier_produit pp
    JOIN produit p ON pp.id_produit = p.id_produit
    WHERE pp.id_panier = :id_panier
");
$stmt->execute([':id_panier' => $id_panier]);
$articles_panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($articles_panier)) {
    die("Aucun article dans le panier");
}

// 📌 Calculs
$total_ht = 0;
$total_ttc = 0;
$total_tva = 0;

foreach ($articles_panier as $article) {
    $total_ht += $article['prix_unitaire_ht'] * $article['quantite'];
    $total_ttc += $article['prix_ttc'] * $article['quantite'];
    $total_tva += ($article['prix_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
}

// 📌 Génération du numéro de facture unique
$numero_facture = "FACT-" . date('Ymd') . "-" . str_pad($id_client, 5, '0', STR_PAD_LEFT);
$date_facture = date('d/m/Y');

// 📌 HTML de la facture (design professionnel)
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
        
        .facture-info p {
            margin: 5px 0;
            font-size: 12px;
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
            <h1>🛒 MarketPlace Bretonne</h1>
            <p>Votre marketplace de produits bretons</p>
            <p>📧 contact@marketplace-bretonne.fr</p>
            <p>📞 02 99 00 00 00</p>
            <p>📍 Rennes, Bretagne, France</p>
        </div>
        <div class='facture-info'>
            <h2>FACTURE</h2>
            <div class='facture-numero'>{$numero_facture}</div>
            <p style='margin-top: 15px;'><strong>Date :</strong> {$date_facture}</p>
        </div>
    </div>
    
    <div class='addresses'>
        <div class='address-box'>
            <h3>📍 Facturé à</h3>
            <p><strong>" . htmlspecialchars($client['prenom']) . " " . htmlspecialchars($client['nom']) . "</strong></p>
            <p>" . htmlspecialchars($client['adresse'] ?? 'Non renseignée') . "</p>
            <p>" . htmlspecialchars($client['code_postal'] ?? '') . " " . htmlspecialchars($client['ville'] ?? '') . "</p>
            <p>" . htmlspecialchars($client['pays'] ?? '') . "</p>
            <p style='margin-top: 10px;'>📧 " . htmlspecialchars($client['adresse_mail']) . "</p>
            <p>📞 " . htmlspecialchars($client['num_tel']) . "</p>
        </div>
        <div class='spacer'></div>
        <div class='address-box'>
            <h3>🏢 Vendeur</h3>
            <p><strong>MarketPlace Bretonne</strong></p>
            <p>SARL au capital de 10 000€</p>
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
                <th class='text-center'>Quantité</th>
                <th class='text-right'>Prix HT</th>
                <th class='text-center'>TVA</th>
                <th class='text-right'>Prix TTC</th>
                <th class='text-right'>Total TTC</th>
            </tr>
        </thead>
        <tbody>";

foreach ($articles_panier as $article) {
    $total_ligne = $article['prix_ttc'] * $article['quantite'];
    $html .= "
            <tr>
                <td>" . htmlspecialchars($article['nom_produit']) . "</td>
                <td class='text-center'>{$article['quantite']}</td>
                <td class='text-right'>" . number_format($article['prix_unitaire_ht'], 2, ',', ' ') . " €</td>
                <td class='text-center'>{$article['taux_tva']}%</td>
                <td class='text-right'>" . number_format($article['prix_ttc'], 2, ',', ' ') . " €</td>
                <td class='text-right'>" . number_format($total_ligne, 2, ',', ' ') . " €</td>
            </tr>";
}

$html .= "
        </tbody>
    </table>
    
    <div class='totals'>
        <table>
            <tr>
                <td>Total HT</td>
                <td class='text-right'><strong>" . number_format($total_ht, 2, ',', ' ') . " €</strong></td>
            </tr>
            <tr>
                <td>Total TVA</td>
                <td class='text-right'><strong>" . number_format($total_tva, 2, ',', ' ') . " €</strong></td>
            </tr>
            <tr class='total-row'>
                <td><strong>TOTAL TTC</strong></td>
                <td class='text-right'><strong>" . number_format($total_ttc, 2, ',', ' ') . " €</strong></td>
            </tr>
        </table>
    </div>
    
    <div class='footer'>
        <p><strong>Conditions de paiement :</strong> Paiement comptant à réception</p>
        <p><strong>Mention légale :</strong> En cas de retard de paiement, une pénalité de 10% sera appliquée.</p>
        <p>Merci pour votre confiance ! 💙</p>
    </div>
</body>
</html>
";

// configuration et génération du PDF
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Téléchargement du PDF
$nom_fichier = "facture_{$numero_facture}_" . date('Ymd') . ".pdf";
$dompdf->stream($nom_fichier);
?>
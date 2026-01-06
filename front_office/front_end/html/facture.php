<?php
    session_start();
    require 'config.php';
    require 'vendor/autoload.php';

    use Dompdf\Dompdf;

    if (!isset($_SESSION['id_panier'])) {
        die("Accès interdit");
    }

    /* 🔹 Récupération panier */
    $stmt = $pdo->prepare("
        SELECT pp.quantite, 
               p.nom_produit, 
               p.prix_unitaire_ht,
               t.taux AS taux_tva,
               ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc
        FROM panier_produit pp
        JOIN produit p ON pp.id_produit = p.id_produit
        LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
        WHERE pp.id_panier = :id_panier
    ");
    $stmt->execute([':id_panier' => $_SESSION['id_panier']]);
    $articles_panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* 🔹 Calculs */
    $total_ht = 0;
    $total_ttc = 0;
    $taxe = 0;

    foreach ($articles_panier as $article) {
        $total_ht += $article['prix_unitaire_ht'] * $article['quantite'];
        $total_ttc += $article['prix_ttc'] * $article['quantite'];
        $taxe += ($article['prix_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
    }

    /* 🔹 HTML facture */
    $html = "
    <h1>Facture</h1>
    <p>Numéro : FACT-" . uniqid() . "</p>
    <p>Date : " . date('d/m/Y') . "</p>

    <table border='1' width='100%' cellpadding='6' cellspacing='0'>
    <tr>
        <th>Produit</th>
        <th>Prix HT</th>
        <th>Quantité</th>
        <th>Total TTC</th>
    </tr>
    ";

    foreach ($articles_panier as $article) {
        $html .= "
        <tr>
            <td>{$article['nom_produit']}</td>
            <td>{$article['prix_unitaire_ht']} €</td>
            <td>{$article['quantite']}</td>
            <td>" . ($article['prix_ttc'] * $article['quantite']) . " €</td>
        </tr>
        ";
    }

    $html .= "
    <tr>
        <td colspan='3'>Total HT</td>
        <td>{$total_ht} €</td>
    </tr>
    <tr>
        <td colspan='3'>TVA</td>
        <td>{$taxe} €</td>
    </tr>
    <tr>
        <td colspan='3'><strong>Total TTC</strong></td>
        <td><strong>{$total_ttc} €</strong></td>
    </tr>
    </table>
    ";

    /*PDF */
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();

    $dompdf->stream(
        "facture_" . date('Ymd') . ".pdf",
        ["Attachment" => true]
    );
    exit;
?>
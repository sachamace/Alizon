<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';
    $stmt = $pdo->query("SELECT version();");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte</title>
    <meta name="description" content="Ceci est le profil  du compte de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <style>
        main {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f7f6;
            margin: 0;
            margin-top: 60px;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            margin-top: 100px;
            background: #ffffff;
            padding: 40px;
            padding-top: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 40px;
        }
        h2 {
            color: #2980b9;
            margin-top: 40px;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 10px;
        }
        h3 {
            color: #2c3e50;
            margin-top: 25px;
            font-size: 1.1em;
        }
        p, ul {
            margin-bottom: 15px;
            color: #555;
        }
        li {
            margin-bottom: 10px;
        }
        footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #95a5a6;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header>
        <?php include 'header.php'?>
    </header>
    <main>
        <div class="container">
            <h2>Conditions Générales d’Utilisation (CGU)</h2>
            <p><em>Les présentes CGU régissent l'utilisation de la marketplace Alizon par tout utilisateur (visiteur ou membre inscrit).</em></p>

            <h3>Objet</h3>
            <p>Les CGU ont pour vocation de définir les règles d’accès et d’usage du site, les droits et obligations des utilisateurs et ceux de Alizon. L’accès et l’utilisation du site impliquent l’acceptation sans réserve des présentes CGU.</p>

            <h3>Accès au service et Inscription</h3>
            <ul>
                <li>L’accès à la marketplace est libre pour la consultation. L'utilisation de certaines fonctionnalités (achat, vente) nécessite l’inscription et la création d’un compte utilisateur.</li>
                <li>L’utilisateur s’engage à fournir des informations exactes, complètes et à jour lors de l'inscription et à les maintenir. Alizon se réserve le droit de suspendre ou de résilier un compte en cas d'informations fausses ou incomplètes.</li>
            </ul>

            <h3>Utilisation de la marketplace - Rôle d’intermédiaire</h3>
            <ul>
                <li>Alizon agit <strong>exclusivement en qualité d’intermédiaire technique</strong> permettant la mise en relation entre vendeurs et acheteurs. Alizon ne prend pas part aux contrats de vente conclus entre eux.</li>
                <li>Alizon n'est pas partie au contrat de vente et ne saurait être tenue responsable des manquements des vendeurs ou des acheteurs à leurs obligations respectives.</li>
            </ul>

            <h3>Comportements interdits</h3>
            <p>Sont notamment proscrits : la fraude, l'usurpation d’identité, la diffusion de contenus illicites (injures, diffamation, apologie de crimes, contrefaçon, violation de droits de propriété intellectuelle), l'utilisation de multi-comptes non autorisés, l'atteinte au bon fonctionnement technique de la plateforme.</p>

            <h3>Responsabilité de la plateforme</h3>
            <p>Alizon n’est pas responsable :</p>
            <ul>
                <li>Des actes, déclarations ou omissions des utilisateurs et des vendeurs tiers.</li>
                <li>Des litiges relatifs aux produits, à leur qualité, leur conformité ou leur livraison. <strong>Le vendeur est seul responsable de ses produits.</strong></li>
                <li>Des interruptions temporaires ou des dysfonctionnements du service, quelle qu'en soit la cause (maintenance, cas de force majeure, problèmes techniques tiers). La plateforme s'engage à rétablir le service dans les meilleurs délais.</li>
            </ul>

            <h2>Conditions Générales de Vente (CGV)</h2>

            <h3>Processus de commande</h3>
            <p>Le processus d’achat est détaillé, comprenant les étapes suivantes : sélection du produit, validation du panier, identification/inscription, choix de l'adresse de livraison, sélection du mode de paiement, acceptation des CGV du vendeur (le cas échéant) et de la marketplace, confirmation finale de la commande. L’acheteur reçoit une confirmation de commande par email récapitulant les détails de la transaction.</p>

            <h3>Prix et paiements</h3>
            <p>Les prix des produits sont affichés par les vendeurs en euros et s'entendent toutes taxes comprises (TTC), hors frais de livraison éventuels. Les modalités de paiement acceptées (carte bancaire, PayPal, etc.) sont indiquées. Les transactions sont sécurisées par un prestataire de paiement certifié.</p>

            <h3>Livraison</h3>
            <p>Les délais, les modes de transport et les transporteurs sont définis par le vendeur pour chaque produit. Le vendeur est responsable de l’acheminement des produits jusqu'à la livraison. Les frais de livraison sont à la charge de l’acheteur, sauf indication contraire.</p>

            <h3>Droit de rétractation</h3>
            <p>Conformément à l’article L. 221-18 du Code de la consommation, l'acheteur dispose, en principe, d'un délai de quatorze (14) jours pour exercer son droit de rétractation sans avoir à justifier de motifs ni à payer de pénalités, à l'exception des frais de retour.</p>
            <ul>
                <li><strong>Exceptions :</strong> Ce droit ne s'applique pas, notamment : aux produits confectionnés selon les spécifications du consommateur ou nettement personnalisés ; aux biens périssables ; aux biens descellés après la livraison et qui ne peuvent être renvoyés pour des raisons d'hygiène ou de protection de la santé ; aux contenus numériques non fournis sur un support matériel dont l'exécution a commencé avec l'accord préalable exprès du consommateur.</li>
            </ul>

            <h3>Garanties légales</h3>
            <p>Les produits vendus par les vendeurs sont soumis aux garanties légales de conformité (articles L. 217-4 et suivants du Code de la consommation) et des vices cachés (articles 1641 et suivants du Code civil). L'acheteur doit s'adresser directement au vendeur pour l'application de ces garanties.</p>

            <div>
                <h3>Rôle de la plateforme (Marketplace)</h3>
                <p>Il est expressément précisé que <strong>Alizon n'est pas le vendeur</strong> et que le vendeur tiers est le seul juridiquement responsable de la vente, de la bonne exécution de la commande, des garanties légales, du service après-vente et du respect des obligations légales incombant aux professionnels de la vente à distance. Alizon décline toute responsabilité à ce titre.</p>
            </div>
    </main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
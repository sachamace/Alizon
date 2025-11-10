<?php 
    $host = "localhost";
    $pass = "";
    $user = "";
    $dbname = "bigou";


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <main class="commande-container">
        <header class="commande-header">
            <button class="back-btn">←</button>
            <h1>Passer la commande</h1>
        </header>

        <section class="bloc paiement">
            <h2>Mode de paiement</h2>
            <div class="options">
                <label>
                    <input type="radio" name="paiement" value="carte">
                    <span>Payer par carte bleue</span>
                </label>
                <label>
                    <input type="radio" name="paiement" value="paypal">
                    <span>Payer par PayPal</span>
                </label>
            </div>
        </section>

        <section class="bloc adresse">
            <h2>Adresse de livraison</h2>
            <p><strong>Adresse</strong><br><?= htmlspecialchars($commande['adresse']); ?></p>
            <p><strong>Code postal</strong><br><?= htmlspecialchars($commande['code_postal']); ?></p>
            <p><strong>Ville</strong><br><?= htmlspecialchars($commande['ville']); ?></p>
            <p><strong>Région</strong><br><?= htmlspecialchars($commande['region']); ?></p>
        </section>

        <section class="bloc recap">
            <h2>Récapitulatif du prix</h2>
            <p>Article <span><?= number_format($commande['prix_article'], 2, ',', ' '); ?>€</span></p>
            <p>Livraison <span><?= number_format($commande['prix_livraison'], 2, ',', ' '); ?>€</span></p>
            <p>Réduction <span>-<?= number_format($commande['reduction'], 2, ',', ' '); ?>€</span></p>
            <p class="total">Total <span><?= number_format($commande['total'], 2, ',', ' '); ?>€</span></p>
            <button class="payer-btn">Payer cette commande</button>
        </section>

        <footer class="navbar">
            <button><i class="fa-regular fa-house"></i></button>
            <button>🔍</button>
            <button>🛒</button>
            <button>🔔</button>
            <button>👤</button>
        </footer>
    </main>
</body>
</html>

<?php 
        require_once "header.php";
?>
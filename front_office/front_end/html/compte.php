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
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
</head>
<body>
    <header class = "disabled">
        <?php include 'header.php'?>
    </header>
    <!-- VERSION DESKTOP -->
    <main class="main_compte compte--desktop">
        <h1 class="titre_compte">Votre Compte</h1>
        <div class="cards_container">
            <a href="consulterProfilClient.php" class="card">
                <img src="../assets/images/info.png" alt="">
                <div class="text">
                    <h3>Vos infos</h3>
                    <p>Consulter et modifier mes données, adresse, nom etc…</p>
                </div>
            </a>

            <a href="#" class="card disabled">
                <img src="../assets/images/commande.png" alt="">
                <div class="text">
                    <h3>Vos commandes</h3>
                    <p>Voir, retourner ou acheter à nouveau les articles que vous avez commandé</p>
                </div>
            </a>

            <a href="deconnecter.php" class="card">
                <img src="../assets/images/logout.png" alt="">
                <div class="text">
                    <h3>Se déconnecter</h3>
                    <p>Déconnecter vous de votre compte</p>
                </div>
            </a>

            <a href="#" class="card disabled">
                <img src="../assets/images/poubelle.png" alt="">
                <div class="text">
                    <h3>Supprimer vos données</h3>
                    <p>Supprimer toutes les données vous concernant enregistrées sur le site</p>
                </div>
            </a>
        </div>
    </main>

    <!-- VERSION MOBILE 428px -->
    <main class="compte__mobile compte--mobile">
        <div class="compte__container">
            <div class="compte__header">Votre compte</div>

            <a href="consulterProfilClient.php" class="compte__button">
                <img src="../assets/images/info.png" class="compte__image">
                <h3>Vos infos</h3>
                <h3>›</h3>
            </a>

            <a class="compte__button disabled">
                <img src="../assets/images/commande.png" class="compte__image">
                <h3>Vos commandes</h3>
                <h3>›</h3>
            </a>

            <a href="deconnecter.php" class="compte__button">
                <img src="../assets/images/logout.png" class="compte__image">
                <h3>Se déconnecter</h3>
                <h3>›</h3>
            </a>

            <a class="compte__button disabled">
                <img src="../assets/images/poubelle.png" class="compte__image">
                <h3>Supprimer vos données</h3>
                <h3>›</h3>
            </a>
        </div>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
</body>
</html>

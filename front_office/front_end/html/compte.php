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
    <title>Votre compte</title>
    <meta name="description" content="Ceci est l'accueil de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body class="body__compte">
    <header class="header__compte">
        <nav>
            <nav>
                <a href="accueil.php"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php"><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
            </nav>
            <nav>
                <div>
                    <a href="produitTerroir.php">Produit du Terroir</a>
                    <a href="modeBretonne.php">Mode Bretonne</a>
                    <a href="">Artisanat Local</a>
                    <a href="">Décoration Intérieure</a>
                    <a href="">Epicerie FIne</a>
                </div>
                <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
                <?php else: ?><a href="seconnecter.php"></i>S'identifier</a>
                <?php endif; ?>
            </nav>

        </nav>
    </header>
    <div class="mobile-frame">
        <div class="account-container">
            <h2 class = "h2__compte">Votre compte</h2>
            <hr class = "hr_compte">
            <div class="btn-list">
                <div class="btn">Vos infos ➜</div>
                <div class="btn">Vos commandes ➜</div>
                <div class="btn">Se déconnecter ➜</div>
                <div class="btn">Supprimer vos données ➜</div>
            </div>
        </div>
    </div>
</body>
</html>

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">
</head>
<body>
    <header class="disabled">
        <nav>
            <nav>
                <a href="accueil.php"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input disabled type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php"><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
            </nav>
            <nav>
                <div>
                <?php
                // On récupère tout le contenu de la table 
                $categorie = $pdo->query('SELECT * FROM categorie');
                // On affiche chaque entrée une à une
                while ($cat = $categorie->fetch()){ 
                    $libelle = urlencode($cat['libelle']); 
                    ?>
                    <a href="accueil.php?categorie=<?php echo $libelle; ?>">
                        <?php echo $cat['libelle']; ?>
                    </a>
                <?php } ?>
                </div>
                <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
                <?php else: ?><a href="seconnecter.php"></i>S'identifier</a>
                <?php endif; ?>
            </nav>
        </nav>
    </header>
    <main>
        <div class="compte__container">
            <div class="compte__header">Votre compte</div>

            <a class="compte__button ">
                <img src="../assets/images/info.png" class ="compte__image">
                <span>Vos infos</span>
                <span>›</span>
            </a>

            <a class="compte__button disabled">
                <img src="../assets/images/commande.png" class ="compte__image">
                <span>Vos commandes</span>
                <span>›</span>
            </a>

            <a class="compte__button">
                <img src="../assets/images/logout.png" class ="compte__image">
                <span>Se déconnecter</span>
                <span>›</span>
            </a>

            <a class="compte__button disabled">
                <img src="../assets/images/poubelle.png" class ="compte__image">
                <span>Supprimer vos données</span>
                <span>›</span>
            </a>
        </div>
    </main>
    <footer class="footer mobile">
        <a href="accueil.php"><i class="fa-solid fa-house icone"></i></a>
        <a class="recherche" href="recherche.php"><i class="fa-solid fa-magnifying-glass icone"></i></a>
        <a href="panier.php"><i class="fa-solid fa-cart-shopping icone"></i></a>
        <a class="notif" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
        <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i></a>
                <?php else: ?><a href="seconnecter.php"><i class="fa-regular fa-user icone"></i></a>
                <?php endif; ?>
    </footer>
</body>
</html>

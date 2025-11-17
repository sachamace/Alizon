<?php
    include 'config.php';
    include 'sessionindex.php';
    $stmt = $pdo->query("SELECT version();");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <title>Accueil - MarketPlace</title>
    <meta name="description" content="Ceci est l'accueil de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">

</head>
<body>
    <header>
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
    <div class="div__catalogue">
        <?php
            // On récupère tout le contenu de la table produit
            $reponse = $pdo->query('SELECT * FROM produit');
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ 
                if ($donnees['categorie'] == $_GET['categorie'] || !(isset($_GET['categorie']))){?>
            <a href="produitdetail.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article>
                    <img src="../assets/images/Tel.jpg" alt="Image du produit" width="350" height="225">
                    <h2 class="titre"><?php echo htmlentities($donnees['nom_produit']) ?></h2>
                    <p class="description"><?php echo htmlentities($donnees['description_produit']) ?></p>
                    <p class="prix"><?php echo htmlentities($donnees['prix_ttc'].'€') ?></p>
                </article>
            </a>
                
            <?php
                }
            }
            $reponse->closeCursor(); // Termine le traitement de la requête
        ?>
    </div>
</body>
</html>
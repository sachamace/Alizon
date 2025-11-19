<?php
    include 'front_office/front_end/html/config.php';
    include 'front_office/front_end/html/sessionindex.php';
    $stmt = $pdo->query("SELECT version();");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - MarketPlace</title>
    <meta name="description" content="Ceci est l'accueil de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="front_office/front_end/assets/csss/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">

</head>
<body>
    <header>
        <nav>
            <nav>
                <a href="index.php"><img src="front_office/front_end/assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input disabled type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="front_office/front_end/html/panier.php" data-panier><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
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
                    <a href="index.php?categorie=<?php echo $libelle; ?>">
                        <?php echo $cat['libelle']; ?>
                    </a>
                <?php } ?>
                </div>
                <?php if($isLogged):?><a href="front_office/front_end/html/compte.php"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
                <?php else: ?><a href="front_office/front_end/html/seconnecter.php"></i>S'identifier</a>
                <?php endif; ?>
            </nav>
        </nav>
    </header>
    <div class="div__catalogue">
        <?php
            

            $stmt = $pdo->query('SELECT p.*, m.chemin_image 
                FROM produit p 
                LEFT JOIN media_produit m ON p.id_produit = m.id_produit');
            // On récupère tout le contenu de la table produit
            $reponse = $pdo->query('SELECT * FROM produit');
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ 
                if ($donnees['categorie'] == $_GET['categorie'] || !(isset($_GET['categorie']))){?>
            <a href="front_office/front_end/html/produitdetail.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article>
                    <?php
                    $requete_img = $pdo->prepare('SELECT * FROM media_produit WHERE id_produit = :id_produit');
                    $requete_img->execute([':id_produit' => $donnees['id_produit']]);
                    $img = $requete_img->fetch();
                    echo $img['chemin_image'];
                    ?>
                    <img src="<?php echo htmlentities($img['chemin_image']); ?>" alt="Image du produit" width="350" height="225">
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
    <footer class="footer mobile">
        <a href="index.php"><i class="fa-solid fa-house icone"></i></a>
        <a class="recherche disabled" href="recherche.php"><i class="fa-solid fa-magnifying-glass icone"></i></a>
        <a href="front_office/front_end/html/panier.php"><i class="fa-solid fa-cart-shopping icone"></i></a>
        <a class="notif disabled" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
        <?php if($isLogged):?><a href="front_office/front_end/html/compte.php"><i class="fa-regular fa-user icone"></i></a>
                <?php else: ?><a href="front_office/front_end/html/seconnecter.php"><i class="fa-regular fa-user icone"></i></a>
                <?php endif; ?>
    </footer>
</body>
</html>
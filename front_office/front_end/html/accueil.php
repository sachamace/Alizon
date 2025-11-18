<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <input disabled type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php"><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
            </nav>
            <nav>
                <div>
                    <a href="produitTerroir.html">Produit du Terroir</a>
                    <a href="modeBretonne.html">Mode Bretonne</a>
                    <a href="">Artisanat Local</a>
                    <a href="">Décoration Intérieure</a>
                    <a href="">Epicerie FIne</a>
                <?php
                // On récupère tout le contenu de la table produit
                $categorie = $pdo->query('SELECT * FROM categorie');
                // On affiche chaque entrée une à une
                while ($cat = $categorie->fetch()){ ?>
                    <a href="<?php echo $cat['libelle']?>.php"><?php echo $cat['libelle']?></a>
                <?php } ?>
                </div>
                <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
                <?php else: ?><a href="seconnecter.php"></i>S'identifier</a>
                <?php endif; ?>
            </nav>
        </nav>
    </header>
    <div class="div__catalogue">
        <?php
            include 'config.php';

            $stmt = $pdo->query('SELECT p.*, m.chemin_image 
                FROM produit p 
                LEFT JOIN media_produit m ON p.id_produit = m.id_produit');
            echo "<pre>";
            print_r($stmt->fetch());
            echo "</pre>";
            // On récupère tout le contenu de la table produit
            $reponse = $pdo->query('SELECT * FROM produit');
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ 
                if ($donnees['categorie'] == $_GET['categorie'] || !(isset($_GET['categorie']))){?>
            <a href="produitdetail.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article>
                    <img src="<?php echo htmlentities($donnees['chemin_image']); ?>" alt="Image du produit" width="350" height="225">
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
        <a><img src="../assets/images/Home-Icon-by-arus-2.jpg"></a>
        <a><img src="../assets/images/magnifying-glass-solid-full (1).svg"></a>
        <a href="panier.php"><i class="fa-solid fa-cart-shopping icone" ></i></a>
        <a class="notif" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
        <a href="consulterProfilClient.php"><i class="fa-regular fa-user icone"></i></a>
    </footer>
    <footer class="footer tablette">
        <article>
            <h3>Informations légale</h3>
            <p>Mention légales</p>
            <p>Condition general de vente</p>
            <p>Politique de confidentialité</p>
            <p>Droit de rétraction</p>
            <p>Gestion des cookies</p>
        </article>
        <article>
            <h3>Besoin d’aide ?</h3>
            <p>Service client</p>
            <p>Suivi de commande</p>
            <p>Retours & remboursements</p>
        </article>
        <article>
            <h3>À propos</h3>
            <p>Qui sommes-nous ?</p>
            <p>Notre histoire</p>
            <p>Engagements & valeurs</p>
            <p>Recrutement</p>
        </article>
    </footer>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
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
                <a href="accueil.html"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.html" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.html"><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
            </nav>
            <nav>
                <div>
                    <a href="accueil.html">Accueil</a>
                    <a href="produitTerroir.html">Produit du Terroir</a>
                    <a href="modeBretonne.html">Mode Bretonne</a>
                    <a href="">Artisanat Local</a>
                    <a href="">Décoration Intérieure</a>
                    <a href="">Epicerie FIne</a>
                </div>
                <a href="seconnecter.html"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
            </nav>
        </nav>
    </header>
    <div>
        <?php
            $host = '127.0.0.1';
            $port = '5432';
            $dbname = 'postgres';
            $user = 'postgres';
            $password = 'bigouden08';

            try {
                // connexion a la base de donnée
                $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                die("❌ Erreur de connexion : " . $e->getMessage());
            }
            // On récupère tout le contenu de la table produit
            $reponse = $pdo->query('SELECT * FROM produit');
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ ?>
            <a href="produit.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article>
                    <img src="../assets/images/Tel.jpg" alt="Image du produit" width="350" height="225">
                    <h2 class="titre"><?php echo htmlentities($donnees['nom_produit']) ?></h2>
                    <p class="description"><?php echo htmlentities($donnees['description_produit']) ?></p>
                    <p class="prix"><?php echo htmlentities($donnees['prix_ttc'].'€') ?></p>
                </article>
            </a>
                
            <?php
            }
            $reponse->closeCursor(); // Termine le traitement de la requête
        ?>
    </div>
</body>
</html>
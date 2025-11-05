<?php
    include 'config.php';
    try {

        $stmt2 = $pdo->query("SELECT * FROM produit;");
        $resultats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $requete_articles = $pdo->query("SELECT * FROM panier_produit WHERE id_panier = 2;");
        $articles = $requete_articles->fetchAll();

    } catch (PDOException $e) {
        echo "Erreur SQL : " . $e->getMessage();
    }
   
?>


<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Récapitulatif du panier</title>
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
    <main class="main_panier">
        <section>
            <?php
                // On suppose que $articles contient les lignes de panier_produit
                // et que tu veux afficher les infos du produit lié
                $prixtotal = 0;
                $taxe = 0;
                $prixht = 0;
    
                foreach ($articles as $article) {
                    // On récupère les infos du produit associé
                    $id_produit = (int)$article['id_produit'];
                    $produit = $pdo->query("SELECT * FROM produit WHERE id_produit = $id_produit")->fetch(PDO::FETCH_ASSOC);

                    // Vérifie qu'on a bien trouvé le produit
                    
                    if ($produit) {
                        $prixtotal += $produit["prix_ttc"] * $article['quantite'];
                        $taxe += $produit['prix_unitaire_ht'] * $produit['taux_tva'] *$article['quantite'];
                        $prixht += $produit['prix_unitaire_ht'] * $article['quantite'];
                        echo '
                        <article>
                            <img src="../assets/images/Tel.jpg" alt="' . htmlspecialchars($produit['nom_produit']) . '">
                            <div class="panier_info">
                                <h4>' . htmlspecialchars($produit['nom_produit']) . '</h4>
                                <p>Prix : ' . number_format($produit['prix_ttc'], 2, ',', ' ') . ' €</p>
                                <p>Stock disponible : ' . htmlspecialchars($produit['stock_disponible']) . '</p>
                                <p>' . htmlspecialchars($produit['description_produit']) . '</p>
                                <div class="panier_quantite">
                                    <span>-</span>
                                    <p>' . htmlspecialchars($article['quantite']) . '</p>
                                    <span>+</span>
                                </div>
                            </div>
                        </article>';
                    }
                }
                ?>
        </section>
        <aside>
            <?php
                echo '
                <h4>Prix total: ' . htmlspecialchars($prixtotal) . '€</h4>
                <p>prix hors taxe : ' . htmlspecialchars($prixht) . '€ <br>
                taxe : ' . htmlspecialchars($taxe) . '€ </p>
                <a>Passer au paiement</a> 
                '
            ?>
            
        </aside>
    </main>
    <footer>

    </footer>
</body>

</html>
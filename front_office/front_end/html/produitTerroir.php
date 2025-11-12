<?php
include 'config.php';
try {
    
    $stmt2 = $pdo->query("SELECT * FROM produit WHERE id_produit = 5;"); // attention pas dynamique
    $infos = $stmt2->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage();
}

$id_produit = 5; // depend du paramètre
$id_panier = 2; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique

$stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
$stmt_stock->execute([':id_produit' => $id_produit]);
$stock_dispo = (int) $stmt_stock->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'];
    $id_produit = 5; // depend du paramètre
    $id_panier = 2; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique

    if ($action === 'panier') {
        $stmt = $pdo->prepare('SELECT * FROM panier_produit WHERE id_produit = :id_produit AND id_panier = :id_panier');
        $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
        $verif = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
        $stmt_stock->execute([':id_produit' => $id_produit]);
        $stock_dispo = (int) $stmt_stock->fetchColumn();

        if ($verif) {
            $stmt_info = $pdo->prepare("SELECT pp.quantite
                FROM panier_produit pp
                WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
            ");
            $stmt_info->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $quantite_actuelle = (int) $info['quantite'];
            if ($quantite_actuelle < $stock_dispo) {
                $augmente = "UPDATE panier_produit 
                SET quantite = quantite + 1 
                WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $requete_augmente = $pdo->prepare($augmente);
                $requete_augmente->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            }
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
            }
        }

        }else if ($action === 'payer') {
        $stmt = $pdo->prepare('SELECT * FROM panier_produit WHERE id_produit = :id_produit AND id_panier = :id_panier');
        $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
        $verif = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($verif) {
            $stmt_info = $pdo->prepare("SELECT pp.quantite
                FROM panier_produit pp
                WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
            ");
            $stmt_info->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $quantite_actuelle = (int) $info['quantite'];
            if ($quantite_actuelle < $stock_dispo) {
                $augmente = "UPDATE panier_produit 
                SET quantite = quantite + 1 
                WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $requete_augmente = $pdo->prepare($augmente);
                $requete_augmente->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            }
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
                
            }
        }
        header('Location: commandes.html');
        exit(); // très important pour arrêter le scrip
        }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity=""
        crossorigin="anonymous">
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
                <a href="panier.html"><i class="fa-solid fa-cart-shopping icone"></i>Panier</a>
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
    <main class="main_produit">
        <section class="fiche-produit">

            <div class="fiche-container">
                <div class="images-produit">
                    <img src="../assets/images/Tel.jpg" alt="Kouign Amann" class="image-principale">

                    <div class="miniatures">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 1">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 2">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 3">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 4">
                    </div>
                </div>

                <div class="infos-produit">
                    <div class="titre-ligne">
                        <h1><?= htmlspecialchars($infos['nom_produit']) ?></h1>
                        <i class="fa-regular fa-heart"></i>
                    </div>
                    

                    <div class="prix">
                        <span class="prix-valeur"><?= number_format($infos['prix_ttc'], 2, ',', ' ') ?>€</span>
                    </div>


                    <div class="stock-avis">
                        <span class="stock-dispo" style="color: <?= $stock_dispo > 0 ? 'green' : 'red' ?>">
                            <?php
                                if ($stock_dispo > 0) {
                                    echo 'stock : ' . $stock_dispo .'';
                                }
                                else {
                                    echo 'Rupture de stock';
                                }
                            ?>
                        </span>

                        <div class="avis">
                            <span class="etoiles">★★★★☆</span>
                            <span class="note">4/5</span>
                            <a href="#">Voir les 51 avis</a>
                        </div>
                    </div>

                    <p class="description">
                        <?= htmlspecialchars($infos['description_produit']) ?>
                    </p>



                    <div class="boutons">
                            <?php echo '
                            <form action="" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="panier">
                                <button type="submit">Ajouter au panier</button>
                            </form>
                            
                            <form action="" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="payer">
                                <button type="submit">Payer maintenant</button>
                            </form>' ?>
                    </div>
                </div>
            </div>
        </section>
        <div id="popup-image" class="popup">
            <span class="close">&times;</span>
            <img class="popup-content" id="popup-img" src="">
        </div>
    </main>

    <script> // PARTI JAVASCRIPT
        // Sélection des éléments
        const miniatures = document.querySelectorAll('.miniatures img');
        const popup = document.getElementById('popup-image');
        const popupImg = document.getElementById('popup-img');
        const closeBtn = document.querySelector('.popup .close');

        // Quand on clique sur une miniature
        miniatures.forEach(img => {
            img.addEventListener('click', () => {
                popup.style.display = 'block';
                popupImg.src = img.src; // affiche la bonne image
            });
        });

        // Quand on clique sur la croix
        closeBtn.addEventListener('click', () => {
            popup.style.display = 'none';
        });

        // Quand on clique en dehors de l’image
        popup.addEventListener('click', (event) => {
            if (event.target === popup) {
                popup.style.display = 'none';
            }
        });
    </script>
</body>
<?php
include 'config.php';
include 'session.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produit = (int) $_POST['id_produit'];
    $action = $_POST['action'];
    $id_panier = $_SESSION['id_panier']; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique

    // Récupérer la quantité actuelle et le stock disponible

    if ($action === 'vider_panier') {
            $stmt = $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = :id_panier");
            $stmt->execute([':id_panier' => $id_panier]);
        }

    $stmt_info = $pdo->prepare("
            SELECT pp.quantite, p.stock_disponible
            FROM panier_produit pp
            JOIN produit p ON pp.id_produit = p.id_produit
            WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
        ");
    $stmt_info->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        $quantite_actuelle = (int) $info['quantite'];
        $stock_dispo = (int) $info['stock_disponible'];

        // === Gestion des actions ===
        if ($action === 'plus') {
            if ($quantite_actuelle < $stock_dispo) {
                $sql = "UPDATE panier_produit 
                            SET quantite = quantite + 1 
                            WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            }
            // sinon : on ne fait rien, quantité déjà au max
        } elseif ($action === 'moins') {
            if ($quantite_actuelle > 1) {
                $sql = "UPDATE panier_produit 
                            SET quantite = quantite - 1 
                            WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            }
            // sinon : on ne descend pas en dessous de 1
        } elseif ($action === 'supprimer_produit') {
            $stmt = $pdo->prepare("
                    DELETE FROM panier_produit 
                    WHERE id_produit = :id_produit AND id_panier = :id_panier
                ");
            $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
        }
    }

    // Recharge la page pour voir la quantité mise à jour
    header("Location: " . $_SERVER['PHP_SELF']);
    echo "<script>
        window.location.href = '" . $_SERVER['PHP_SELF'] . "';
    </script>";

    exit();
}


try {

    $stmt2 = $pdo->query("SELECT * FROM produit;");
    $resultats = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $requete_articles = $pdo->prepare("SELECT * FROM panier_produit WHERE id_panier = ? ORDER BY id_produit ASC;");
    $requete_articles->execute([$_SESSION['id_panier']]);
    $articles = $requete_articles->fetchAll();

} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage();
}

?>


<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page du panier - Compte CLient</title>
    <meta name="description" content="Page du panier , l'ensemble des article que tu as mis coté client !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
</head>

<body>
    <header>
        <nav>
            <nav>
                <a href="/index.php"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><svg width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><<path d="M224 0c-13.3 0-24 10.7-24 24l0 9.7C118.6 45.3 56 115.4 56 200l0 14.5c0 37.7-10 74.7-29 107.3L5.1 359.2C1.8 365 0 371.5 0 378.2 0 399.1 16.9 416 37.8 416l372.4 0c20.9 0 37.8-16.9 37.8-37.8 0-6.7-1.8-13.3-5.1-19L421 321.7c-19-32.6-29-69.6-29-107.3l0-14.5c0-84.6-62.6-154.7-144-166.3l0-9.7c0-13.3-10.7-24-24-24zM392.4 368l-336.9 0 12.9-22.1C91.7 306 104 260.6 104 214.5l0-14.5c0-66.3 53.7-120 120-120s120 53.7 120 120l0 14.5c0 46.2 12.3 91.5 35.5 131.4L392.4 368zM156.1 464c9.9 28 36.6 48 67.9 48s58-20 67.9-48l-135.8 0z"/></svg></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input disabled type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php" data-panier><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>Panier</a>
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
                    <a href="../../../index.php?categorie=<?php echo $libelle; ?>">
                        <?php echo $cat['libelle']; ?>
                    </a>
                <?php } ?>
                </div>
                <a href="compte.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg>Compte</a>
            </nav>
        </nav>
    </header>
    <main class="main_panier">
        <section>
            <?php
            // On suppose que $articles contient les lignes de panier_produit
            // et que on veux afficher les infos du produit lié
            $prixtotal = 0;
            $taxe = 0;
            $prixht = 0;

            foreach ($articles as $article) {
                // On récupère les infos du produit associé
                $id_produit = (int) $article['id_produit'];
                $produit = $pdo->query("SELECT * FROM produit WHERE id_produit = $id_produit")->fetch(PDO::FETCH_ASSOC);

                // Vérifie qu'on a bien trouvé le produit
            
                if ($produit) {
                    $prixtotal += $produit["prix_ttc"] * $article['quantite'];
                    $taxe += ($produit['prix_ttc'] - $produit['prix_unitaire_ht']) * $article["quantite"];
                    $prixht += $produit['prix_unitaire_ht'] * $article['quantite'];
                    $requete_img = $pdo->prepare('SELECT * FROM media_produit WHERE id_produit = :id_produit');
                    $requete_img->execute([':id_produit' => $id_produit]);
                    $img = $requete_img->fetch();
                    
                    echo '
                        <article>
                            <img src="' . $img["chemin_image"] .'" alt="' . htmlspecialchars($produit['nom_produit']) . '">
                            <div class="panier_info">
                                <h4>' . htmlspecialchars($produit['nom_produit']) . '</h4>
                                <p>Prix : ' . number_format($produit['prix_ttc'], 2, ',', ' ') . ' €</p>
                                <p>Stock disponible : ' . htmlspecialchars($produit['stock_disponible']) . '</p>
                                <p>' . htmlspecialchars($produit['description_produit']) . '</p>
                                <div class="panier_bottom">
                                    <div class="panier_quantite">
                                        <form action="" method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="moins">
                                            <input type="hidden" name="id_produit" value="' . $produit["id_produit"] . '">
                                            <button type="submit">-</button>
                                        </form>

                                        <p>' . htmlspecialchars($article["quantite"]) . '</p>

                                        <form action="" method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="plus">
                                            <input type="hidden" name="id_produit" value="' . $produit["id_produit"] . '">
                                            <button type="submit">+</button>
                                        </form>
                                    </div>
                                    <div class="panier_actions">
                                        <a href="produitdetail.php?article=' . $produit["id_produit"] . '" class="en_savoir_plus">En savoir plus</a>

                                        <form class="supprimer-produit" method="post">
                                            <input type="hidden" name="action" value="supprimer_produit">
                                            <input type="hidden" name="id_produit" value="' . $produit["id_produit"] . '">
                                            <button type="submit" class="btn-supprimer">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </div>  
                        </article>';
                }
            }
            ?>
        </section>
        <form class="vider-panier" method="post" style="text-align:center; margin-top: 2.5em;">
            <input type="hidden" name="action" value="vider_panier">
            <input type="hidden" name="id_produit" value="2">
            <button type="submit" class="btn-vider">Vider le panier</button>
        </form>
        <aside>
            <?php
            echo '
                <h4>Prix total: ' . number_format($prixtotal, 2, ',', ' ') . '€</h4>
                <p>prix hors taxe : ' . number_format($prixht, 2, ',', ' ') . '€ <br>
                taxe : ' . number_format($taxe, 2, ',', ' ') . '€ </p>
                <a href="paiement.php">Passer au paiement</a> 
                '
                ?>

        </aside>
    </main>
    <footer class="footer mobile">
        <a href="/index.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M277.8 8.6c-12.3-11.4-31.3-11.4-43.5 0l-224 208c-9.6 9-12.8 22.9-8 35.1S18.8 272 32 272l16 0 0 176c0 35.3 28.7 64 64 64l288 0c35.3 0 64-28.7 64-64l0-176 16 0c13.2 0 25-8.1 29.8-20.3s1.6-26.2-8-35.1l-224-208zM240 320l32 0c26.5 0 48 21.5 48 48l0 96-128 0 0-96c0-26.5 21.5-48 48-48z"/></svg></a>
        <a class="recherche disabled" href="recherche.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg></a>
        <a href="panier.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg></a>
        <a class="notif disabled" href="notification.html"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 0c-13.3 0-24 10.7-24 24l0 9.7C118.6 45.3 56 115.4 56 200l0 14.5c0 37.7-10 74.7-29 107.3L5.1 359.2C1.8 365 0 371.5 0 378.2 0 399.1 16.9 416 37.8 416l372.4 0c20.9 0 37.8-16.9 37.8-37.8 0-6.7-1.8-13.3-5.1-19L421 321.7c-19-32.6-29-69.6-29-107.3l0-14.5c0-84.6-62.6-154.7-144-166.3l0-9.7c0-13.3-10.7-24-24-24zM392.4 368l-336.9 0 12.9-22.1C91.7 306 104 260.6 104 214.5l0-14.5c0-66.3 53.7-120 120-120s120 53.7 120 120l0 14.5c0 46.2 12.3 91.5 35.5 131.4L392.4 368zM156.1 464c9.9 28 36.6 48 67.9 48s58-20 67.9-48l-135.8 0z"/></svg></a>
        <a href="compte.php"><svg width="48" height="48" class="icone" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg></a>
    </footer>
    <script>
    document.addEventListener("DOMContentLoaded", function() {

        // Confirmation pour supprimer un produit
        document.querySelectorAll(".supprimer-produit").forEach(form => {
            form.addEventListener("submit", function(event) {
                event.preventDefault(); // bloque l’envoi
                if (confirm("Voulez-vous vraiment supprimer ce produit du panier ?")) {
                    form.submit(); // envoie seulement si OK
                }
            });
        });

        // Confirmation pour vider tout le panier
        const viderForm = document.querySelector(".vider-panier");
        if (viderForm) {
            viderForm.addEventListener("submit", function(event) {
                event.preventDefault();
                if (confirm("Voulez-vous vraiment vider l'intégralité du panier ?")) {
                    viderForm.submit();
                }
            });
        }

    });
    </script>
</body>

</html>
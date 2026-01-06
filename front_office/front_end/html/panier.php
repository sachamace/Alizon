<?php
include 'config.php';
include 'session.php';
include 'sessionindex.php';
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
    $panierVide = empty($articles);

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
        <?php include 'header.php'?>
    </header>
    <main class="main_panier">
        <?php if ($panierVide) : ?>

        <section class="panier-vide">
            <h2>Votre panier est vide</h2>
            <a href="../../../index.php" class="btn-retour">Retour à la boutique</a>
        </section>

        <?php else : ?>
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
                                    <p>Prix : ' . number_format($produit['prix_ttc'], 2, '.', ' ') . ' €</p>
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
                    <h4>Prix total: ' . number_format($prixtotal, 2, '.', ' ') . '€</h4>
                    <p>prix hors taxe : ' . number_format($prixht, 2, '.', ' ') . '€ <br>
                    taxe : ' . number_format($taxe, 2, '.', ' ') . '€ </p>
                    <a href="paiement.php">Passer au paiement</a> 
                    '
                    ?>

            </aside>
        <?php endif; ?>
    </main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
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
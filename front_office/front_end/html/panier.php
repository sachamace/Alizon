<?php
include 'config.php';
include 'sessionindex.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produit = (int) $_POST['id_produit'];
    $id_produit_favoris = (int) $_POST['id_produit_favoris'];
    $action = $_POST['action'];
    $id_panier = $_SESSION['id_panier'];

    if ($action === 'vider_panier') {
        $stmt = $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = :id_panier");
        $stmt->execute([':id_panier' => $id_panier]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    if ($action === 'ajouter_panier_depuis_fav') {
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_produit = :id_produit AND id_client = :id_client");
        $stmt->execute([':id_produit' => $id_produit_favoris, ':id_client' => $_SESSION['id_client']]);
        $stmt = $pdo->prepare('SELECT * FROM panier_produit WHERE id_produit = :id_produit AND id_panier = :id_panier');
        $stmt->execute([':id_produit' => $id_produit_favoris, ':id_panier' => $id_panier]);
        $verif = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
        $stmt_stock->execute([':id_produit' => $id_produit_favoris]);
        $stock_dispo = (int) $stmt_stock->fetchColumn();

        if ($verif) {
            $stmt_info = $pdo->prepare("SELECT pp.quantite
                FROM panier_produit pp
                WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
            ");
            $stmt_info->execute([':id_produit' => $id_produit_favoris, ':id_panier' => $id_panier]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $quantite_actuelle = (int) $info['quantite'];
            if ($quantite_actuelle < $stock_dispo) {
                $augmente = "UPDATE panier_produit 
                SET quantite = quantite + 1 
                WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $requete_augmente = $pdo->prepare($augmente);
                $requete_augmente->execute([':id_produit' => $id_produit_favoris, ':id_panier' => $id_panier]);
            }
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit_favoris, ":id_panier"=> $id_panier]);
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    else{

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

            if ($action === 'plus') {
                if ($quantite_actuelle < $stock_dispo) {
                    $sql = "UPDATE panier_produit 
                            SET quantite = quantite + 1 
                            WHERE id_produit = :id_produit AND id_panier = :id_panier";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
                }
            } elseif ($action === 'moins') {
                if ($quantite_actuelle > 1) {
                    $sql = "UPDATE panier_produit 
                            SET quantite = quantite - 1 
                            WHERE id_produit = :id_produit AND id_panier = :id_panier";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
                }
            } elseif ($action === 'supprimer_produit') {
                $stmt = $pdo->prepare("
                    DELETE FROM panier_produit 
                    WHERE id_produit = :id_produit AND id_panier = :id_panier
                ");
                $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
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
    <title>Page du panier - Compte Client</title>
    <meta name="description" content="Page du panier, l'ensemble des articles que tu as mis côté client!">
    <meta name="keywords" content="MarketPlace, Shopping, Ventes, Breton, Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
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
                $prixtotal = 0;
                $taxe = 0;
                $prixht = 0;

                foreach ($articles as $article) {
                    $id_produit = (int) $article['id_produit'];
                    
                    // ✅ REQUÊTE CORRIGÉE - 4 CAS DE REMISES
                    $stmt_produit = $pdo->prepare("
                        SELECT p.*, 
                               t.taux AS taux_tva,
                               ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2) AS prix_ttc_sans_remise,
                               r.id_remise,
                               r.nom_remise,
                               r.type_remise,
                               r.valeur_remise,
                               CASE 
                                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'pourcentage' THEN
                                       ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) * (1 - r.valeur_remise / 100) AS NUMERIC), 2)
                                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'fixe' THEN
                                       GREATEST(0, ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) - r.valeur_remise AS NUMERIC), 2))
                                   ELSE
                                       ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2)
                               END AS prix_ttc,
                               CASE 
                                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'pourcentage' THEN
                                       ROUND(CAST(p.prix_unitaire_ht * (1 - r.valeur_remise / 100) AS NUMERIC), 2)
                                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'fixe' THEN
                                       ROUND(CAST(p.prix_unitaire_ht * (GREATEST(0, ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) - r.valeur_remise AS NUMERIC), 2)) / NULLIF(ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2), 0)) AS NUMERIC), 2)
                                   ELSE
                                       p.prix_unitaire_ht
                               END AS prix_unitaire_ht_avec_remise
                        FROM produit p
                        LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                        LEFT JOIN remise r ON (
                            r.id_vendeur = p.id_vendeur
                            AND r.est_actif = true
                            AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
                            AND (
                                -- Cas 1: Remise sur CE produit spécifique (via id_produit)
                                r.id_produit = p.id_produit
                                -- Cas 2: Remise sur CE produit spécifique (via table remise_produit)
                                OR EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise AND rp.id_produit = p.id_produit)
                                -- Cas 3: Remise sur TOUS les produits (pas de produit spécifique, pas de catégorie)
                                OR (r.id_produit IS NULL AND r.categorie IS NULL AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                                -- Cas 4: Remise sur CATÉGORIE spécifique (pas de produit spécifique, catégorie correspond)
                                OR (r.id_produit IS NULL AND r.categorie = p.categorie AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                            )
                        )
                        WHERE p.id_produit = ?
                    ");
                    $stmt_produit->execute([$id_produit]);
                    $produit = $stmt_produit->fetch(PDO::FETCH_ASSOC);

                    if ($produit) {
                        $prixtotal += $produit["prix_ttc"] * $article['quantite'];
                        $taxe += ($produit['prix_ttc'] - $produit['prix_unitaire_ht_avec_remise']) * $article["quantite"];
                        $prixht += $produit['prix_unitaire_ht_avec_remise'] * $article['quantite'];
                        
                        $requete_img = $pdo->prepare('SELECT * FROM media_produit WHERE id_produit = :id_produit');
                        $requete_img->execute([':id_produit' => $id_produit]);
                        $img = $requete_img->fetch();
                        
                        echo '
                            <article>
                                <img src="' . $img["chemin_image"] .'" alt="' . htmlspecialchars($produit['nom_produit']) . '">
                                <div class="panier_info">
                                    <h4>' . htmlspecialchars($produit['nom_produit']) . '</h4>';
                        
                        if ($produit['id_remise']) {
                            echo '
                                    <span class="badge-remise-panier">';
                            if ($produit['type_remise'] === 'pourcentage') {
                                echo '-' . number_format($produit['valeur_remise'], 0) . '%';
                            } else {
                                echo '-' . number_format($produit['valeur_remise'], 2, ',', ' ') . '€';
                            }
                            echo '  </span>
                                    <p class="prix-original-panier">' . number_format($produit['prix_ttc_sans_remise'], 2, ',', ' ') . ' €</p>';
                        }
                        
                        echo '
                                    <p class="prix-panier">Prix : ' . number_format($produit['prix_ttc'], 2, ',', ' ') . ' €</p>
                                    <p class="description-panier">' . htmlspecialchars($produit['description_produit']) . '</p>
                                    <p class="stock-panier">Stock disponible : ' . htmlspecialchars($produit['stock_disponible']) . '</p>
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
            <div class="colonne-droite-panier">

                <aside class="box-droite">
                    <h4>Prix total: <?= number_format($prixtotal, 2, ',', ' ') ?>€</h4>
                    <p>prix hors taxe : <?= number_format($prixht, 2, ',', ' ') ?>€ <br>
                    taxe : <?= number_format($taxe, 2, ',', ' ') ?>€ </p>
                    <a href="paiement.php" class="btn-paiement">Passer au paiement</a> 
                </aside>

                <aside class="box-droite liste-souhait">
                    <h2>Liste de souhait</h2>
                    <?php
                    $stmt_fav = $pdo->prepare("
                        SELECT p.id_produit, p.nom_produit, 
                            (SELECT chemin_image FROM media_produit WHERE id_produit = p.id_produit LIMIT 1) AS image_produit
                        FROM favoris f
                        JOIN produit p ON f.id_produit = p.id_produit
                        WHERE f.id_client = ?
                    ");
                    $stmt_fav->execute([$_SESSION["id_client"]]);
                    $favoris = $stmt_fav->fetchAll(PDO::FETCH_ASSOC);
                    if (count($favoris) != 0){
                        foreach ($favoris as $fav) {
                            $image_src = !empty($fav['image_produit']) ? htmlspecialchars($fav['image_produit']) : 'front_end/assets/images_produits/default.png';
                            ?>
                            <div class="item-souhait">
                                <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($fav['nom_produit']) ?>" class="img-souhait">
                                <div class="details-souhait">
                                    <h5><?= htmlspecialchars($fav['nom_produit']) ?></h5>
                                    <div class="actions-souhait">
                                        <form action="" method="post">
                                            <input type="hidden" name="action" value="ajouter_panier_depuis_fav">
                                            <input type="hidden" name="id_produit_favoris" value="<?= $fav['id_produit'] ?>">
                                            <button type="submit" class="btn-ajout-panier">Ajouter</button>
                                        </form>
                                        <a href="produitdetail.php?article=<?= $fav['id_produit'] ?>" class="lien-savoir-plus">En savoir plus</a>
                                    </div>
                                </div>
                            </div>
                            <hr class="separateur-souhait">
                            <?php
                        }
                    }
                    else{
                        ?>
                        <p>Vos articles mis de côté apparaîtront ici.</p>
                        <?php
                    }
                    ?>
                </aside>
            </div>
            <form class="vider-panier" method="post" style="text-align:center; margin-top: 2.5em;">
                <input type="hidden" name="action" value="vider_panier">
                <input type="hidden" name="id_produit" value="2">
                <button type="submit" class="btn-vider">Vider le panier</button>
            </form>
        <?php endif; ?>
    </main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".supprimer-produit").forEach(form => {
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                if (confirm("Voulez-vous vraiment supprimer ce produit du panier ?")) {
                    form.submit();
                }
            });
        });

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
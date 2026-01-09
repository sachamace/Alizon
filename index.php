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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
</head>
<body>
    <header>
        <?php include 'front_office/front_end/html/header.php' ?>
    </header>
    <div class="div__catalogue">
        <?php
            $tri = $_GET['tri'] ?? '';
            switch ($tri) {
                case 'prix_asc':
                    $orderBy = 'prix_ttc ASC';
                    break;
                case 'prix_desc':
                    $orderBy = 'prix_ttc DESC';
                    break;
                case 'note_asc':
                    $orderBy = 'note_produit ASC';
                    break;
                case 'note_desc':
                    $orderBy = 'note_produit DESC';
                    break;
                default:
                    $orderBy = 'id_produit ASC';
            }
            // On récupère tout le contenu de la table produit disponible AVEC le calcul du prix TTC
            if (isset($_GET['search'])){
                $sql = "SELECT p.*, 
                    ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc
                FROM produit p
                LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                WHERE p.est_actif = true 
                AND (p.nom_produit LIKE :query OR p.description_produit LIKE :query)
                ORDER BY " . $orderBy;

                // 2. On prépare cette grosse requête
                $stmt = $pdo->prepare($sql);

                // 3. On l'exécute avec le mot-clé
                $stmt->execute(['query' => '%' . urldecode($_GET['search']) . '%']);

                // C'EST ICI LA CLÉ :
                // Après le execute(), $stmt se comporte EXACTEMENT comme le retour de $pdo->query().
                // Tu peux donc l'utiliser directement dans ton foreach ou le stocker dans $reponse
                $reponse = $stmt;
            }
            else{
                $reponse = $pdo->query('
                    SELECT p.*, 
                        ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc
                    FROM produit p
                    LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                    WHERE p.est_actif = true
                    ORDER BY ' . $orderBy
                );
            }
            
            
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ 
                if (!isset($_GET['categorie']) || $donnees['categorie'] == $_GET['categorie']){
                
                // Récupération du stock pour afficher le statut
                $stock_dispo = (int) $donnees['stock_disponible'];
                $stock_class = 'in-stock';
                
                if ($stock_dispo <= 0) {
                    $stock_class = 'out-of-stock';
                } elseif ($stock_dispo <= 5) {
                    $stock_class = 'low-stock';
                }
                
                ?>
            <a href="front_office/front_end/html/produitdetail.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article>
                    <?php
                    $requete_img = $pdo->prepare('SELECT chemin_image FROM media_produit WHERE id_produit = :id_produit LIMIT 1');
                    $requete_img->execute([':id_produit' => $donnees['id_produit']]);
                    $img = $requete_img->fetch();
                    ?>
                    
                    <div class="image-container">
                        <img src="<?= $img['chemin_image'] ? htmlentities($img['chemin_image']) : 'front_end/assets/images_produits/' ?>" alt="Image du produit" width="350" height="350">
                    </div>
                    
                    <div class="product-info">
                        <h2 class="titre"><?php echo htmlentities($donnees['nom_produit']) ?></h2>
                        <p class="description"><?php echo htmlentities($donnees['description_produit']) ?></p>
                        
                        <div class="price-section">
                            <p class="prix"><?php echo number_format($donnees['prix_ttc'], 2, ',', ' ') . '€' ?></p>
                            <span class="stock-info <?php echo $stock_class; ?>">
                                <?php 
                                if ($stock_dispo > 0) {
                                    echo $stock_dispo . ' en stock';
                                } else {
                                    echo 'Indisponible';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </article>
            </a>
                
            <?php
                }
            }
            $reponse->closeCursor(); // Termine le traitement de la requête
        ?>
        <aside id="filtre">
            <form action="" method="get" id="tri-form">
                <?php if (isset($_GET['categorie'])): ?>
                    <input type="hidden" name="categorie" value="<?= htmlspecialchars($_GET['categorie']) ?>">
                <?php endif; ?>
                <label for="tri">Trier par :</label>
                <select name="tri" id="tri" onchange="this.form.submit()">
                    <option value="">-- Sélectionner --</option>
                    <option value="prix_asc" <?php if (isset($_GET['tri']) && $_GET['tri'] === 'prix_asc') echo 'selected'; ?>>Prix croissant</option>
                    <option value="prix_desc" <?php if (isset($_GET['tri']) && $_GET['tri'] === 'prix_desc') echo 'selected'; ?>>Prix décroissant</option>
                    <option value="note_asc" <?php if (isset($_GET['tri']) && $_GET['tri'] === 'note_asc') echo 'selected'; ?>>Note 1-5</option>
                    <option value="note_desc" <?php if (isset($_GET['tri']) && $_GET['tri'] === 'note_desc') echo 'selected'; ?>>Note 5-1</option>
                </select>
                <br><label for="prixMin">Prix Min :</label>
                    <input type="number" id="prixMinInput" name="prixMin" min="0" max="1000" value="0">
                <label for="prixMax">Prix Max :</label>
                    <input type="number" id="prixMaxInput" name="prixMax" min="0" max="1000" value="0">
                <fieldset>
                    <legend>Vendeurs :</legend>
                    <?php
                    $vendeurs = $pdo->query('SELECT * FROM compte_vendeur');
                    while ($vendeur = $vendeurs->fetch()){?>
                        <input type="checkbox" id="<?php echo $vendeur['id_vendeur'];?>" name="<?php echo $vendeur['raison_sociale'];?>"/>
                        <label for="<?php echo $vendeur['raison_sociale'];?>"><?php echo $vendeur['raison_sociale'];?></label><br>
                    <?php } ?>
                </fieldset>
                <fieldset>
                    <legend>Notes :</legend>
                    <div>
                        <input type="checkbox" id="1" name="1"/>
                            <label for="1">★</label><br>
                        <input type="checkbox" id="2" name="2"/>
                            <label for="2">★★</label><br>
                        <input type="checkbox" id="3" name="3"/>
                            <label for="3">★★★</label><br>
                        <input type="checkbox" id="4" name="4"/>
                            <label for="4">★★★★</label><br>
                        <input type="checkbox" id="5" name="5"/>
                            <label for="5">★★★★★</label>
                </fieldset>
                <button type="submit">Appliquer les filtres</button>
                <button type="button" id="resetAllFilters">Réinitialiser les filtres</button>
            </form>
        </aside>
    </div>
    <footer class="footer mobile">
        <?php include 'front_office/front_end/html/footer.php'?>
    </footer>
    <script>
        $(function(){
            $('#openFilter').on('click', function(e){
            e.preventDefault();
            $('#filtre').toggle();
            const expanded = $(this).attr('aria-expanded') === 'true' ? 'false' : 'true';
            $(this).attr('aria-expanded', expanded);
            });
        });
    </script>
</body>
</html>
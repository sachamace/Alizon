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
</head>
<body>
    <header>
        <?php include 'front_office/front_end/html/header.php' ?>
    </header>
    <div class="div__catalogue">
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

                <label>Prix</label>
                <div class="inputs">
                    <input type="number" placeholder="Min" id="prixMinInput" name="prixMin" min="0" max="1000" value="<?php echo $_GET['prixMin']?>">
                    <input type="number" placeholder="Max" id="prixMaxInput" name="prixMax" min="0" max="1000" value="<?php echo $_GET['prixMax']?>">
                </div>
                <fieldset>
                    <legend>Vendeurs :</legend>
                    <?php
                    $vendeurs_selectionnes = $_GET['vendeurs'] ?? []; // On récupère les IDs cochés
                    $vendeurs = $pdo->query('SELECT * FROM compte_vendeur');
                    
                    while ($vendeur = $vendeurs->fetch()){
                        // On vérifie si l'id actuel est dans le tableau des vendeurs sélectionnés
                        $is_checked = in_array($vendeur['id_vendeur'], $vendeurs_selectionnes) ? 'checked' : '';
                        ?>
                        <input type="checkbox" 
                            id="vend_<?php echo $vendeur['id_vendeur'];?>" 
                            name="vendeurs[]" 
                            value="<?php echo $vendeur['id_vendeur']; ?>"
                            <?php echo $is_checked; ?> /> <label for="vend_<?php echo $vendeur['id_vendeur'];?>"><?php echo $vendeur['raison_sociale'];?></label><br>
                    <?php } ?>
                </fieldset>
                <label>Note</label>
                <div class="inputs">
                    <input type="number" placeholder="Min" id="noteMinInput" name="noteMin" min="0" max="5" value="<?php echo $_GET['noteMin']?>">
                    <input type="number" placeholder="Max" id="noteMaxInput" name="noteMax" min="0" max="5" value="<?php echo $_GET['noteMax']?>">
                </div>
                <button type="button" id="resetAllFilters" onclick="window.location.href=window.location.pathname">
                    Réinitialiser les filtres
                </button>
            </form>
        </aside>
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
                    $orderBy = 'note_moyenne ASC NULLS LAST';
                    break;
                case 'note_desc':
                    $orderBy = 'note_moyenne DESC NULLS LAST';
                    break;
                default:
                    $orderBy = 'id_produit ASC';
            }
            $params = [];
            $having = "1 = 1"; 
            $where = "1 = 1";

            if (!empty($_GET['prixMin'])) {
                $having .= " AND ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) >= :prixMin";
                $params['prixMin'] = $_GET['prixMin'];
            }

            if (!empty($_GET['prixMax'])) {
                $having .= " AND ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) <= :prixMax";
                $params['prixMax'] = $_GET['prixMax'];
            }

            if (!empty($_GET['vendeurs']) && is_array($_GET['vendeurs'])) {
                $vendeurs = $_GET['vendeurs'];
                $placeholders = [];
                
                foreach ($vendeurs as $key => $id) {
                    $placeholders[] = ':' . $key;
                    $params[$key] = $id;
                }

                $where .= " AND p.id_vendeur IN (" . implode(',', $placeholders) . ")";
            }

            if (!empty($_GET['noteMin'])) {
                $having .= " AND AVG(a.note) >= :noteMin";
                $params['noteMin'] = $_GET['noteMin'];
            }

            if (!empty($_GET['noteMax'])) {
                $having .= " AND AVG(a.note) <= :noteMax";
                $params['noteMax'] = $_GET['noteMax'];
            }

            $sql = "
                SELECT p.*, 
                       ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc,
                       r.id_remise, 
                       r.nom_remise, 
                       r.type_remise, 
                       r.valeur_remise,
                       AVG(a.note) AS note_moyenne
                FROM produit p
                LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                LEFT JOIN avis a ON p.id_produit = a.id_produit
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
                WHERE $where
                GROUP BY 
                    p.id_produit, 
                    t.taux, 
                    r.id_remise, 
                    r.nom_remise, 
                    r.type_remise, 
                    r.valeur_remise
                HAVING $having
                ORDER BY $orderBy
            ";

            $reponse = $pdo->prepare($sql);
            $reponse->execute($params);
            
            // On affiche chaque entrée une à une
            while ($donnees = $reponse->fetch()){ 
                if (!isset($_GET['categorie']) || $donnees['categorie'] == $_GET['categorie']){
                
                // Récupération du stock
                $stock_dispo = (int) $donnees['stock_disponible'];
                $stock_class = 'in-stock';
                
                if ($stock_dispo <= 0) {
                    $stock_class = 'out-of-stock';
                } elseif ($stock_dispo <= 5) {
                    $stock_class = 'low-stock';
                }
                
                // Calcul du prix avec remise
                $prix_final = $donnees['prix_ttc'];
                $a_une_remise = false;
                
                if ($donnees['id_remise']) {
                    $a_une_remise = true;
                    if ($donnees['type_remise'] === 'pourcentage') {
                        $prix_final = $donnees['prix_ttc'] * (1 - $donnees['valeur_remise'] / 100);
                    } else {
                        $prix_final = $donnees['prix_ttc'] - $donnees['valeur_remise'];
                    }
                    if ($prix_final < 0) $prix_final = 0;
                }
                ?>
            <a href="front_office/front_end/html/produitdetail.php?article=<?php echo $donnees['id_produit']?>" style="text-decoration:none; color:inherit;">
                <article class="<?= $a_une_remise ? 'has-remise-front' : '' ?>">
                    <?php
                    // Badge remise si applicable
                    if ($a_une_remise): ?>
                        <div class="remise-badge-front">
                            <?php if ($donnees['type_remise'] === 'pourcentage'): ?>
                                -<?= number_format($donnees['valeur_remise'], 0) ?>%
                            <?php else: ?>
                                -<?= number_format($donnees['valeur_remise'], 2, ',', ' ') ?>€
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
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
                            <?php if ($a_une_remise): ?>
                                <div class="prix-container-front">
                                    <p class="prix prix-original-front"><?php echo number_format($donnees['prix_ttc'], 2, ',', ' ') . '€' ?></p>
                                    <p class="prix prix-remise-front"><?php echo number_format($prix_final, 2, ',', ' ') . '€' ?></p>
                                </div>
                                <?php if ($donnees['nom_remise']): ?>
                                    <p class="remise-nom-front"><?= htmlentities($donnees['nom_remise']) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="prix"><?php echo number_format($donnees['prix_ttc'], 2, ',', ' ') . '€' ?></p>
                            <?php endif; ?>
                            
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
            $reponse->closeCursor();
        ?>
    </div>
    <footer class="footer mobile">
        <?php include 'front_office/front_end/html/footer.php'?>
    </footer>
    <script>
        $(function(){
            $('#tri, input[type="checkbox"]').on('change', function() {
                $('#tri-form').submit();
            });
            let timeout = null;
            $('#prixMinInput, #prixMaxInput, #noteMinInput, #noteMaxInput').on('keyup input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    $('#tri-form').submit();
                }, 800);
            });
        });
    </script>
</body>
</html>
<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';
/* ---------------------------------------------------
   1. Vérification de la connexion client
--------------------------------------------------- */

    $id_client_connecte = $_SESSION['id'];

/* ---------------------------------------------------
   2. Récupération de l'adresse du client
--------------------------------------------------- */

    try {
        $stmt = $pdo->prepare("
            SELECT adresse, code_postal, ville, pays
            FROM public.adresse a
            WHERE id_client = :id_client
            ORDER BY id_adresse DESC 
            LIMIT 1
        ");
        $stmt->execute(['id_client' => $id_client_connecte]);
        $client = $stmt->fetch();


    } catch (PDOException $e) {
        die("Erreur lors de la récupération des infos client : " . $e->getMessage());
    }

    try {
    // Récupérer les informations sur les articles du panier avec calcul du prix TTC
    $stmt = $pdo->prepare("
        SELECT pp.quantite, 
               p.nom_produit, 
               p.prix_unitaire_ht,
               t.taux AS taux_tva,
               ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc
        FROM panier_produit pp
        JOIN produit p ON pp.id_produit = p.id_produit
        LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
        WHERE pp.id_panier = :id_panier
    ");
    $stmt->execute([':id_panier' => $_SESSION['id_panier']]);
    $articles_panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer le total HT, TTC et nombre d'articles
    $total_ht = 0;
    $total_ttc = 0;
    $nb_articles = 0;
    $taxe = 0;

    foreach ($articles_panier as $article) {
        $total_ht += $article['prix_unitaire_ht'] * $article['quantite'];
        $total_ttc += $article['prix_ttc'] * $article['quantite'];
        //taxe = ttc - ht
        $taxe += ($article['prix_ttc'] - $article['prix_unitaire_ht']) * $article['quantite'];
        $nb_articles += $article['quantite'];
    }

} catch (PDOException $e) {
    die("Erreur lors de la récupération du panier : " . $e->getMessage());
}
/* ---------------------------------------------------
   3. Variables par défaut
--------------------------------------------------- */

    $erreurs = [];
    $success = false;

    $numero = "";
    $securite = "";
    $expiration = "";
    $nom = "";
    $email = "";
    $message = "";

/* ---------------------------------------------------
   4. Traitement du formulaire
--------------------------------------------------- */

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        // Vérification du type de paiement
        $type_paiement = $_POST['paiement'] ?? null;

        if ($type_paiement === "carte") {

            // Récupération des données
            $numero = str_replace(' ', '', $_POST['carte'] ?? '');  // ?? '' : renvoie une chaine vide si le champ concerné est vide
            $securite = str_replace('-', '', $_POST['cvv'] ?? '');
            $expiration = $_POST['expiration'] ?? '';
            $nom = $_POST['nom_titulaire'] ?? '';

            // Vérification numéro de carte (16 chiffres)
            if (!preg_match('/^[0-9]{16}$/', $numero)) {
                $erreurs['carte'] = "Numéro de carte invalide (16 chiffres requis).";
            }
            elseif(!verifLuhn($numero)){
                $erreurs['carte'] = "Numéro de carte invalide (algorithme de luhn).";
            }

            // Vérification CVV (3 chiffres)
            if (!preg_match('/^[0-9]{3}$/', $securite)) {
                $erreurs['cvv'] = "CVV invalide (3 chiffres).";
            }

            // Vérification expiration
            if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $expiration)) {
                $erreurs['expiration'] = "Format invalide (AAAA-MM).";
            }
            else {
                list($annee, $mois) = explode("-", $expiration);
                $timestampExpiration = mktime(23, 59, 59, $mois + 1, 0, $annee);
                
                if ($timestampExpiration < time()) {
                    $erreurs['expiration'] = "Votre carte est expirée.";
                }
            }

        }

        // Si aucune erreur de validation de formulaire
        if (empty($erreurs)) {
            // ⚠️ VÉRIFICATION FINALE DU STOCK AVANT VALIDATION
            try {
                $stmt_verif_stock = $pdo->prepare("
                    SELECT pp.id_produit, pp.quantite, p.nom_produit, p.stock_disponible
                    FROM panier_produit pp
                    JOIN produit p ON pp.id_produit = p.id_produit
                    WHERE pp.id_panier = :id_panier
                ");
                $stmt_verif_stock->execute([':id_panier' => $_SESSION['id_panier']]);
                $articles_a_verifier = $stmt_verif_stock->fetchAll(PDO::FETCH_ASSOC);
                
                // Vérifier chaque article
                foreach ($articles_a_verifier as $article) {
                    if ($article['quantite'] > $article['stock_disponible']) {
                        $erreurs['stock'] = "Stock insuffisant pour " . htmlentities($article['nom_produit']) . 
                                           " (demandé: " . $article['quantite'] . ", disponible: " . $article['stock_disponible'] . ")";
                        break; // Arrêter à la première erreur
                    }
                }
            } catch (PDOException $e) {
                $erreurs['stock'] = "Erreur lors de la vérification du stock.";
            }
            
            // Si toujours aucune erreur après vérification stock
            if (empty($erreurs)) {
                $success = true;
            }
        }    
    }
    //Affichage du modal "Paiement réussi"
    if ($success){ 
        // 🔹 DÉCRÉMENTATION DU STOCK AVANT DE VIDER LE PANIER
        try {
            // Récupérer tous les articles du panier avec leurs quantités
            $stmt_articles = $pdo->prepare("
                SELECT id_produit, quantite 
                FROM panier_produit 
                WHERE id_panier = :id_panier
            ");
            $stmt_articles->execute([':id_panier' => $_SESSION['id_panier']]);
            $articles_a_traiter = $stmt_articles->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque article du panier, décrémenter le stock
            foreach ($articles_a_traiter as $article) {
                $stmt_update_stock = $pdo->prepare("
                    UPDATE produit 
                    SET stock_disponible = stock_disponible - :quantite 
                    WHERE id_produit = :id_produit 
                    AND stock_disponible >= :quantite
                ");
                $stmt_update_stock->execute([
                    ':quantite' => $article['quantite'],
                    ':id_produit' => $article['id_produit']
                ]);
                
                // Vérifier si le stock a bien été mis à jour
                if ($stmt_update_stock->rowCount() == 0) {
                    // Stock insuffisant - ne devrait pas arriver si validation OK avant
                    throw new Exception("Stock insuffisant pour le produit ID " . $article['id_produit']);
                }
            }
            
            // ✅ Stock mis à jour avec succès, maintenant on vide le panier
            $stmt = $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = :id_panier");
            $stmt->execute([':id_panier' => $_SESSION['id_panier']]);
            
        } catch (Exception $e) {
            // En cas d'erreur, on affiche un message et on arrête
            die("Erreur lors de la mise à jour du stock : " . $e->getMessage());
        }
        ?>
    <div id="modal-success" style="
        position: fixed;
        top: 0; left: 0; width: 100% ; height: 100%;
        background: rgba(0,0,0,0.6); 
        display: flex; justify-content: center; align-items: center;
        z-index: 9999; 
        backdrop-filter: blur(2px);">

        <div style="
            background: white; 
            padding: 30px; 
            text-align: center; 
            border-radius: 10px; 
            width: 200px; 
            border: 2px solid #f0a8d0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            
            <h2>Commande bien effectuée !</h2>
            <a href="/index.php" style="
                margin-top:20px; 
                padding:20px 20px; 
                background:#f07ab0; 
                color:white; 
                text-decoration:none; 
                border-radius:5px;
                pointer-events: none;
                opacity: 0;
                ">V
            </a>
        </div>
    </div>
    <script>
        // Clic => redirection
        document.getElementById('modal-success').addEventListener('click', function () {
            window.location.href = '/index.php';
        });
    </script>
<?php }

function verifLuhn($numero){
    $sum = 0;
    $shouldDouble = false;

    //Boucle de droite à gauche
    for($i = strlen($numero) - 1; $i >= 0; $i--){
        $digit = (intval($numero[$i]));

        if($shouldDouble){
            $digit *= 2;
            if($digit > 9){
                $digit -= 9;
            }
        }

        $sum += $digit;
        $shouldDouble = !$shouldDouble;
    }
    return $sum % 10 === 0;
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page de paiement - Compte Client</title>
    <meta name="description" content="Page lors du paiement de ton panier coté client!">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    
    <!-- Cache-buster : force le rechargement du CSS -->
    <link rel="stylesheet" href="../assets/csss/style.css?v=<?php echo time(); ?>" id="main-css">


</head>
<body class="body_paiement">
    <header class = "disabled">
        <?php include 'header.php'?>
    </header>
    <div class="compte__header disabled"><a href="panier.php">←  </a>Passer la commande</div>
    <main class="main_paiement">
        <!-- ADRESSE CLIENT -->
        <div class="bloc recap">
            <h2>Adresse de livraison</h2>

            <?php if ($client){ ?>
                <p>
                    <span>Adresse :</span>
                    <span><?= htmlentities($client['adresse']) ?></span>
                </p>
                <p>
                    <span>Ville :</span>
                    <span><?= htmlentities($client['code_postal']) ?> <?= htmlentities($client['ville']) ?></span>
                </p>
                <p>
                    <span>Pays :</span>
                    <span><?= htmlentities($client['pays']) ?></span>
                </p>
            <?php } else { ?>
                <p>Aucune adresse enregistrée.</p>
            <?php } ?>
        </div>

        <div class="bloc recap">
            <h2>Récapitulatif de la commande</h2>
            <?php if ($articles_panier) { ?>
                <p>
                    <span>Articles :</span>
                    <span><?= $nb_articles ?></span>
                </p>
                <div class="liste-produit">
                    <ul>
                        <?php foreach ($articles_panier as $article): ?>
                        <p>
                            <li><?= $article['nom_produit'] ?> — x<?= $article['quantite'] ?></li>
                            <span>
                            <?= number_format($article['prix_ttc'], 2, ',', ' ') ?>€ (Total: <?= number_format($article['prix_ttc'] * $article['quantite'], 2, ',', ' ') ?>€)
                            </span>
                        </p>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <p>
                    <span>Total HT :</span>
                    <span><?= number_format($total_ht, 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <span>Taxe :</span>
                    <span><?= number_format($taxe, 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <span>Total TTC :</span>
                    <span><?= number_format($total_ttc, 2, ',', ' ') ?> €</span>
                </p>
            <?php } else { ?>
                <p>Votre panier est vide.</p>
            <?php } ?>
        </div>

        <!-- FORMULAIRE PAIEMENT -->
        <form method="POST" class="paiement">

            <div class="bloc">
                <h2>Méthode de paiement</h2>
                
                <!-- ERREUR DE STOCK -->
                <?php if(isset($erreurs['stock'])){ ?>
                    <div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #c62828;">
                        <strong>⚠️ Erreur de stock :</strong><br>
                        <?= htmlentities($erreurs['stock']) ?>
                        <br><br>
                        <a href="panier.php" style="color: #c62828; text-decoration: underline;">Retourner au panier pour ajuster les quantités</a>
                    </div>
                <?php } ?>

                <div class="options">

                    <!-- OPTION CARTE -->
                    <label class="option">
                        <input type="radio" name="paiement" id="radio-carte" value="carte" <?= empty($articles_panier) ? 'disabled' : '' ?>>
                        <span style="font-size: 1rem;">Carte bancaire</span>
                    </label>

                    <?php if(empty($articles_panier)){ ?>
                        <?php $erreursPanier = "Votre panier est vide. Impossible de payer."; ?>
                        <p style="color: red;"><?= htmlentities($erreursPanier) ?></p>
                    <?php } ?>

                    <!-- FORMULAIRE CARTE -->
                    <div class="formulaire hidden" id="form-carte">
                        <input type="text" name="carte" id ="carte" placeholder="Numéro de carte (16 chiffres)" 
                            value="<?= htmlentities($numero) ?>">
                        <p class="required"><?= htmlentities($erreurs['carte'] ?? '') ?></p>
                        <input type="month" name="expiration" placeholder="AAAA-MM" 
                            value="<?= htmlentities($expiration) ?>">
                        <p class="required"><?= htmlentities($erreurs['expiration'] ?? '') ?></p>
                        <input type="text" name="cvv" placeholder="CVV" 
                            value="<?= htmlentities($securite) ?>">
                        <p class="required"><?= htmlentities($erreurs['cvv'] ?? '')?></p>
                        <input type="text" name="nom_titulaire" placeholder="Nom du titulaire"
                            value="<?= htmlentities($nom) ?>">
                        <p class="required"><?= htmlentities($erreurs['nom'] ?? '') ?></p>
                    </div>

                    <!-- OPTION PAYPAL -->
                    <label class="option">
                        <input type="radio" name="paiement" id="radio-paypal" value="paypal" disabled>
                        <span style="opacity: 0.5; font-size: 1rem;">PayPal</span>
                    </label>

                    <div class="formulaire hidden" id="form-paypal">
                        <p>PayPal est désactivé.</p>
                    </div>

                </div>
            </div>

            <button type="submit" class="payer-btn">Payer</button>

    </form>
</main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
<script src="../assets/js/paiement.js"></script>
</body>
</html>
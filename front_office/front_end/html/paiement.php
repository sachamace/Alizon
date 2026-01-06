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

        // Si aucune erreur
        if (empty($erreurs)) {
            $success = true;

            header("Location: confirmation_achat.php");
            exit();
        }    
    }
    //Affichage du modal "Paiement réussi"
    if ($success){ 
        $stmt = $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = :id_panier");
        $stmt->execute([':id_panier' => $_SESSION['id_panier']]);
    ?> <?php }

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
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page de paiement - Compte CLient</title>
    <meta name="description" content="Page lors du paiement de ton panier coté client!">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
    <link rel="stylesheet" href="../assets/csss/style.css">

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
                        <li>
                            <div class="produit-ligne">
                                <span class="produit-nom"><?= htmlentities($article['nom_produit']) ?> × <?= $article['quantite'] ?></span>
                                <span class="produit-prix">
                                    <?= number_format($article['prix_ttc'], 2, ',', ' ') ?> €
                                    <small>(Total: <?= number_format($article['prix_ttc'] * $article['quantite'], 2, ',', ' ') ?> €)</small>
                                </span>
                            </div>
                        </li>
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

                        <div class="securite-info">
                            <p>🔒 Paiement 100% sécurisé</p>
                        </div>
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

            <button type="submit" class="payer-btn">Payer <?= number_format($total_ttc, 2, ',', ' ') ?> €</button>

    </form>
</main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
<script src="../assets/js/paiement.js"></script>
</body>
</html>
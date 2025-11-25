<?php
    include 'config.php';
    include 'session.php';
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
    // Récupérer les informations sur les articles du panier
    $stmt = $pdo->prepare("
        SELECT pp.quantite, p.nom_produit, p.prix_unitaire_ht, p.taux_tva, p.prix_ttc
        FROM panier_produit pp
        JOIN produit p ON pp.id_produit = p.id_produit
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

            // Ici tu peux créer la commande, enregistrer dans la BDD, etc.
        }
    }
    //Affichage du modal "Paiement réussi"
    if ($success){ ?>
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
            <a href="./paiement.php" style="
                display:inline-block; 
                margin-top:20px; 
                padding:20px 20px; 
                background:#f07ab0; 
                color:white; 
                text-decoration:none; 
                border-radius:5px;
                ">Voir ma commande</a>
        </div>
    </div>
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
    <div class="compte__header disabled">
        <button onclick="history.back();" class="back-button" style="background:none;border:none;cursor:pointer;">
            <img src="../assets/images/back-arrow.svg" alt="Retour" style="width:32px;height:32px;">
        </button>
    Passer la commande
    </div>
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
                            <li><?= $article['nom_produit'] ?> — x<?= $article['quantite'] ?></li>
                        <?php endforeach; ?> 
                    </ul>
                    
                </div>
                <p>
                    <span>Total HT :</span>
                    <span><?= number_format($total_ht, 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <span>Taxe : :</span>
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
                        <input type="radio" name="paiement" id="radio-carte" value="carte">
                        <span>Carte bancaire</span>
                    </label>

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
                        <span style="opacity: 0.5;">PayPal</span>
                    </label>

                    <div class="formulaire hidden" id="form-paypal">
                        <p>PayPal est désactivé.</p>
                    </div>

                </div>
            </div>

            <button type="submit" class="payer-btn">Payer</button>

    </form>
</main>
<script src="../assets/js/paiement.js"></script>
</body>
</html>
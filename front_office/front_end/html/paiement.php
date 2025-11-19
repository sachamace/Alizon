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

            // Vérification CVV (3 chiffres)
            if (!preg_match('/^[0-9]{3}$/', $securite)) {
                $erreurs['cvv'] = "CVV invalide (3 chiffres).";
            }

            // Vérification expiration
            if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $expiration)) {
                $erreurs['expiration'] = "Format d’expiration invalide.";
            } else {
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
            <a href="./commande.php" style="
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

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-RXf+QSDCUQs6fP0Yk8U58+Z0hx+2b0sVjS3+qz1b1Hcl7uDd+v5b4rVZzS1MqYlIh7uNwT8Z3vJf8F0Ew2p3Mg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="../assets/csss/style.css">
    <script src="../assets/js/paiement.js"></script>
</head>
<body class="body_paiement">

<main class="main_paiement">

    <!-- HEADER -->
    <div class="commande-header">
        <a href="panier.php">
            <button class="back-btn">←</button>
        </a>
        <h1>Paiement</h1>
    </div>
    
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

    <!-- FORMULAIRE PAIEMENT -->
    <form method="POST" class="paiement">

        <div class="bloc">
            <h2>Méthode de paiement</h2>

            <div class="options">

                <!-- OPTION CARTE -->
                <label class="option">
                    <input type="radio" name="paiement" value="carte">
                    <span>Carte bancaire</span>
                </label>

                <!-- FORMULAIRE CARTE -->
                <div class="formulaire" id="form-carte">
                    <input type="text" name="carte" placeholder="Numéro de carte (16 chiffres)" 
                           value="<?= htmlentities($numero) ?>">
                    <p><?= htmlentities($erreurs['carte']) ?></p>
                    <input type="month" name="expiration"
                           value="<?= htmlentities($expiration) ?>">
                    <p><?= htmlentities($erreurs['expiration']) ?></p>
                    <input type="text" name="cvv" placeholder="CVV" 
                           value="<?= htmlentities($securite) ?>">
                    <p><?= htmlentities($erreurs['cvv']) ?></p>
                    <input type="text" name="nom_titulaire" placeholder="Nom du titulaire"
                           value="<?= htmlentities($nom) ?>">
                    <p><?= htmlentities($erreurs['nom']) ?></p>
                </div>

                <!-- OPTION PAYPAL -->
                <label class="option">
                    <input type="radio" name="paiement" value="paypal" disabled>
                    <span>PayPal</span>
                </label>

            </div>
        </div>

        <button type="submit" class="payer-btn">Payer</button>

    </form>

    <!-- NAVBAR BAS DE PAGE -->
    <div class="navbar">
        <button>🏠</button>
        <button>🛒</button>
        <button>👤</button>
    </div>

</main>
<script src="javascript.js"></script>
</body>
</html>
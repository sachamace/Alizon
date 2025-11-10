<?php
    $erreurs = [];
    $success = false;

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        // Vérification paiement par carte
        if (isset($_POST['carte']) || isset($_POST['cvv']) || isset($_POST['expiration'])) {
            $numero = $_POST['carte'] ?? '';
            $securite = $_POST['cvv'] ?? '';
            $expiration = $_POST['expiration'] ?? '';
            $nom = $POST['nom_titulaire'] ?? '';

            // Enlever les espaces
            $numero = str_replace([' ', ''], '', $numero);
            $securite = str_replace([' ', '-'], '', $securite);
            $expiration = trim($expiration);

            // Vérification du numéro de carte
            if (!preg_match('/^\d{16}$/', $numero)) {
                $erreurs['carte'] = "Numéro de carte invalide, 16 chiffres requis";
            }

            // Vérification du CVV ou cryptogramme visuel
            if (!preg_match('/^\d{3}$/', $securite)) {
                $erreurs['cvv'] = "Code de sécurité invalide.";
            }

            // Vérification de l'expiration de la carte
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $expiration)) {
                $erreurs[] = "Format d'expiration invalide.";
            } else {
                list($annee, $mois) = explode("-", $expiration);
                $timestampExpiration = mktime(23, 59, 59, (int)$mois + 1, 0, (int)$annee);
                if ($timestampExpiration < time()) {
                    $erreurs['expiration'] = "Votre carte est expirée.";
                }
            }
        }

        // Vérification de l'email PayPal
        if (isset($_POST['paypal_email'])) {
            $email = trim($_POST['paypal_email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erreurs['paypal_email'] = "Adresse email invalide.";
            }
        }
        //Pour pas afficher la liste des erreurs au début du lancement du script php
        if(empty($erreurs)){
            $success = true;
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
    <link rel="stylesheet" href="style.css">
</head>
<body class="body_paiement">
    <main class="main_paiement">
        <header class="commande-header">
            <a href="./panier.php"><button class="back-btn">←</button></a>
            <h1>Passer la commande</h1>
        </header>

        <section class="bloc paiement">
            <h2>Mode de paiement</h2>
            <div class="options">
                <!-- Bouton radio carte bancaire -->
                <label class="option">
                    <input type="radio" name="paiement" value="carte" id="radio-carte">
                    <span>Payer par carte bleue</span>
                </label>

                <!-- Formulaire Carte Bancaire-->
                <form class="formulaire paiement-carte hidden" action="paiement.php" method="post" id="form-carte">
                    <label>Numéro de carte<span class="required">*</span></label>
                    <input type="text" id="carte" maxlength="19" placeholder="Saisir le numéro de carte" name="carte" value ="<?=$numero?>" required>
                    <p style="color: red; font-size: 12px;"><?php echo isset($erreurs['carte']) ? htmlentities($erreurs['carte']) : ''; ?></p>

                    <div>
                        <label>Date d’expiration<span class="required">*</span></label>
                        <input type="month" name="expiration" value ="<?=$expiration?>" required>
                        <p style="color: red; font-size: 12px;"><?php echo isset($erreurs['expiration']) ? htmlentities($erreurs['expiration']) : ''; ?></p>
                    </div>
                    <div>
                        <label>Cryptogramme visuel<span class="required">*</span></label>
                        <input type="text" placeholder="CVV" name="cvv" value ="<?=$securite?>" required>
                        <p style="color: red; font-size: 12px;"><?php echo isset($erreurs['cvv']) ? htmlentities($erreurs['cvv']) : ''; ?></p>
                    </div>

                    <label>Nom du titulaire de la carte<span class="required">*</span></label>
                    <input type="text" placeholder="Nom complet" name="nom_titulaire" value ="<?=$nom?>" required>

                    <div class="checkbox">
                        <input type="checkbox" id="save-carte" name="save_carte">
                        <label for="save-carte">Enregistrer pour vos prochains achats</label>
                    </div>
                    <button form="form-carte" type="submit" class="payer-btn">Payer cette commande</button>
                </form>

                <!-- Bouton radio Paypal -->
                <label class="option">
                    <input type="radio" name="paiement" value="paypal" id="radio-paypal" disable>
                    <span>Payer par PayPal</span>
                </label>

                <!-- Formulaire PayPal -->
                <form class="formulaire paiement-paypal hidden" action="paiement.php" method="post" id="form-paypal">
                    <label>Adresse mail du compte<span class="required">*</span></label>
                    <input type="email" placeholder="Saisir son adresse mail PayPal" name="paypal_email" value ="<?=$email?>" required>
                    <p style="color: red; font-size: 12px;"><?php echo isset($erreurs['paypal_email']) ? htmlentities($erreurs['paypal_email']) : ''; ?></p>
                    <div class="checkbox">
                        <input type="checkbox" id="save-paypal" name="save_paypal">
                        <label for="save-paypal">Enregistrer pour vos prochains achats</label>
                    </div>
                    <button form="form-paypal" type="submit" class="payer-btn">Payer cette commande</button>
                </form>
            </div>
        </section>

        <!-- Bloc des informations personnelles du client -->
        <section class="bloc adresse">
            <h2>Adresse de livraison</h2>
            <p>Adresse<br><?php htmlspecialchars($commande['adresse']); ?></p>
            <p>Code postal<br><?php htmlspecialchars($commande['code_postal']); ?></p>
            <p>Ville<br><?php htmlspecialchars($commande['ville']); ?></p>
            <p>Pays<br><?php htmlspecialchars($commande['pays']); ?></p>
        </section>

        <!-- Bloc du récapitulatif du prix des articles du panier -->
        <section class="bloc recap">
            <h2>Récapitulatif du prix</h2>
            <p>Article <span><?= number_format($commande['prix_article'], 2, ',', ' '); ?>€</span></p>
            <p>Livraison <span><?= number_format($commande['prix_livraison'], 2, ',', ' '); ?>€</span></p>
            <p>Réduction <span>-<?= number_format($commande['reduction'], 2, ',', ' '); ?>€</span></p>
            <p class="total">Total <span><?= number_format($commande['total'], 2, ',', ' '); ?>€</span></p>
        </section>

        <footer class="navbar">
            <button><i class="fa-regular fa-house"></i></button>
            <button><i class="fa-solid fa-magnifying-glass"></i></button>
            <button><i class="fa-solid fa-cart-shopping"></i></button>
            <button><i class="fa-regular fa-bell"></i></button>
            <button><i class="fa-regular fa-user"></i></button>
        </footer>
    </main>
    
    <script>
        const radioCarte = document.getElementById("radio-carte");
        const formCarte = document.querySelector(".paiement-carte");

        const radioPaypal = document.getElementById("radio-paypal");
        const formPaypal = document.querySelector(".paiement-paypal");

        const carteInput = document.getElementById('carte');

        radioPaypal.disabled = true;

        // Variables pour suivre la visibilité des formulaires
        let carteVisible = false;   // true si formulaire carte visible
        let paypalVisible = false;

        // Gestion du clic sur la radio "Carte"
        radioCarte.addEventListener("click", () => {
            if (carteVisible) {  // si le formulaire carte est déjà visible
                radioCarte.checked = false;  // décocher la radio
                formCarte.classList.add("hidden"); // cacher le formulaire
                carteVisible = false; // mettre à jour l'état
            } else {
                formCarte.classList.remove("hidden"); // afficher le formulaire
                carteVisible = true; // mettre à jour l'état
                // S'assurer que PayPal est caché
                formPaypal.classList.add("hidden");
                paypalVisible = false;
            }
        });

        carteInput.addEventListener('input', function (e) {
            // On retire tous les espaces
            let valeur = this.value.replace(/\s+/g, '');
            // On garde seulement les chiffres
            valeur = valeur.replace(/\D/g, '');
            // On regroupe tous les 4 chiffres avec un espace
            this.value = valeur.match(/.{1,4}/g)?.join(' ') || '';
        });
</script>
</body>
</html>
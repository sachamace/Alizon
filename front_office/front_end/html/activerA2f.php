<?php
    use OTPHP\TOTP;
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';
    require_once '../../../vendor/autoload.php';
    
    $id_client_connecte = $_SESSION['id_client'];  

    if ($_SERVER["REQUEST_METHOD"] === "GET") {

        $otp = TOTP::create(); 
        $_SESSION['temp_secret_a2f'] = $otp->getSecret(); 
    } else {
        if (isset($_SESSION['temp_secret_a2f'])) {
            $otp = TOTP::create($_SESSION['temp_secret_a2f']);
        } else {
            // Si la session est perdue lors de l'AJAX
            if (isset($_POST['code'])) {
                echo json_encode(['success' => false, 'message' => "Session expirée, veuillez recharger la page."]);
                exit();
            }
            header("Location: activerA2f.php");
            exit();
        }
    }


    if (isset($_POST['code'])) {

        $code_recu = trim($_POST['code']); 
        

        if ($otp->verify($code_recu, null, 1)) {

            $stmtcode = $pdo->prepare("UPDATE compte_client SET codea2f = :codea2f WHERE id_client = :id_client");
            $stmtcode->execute([
                'id_client' => $id_client_connecte,
                'codea2f' => $_SESSION['temp_secret_a2f']
            ]);
            unset($_SESSION['temp_secret_a2f']);
            

            echo json_encode(['success' => true]);
            exit();
        } else {

            echo json_encode(['success' => false, 'message' => "Le code à 6 chiffres n'est pas bon !"]);
            exit();
        }
    }   
    $attente_a2f = false;
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['code'])){
        $attente_a2f = true;
    }

    
    if (!$attente_a2f) {
        $code_secret = $otp->getSecret(); 

        $stmtmail = $pdo->prepare("SELECT adresse_mail FROM compte_client WHERE id_client = :id_client");
        $stmtmail->execute([
            'id_client' => $id_client_connecte
        ]);
        $mail = $stmtmail->fetchColumn();

        $otp = $otp->withLabel($mail); 
        $otp = $otp->withIssuer('AuthentikATOR');
        $provisioningUri = $otp->getProvisioningUri();  
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter</title>
    <meta name="description" content="Ceci est le profil du compte de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>
    <body class="body__connexion">
        <?php if ($attente_a2f){ ?>
        <div class="popup-overlay">
            <div class="popup-content">
                <div class="header__connexion">
                    <h2>Vérification de ton Auth</h2>
                </div>    
                <div class="form__connexion">
                    <p>Veuillez entrer le code de vérification à 6 chiffres pour sécuriser votre connexion.</p>
                    
                    <input type="text" id="code_2fa" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code">
                    
                    <div class="popup-buttons">
                        <button type="submit" class="btn-popup btn-valider" onclick="valider()">
                            Vérifier
                        </button>
                    </div>
                </div>

                <div id="erreur-msg-js" class="erreur-msg" style="color: red; margin-top: 15px;"></div>
            </div>
        </div>
        <?php } else{?>
        <div class="container__connexion">
            <p>Scannez ce QR code avec Google Authenticator ou Authy :<br>
            <strong>ATTENTION : CE CODE ET LE QR CODE NE SONT AFFICHÉS QUE MAINTENANT !</strong></p>
            
            <div id="qrcode" data-uri="<?php echo htmlspecialchars($provisioningUri); ?>" style = "width: 80%; max-width: 250px; margin: 0px auto;"></div>
            
            <p>Clé de configuration manuelle : </p>
            <p style = "white-space: normal;
                        overflow: visible;
                        text-overflow: clip;
                        word-break: break-word;
                        box-sizing: border-box;"
            >
            <strong><?php echo $code_secret; ?></strong></p>
            <form method="POST">
                <button type="submit" class="btn__connexion">Vérification </button>
            </form>
            
        </div>
        <?php }?>
        <div id="toast-global" class="toast"></div>
        <script src="../assets/js/toast.js"></script>
        <script src="../assets/js/a2f.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    </body>
</html>
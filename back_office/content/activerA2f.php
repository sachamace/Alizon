<?php
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    $id_vendeur_connecte = $_SESSION['vendeur_id'];   
    use OTPHP\TOTP;
    if (isset($_POST['code'])) {
        // On enlève les éventuels espaces invisibles avant/après avec trim()
        $code_recu = trim($_POST['code']); 
        
        // 2. VÉRIFICATION DE LA VALIDITÉ DU CODE (si le format est bon)
        if (isset($_SESSION['temp_secret_a2f'])) {
            $otp = TOTP::create($_SESSION['temp_secret_a2f']);
            
            if ($otp->verify($code_recu)) {
                // Le code est bon ! On met à jour la BDD
                $stmtcode = $pdo->prepare("UPDATE compte_vendeur SET codea2f = :codea2f WHERE id_vendeur = :id_vendeur");
                $stmtcode->execute([
                    'id_vendeur' => $id_vendeur_connecte,
                    'codea2f' => $_SESSION['temp_secret_a2f']
                ]);
                unset($_SESSION['temp_secret_a2f']);
                
                // On nettoie tout le HTML précédent et on renvoie le succès
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
                
            } else {
                // Le code a le bon format, mais il est périmé ou faux
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Le code à 6 chiffres est incorrect ou a expiré."]);
                exit();
            }
        }
    } 

    $attente_a2f = false;
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['code'])){
        $attente_a2f = true;
    }
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $clock = new class implements \Psr\Clock\ClockInterface {
            public function now(): \DateTimeImmutable {return new \DateTimeImmutable();}
        };
        $otp = TOTP::generate($clock);
        $_SESSION['temp_secret_a2f'] = $otp->getSecret(); 
    } else {
        if (isset($_SESSION['temp_secret_a2f'])) {
            $otp = TOTP::create($_SESSION['temp_secret_a2f']);
        } else {
            echo "<script>
                window.location.href = 'index.php?page=activerA2f';
            </script>";
            exit();
        }
    }

    $code_secret = $otp->getSecret(); 

    // Configuration de l'affichage 
    $stmtmail = $pdo->prepare("SELECT raison_sociale FROM compte_vendeur WHERE id_vendeur = :id_vendeur");
    $stmtmail->execute([
        'id_vendeur' => $id_vendeur_connecte
    ]);
    $mail = $stmtmail->fetchColumn();

    $otp = $otp->withLabel($mail); 
    $otp = $otp->withIssuer('AuthentikATOR');
    $provisioningUri = $otp->getProvisioningUri();  
    if ($attente_a2f){ 
?>
<div class="popup-overlay">
    <div class="popup-content">
        <div class="header__connexion">
            <h2>Vérification de ton Auth</h2>
        </div>    
        <div class="form__connexion">
            <p>Veuillez entrer le code de vérification à 6 chiffres pour sécuriser votre connexion.</p>
            
            <input type="text" id="code_2fa" name="code_2fa" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code">
            
            <div class="popup-buttons">
                <button type="submit" class="btn-popup btn-valider" onclick="valider()">
                    Vérifier
                </button>
            </div>
        </div>
        <div id="erreur-msg-js" class="erreur-msg"></div>
        
    </div>
</div>
<?php } else{?>
<div class="container__connexion" style="align-self: center;">
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
<script src="front_end/assets/js/a2f.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

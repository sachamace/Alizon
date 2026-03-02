<?php
    include 'config.php';
    include 'session.php';
    include 'sessionindex.php';
    require_once '../../../vendor/autoload.php';

    $id_client_connecte = $_SESSION['id_client'];   
    use OTPHP\TOTP;

    $clock = new class implements \Psr\Clock\ClockInterface {
        public function now(): \DateTimeImmutable {
            return new \DateTimeImmutable();
        }
    };

    $otp = TOTP::generate($clock);

    $code_secret = $otp->getSecret(); // Permet d'avoir le code de la struct otp 

    $stmtcode = $pdo->prepare("UPDATE compte_client SET codea2f = :codea2f WHERE id_client = :id_client");
    $stmtcode->execute([
        'id_client' => $id_client_connecte,
        'codea2f' => $code_secret
    ]);

    echo "Le secret à sauvegarder en BDD : <strong>{$userSecret}</strong><br>";

    // Configuration de l'affichage 
    $stmtmail = $pdo->prepare("SELECT adresse_mail FROM compte_client WHERE id_client = :id_client");
    $stmtmail->execute([
        'id_client' => $id_client_connecte
    ]);
    $mail = $stmtmail->fetchColumn();

    $otp = $otp->withLabel($mail); // Souvent l'email de l'user
    $otp = $otp->withIssuer('Authentificator');

    // Note: You must set label before generating the QR code
    $grCodeUri = $otp->getQrCodeUri(
        'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
        '[DATA]'
    );
    echo 
        "
            Scannez ce qrcode avec Google Authenticator ou Authy ou une autre application d'Authentification:<br>
            ATTENTION CE CODE ET LE QRCODE EST AFFICHE SEULEMENT MAINTENANT ! <br>
        ";
    echo "<img src='{$grCodeUri}'> <br>";
    echo "Clé de configuration : $code_secret <br>";
?>
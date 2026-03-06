<?php
session_start();
include 'config.php';
// --- AJOUT : Import de la bibliothèque OTPHP ---
require_once '../vendor/autoload.php';
use OTPHP\TOTP;

$erreur = "";
$erreur_a2f = ""; 
$attente_a2f = false; 
$delai_attente = 5;

// 1. GESTION DE LA VÉRIFICATION DU CODE A2F (Étape 2)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['code_a2f'])) {
    $time = time();
    $code_saisi = trim($_POST['code_a2f']);
    $secret = $_SESSION['temp_secret'] ?? null;

    if (isset($_SESSION['dernier_envoi'])) {
        $temps_ecoule = $time - $_SESSION['dernier_envoi'];

        if ($temps_ecoule < $delai_attente) {
            $temps_restant = $delai_attente - $temps_ecoule;
            $message = "Trop vite ! Veuillez patienter encore <strong>$temps_restant secondes</strong>.";
        } else {
            $_SESSION['dernier_envoi'] = $time;
            if ($secret) {
                $otp = TOTP::createFromSecret($secret);
                if ($otp->verify($code_saisi)) {
                    // La vérification A2F a réussi
                    if(isset($_SESSION['temp_vendeur'])) {
                        $vendeur = $_SESSION['temp_vendeur'];
                        
                        // Connexion définitive (Variables originales de connecter.php)
                        $_SESSION['vendeur_id'] = $vendeur['id_vendeur'];
                        $_SESSION['vendeur_nom'] = $vendeur['raison_sociale'];
                        $_SESSION['vendeur_email'] = $vendeur['login'];
                        $_SESSION['est_connecte'] = true;
                        
                        // Nettoyage
                        unset($_SESSION['temp_vendeur']);
                        unset($_SESSION['temp_secret']);
                        
                        echo "<script>window.location.href = 'index.php?page=dashboard';</script>";
                        exit();
                    }
                } else {
                    $erreur_a2f = "Code de vérification incorrect.";
                    $attente_a2f = true; 
                }
            }
            
        }
    } else {
        $_SESSION['dernier_envoi'] = $time;
        if ($secret) {
            $otp = TOTP::createFromSecret($secret);
            if ($otp->verify($code_saisi)) {
                // La vérification A2F a réussi
                if(isset($_SESSION['temp_vendeur'])) {
                    $vendeur = $_SESSION['temp_vendeur'];
                    
                    // Connexion définitive (Variables originales de connecter.php)
                    $_SESSION['vendeur_id'] = $vendeur['id_vendeur'];
                    $_SESSION['vendeur_nom'] = $vendeur['raison_sociale'];
                    $_SESSION['vendeur_email'] = $vendeur['login'];
                    $_SESSION['est_connecte'] = true;
                    
                    // Nettoyage
                    unset($_SESSION['temp_vendeur']);
                    unset($_SESSION['temp_secret']);
                    
                    echo "<script>window.location.href = 'index.php?page=dashboard';</script>";
                    exit();
                }
            } else {
                $erreur_a2f = "Code de vérification incorrect.";
                $attente_a2f = true; 
            }
        }
    }

}

// 2. CONNEXION INITIALE (Étape 1 : Login/MDP)
elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $motdepasse = trim($_POST['motdepasse']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT i.id_num, i.login, i.mdp, cv.id_vendeur, cv.raison_sociale 
            FROM public.identifiants i 
            JOIN public.compte_vendeur cv ON i.id_num = cv.id_num 
            WHERE i.login = ?
        ");
        $stmt->execute([$email]);
        $vendeur = $stmt->fetch();
        
        if ($vendeur && $motdepasse === $vendeur['mdp']) {
            
            // --- ALGO A2F INTÉGRÉ ---
            // On vérifie si le vendeur a un code A2F activé
            $stmtsecret = $pdo->prepare("SELECT codea2f FROM compte_vendeur WHERE id_vendeur = :id_vendeur");
            $stmtsecret->execute(['id_vendeur' => $vendeur['id_vendeur']]);
            $secret = $stmtsecret->fetchColumn();

            if($secret && strcmp($secret, "") != 0){
                // On stocke temporairement les infos et on demande le code
                $_SESSION['temp_vendeur'] = $vendeur;
                $_SESSION['temp_secret'] = $secret;
                $attente_a2f = true; 
            }
            else {
                // Pas d'A2F : Connexion directe (Logique originale)
                $_SESSION['vendeur_id'] = $vendeur['id_vendeur'];
                $_SESSION['vendeur_nom'] = $vendeur['raison_sociale'];
                $_SESSION['vendeur_email'] = $vendeur['login'];
                $_SESSION['est_connecte'] = true;
                
                echo "<script>window.location.href = 'index.php?page=dashboard';</script>";
                exit();
            }
        } else {
            $erreur = "Email ou mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $erreur = "Erreur lors de la connexion : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Vendeur - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">
</head>
<body class="body__connexion">
    <!-- Logo en haut à gauche -->
    <div class="logo__connexion">
        <img src="front_end/assets/images/logo_Alizon.png" alt="Logo Alizon" width="150">
    </div>
        <?php if ($attente_a2f): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <h2>Double Authentification</h2>
            
            <form action="" class="form__connexion" method="post" enctype="multipart/form-data">
                <p>Veuillez entrer le code de vérification à 6 chiffres pour sécuriser votre connexion.</p>
                
                <input type="text" name="code_a2f" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code">
                
                <div class="popup-buttons">
                    <button type="submit" class="btn-popup btn-valider" >
                        Vérifier
                    </button>
                </div>
                <div style="margin-top: 15px;">
                     <a href="connecter.php" style="color: #666; text-decoration: none; font-size: 14px;">Annuler et retourner à la connexion</a>
                </div>
            </form>

            <?php if (!empty($erreur_a2f)): ?>
                <div class="erreur-msg">
                    <?= htmlspecialchars($erreur_a2f) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="erreur-msg">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="container__connexion">
        <div class="header__connexion">
            <h2>Rebonjour</h2>
        </div>
        
        <form class="form__connexion" method="POST">
            <?php if (!empty($erreur)): ?>
                <div class="error-message"><?= htmlentities($erreur) ?></div>
            <?php endif; ?>
            
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input type="email" id="email" name="email" placeholder="Votre adresse email" required class="input__connexion">
            </div>
            
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input type="password" id="motdepasse" name="motdepasse" placeholder="Votre mot de passe" required class="input__connexion">
            </div>
            
            <a href="recupmdp.php" class="forgot-password">Mot de passe oublié ?</a>
            
            <button type="submit" class="btn__connexion">Se connecter</button>
            
            <div class="separator">ou</div>
            
            <a href="creercompte.php" class="btn__creer-compte">Créer Compte</a>
            <a href="../../front_office/front_end/html/seconnecter.php" class="forgot-password">Coté Client</a>
        </form>
    </div>
</body>
</html>
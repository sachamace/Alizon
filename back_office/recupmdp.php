<?php
session_start();
include 'config.php';

$erreur = "";
$succes = "";
$etape = isset($_GET['etape']) ? $_GET['etape'] : 'email';

// Initialiser le compteur d'essais si pas déjà fait
if (!isset($_SESSION['essais_verification'])) {
    $_SESSION['essais_verification'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if ($etape === 'email') {
            $email = trim($_POST['email']);
            
            $stmt = $pdo->prepare("SELECT id_num, mdp FROM public.identifiants WHERE login = ?");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();
            
            if ($utilisateur) {
                $code_verification = sprintf("%06d", mt_rand(1, 999999));
                $expiration = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                $_SESSION['code_verification'] = $code_verification;
                $_SESSION['email_recuperation'] = $email;
                $_SESSION['code_expiration'] = $expiration;
                $_SESSION['ancien_mdp_hash'] = $utilisateur['mdp']; // Stocker l'ancien hash
                $_SESSION['essais_verification'] = 0; // Réinitialiser les essais
                
                // Envoi simple de l'email
                $to = $email;
                $subject = "Code de recuperation Alizon";
                $message = "Votre code de recuperation est: $code_verification\nValable 5 minutes";
                $headers = "From: noreply@gmail.com";
                
                mail($to, $subject, $message, $headers);
                
                header("Location: recupmdp.php?etape=verification");
                exit();
            } else {
                $erreur = "Aucun compte avec cet email.";
            }
            
        } elseif ($etape === 'verification') {
            // Vérifier si le nombre maximum d'essais est atteint
            if ($_SESSION['essais_verification'] >= 3) {
                $erreur = "Nombre maximum d'essais atteint. Veuillez redémarrer la procédure.";
                unset($_SESSION['code_verification']);
                unset($_SESSION['email_recuperation']);
                unset($_SESSION['code_expiration']);
                unset($_SESSION['essais_verification']);
                unset($_SESSION['ancien_mdp_hash']);
            } else {
                $code_saisi = trim($_POST['code']);
                
                if (isset($_SESSION['code_verification']) && 
                    $_SESSION['code_verification'] === $code_saisi &&
                    time() <= strtotime($_SESSION['code_expiration'])) {
                    
                    $_SESSION['essais_verification'] = 0; // Réinitialiser les essais en cas de succès
                    header("Location: recupmdp.php?etape=nouveau_mdp");
                    exit();
                } else {
                    $_SESSION['essais_verification']++;
                    $essais_restants = 3 - $_SESSION['essais_verification'];
                    
                    if ($_SESSION['essais_verification'] >= 3) {
                        $erreur = "Code invalide. Nombre maximum d'essais atteint. Veuillez redémarrer la procédure.";
                        unset($_SESSION['code_verification']);
                        unset($_SESSION['email_recuperation']);
                        unset($_SESSION['code_expiration']);
                        unset($_SESSION['essais_verification']);
                        unset($_SESSION['ancien_mdp_hash']);
                    } else {
                        $erreur = "Code invalide ou expiré. Il vous reste $essais_restants essai(s).";
                    }
                }
            }
            
        } elseif ($etape === 'nouveau_mdp') {
            $nouveau_mdp = trim($_POST['nouveau_mdp']);
            $confirmation_mdp = trim($_POST['confirmation_mdp']);
            
            // Vérifier la longueur du mot de passe
            if (strlen($nouveau_mdp) < 12) {
                $erreur = "Le mot de passe doit contenir au moins 12 caractères.";
            } 
            // Vérifier que les mots de passe correspondent
            elseif ($nouveau_mdp !== $confirmation_mdp) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } 
            // Vérifier que ce n'est pas l'ancien mot de passe
            elseif (isset($_SESSION['ancien_mdp_hash']) && password_verify($nouveau_mdp, $_SESSION['ancien_mdp_hash'])) {
                $erreur = "Vous ne pouvez pas utiliser votre ancien mot de passe. Veuillez en choisir un nouveau.";
            } 
            else {
                $email = $_SESSION['email_recuperation'];
                $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE public.identifiants SET mdp = ? WHERE login = ?");
                $stmt->execute([$mdp_hash, $email]);
                
                // Nettoyer toutes les variables de session
                unset($_SESSION['code_verification']);
                unset($_SESSION['email_recuperation']);
                unset($_SESSION['code_expiration']);
                unset($_SESSION['essais_verification']);
                unset($_SESSION['ancien_mdp_hash']);
                
                $succes = "Mot de passe modifié avec succès !";
                $etape = 'termine';
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">
</head>
<body class="body__connexion">
    <div class="logo__connexion">
        <img src="front_end/assets/images/logo_Alizon.png" alt="Logo Alizon" width="150">
    </div>
    
    <div class="recuperation-container">
        <div class="header__recuperation">
            <h2>Mot de passe oublié</h2>
        </div>
        
        <?php if (!empty($succes)): ?>
            <div class="succes-message">
                <?= htmlentities($succes) ?>
            </div>
            <a href="connecter.php" class="btn__link">Se connecter</a>
        <?php else: ?>
        
        <form class="form__recuperation" method="POST">
            <?php if (!empty($erreur)): ?>
                <div class="error-message"><?= htmlentities($erreur) ?></div>
            <?php endif; ?>
            
            <?php if ($etape === 'email'): ?>
                <div class="input-group">
                    <label for="email" class="input-label">Votre email</label>
                    <input type="email" id="email" name="email" placeholder="email@exemple.com" required class="input__connexion" value="<?= htmlentities($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn__connexion">Envoyer le code</button>
                
            <?php elseif ($etape === 'verification'): ?>
                <div class="input-group">
                    <label for="code" class="input-label">Code reçu par email</label>
                    <input type="text" id="code" name="code" placeholder="123456" required class="input__connexion" maxlength="6" pattern="[0-9]{6}" title="6 chiffres uniquement">
                    <small>Code à 6 chiffres - Valable 5 minutes - <?= (3 - $_SESSION['essais_verification']) ?> essai(s) restant(s)</small>
                </div>
                <button type="submit" class="btn__connexion">Vérifier</button>
                
            <?php elseif ($etape === 'nouveau_mdp'): ?>
                <div class="input-group">
                    <label for="nouveau_mdp" class="input-label">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mdp" name="nouveau_mdp" placeholder="12 caractères minimum" required class="input__connexion" minlength="12">
                    <small>Minimum 12 caractères. Ne peut pas être votre ancien mot de passe.</small>
                </div>
                <div class="input-group">
                    <label for="confirmation_mdp" class="input-label">Confirmation</label>
                    <input type="password" id="confirmation_mdp" name="confirmation_mdp" placeholder="Retapez le mot de passe" required class="input__connexion" minlength="12">
                </div>
                <button type="submit" class="btn__connexion">Changer le mot de passe</button>
            <?php endif; ?>
            
            <div class="recuperation-links">
                <a href="connecter.php">Retour connexion</a>
                <?php if ($etape === 'verification' && $_SESSION['essais_verification'] < 3): ?>
                    <br><a href="recupmdp.php?etape=email">Renvoyer le code</a>
                <?php elseif ($etape === 'verification' && $_SESSION['essais_verification'] >= 3): ?>
                    <br><a href="recupmdp.php">Redémarrer la procédure</a>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
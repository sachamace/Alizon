<?php
session_start();
include 'config.php';

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $motdepasse = trim($_POST['motdepasse']);
    
    try {
        // Vérifier si l'email existe dans la table identifiants pour un vendeur
        $stmt = $pdo->prepare("
            SELECT i.id_num, i.login, i.mdp, cv.id_vendeur, cv.raison_sociale 
            FROM public.identifiants i 
            JOIN public.compte_vendeur cv ON i.id_num = cv.id_num 
            WHERE i.login = ?
        ");
        $stmt->execute([$email]);
        $vendeur = $stmt->fetch();
        
        // Vérifier si le vendeur existe ET si le mot de passe correspond (en clair)
        if ($vendeur && $motdepasse === $vendeur['mdp']) {
            // Connexion réussie
            $_SESSION['vendeur_id'] = $vendeur['id_vendeur'];
            $_SESSION['vendeur_nom'] = $vendeur['raison_sociale'];
            $_SESSION['vendeur_email'] = $vendeur['login'];
            $_SESSION['est_connecte'] = true;
            
            // Redirection vers le tableau de bord
            header("Location: index.php?page=dashboard");
            exit();
        } else {
            // Identifiants incorrects
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
                <input type="email" id="email" name="email" placeholder="Votre adresse email" required class="input__connexion" value="<?= htmlentities($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input type="password" id="motdepasse" name="motdepasse" placeholder="Votre mot de passe" required class="input__connexion">
            </div>
            
            <a href="recupmdp.php" class="forgot-password">Mot de passe oublié ?</a>
            
            <button type="submit" class="btn__connexion">Se connecter</button>
            
            <div class="separator">ou</div>
            
            <a href="creercompte.php" class="btn__creer-compte">Créer Compte</a>
        </form>
    </div>
</body>
</html>
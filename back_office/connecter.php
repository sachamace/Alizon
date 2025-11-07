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
        
        <form class="form__connexion" method="POST" >
            <?php if (isset($_GET['erreur'])): ?>
                <div class="error-message">Email ou mot de passe incorrect</div>
            <?php endif; ?>
            
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input type="email" id="email" name="email" placeholder="Votre adresse email" required class="input__connexion">
            </div>
            
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input type="password" id="motdepasse" name="motdepasse" placeholder="Votre mot de passe" required class="input__connexion">
            </div>
            
            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
            
            <button type="submit" class="btn__connexion">Se connecter</button>
            
            <div class="separator">ou</div>
            
            <a href="creercompte.php" class="btn__creer-compte">Créer Compte</a>
        </form>
    </div>
</body>
</html>
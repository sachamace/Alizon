<?php
session_start();
include 'config.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$erreur_mdp = "";
$erreur_ident = "";
$mdp = "";
$email = "";

// Vérifier si l'utilisateur a déjà validé son âge dans cette session
$age_verifie = isset($_SESSION['age_verifie']) && $_SESSION['age_verifie'] === true;

// Gestion de la vérification d'âge
if (isset($_POST['verif_age'])) {
    if ($_POST['verif_age'] === 'oui') {
        $_SESSION['age_verifie'] = true;
        $age_verifie = true;
    } else {
        // L'utilisateur a dit non, on le redirige ou on affiche un message
        $erreur_age = "Vous devez avoir 18 ans ou plus pour vous connecter.";
    }
}

// Connexion normale uniquement si l'âge est vérifié
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['motdepasse']) && $age_verifie) {
    $email = trim($_POST['adresse_mail']);
    $mdp = trim($_POST['motdepasse']);
    
    $user_sql = $pdo->prepare("
        SELECT i.id_num, i.login, i.mdp, cv.id_client 
        FROM public.identifiants i 
        JOIN public.compte_client cv ON i.id_num = cv.id_num 
        WHERE i.login = ?
    ");
    $user_sql->execute([$email]);
    $user = $user_sql->fetch();
    
    // 1) LOGIN INCORRECT
    if (!$user) {
        $erreur_ident = "Identifiant incorrect";
    }
    // 2) MOT DE PASSE INCORRECT
    elseif ($mdp !== $user['mdp']) {
        $erreur_mdp = "Mot de passe incorrect";
    }
    // 3) OK → CONNECTER
    else {
        // Récup panier
        $panier_sql = $pdo->prepare("SELECT id_panier FROM public.panier WHERE id_client = ?");
        $panier_sql->execute([$user['id_client']]); 
        $panier = $panier_sql->fetch();

        $_SESSION['id'] = $user['id_num'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['id_client'] = $user['id_client'];
        $_SESSION['id_panier'] = $panier['id_panier'];

        echo "<script>window.location.href = '/index.php';</script>";
        exit();
    }
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
    <style>
        /* Styles pour la popup d'âge */
        .age-popup-overlay {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(236, 236, 236, 0.29);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .age-popup-content {
            background: white;
            padding: 50px;
            border-radius: 15px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .age-popup-content h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 26px;
        }
        
        .age-popup-content p {
            color: #555;
            margin-bottom: 30px;
            font-size: 18px;
        }

        .age-popup-content input,
        .age-popup-content input {
            margin-bottom: 20px;
        }
        
        .age-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .btn-age {
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-oui {
            background-color: #28a745;
            color: white;
        }
        
        .btn-oui:hover {
            background-color: #218838;
            transform: scale(1.05);
        }
        
        .btn-non {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-non:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }
        
        .erreur-age {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body class="body__connexion">
    <?php if (!$age_verifie): ?>
    <div class="age-popup-overlay">
        <div class="age-popup-content">
            <h2>Vérification d'âge</h2>
            
            
            <form method="post" action="">
                <p><strong>Avez-vous plus de 18 ans ?</strong></p>
                <p>Certains de nos produits sont réservés aux personnes majeures et ne conviennent pas aux personnes mineurs.<br>
                    Veuillez donc accepter les conditions d'utilistions ci-dessous.</p>
                <input type="checkbox" required> <label>Accepter les condtions d'utilisations. *</label>
                <div class="age-buttons">
                    <button type="submit" name="verif_age" value="oui" class="btn-age btn-oui">
                        Oui
                    </button>
                    <button type="submit" name="verif_age" value="non" class="btn-age btn-non">
                        Non
                    </button>
                </div>
            </form>
            <?php if (isset($erreur_age)): ?>
                <div class="erreur-age">
                    <?= htmlspecialchars($erreur_age) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($age_verifie): ?>
    <div class="container__connexion">
        <div class="header__connexion">
            <h2>Rebonjour</h2>
        </div>
        <form action="" class="form__connexion" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input class="input__connexion" type="email" name="adresse_mail" placeholder="Votre adresse email" value="<?= htmlspecialchars($email) ?>" required />
                <?php
                    if (!empty($erreur_ident)) {
                        echo "<span style='color:red'>$erreur_ident</span><br />";
                    }
                ?>
            </div>
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input class="input__connexion" type="password" name="motdepasse" placeholder="Votre mot de passe" required />
                <?php
                    if (!empty($erreur_mdp)) {
                        echo "<span style='color:red'>$erreur_mdp</span><br />";
                    }
                ?>
            </div>
            <a href="recupmdp.php" class="forgot-password">Mot de passe oublié ?</a>
            <button type="submit" class="btn__connexion">Se connecter</button>
            <div class="separator"><p>ou</p></div>
            <label><a href="createcompte.php" class="btn__creer-compte">Créer compte</a></label>
            <a href="../../../back_office/connecter.php" class="forgot-password">Côté Vendeur</a>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>
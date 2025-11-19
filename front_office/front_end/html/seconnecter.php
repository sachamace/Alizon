<?php
    session_start();
    include 'config.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT version();");

    $erreur_mdp = "";
    $erreur_ident = "";
    $mdp = "";
    $email = "" ;
    if($_SERVER["REQUEST_METHOD"]==="POST"){
        $email = trim($_POST['adresse_mail']);
        $mdp = trim($_POST['motdepasse']);
        $user_sql = $pdo->prepare("SELECT i.id_num, i.login, i.mdp, cv.id_client FROM public.identifiants i JOIN public.compte_client cv ON i.id_num = cv.id_num WHERE i.login = ?");
        $user_sql->execute([$email]);
        $user = $user_sql->fetch();
        $panier_sql = $pdo->prepare("SELECT id_panier FROM public.panier WHERE id_client = ?");
        $panier_sql->execute([$user['id_num']]);
        $panier = $panier_sql->fetch();
        if($user){
            if(strcmp($mdp,$user['mdp']) == 0){
                $_SESSION['id'] = $user['id_num'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['id_panier'] = $panier['id_panier'];
                header("Location: accueil.php");
                exit();
            }
            else{
                $erreur_mdp = "Mot de passe Incorrect";
            }
        }
        else{
            $erreur_ident = "Identifiant Incorrect";
        }

    }
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter - Clients</title>
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>

<body class="body__connexion">
    <div class="container__connexion">
        <div class="header__connexion">
            <h2>Se connecter</h2>
        </div>
        <form action =""class="form__connexion"  method="post" enctype = "multipart/form-data">
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input class="input__connexion" type="email"  name="adresse_mail" placeholder="Adresse de Mail" value ="<?= $email?>"required />
                <?php
                    if (!empty($erreur_ident)){
                        echo "<span class='error-message'>$erreur_ident</span><br />";
                    }
                ?>
            </div>
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input class="input__connexion" type="password"  name="motdepasse" placeholder="Mot de passe"required />
                <?php
                    if (!empty($erreur_mdp)){
                        echo "<span class='error-message'>$erreur_mdp</span><br />";
                    }
                ?>
            </div>
            <a href="recupmdp.php" class="forgot-password">Mot de passe oublié ?</a>
            <button type="submit" class="btn__connexion">Se connecter</button>
            <div class="separator"></div>
            <label><a href="createcompte.php" class="btn__creer-compte">Créer un compte</a></label>
            <a href="../../../back_office/connecter.php" class="forgot-password">Coté Vendeur</a>
        </form>
    </div>
</body>
</html>
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
        $user_sql = $pdo->prepare("SELECT * FROM public.identifiants WHERE login = :login");
        $user_sql->execute(['login' => $email]);
        $user = $user_sql->fetch();
        if($user){
            if(strcmp($mdp,$user['mdp']) == 0){
                $_SESSION['id'] = $user['id_num'];
                $_SESSION['login'] = $user['login'];
                header("Location: test.php");
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

<body class="body__creercompte">
    <form action =""class="form__creercompte"  method="post" enctype = "multipart/form-data">
        <h2>Se connecter</h2>
        <input class="input__creercompte" type="text"  name="adresse_mail" placeholder="Adresse de Mail" value ="<?= $email?>"required />
        <br />
        <?php
            if (!empty($erreur_ident)){
                echo "<span class='error-message'>$erreur_ident</span><br />";
            }
        ?>
        <input class="input__creercompte" type="password"  name="motdepasse" placeholder="Mot de passe"required />
        <br />
        <?php
            if (!empty($erreur_mdp)){
                echo "<span class='error-message'>$erreur_mdp</span><br />";
            }
        ?>
        <input class="input__creercompte--submit" type="submit" value="Se connecter" />
        <br />
        <label><a href="createcompte.php">Créer un compte</a></label>
    </form>
</body>
</html>
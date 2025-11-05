<?php
    $erreur_siren ="";
    $erreur_tel ="";
    $erreur_mail ="";
    $erreur_mdp ="";
    $num_siren = "" ;
    $tel = "" ;
    $mail= "" ;
    $mdp = "" ;
    $mdpconfirm = "";
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        $num_siren = trim($_POST['num_siren']);
        $tel = trim($_POST['tel']);
        $mail = trim($_POST['email']);
        $mdp = trim($_POST['motdepasse']);
        $mdpconfirm = trim($_POST['confirm']);
        if (!preg_match("/^[0-9]{9}$/", $num_siren)) {
            $erreur_siren = "Total de chiffre invalides ! Nombre de chiffre qu'on requière = 9";
        }
        if(!preg_match("/^[0-9]{10}$/",$tel)){
            $erreur_tel = "Total de chiffres invalides ! Nombre de chiffre qu'on requière = 10";
        }
        if(!preg_match("/^.{12,}$/",$mdp)){
            $erreur_mdp="Nombre de caractère trop petit ! Nombre de caractère minimale = 12";
        }
        if(strcmp($mdp,$mdpconfirm) != 0){
            $erreur_confirm = "La confirmation du mot de passe ne correspond pas au mot de passe que vous avez mis.";
        }
    }
    
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">

</head>

<body class="body__creercompte">
    <form class="form__creercompte" method="post" enctype = "multipart/form-data">
        <h2>Créer un compte</h2>
        <!-- Numéro de Siren -->
        <input class="input__creercompte" type="text" id="num_siren" name="num_siren" placeholder="Numéro de SIREN *" value ="<?= $num_siren?>"required />
        <?php
            if (!empty($erreur_siren)){
                echo "<span class='error-message'>$erreur_siren</span>";
            }
        ?>
        <br />
        <!-- Raison Sociale -->
        <select class="select__raison" id="raison" name="raison">
                <option disabled selected>Raison Sociale</option>
                <option value="SA">SA</option>
                <option value="SAS">SAS</option>
                <option value="SARL">SARL</option>
                <option value="EURL">EURL</option>
                <option value="SASU">SASU</option>
                <option value="SCP">SCP</option>
        </select>
        <br />
        <!-- Numéro de Téléphone -->
        <input class="input__creercompte" type="tel" id="tel" name="tel" placeholder="Numéro de Téléphone *" value ="<?= $tel?>"required />
        <br />
        <?php
            if (!empty($erreur_tel)){
                echo "<span>$erreur_tel</span>";
            }
        ?>
        <!-- Email -->
        <input class="input__creercompte" type="email"  name="email" placeholder="Adresse Mail *" value ="<?= $mail?>"required />
        <br />
        <!-- Mot de passe -->
        <input class="input__creercompte" type="password"  name="motdepasse" placeholder="Mot de passe *" value ="<?= $mdp?>"required />
        <br />
        <?php
            if (!empty($erreur_mdp)){
                echo "<span>$erreur_mdp</span>";
            }
        ?>
        <!-- Confirmer le mot de passe -->
        <input class="input__creercompte"  type="password" name="confirm" placeholder="Confirmer le mot de passe *" required />
        <!-- Bouton de création de compte -->
        <br />
        <?php
            if(!empty($erreur_confirm)){
                echo "<span>$erreur_confirm</span>";
            }
        ?>
        <input class="input__creercompte--submit" type="submit" value="Créer un compte" />

        <label><a href="connecter.php">Se connecter</a></label>
    </form>
</body>
</html>
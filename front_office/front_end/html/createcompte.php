<?php
    include 'config.php';
    $stmt = $pdo->query("SELECT version();");
?>

<?php

    $erreur_mail ="";//
    $erreur_tel ="";//
    $erreur_mdp ="";//
    $erreur_nom = "";//
    $erreur_prenom = "";//
    $erreur_confirm = "";//
    $nom = "";
    $pseudo = "";
    $prenom = "";
    $mail = "" ;
    $tel = "" ;
    $mail= "" ;
    $password = "" ;
    $mdpconfirm = "";
    $date = "";
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        $pseudo = trim($_POST['pseudo']);
        $mail = trim($_POST['adresse_mail']);
        $tel = trim($_POST['tel']);
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $password = trim($_POST['motdepasse']);
        $mdpconfirm = trim($_POST['confirm']);
        $date = trim($_POST['date_de_naissance']);
        if(!preg_match("/^0\d{9}$/",$tel)){
            $erreur_tel = "Total de chiffres invalides ! Nombre de chiffre qu'on requière = 10 et un zéro au début.";
        }
        if(!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.fr|yahoo\.com|orange\.fr|free\.fr|sfr\.fr)$/",$mail)){
            $erreur_mail = "Email invalide ou domaine non autorisé.(Gmail , Outlook , Yahoo , Orange , free et sfr acceptés.";
        }
        if(!preg_match("/^.{12,}$/",$password)){
            $erreur_mdp = "Nombre de caractère trop petit ! Minimum = 12";
        }
        if(!preg_match("/^[a-zA-ZÀ-ÿ]+([ -][a-zA-ZÀ-ÿ]+)?$/",$nom)){
            $erreur_nom= "Nom invalides ! (Lettres , '-' et un espace acceptés).";
        }
        if(!preg_match("/^[a-zA-ZÀ-ÿ]+([ -][a-zA-ZÀ-ÿ]+)?$/",$prenom)){
            $erreur_prenom = "Prénom invalides ! (Lettres , '-' et un espace acceptés).";
        }
        if(strcmp($password,$mdpconfirm) != 0){
            $erreur_confirm = "La confirmation du mot de passe ne correspond pas au mot de passe que vous avez mis.";
        }
        $erreurs = [ // la liste entière des erreurs par ce que y'en a beaucoup 
            $erreur_mail,
            $erreur_tel,
            $erreur_mdp,
            $erreur_nom,
            $erreur_prenom,
            $erreur_confirm,
            $erreur_pseudo
        ];
        if(!array_filter($erreurs)){
            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT); // Permet le hachage du mot de passe.
            $sqlident = "INSERT INTO public.identifiants (login,mdp) VALUES (:login, :mdp)";
            $stmtident = $pdo->prepare($sqlident);
            $stmtident->execute([
                'login' => $mail,
                'mdp' => $mdp_hash
            ]);
            // Partie pour récupérer le numéro de l'identifiants qu'on vient de créer .
            $id_num_sql = "SELECT id_num FROM public.identifiants WHERE login = :login";
            $stmtid = $pdo->prepare($id_num_sql);
            $stmtid->execute(['login' => $mail]);
            $id_num = (int) $stmtid->fetchColumn();
            // Partie pour savoir si il est majeur.
            $date_naissance = new DateTime($date);
            $aujourdhui = new DateTime();
            $age = $aujourdhui->diff($date_naissance)->y;
            if($age>=18){
                $majeur = true;
            }
            else{
                $majeur = false;
            }
            $sqlclient = "INSERT INTO public.compte_client (adresse_mail,est_majeur,date_naissance,nom,prenom,pseudo,num_tel,id_num) VALUES (:adresse_mail,:est_majeur,:date_naissance,:nom,:prenom,:pseudo,:num_tel,:id_num)";
            $stmtclient = $pdo->prepare($sqlclient);
            $stmtclient->execute([
                'adresse_mail' => $mail,
                'est_majeur' => $majeur,
                'date_naissance' => $date,
                'nom' => $nom,
                'prenom' => $prenom,
                'pseudo' => $pseudo,
                'num_tel' => $tel,
                'id_num' => $id_num
            ]);
            header("Location: seconnecter.php");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - Clients</title>
    <link rel="stylesheet" href="../front_end/assets/csss/style.css">
    <script src="../assets/js/date.js"></script> 
</head>

<body class="body__creercompte">
    <form action ="" class="form__creercompte"  method="post" enctype = "multipart/form-data">
        <h2>Créer un compte</h2>
        <!-- Prénom et Nom du Client -->
        <input class="input__creercompte" type="text"  name="nom" placeholder="Nom *" value ="<?= $nom?>"required />
        <br />
        <?php
            if (!empty($erreur_nom)){
                echo "<span class='error-message'>$erreur_nom</span><br />";
            }
        ?>
        <input class="input__creercompte" type="text"  name="prenom" placeholder="Prénom *" value ="<?= $prenom?>"required />
        <br />
        <?php
            if (!empty($erreur_prenom)){
                echo "<span class='error-message'>$erreur_prenom</span><br />";
            }
        ?>
        <!-- Adresse Mail -->
        <input class="input__creercompte" type="text"  name="adresse_mail" placeholder="Adresse de Mail *" value ="<?= $mail?>"required />
        <br />
        <?php
            if (!empty($erreur_mail)){
                echo "<span class='error-message'>$erreur_mail</span><br />";
            }
        ?>
        <!-- Numéro de Téléphone -->
        <input class="input__creercompte" type="tel"  name="tel" placeholder="Numéro de Téléphone *" value ="<?= $tel?>"required />
        <br />
        <?php
            if (!empty($erreur_tel)){
                echo "<span class='error-message'>$erreur_tel</span><br />";
            }
        ?>
        <!-- Date de Naissance -->
        <input class="input__creercompte" type="date" id="date_de_naissance" name="date_de_naissance" placeholder="Date de Naissance *"required />
        <br />
        <!-- Nom_Utilisateur -->
        <input class="input__creercompte" type="text"  name="pseudo" placeholder="Nom d'Utilisateur *" value ="<?= $pseudo?>"required />
        <br />
        <?php
            if (!empty($erreur_pseudo)){
                echo "<span class='error-message'>$erreur_pseudo</span><br />";
            }
        ?>
        <!-- Mot de passe -->
        <input class="input__creercompte" type="password"  name="motdepasse" placeholder="Mot de passe "required />
        <br />
        <?php
            if (!empty($erreur_mdp)){
                echo "<span class='error-message'>$erreur_mdp</span><br />";
            }
        ?>
        <!-- Confirmer le mot de passe -->
        <input class="input__creercompte"  type="password" name="confirm" placeholder="Confirmer le mot de passe *" required />
        <!-- Bouton de création de compte -->
        <br />
        <?php
            if (!empty($erreur_confirm)){
                echo "<span class='error-message'>$erreur_confirm</span><br />";
            }
        ?>
        
        <input class="input__creercompte--submit" type="submit" value="Créer un compte" />
        <br />
        <label><a href="seconnecter.php">Se connecter</a></label>
    </form>
</body>
</html>
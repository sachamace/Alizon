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
        // TEST SUR Base de données 
        // TEST pour le téléphone.
        $tel_sql = "SELECT num_tel FROM public.compte_client";
        $stmt_tel = $pdo->query($tel_sql);
        $tab_tel = $stmt_tel->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($tab_tel as $num_tel) {
            if($tel == $num_tel){
                $erreur_tel = "Il existe déja !";
            }
        }
        // TEST POUR email
        $email_sql = "SELECT adresse_mail FROM public.compte_client";
        $stmt_email= $pdo->query($email_sql);
        $tab_email = $stmt_email->fetchAll(PDO::FETCH_COLUMN, 0);
        
        foreach ($tab_email as $email) {
            if($mail == $email){
                $erreur_mail = "Il existe déja !";
            }
        }
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
            $erreur_confirm
        ];
        if(!array_filter($erreurs)){
            $sqlident = "INSERT INTO public.identifiants (login,mdp) VALUES (:login, :mdp)";
            $stmtident = $pdo->prepare($sqlident);
            $stmtident->execute([
                'login' => $mail,
                'mdp' => $password
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
            echo $age;
            if($age>=18){
                $majeur = 'true';
            }
            else{
                $majeur = 'false';
            }
            $sqlclient = "INSERT INTO public.compte_client (adresse_mail,est_majeur,date_naissance,nom,prenom,num_tel,id_num) VALUES (:adresse_mail,:est_majeur,:date_naissance,:nom,:prenom,:num_tel,:id_num)";
            $stmtclient = $pdo->prepare($sqlclient);
            $stmtclient->execute([
                'adresse_mail' => $mail,
                'est_majeur' => $majeur,
                'date_naissance' => $date,
                'nom' => $nom,
                'prenom' => $prenom,
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
    <link rel="stylesheet" href="../assets/csss/style.css">
    <script src="../assets/js/date.js"></script> 
</head>

<body class="body__connexion">
    <div class="container__connexion">
        <div class="header__connexion">
            <h2>Créer un compte</h2>
        </div>
        <form action ="" class="form__connexion"   method="post" enctype = "multipart/form-data">
            <!-- Prénom et Nom du Client -->
            <div class="input-group">
                <label for="nom" class="input-label">Nom</label>
                <input class="input__connexion" type="text"  name="nom" placeholder="Nom *" value ="<?= $nom?>"required />
                <?php
                    if (!empty($erreur_nom)){
                        echo "<span class='error-message'>$erreur_nom</span><br />";
                    }
                ?>
            </div>
            
            <div class="input-group">
                <label for="prenom" class="input-label">Prénom</label>
                <input class="input__connexion" type="text"  name="prenom" placeholder="Prénom *" value ="<?= $prenom?>"required />
                <?php
                    if (!empty($erreur_prenom)){
                        echo "<span class='error-message'>$erreur_prenom</span><br />";
                    }
                ?>
            </div>
            <!-- Adresse Mail -->
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input class="input__connexion" type="text"  name="adresse_mail" placeholder="Adresse de Mail *" value ="<?= $mail?>"required />
                <?php
                    if (!empty($erreur_mail)){
                        echo "<span class='error-message'>$erreur_mail</span><br />";
                    }
                ?>
            </div>
            <!-- Numéro de Téléphone -->
            <div class="input-group">
                <label for="tel" class="input-label">Numéro de Téléphone</label>
                <input class="input__connexion" type="tel"  name="tel" placeholder="Numéro de Téléphone *" value ="<?= $tel?>"required />
                <?php
                    if (!empty($erreur_tel)){
                        echo "<span class='error-message'>$erreur_tel</span><br />";
                    }
                ?>
            </div>
            <!-- Date de Naissance -->
            <div class="input-group">
                <label for="naissance" class="input-label">Date de Naissance</label>
                <input class="input__connexion" type="date" id="date_de_naissance" name="date_de_naissance" placeholder="Date de Naissance *"required />
            </div>
            <div class="input-group">
                <!-- Mot de passe -->
                <label for="mdp" class="input-label">Mot de passe</label>
                <input class="input__connexion" type="password"  name="motdepasse" placeholder="Mot de passe "required />
                <?php
                    if (!empty($erreur_mdp)){
                        echo "<span class='error-message'>$erreur_mdp</span><br />";
                    }
                ?>                
            </div>
            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
            <div class="input-group">
                <!-- Confirmer le mot de passe -->
                 <label for="confirm" class="input-label">Confirmer le mot de passe</label>
                <input class="input__connexion"  type="password" name="confirm" placeholder="Confirmer le mot de passe *" required />
                <?php
                    if (!empty($erreur_confirm)){
                        echo "<span class='error-message'>$erreur_confirm</span><br />";
                    }
                ?>
            </div>



            <button type="submit" class="btn__connexion">Créer un compte</button>
            
            <div class="separator"></div>
            
            <a href="seconnecter.php" class="btn__creer-compte">Se connecter</a>
        </form>
    </div>
</body>
</html>
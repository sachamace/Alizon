<?php
    include 'config.php';
    $stmt = $pdo->query("SELECT version();");
    // Init des variables erreur et des valeurs qui seront dans les input du formulaire.
    $erreur_siren ="";
    $erreur_mail = "";
    $erreur_tel ="";
    $erreur_mdp ="";
    $erreur_statut ="";
    $erreur_confirm ="";
    $num_siren = "" ;
    $tel = "" ;
    $mail= "" ;
    $mdp = "" ;
    $num_entreprise = "" ;
    $mdpconfirm = "";
    $raison_sociale = "" ;
    $statut_juridique = "" ;
    
    // Si le bouton créer compte a été cliqué alors .
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        // Ajout de chaque input à une valeur.
        $num_siren = trim($_POST['num_siren']);
        $tel = trim($_POST['tel']);
        $mail = trim($_POST['email']);
        $mdp = trim($_POST['motdepasse']);
        $mdpconfirm = trim($_POST['confirm']);
        $raison_sociale = trim($_POST['raison']);
        $statut_juridique = trim($_POST['statut']);
        // TEST SUR Base de données 
        // TEST pour le num_siren.
        $siren_sql = "SELECT num_siren FROM public.compte_vendeur";
        $stmt_siren = $pdo->query($siren_sql);
        $tab_num_siren = $stmt_siren->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($tab_num_siren as $siren) {
            if($num_siren == $siren){
                $erreur_siren = "Il existe déja !";
            }
        }
        // TEST POUR email
        $email_sql = "SELECT adresse_mail FROM public.compte_vendeur";
        $stmt_email= $pdo->query($email_sql);
        $tab_email = $stmt_email->fetchAll(PDO::FETCH_COLUMN, 0);
        
        foreach ($tab_email as $email) {
            if($mail == $email){
                $erreur_mail = "Il existe déja !";
            }
        }
        // TEST POUR Numéro de Téléphone
        $tel_sql = "SELECT num_tel FROM public.compte_vendeur";
        $stmt_tel= $pdo->query($tel_sql);
        $tab_tel = $stmt_tel->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($tab_tel as $telephone) {
            if($tel == $telephone){
                $erreur_tel = "Il existe déja !";
            }
        }
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
        if(empty($statut_juridique)){
            $erreur_statut ="Vous n'avez pas sélectionner de raison_sociale.";
        }
        $erreurs = [ // la liste entière des erreurs par ce que y'en a beaucoup 
            $erreur_siren,
            $erreur_confirm,
            $erreur_tel,
            $erreur_mdp,
            $erreur_statut,
            $erreur_mail
        ];
        if(!array_filter($erreurs)){ // array_filter permet de vérifier si y'a des élements dans la array en gros si y'a rien ça fait la condition .
            // La parties pour créer l'identifiants
            //if(){
                $sqlident = "INSERT INTO public.identifiants (login,mdp) VALUES (:login, :mdp)";
                $stmt = $pdo->prepare($sqlident);
                $stmt->execute([
                    'login' => $mail,
                    'mdp' => $mdp
                ]);
                // Partie pour récupérer le numéro de l'identifiants qu'on vient de créer .
                $id_num_sql = "SELECT id_num FROM public.identifiants WHERE login = :login";
                $stmtid = $pdo->prepare($id_num_sql);
                $stmtid->execute(['login' => $mail]);
                $id_num = (int) $stmtid->fetchColumn();
                // Partie pour créer le compte vendeur avec toute ces informations.
                $sqlvendeur = "INSERT INTO public.compte_vendeur (raison_sociale,statut_juridique,num_siren,num_tel,adresse_mail,id_num) VALUES (:raison_sociale,:statut_juridique,:num_siren,:num_tel,:adresse_mail,:id_num)";
                $stmtvendeur = $pdo->prepare($sqlvendeur);
                $stmtvendeur->execute([
                    'raison_sociale' => $raison_sociale,
                    'statut_juridique' => $statut_juridique,
                    'num_siren' => $num_siren,
                    'num_tel' => $tel,
                    'adresse_mail' => $mail,
                    'id_num' => $id_num
                ]);
            echo "<script>
                window.location.href = 'connecter.php';
            </script>";
            exit();
            //}   
        }
    }
    
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">
</head>

<body class="body__creation-compte">
    <div class="logo__creation-compte">
        <img src="front_end/assets/images/logo_Alizon.png" alt="Logo Alizon" width="150">
    </div>
    
    <div class="container__creation-compte">
        <div class="header__creation-compte">
            <h1>Rejoignez Alizon</h1>
            <h2>Créer votre compte vendeur</h2>
        </div>
        <form action="" class="form__creation-compte" method="post" enctype="multipart/form-data">
            <!-- Raison sociale de l'entreprise -->
            <div class="input-group">
                <label for="raison" class="input-label">Raison sociale</label>
                <input class="input__creation-compte" type="text" name="raison" placeholder="Nom de votre entreprise *" value="<?= $raison_sociale?>" required />
            </div>

            <div class="form-row">
                <!-- Statut juridique -->
                <div class="input-group">
                    <label for="statut" class="input-label">Statut juridique</label>
                    <select class="select__creation-compte" name="statut" value="<?= $statut_juridique?>">
                        <option disabled selected>Choisir</option>
                        <option value="SA">SA</option>
                        <option value="SAS">SAS</option>
                        <option value="SARL">SARL</option>
                        <option value="EURL">EURL</option>
                        <option value="SASU">SASU</option>
                        <option value="SCP">SCP</option>
                    </select>
                    <?php if (!empty($erreur_statut)): ?>
                        <span class="error-message"><?= $erreur_statut ?></span>
                    <?php endif; ?>
                </div>

                <!-- Numéro de Siren -->
                <div class="input-group">
                    <label for="num_siren" class="input-label">Numéro de SIREN</label>
                    <input class="input__creation-compte" type="text" name="num_siren" placeholder="Numéro de SIREN *" value="<?= $num_siren?>" required />
                    <?php if (!empty($erreur_siren)): ?>
                        <span class="error-message"><?= $erreur_siren ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="input-group">
                <label for="mail" class="input-label">E-Mail</label>
                <input class="input__creation-compte" type="email" name="email" placeholder="Adresse Mail *" value="<?= $mail?>" required />
                <?php if (!empty($erreur_mail)): ?>
                    <span class="error-message"><?= $erreur_mail ?></span>
                <?php endif; ?>
            </div>

            <!-- Numéro de Téléphone -->
            <div class="input-group">
                <label for="tel" class="input-label">Numéro de Téléphone</label>
                <input class="input__creation-compte" type="tel" id="tel" name="tel" placeholder="Numéro de Téléphone *" value="<?= $tel?>" required />
                <?php if (!empty($erreur_tel)): ?>
                    <span class="error-message"><?= $erreur_tel ?></span>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <!-- Mot de passe -->
                <div class="input-group">
                    <label for="mdp" class="input-label">Mot de passe</label>
                    <input id="mdp" class="input__connexion" type="password"  name="motdepasse" placeholder="Mot de passe "required />
                    <?php
                        if (!empty($erreur_mdp)){
                            echo "<span style='color:red'>$erreur_mdp</span><br />";
                        }
                    ?>
                    <div id="message-box">
                        <ul>
                            <li id="length" class="validation-item">12 caractères minimum</li>
                            <li id="lowercase" class="validation-item">Une minuscule</li>
                            <li id="uppercase" class="validation-item">Une majuscule</li>
                            <li id="special" class="validation-item">Un caractère spécial</li>
                        </ul>
                    </div> 
                </div>

                <!-- Confirmer le mot de passe -->
            <div class="input-group">
                <!-- Confirmer le mot de passe -->
                <label for="confirm" class="input-label">Confirmer le mot de passe</label>
                
                <input class="input__connexion" type="password" id="confirm" name="confirm" placeholder="Confirmer le mot de passe *" required />
                
                <span id="match-message" style="font-size: 0.8em; display:none;"></span>
                <?php
                    if (!empty($erreur_confirm)){
                        echo "<span style='color:red'>$erreur_confirm</span><br />";
                    }
                ?>
            </div>
            </div>

            <button type="submit" class="btn__creation-compte">Créer mon compte</button>
            
            <div class="separator">Déjà un compte ?</div>
            
            <a href="connecter.php" class="btn__connecter-compte">Se connecter</a>
        </form>
    </div>
    <script src="/front_office/front_end/assets/js/normalisation.js" ></script>
</body>
</html>
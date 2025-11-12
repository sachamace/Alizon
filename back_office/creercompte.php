<?php
include 'config.php';

// Init des variables erreur et des valeurs qui seront dans les input du formulaire.
$erreur_siren = "";
$erreur_tel = "";
$erreur_mdp = "";
$erreur_raison = "";
$erreur_confirm = "";
$num_siren = "";
$tel = "";
$mail = "";
$mdp = "";
$num_entreprise = "";
$mdpconfirm = "";
$raison_sociale = "";

// Si le bouton créer compte a été cliqué alors
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Ajout de chaque input à une valeur.
    $num_siren = trim($_POST['num_siren']);
    $tel = trim($_POST['tel']);
    $mail = trim($_POST['email']);
    $mdp = trim($_POST['motdepasse']);
    $mdpconfirm = trim($_POST['confirm']);
    $num_entreprise = trim($_POST['nom_entreprise']);
    $raison_sociale = trim($_POST['raison']);
    $fin_raison = $num_entreprise . " " . $raison_sociale;

    if (!preg_match("/^[0-9]{9}$/", $num_siren)) {
        $erreur_siren = "Total de chiffre invalides ! Nombre de chiffre qu'on requière = 9";
    }
    if (!preg_match("/^[0-9]{10}$/", $tel)) {
        $erreur_tel = "Total de chiffres invalides ! Nombre de chiffre qu'on requière = 10";
    }
    if (!preg_match("/^.{12,}$/", $mdp)) {
        $erreur_mdp = "Nombre de caractère trop petit ! Nombre de caractère minimale = 12";
    }
    if (strcmp($mdp, $mdpconfirm) != 0) {
        $erreur_confirm = "La confirmation du mot de passe ne correspond pas au mot de passe que vous avez mis.";
    }
    if (empty($raison_sociale)) {
        $erreur_raison = "Vous n'avez pas sélectionné de raison sociale.";
    }
    
    $erreurs = [
        $erreur_siren,
        $erreur_confirm,
        $erreur_tel,
        $erreur_mdp,
        $erreur_raison
    ];
    
    if (!array_filter($erreurs)) {
        // La partie pour créer l'identifiant
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
        $sqlident = "INSERT INTO public.identifiants (login, mdp) VALUES (:login, :mdp)";
        $stmt = $pdo->prepare($sqlident);
        $stmt->execute([
            'login' => $mail,
            'mdp' => $mdp_hash
        ]);
        
        // Partie pour récupérer le numéro de l'identifiant qu'on vient de créer
        $id_num_sql = "SELECT id_num FROM public.identifiants WHERE login = :login";
        $stmtid = $pdo->prepare($id_num_sql);
        $stmtid->execute(['login' => $mail]);
        $id_num = (int) $stmtid->fetchColumn();
        
        // Partie pour créer le compte vendeur avec toutes ces informations
        $sqlvendeur = "INSERT INTO public.compte_vendeur (raison_sociale, num_siren, num_tel, adresse_mail, id_num) VALUES (:raison_sociale, :num_siren, :num_tel, :adresse_mail, :id_num)";
        $stmtvendeur = $pdo->prepare($sqlvendeur);
        $stmtvendeur->execute([
            'raison_sociale' => $fin_raison,
            'num_siren' => $num_siren,
            'num_tel' => $tel,
            'adresse_mail' => $mail,
            'id_num' => $id_num
        ]);
        
        // Redirection vers la page de connexion
        header("Location: connecter.php");
        exit();
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

<body class="body__creercompte">
    <form class="form__creercompte" method="post" enctype="multipart/form-data">
        <h2>Créer un compte</h2>
        
        <!-- Numéro de Siren -->
        <input class="input__creercompte" type="text" name="num_siren" placeholder="Numéro de SIREN *" value="<?= htmlentities($num_siren) ?>" required />
        <?php if (!empty($erreur_siren)): ?>
            <span class='error-message'><?= $erreur_siren ?></span>
        <?php endif; ?>
        <br />
        
        <!-- Raison Sociale -->
        <select class="select__raison" name="raison" required>
            <option value="" disabled selected>Raison Sociale</option>
            <option value="SA" <?= $raison_sociale == 'SA' ? 'selected' : '' ?>>SA</option>
            <option value="SAS" <?= $raison_sociale == 'SAS' ? 'selected' : '' ?>>SAS</option>
            <option value="SARL" <?= $raison_sociale == 'SARL' ? 'selected' : '' ?>>SARL</option>
            <option value="EURL" <?= $raison_sociale == 'EURL' ? 'selected' : '' ?>>EURL</option>
            <option value="SASU" <?= $raison_sociale == 'SASU' ? 'selected' : '' ?>>SASU</option>
            <option value="SCP" <?= $raison_sociale == 'SCP' ? 'selected' : '' ?>>SCP</option>
        </select>
        <br />
        <?php if (!empty($erreur_raison)): ?>
            <span class='error-message'><?= $erreur_raison ?></span>
        <?php endif; ?>

        <!-- Numéro de Téléphone -->
        <input class="input__creercompte" type="tel" id="tel" name="tel" placeholder="Numéro de Téléphone *" value="<?= htmlentities($tel) ?>" required />
        <br />
        <?php if (!empty($erreur_tel)): ?>
            <span class='error-message'><?= $erreur_tel ?></span>
        <?php endif; ?>
        
        <!-- Nom de l'entreprise -->
        <input class="input__creercompte" type="text" name="nom_entreprise" placeholder="Nom de votre entreprise *" value="<?= htmlentities($num_entreprise) ?>" required />
        
        <!-- Email -->
        <input class="input__creercompte" type="email" name="email" placeholder="Adresse Mail *" value="<?= htmlentities($mail) ?>" required />
        <br />
        
        <!-- Mot de passe -->
        <input class="input__creercompte" type="password" name="motdepasse" placeholder="Mot de passe *" value="<?= htmlentities($mdp) ?>" required />
        <br />
        <?php if (!empty($erreur_mdp)): ?>
            <span class='error-message'><?= $erreur_mdp ?></span>
        <?php endif; ?>
        
        <!-- Confirmer le mot de passe -->
        <input class="input__creercompte" type="password" name="confirm" placeholder="Confirmer le mot de passe *" required />
        <br />
        <?php if (!empty($erreur_confirm)): ?>
            <span class='error-message'><?= $erreur_confirm ?></span>
        <?php endif; ?>
        
        <!-- Bouton de création de compte -->
        <input class="input__creercompte--submit" type="submit" value="Créer un compte" />

        <label><a href="connecter.php">Se connecter</a></label>
    </form>
</body>
</html>
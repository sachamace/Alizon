<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">

</head>

<<<<<<< HEAD
<body class="body__creercompte">
    <!-- /**<aside><img src="front_end/assets/images/logo_Alizon.png" alt="Logo Alizon" class="logo" width="270" height ="115"></aside>**/ -->
    <form class="form__creercompte" method="post" enctype = "multipart/form-data">
        <h2>Créer un compte</h2>
        <!-- Numéro de Siren -->
        <input type="text" id="num_siren" name="num_siren" placeholder="Numéro de SIREN"required />
        <br />
        <!-- Raison Sociale -->
        <input type="text" id="raison_sociale" name="raison_sociale" placeholder="Raison Sociale"required />
        <br />
        <!-- Numéro de Téléphone -->
        <input type="tel" id="tel" name="tel" placeholder="Numéro de Téléphone" required />
        <br />
        <!-- Email -->
        <input type="email" id="email" name="email" placeholder="Adresse Mail"required />
        <br />
        <!-- Mot de passe -->
        <input type="mdp" id="mdp" name="motdepasse" placeholder="Mot de passe"required />
        <br />
        <!-- Confirmer le mot de passe -->
        <input type="confirm" id="confirm" name="confirm" placeholder="Confirmer le mot de passe" required />
        <!-- Bouton de création de compte -->
        <br />
=======
<body>
    <img href="front_end/assets/images/logo_Alizon.png">
    <form method="post" enctype = "multipart/form-data">
        <h2>Créer un compte</h2>
        <!-- Numéro de Siren -->
        <label for="num_siren">Numéro de SIREN</label>
        <input type="text" id="num_siren" name="num_siren" required />
        <br />
        <!-- Raison Sociale -->
        <label for="raison_sociale">Raison Sociale</label>
        <input type="text" id="raison_sociale" name="raison_sociale" required />
        <br />
        <!-- Numéro de Téléphone -->
        <label for="tel">Numéro de Télephone</label>
        <input type="tel" id="tel" name="tel" />
        <br />
        <!-- Email -->
        <label for="email">Adresse Mail</label>
        <input type="email" id="email" name="email" required />
        <!-- Bouton de création de compte -->
>>>>>>> f51834d (:construction: formulaire creer compte html fait !)
        <input type="submit" value="Soumettre" />
    </form>
</body>
</html>
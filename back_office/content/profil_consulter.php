<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <section class="profil">
        <h3>Raison sociale</h3>
        <p><?php echo htmlentities($profil["raison_sociale"])?></p>

        <h3>Adresse email</h3>
        <p><?php echo htmlentities($profil["adresse_mail"])?></p>

        <h3>Mot de passe</h3>
        <p><?php echo htmlentities($mdp)?></p>

        <h3>Numéro de téléphone</h3>
        <p><?php echo htmlentities($profil["num_tel"])?></p>

        <h3>Numéro de SIREN</h3>
        <p><?php echo htmlentities($profil["num_siren"])?></p>
    </section>
</body>
</html>
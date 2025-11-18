<section class="profil-container">
    <h2>Votre profil</h2>
    <article>
        <h3>Raison sociale</h3>
        <p><?php echo htmlentities($profil["raison_sociale"]) ?></p>
    </article>

    <article>
        <h3>Adresse email</h3>
        <p><?php echo htmlentities($profil["adresse_mail"]) ?></p>
    </article>

    <article>
        <h3>Mot de passe</h3>
        <p>********</p>
    </article>

    <article>
        <h3>Numéro de téléphone</h3>
        <p><?php echo htmlentities($profil["num_tel"]) ?></p>
    </article>

    <article>
        <h3>Numéro de SIREN</h3>
        <p><?php echo htmlentities($profil["num_siren"]) ?></p>
    </article>

    <div class="btn-modif">
        <a href="index.php?page=profil&type=modifier" class="modifier">Modifier</a>
    </div>
</section>
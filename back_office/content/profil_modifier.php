<section class="profil-container">
    <form action="">
        <h2>Votre profil</h2>
        <article>
            <h3>Raison sociale</h3>
            <input type="text" name="raison_sociale" id="raison_sociale" value="<?php echo htmlentities($profil["raison_sociale"]) ?>">
        </article>

        <article>
            <h3>Adresse email</h3>
            <input type="text" name="adresse_mail" id="adresse_mail" value="<?php echo htmlentities($profil["adresse_mail"]) ?>">
        </article>

        <article>
            <h3>Mot de passe</h3>
            <p>********</p>
        </article>

        <article>
            <h3>Numéro de téléphone</h3>
            <input type="text" name="num_tel" id="num_tel" value="<?php echo htmlentities($profil["num_tel"]) ?>">
        </article>

        <article>
            <h3>Numéro de SIREN</h3>
            <input type="text" name="num_siren" id="num_siren" value="<?php echo htmlentities($profil["num_siren"]) ?>">
        </article>

        <div class="btn-modif">
            <input type="submit" name="confirmer" class="confirmer" value="Confirmer">
            <a href="index.php?page=profil&type=consulter" class="annuler">Annuler</a>
        </div>
    </form>
</section>
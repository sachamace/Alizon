<section class="profil-container">
    <form action="">
        <h2>Votre profil</h2>
        <article>
            <h3>Raison sociale</h3>
            <input type="text" name="raison_sociale" id="raison_sociale"
                value="<?php echo htmlentities($profil["raison_sociale"]) ?>">
        </article>

        <article>
            <h3>Adresse email</h3>
            <input type="text" name="adresse_mail" id="adresse_mail"
                value="<?php echo htmlentities($profil["adresse_mail"]) ?>">
        </article>

        <article class="password-field" id="toggle-password">
            <h3>Mot de passe</h3>
            <div>
                <p>********</p>
                <svg xmlns="http://www.w3.org/2000/svg" id="arrow" width="16" height="16" fill="black"
                    class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                    <path
                        d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z" />
                </svg>
            </div>
        </article>

        <div class="password-edit" id="password-edit">
            <label>Mot de passe actuel</label>
            <input type="password" name="mdp_actuel">

            <label>Nouveau mot de passe</label>
            <input type="password" name="mdp_nouveau">

            <label>Confirmer le nouveau mot de passe</label>
            <input type="password" name="mdp_confirmation">
        </div>

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
<script>
    document.getElementById("toggle-password").addEventListener("click", () => {
        const bloc = document.getElementById("password-edit");
        const arrow = document.getElementById("arrow");

        bloc.classList.toggle("visible");
        arrow.classList.toggle("rotate");
    });

</script>
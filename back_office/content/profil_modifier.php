<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $raison_sociale = $_POST['raison_sociale'];
    $statut_juridique = $_POST['statut_juridique'];
    $num_siren = trim($_POST['num_siren']);
    $adresse_mail = trim($_POST['adresse_mail']);
    $num_tel = trim($_POST['num_tel']);
    $mdp_actuel = trim($_POST['mdp_actuel']);
    $mdp_nouveau = trim($_POST['mdp_nouveau']);
    $mdp_confirmation = trim($_POST['mdp_confirmation']);

    if (empty($statut_juridique)) {
        $errors[] = "Veuillez choisir un statut juridique";
    }

    if (!preg_match("/^[0-9]{9}$/", $num_siren)) {
        $errors[] = "Total de chiffre invalides ! Nombre de chiffre qu'on requière = 9";
    }

    if ($adresse_mail != $profil['adresse_mail']) {
        $email_sql = "SELECT adresse_mail FROM public.compte_vendeur";
        $stmt_email = $pdo->query($email_sql);
        $tab_email = $stmt_email->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($tab_email as $email) {
            if ($adresse_mail == $email) {
                $errors[] = "L'adresse e-mail existe déja !";
            }
        }
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.fr|yahoo\.com|orange\.fr|free\.fr|sfr\.fr)$/", $adresse_mail)) {
            $errors[] = "Email invalide ou domaine non autorisé.(Gmail , Outlook , Yahoo , Orange , free et sfr acceptés.";
        }
    }

    if (!empty($mdp_actuel) && !empty($mdp_nouveau) && !empty($mdp_confirmation)) {
        if ($mdp_actuel != $profil_mdp) {
            $errors[] = "Le mot de passe actuel n'est pas le bon";
        }
        if ($mdp_nouveau != $mdp_confirmation) {
            $errors[] = "Entrez 2 fois le même nouveau mot de passe";
        }
    }

    if ($num_tel != $profil['num_tel']) {
        $tel_sql = "SELECT num_tel FROM public.compte_vendeur";
        $stmt_tel = $pdo->query($tel_sql);
        $tab_tel = $stmt_tel->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($tab_tel as $telephone) {
            if ($num_tel == $telephone) {
                $errors[] = "Le numéro de téléphone existe déja !";
            }
        }
        if (!preg_match("/^[0-9]{10}$/", $num_tel)) {
            $errors[] = "Total de chiffres invalides ! Nombre de chiffre qu'on requière = 10";
        }
    }

    if (empty($errors)) {
        if ($adresse_mail != $profil['adresse_mail']) {
            $sqlident = "UPDATE public.identifiants SET login = :login WHERE id_num = :num";
            $stmt = $pdo->prepare($sqlident);
            $stmt->execute([
                'num' => $profil['id_num'],
                'login' => $adresse_mail
            ]);
        }

        if (!empty($mdp_actuel) && !empty($mdp_nouveau) && !empty($mdp_confirmation)) {
            $sqlPass = "UPDATE public.identifiants SET mdp = :mdp WHERE id_num = :num";
            $stmtPass = $pdo->prepare($sqlPass);
            $stmtPass->execute([
                'num' => $profil['id_num'],
                'mdp' => $mdp_nouveau
            ]);
        }

        $sqlvendeur = "UPDATE public.compte_vendeur SET raison_sociale= :raison_sociale, statut_juridique= :statut_juridique, num_siren= :num_siren, num_tel= :num_tel, adresse_mail= :adresse_mail WHERE id_num= :id_num";
        $stmtvendeur = $pdo->prepare($sqlvendeur);
        $stmtvendeur->execute([
            'raison_sociale' => $raison_sociale,
            'statut_juridique' => $statut_juridique,
            'num_siren' => $num_siren,
            'num_tel' => $num_tel,
            'adresse_mail' => $adresse_mail,
            'id_num' => $profil['id_num']
        ]);
        echo "<script>
            window.location.href = 'index.php?page=profil&type=consulter';
        </script>";
        exit();
    } else {
        echo "<ul style='color:red'>";
        foreach ($errors as $err)
            echo "<li>$err</li>";
        echo "</ul>";
    }
}
?>

<section class="profil-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <h2>Votre profil</h2>
        <article>
            <h3>Raison sociale</h3>
            <input type="text" name="raison_sociale" id="raison_sociale"
                value="<?php echo isset($_POST['raison_sociale']) ? htmlentities($_POST['raison_sociale']) : htmlentities($profil['raison_sociale']); ?>">
        </article>

        <article>
            <h3>Statut juridique</h3>
            <select name="statut_juridique">
                <option disabled <?= !isset($_POST['statut_juridique']) ? "selected" : "" ?>>Choisir</option>

                <option value="SA" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "SA" ? "selected" : "" ?>>SA</option>
                <option value="SAS" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "SAS" ? "selected" : "" ?>>SAS</option>
                <option value="SARL" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "SARL" ? "selected" : "" ?>>SARL</option>
                <option value="EURL" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "EURL" ? "selected" : "" ?>>EURL</option>
                <option value="SASU" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "SASU" ? "selected" : "" ?>>SASU</option>
                <option value="SCP" <?= (isset($_POST['statut_juridique']) ? $_POST['statut_juridique'] : $profil["statut_juridique"]) == "SCP" ? "selected" : "" ?>>SCP</option>
            </select>

        </article>

        <article>
            <h3>Numéro de SIREN</h3>
            <input type="text" name="num_siren" id="num_siren"
                value="<?php echo isset($_POST['num_siren']) ? htmlentities($_POST['num_siren']) : htmlentities($profil['num_siren']); ?>">
        </article>

        <article>
            <h3>Adresse email</h3>
            <input type="text" name="adresse_mail" id="adresse_mail"
                value="<?php echo isset($_POST['adresse_mail']) ? htmlentities($_POST['adresse_mail']) : htmlentities($profil['adresse_mail']); ?>">
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
            <input type="text" name="num_tel" id="num_tel"
                value="<?php echo isset($_POST['num_tel']) ? htmlentities($_POST['num_tel']) : htmlentities($profil['num_tel']); ?>">
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
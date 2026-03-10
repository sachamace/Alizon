<?php
    $id_vendeur_connecte = $_SESSION['vendeur_id'];
    // Savoir si l'a2f est déja activé 
    $stmt_a2f = $pdo->prepare("SELECT codea2f FROM compte_vendeur WHERE id_vendeur = :id_vendeur");
    $stmt_a2f->execute(['id_vendeur' => $id_vendeur_connecte]);
    $a2f = $stmt_a2f->fetchColumn();
    if($a2f == NULL){
        $a2f = "";
    }
?>
<section class="profil-container">
    <h2>Votre profil</h2>
    <article>
        <h3>Raison sociale</h3>
        <p><?php echo htmlentities($profil["raison_sociale"]) ?></p>
    </article>

    <article>
        <h3>Statut juridique</h3>
        <p><?php echo htmlentities($profil["statut_juridique"]) ?></p>
    </article>

    <article>
        <h3>Numéro de SIREN</h3>
        <p><?php echo htmlentities($profil["num_siren"]) ?></p>
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
        <h3>Adresse</h3>
        <p><?php echo htmlentities($profil_adresse['adresse'] ?? 'Non renseignée') ?></p>
    </article>

    <div class="btn-modif">
        <a href="index.php?page=profil&type=modifier" class="modifier">Modifier</a>
    </div>

    <div class="profil-container">
        <?php if(!$a2f && strcmp($a2f, "") == 0){?>
            <a href="index.php?page=activerA2f">Activer l'A2F</a>
        <?php }else{?>
            <a href="index.php?page=desactiverA2f">Désactiver l'A2F</a>
        <?php }?>
    </div>
</section>
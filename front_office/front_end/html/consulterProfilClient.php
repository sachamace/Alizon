<?php
include 'config.php';
include 'session.php';
include 'sessionindex.php';
$id_client_connecte = $_SESSION['id_client'];

try {
    // Récupération des données du client
    $stmt = $pdo->prepare("
        SELECT 
            cc.id_client,
            cc.nom,
            cc.prenom,
            cc.date_naissance,
            cc.adresse_mail AS email,
            cc.num_tel AS telephone,
            cc.id_num,
            a.adresse,
            a.code_postal,
            a.ville,
            a.pays
        FROM compte_client cc
        LEFT JOIN adresse a ON cc.id_client = a.id_client
        WHERE cc.id_client = :id_client
    ");
    $stmt->execute(['id_client' => $id_client_connecte]);
    $profil = $stmt->fetch();

    if (!$profil) {
        die("Utilisateur introuvable.");
    }

    // Récupération du mot de passe
    $stmt = $pdo->prepare("SELECT mdp FROM identifiants WHERE id_num = :id");
    $stmt->execute(['id' => $profil['id_num']]);
    $profil_mdp = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <meta name="description" content="Consultez votre profil">
    <meta name="keywords" content="MarketPlace, Shopping, Ventes, Breton, Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body_profilClient">
    <header class = "disabled">
        <?php include 'header.php'?>
    </header>

    <div class="compte__header">
        <a href="compte.php">← </a>Mon Profil
    </div> 

    <main class="main_profilClient">
        <section class="profil-container">
            <h2>Votre profil</h2>
            
            <article>
                <h3>Prénom</h3>
                <p><?php echo htmlentities($profil["prenom"]) ?></p>
            </article>

            <article>
                <h3>Nom</h3>
                <p><?php echo htmlentities($profil["nom"]) ?></p>
            </article>

            <article>
                <h3>Date de naissance</h3>
                <p><?php echo htmlentities($profil["date_naissance"]) ?></p>
            </article>

            <article>
                <h3>Adresse email</h3>
                <p><?php echo htmlentities($profil["email"]) ?></p>
            </article>

            <article>
                <h3>Mot de passe</h3>
                <p>********</p>
            </article>

            <article>
                <h3>Numéro de téléphone</h3>
                <p><?php echo htmlentities($profil["telephone"]) ?></p>
            </article>

            <article>
                <h3>Adresse</h3>
                <p><?php echo htmlentities($profil["adresse"] ?? 'Non renseignée') ?></p>
            </article>

            <article>
                <h3>Code postal</h3>
                <p><?php echo htmlentities($profil["code_postal"] ?? 'Non renseigné') ?></p>
            </article>

            <article>
                <h3>Ville</h3>
                <p><?php echo htmlentities($profil["ville"] ?? 'Non renseignée') ?></p>
            </article>

            <article>
                <h3>Pays</h3>
                <p><?php echo htmlentities($profil["pays"] ?? 'Non renseigné') ?></p>
            </article>

            <div class="btn-modif">
                <a href="modifierProfilClient.php" class="modifier">Modifier</a>
            </div>
        </section>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
</body>
</html>
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
            cc.id_num
        FROM compte_client cc
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

    // Récupération de l'adresse par défaut
    $stmt_adresse = $pdo->prepare("
        SELECT * FROM adresse 
        WHERE id_client = :id_client AND est_defaut = TRUE
        LIMIT 1
    ");
    $stmt_adresse->execute(['id_client' => $id_client_connecte]);
    $adresse_defaut = $stmt_adresse->fetch(PDO::FETCH_ASSOC);

    // Compter le nombre total d'adresses
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM adresse WHERE id_client = :id_client");
    $stmt_count->execute(['id_client' => $id_client_connecte]);
    $nb_adresses = $stmt_count->fetchColumn();

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
    <style>
        .adresse-card p strong{
            font-weight: 700;
            text-transform: uppercase;
            color: #5a5a5a
        }
    </style>
</head>
<body class="body_profilClient">
    <header class="disabled">
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

            <!-- SECTION ADRESSE PAR DÉFAUT -->
            <div class="adresse-card" style="margin-top: 2rem; padding: 1.5rem; background: #f9f9f9; border-radius: 8px; border: 2px solid #ddd;">
                <h3 style="margin-top: 0; color: #333;">Adresse de livraison/facturation par défaut</h3>
                
                <?php if ($adresse_defaut): ?>
                    <p><strong>Libellé :</strong> <?php echo htmlentities($adresse_defaut['libelle']) ?></p>
                    <p><strong>Adresse :</strong> <?php echo htmlentities($adresse_defaut['adresse']) ?></p>
                    <p><strong>Code postal :</strong> <?php echo htmlentities($adresse_defaut['code_postal']) ?></p>
                    <p><strong>Ville :</strong> <?php echo htmlentities($adresse_defaut['ville']) ?></p>
                    <p><strong>Pays :</strong> <?php echo htmlentities($adresse_defaut['pays']) ?></p>
                    <?php if ($adresse_defaut['num_tel']): ?>
                        <p><strong>Téléphone :</strong> <?php echo htmlentities($adresse_defaut['num_tel']) ?></p>
                    <?php endif; ?>
                    
                    <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                        Vous avez <strong><?php echo $nb_adresses ?></strong> adresse(s) enregistrée(s)
                    </p>
                <?php else: ?>
                    <p style="color: #999;">Vous n'avez pas encore d'adresse enregistrée.</p>
                <?php endif; ?>
                
                <a href="gererAdresses.php" style="display: inline-block; margin-top: 1rem; padding: 0.7rem 1.5rem; background: #ff6ce2; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    Gérer mes adresses (<?php echo $nb_adresses ?>)
                </a>
            </div>

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
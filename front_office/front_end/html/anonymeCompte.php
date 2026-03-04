<?php
include 'config.php';
include 'sessionindex.php';

$id_client_connecte = $_SESSION['id_client'];
$erreur_mdp = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['motdepasse'])) {
    try {
        $mdp = $_POST['motdepasse'];

        $user_sql = $pdo->prepare("
            SELECT i.mdp, i.id_num
            FROM public.identifiants i 
            JOIN public.compte_client c ON i.id_num = c.id_num 
            WHERE c.id_client = ?
        ");
        $user_sql->execute([$id_client_connecte]);
        $user = $user_sql->fetch();
        
        if ($user && $mdp == $user['mdp']) {
            $sql = "UPDATE public.compte_client SET 
                    nom = ?, 
                    prenom = ?, 
                    adresse_mail = ?,
                    num_tel = ?,
                    date_naissance = NULL,
                    somme_avoir = 0,
                    id_num = NULL
                    WHERE id_client = ?";
            
            $update = $pdo->prepare($sql);
            $update->execute(['ANONYME', 'compte', 'anonyme@exemple.com', '0000000000', $id_client_connecte]);

            $id_num = $user['id_num'];

            $del = $pdo->prepare("DELETE FROM public.identifiants WHERE id_num = ?");
            $del->execute([$id_num]);
            
            session_destroy();
            exit();
        } else {
            $erreur_mdp = "Mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $erreur_mdp = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon Profil</title>
    <meta name="description" content="Modifiez votre profil">
    <meta name="keywords" content="MarketPlace, Shopping, Ventes, Breton, Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body_profilClient">
    <header class = "disabled">
        <?php include 'header.php'?>
    </header>
    <main class="main_profilClient">
        <section class="profil-container">
            <h2>Supprimer mon compte</h2>
            <p style="color: red;">En supprimant votre compte, toutes vos données personnelles seront supprimées. Cette action est irréversible.</p>
            <form method="post" action="">
                <label for="motdepasse" class="input-label">mot de passe</label>
                    <input class="input__connexion" type="password" name="motdepasse" placeholder="Votre mot de passe" required >
                <?php
                    if (!empty($erreur_mdp)) {
                        echo "<span style='color:red'>$erreur_mdp</span><br />";
                    }
                ?>
                <div class="btn-modif">
                    <input type="submit" class="ano" value="Supprimer mon compte">
                </div>
            </form>
        </section>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
</body>
</html>
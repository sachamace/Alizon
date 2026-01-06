<?php
include 'config.php';
include 'session.php';

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

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $date_naissance = trim($_POST['date_naissance']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $mdp_actuel = trim($_POST['mdp_actuel']);
    $mdp_nouveau = trim($_POST['mdp_nouveau']);
    $mdp_confirmation = trim($_POST['mdp_confirmation']);
    $adresse = trim($_POST['adresse']);
    $code_postal = trim($_POST['code_postal']);
    $ville = trim($_POST['ville']);
    $pays = trim($_POST['pays']);

    // Validations
    if (empty($prenom)) {
        $errors['prenom'] = "Veuillez entrer un prénom";
    }

    if (empty($nom)) {
        $errors['nom'] = "Veuillez entrer un nom";
    }

    if (empty($date_naissance)) {
        $errors['date_naissance'] = "Veuillez entrer une date de naissance";
    }

    // Validation de l'email
    if ($email != $profil['email']) {
        $email_sql = "SELECT adresse_mail FROM compte_client WHERE adresse_mail = :email";
        $stmt_email = $pdo->prepare($email_sql);
        $stmt_email->execute(['email' => $email]);
        if ($stmt_email->rowCount() > 0) {
            $errors['email'] = "L'adresse e-mail existe déjà !";
        }
        if (!preg_match("/^[a-zA-Z0-9._%+-]+@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.fr|yahoo\.com|orange\.fr|free\.fr|sfr\.fr)$/", $email)) {
            $errors['email'] = "Email invalide ou domaine non autorisé.";
        }
    }

    // Validation du mot de passe
    if (!empty($mdp_actuel) || !empty($mdp_nouveau) || !empty($mdp_confirmation)) {
        if (empty($mdp_actuel) || empty($mdp_nouveau) || empty($mdp_confirmation)) {
            $errors['mdp_champs'] = "Veuillez remplir les 3 champs du mot de passe";
        } elseif ($mdp_actuel != $profil_mdp) {
            $errors['mdp_pas_bon'] = "Le mot de passe actuel n'est pas correct";
        } elseif ($mdp_nouveau != $mdp_confirmation) {
            $errors['mdp_confirmation'] = "Entrez 2 fois le même nouveau mot de passe";
        }
    }

    // Validation du téléphone
    if ($telephone != $profil['telephone']) {
        $tel_sql = "SELECT num_tel FROM compte_client WHERE num_tel = :tel";
        $stmt_tel = $pdo->prepare($tel_sql);
        $stmt_tel->execute(['tel' => $telephone]);
        if ($stmt_tel->rowCount() > 0) {
            $errors['telephone'] = "Le numéro de téléphone existe déjà !";
        }
        if (!preg_match("/^[0-9]{10}$/", $telephone)) {
            $errors['telephone'] = "Total de chiffres invalides ! Nombre de chiffres requis = 10";
        }
    }

    // Validation du code postal
    if (!empty($code_postal) && !preg_match("/^[0-9]{5}$/", $code_postal)) {
        $errors['code_postal'] = "Code postal invalide (5 chiffres requis)";
    }

    // Si pas d'erreurs, mise à jour
    if (empty($errors)) {
        if ($email != $profil['email']) {
            $sqlident = "UPDATE identifiants SET login = :login WHERE id_num = :num";
            $stmt = $pdo->prepare($sqlident);
            $stmt->execute([
                'num' => $profil['id_num'],
                'login' => $email
            ]);
        }

        if (!empty($mdp_actuel) && !empty($mdp_nouveau) && !empty($mdp_confirmation)) {
            $sqlPass = "UPDATE identifiants SET mdp = :mdp WHERE id_num = :num";
            $stmtPass = $pdo->prepare($sqlPass);
            $stmtPass->execute([
                'num' => $profil['id_num'],
                'mdp' => $mdp_nouveau
            ]);
        }

        $sqlclient = "UPDATE compte_client SET 
            prenom = :prenom, 
            nom = :nom, 
            date_naissance = :date_naissance, 
            num_tel = :telephone, 
            adresse_mail = :email 
            WHERE id_num = :id_num";
        $stmtclient = $pdo->prepare($sqlclient);
        $stmtclient->execute([
            'prenom' => $prenom,
            'nom' => $nom,
            'date_naissance' => $date_naissance,
            'telephone' => $telephone,
            'email' => $email,
            'id_num' => $profil['id_num']
        ]);

        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM adresse WHERE id_client = :id_client");
        $stmt_check->execute(['id_client' => $profil['id_client']]);
        $adresse_exists = $stmt_check->fetchColumn() > 0;

        if ($adresse_exists) {
            $sqladresse = "UPDATE adresse SET 
                adresse = :adresse, 
                code_postal = :code_postal, 
                ville = :ville, 
                pays = :pays 
                WHERE id_client = :id_client";
            $stmtadresse = $pdo->prepare($sqladresse);
            $stmtadresse->execute([
                'adresse' => $adresse,
                'code_postal' => $code_postal,
                'ville' => $ville,
                'pays' => $pays,
                'id_client' => $profil['id_client']
            ]);
        } else {
            if (!empty($adresse) || !empty($code_postal) || !empty($ville) || !empty($pays)) {
                $sqladresse = "INSERT INTO adresse (id_client, adresse, code_postal, ville, pays) 
                    VALUES (:id_client, :adresse, :code_postal, :ville, :pays)";
                $stmtadresse = $pdo->prepare($sqladresse);
                $stmtadresse->execute([
                    'id_client' => $profil['id_client'],
                    'adresse' => $adresse,
                    'code_postal' => $code_postal,
                    'ville' => $ville,
                    'pays' => $pays
                ]);
            }
        }

        header("Location: consulterProfilClient.php");
        exit();
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

    <div class="compte__header">
        <a href="compte.php">← </a>Mon Profil
    </div>  

    <main class="main_profilClient">
        <section class="profil-container">
            <form action="" method="POST">
                <h2>Modifier votre profil</h2>
                
                <article>
                    <h3>Prénom</h3>
                    <input type="text" name="prenom" id="prenom"
                        value="<?php echo isset($_POST['prenom']) ? htmlentities($_POST['prenom']) : htmlentities($profil['prenom']); ?>">
                </article>
                <?php if (isset($errors['prenom'])) { ?>
                    <p class="error"><?php echo $errors['prenom']; ?></p>
                <?php } ?>

                <article>
                    <h3>Nom</h3>
                    <input type="text" name="nom" id="nom"
                        value="<?php echo isset($_POST['nom']) ? htmlentities($_POST['nom']) : htmlentities($profil['nom']); ?>">
                </article>
                <?php if (isset($errors['nom'])) { ?>
                    <p class="error"><?php echo $errors['nom']; ?></p>
                <?php } ?>

                <article>
                    <h3>Date de naissance</h3>
                    <input type="date" name="date_naissance" id="date_naissance"
                        value="<?php echo isset($_POST['date_naissance']) ? htmlentities($_POST['date_naissance']) : htmlentities($profil['date_naissance']); ?>">
                </article>
                <?php if (isset($errors['date_naissance'])) { ?>
                    <p class="error"><?php echo $errors['date_naissance']; ?></p>
                <?php } ?>

                <article>
                    <h3>Adresse email</h3>
                    <input type="email" name="email" id="email"
                        value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : htmlentities($profil['email']); ?>">
                </article>
                <?php if (isset($errors['email'])) { ?>
                    <p class="error"><?php echo $errors['email']; ?></p>
                <?php } ?>

                <article class="password-field" id="toggle-password">
                    <h3>Mot de passe</h3>
                    <div>
                        <p>********</p>
                        <svg xmlns="http://www.w3.org/2000/svg" id="arrow" width="16" height="16" fill="black"
                            class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z" />
                        </svg>
                    </div>
                </article>

                <div class="password-edit" id="password-edit">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="mdp_actuel">
                    <?php if (isset($errors['mdp_champs'])) { ?>
                        <p class="error"><?php echo $errors['mdp_champs']; ?></p>
                    <?php } ?>
                    <?php if (isset($errors['mdp_pas_bon'])) { ?>
                        <p class="error"><?php echo $errors['mdp_pas_bon']; ?></p>
                    <?php } ?>

                    <label>Nouveau mot de passe</label>
                    <input type="password" name="mdp_nouveau">

                    <label>Confirmer le nouveau mot de passe</label>
                    <input type="password" name="mdp_confirmation">
                    <?php if (isset($errors['mdp_confirmation'])) { ?>
                        <p class="error"><?php echo $errors['mdp_confirmation']; ?></p>
                    <?php } ?>
                </div>

                <article>
                    <h3>Numéro de téléphone</h3>
                    <input type="text" name="telephone" id="telephone"
                        value="<?php echo isset($_POST['telephone']) ? htmlentities($_POST['telephone']) : htmlentities($profil['telephone']); ?>">
                </article>
                <?php if (isset($errors['telephone'])) { ?>
                    <p class="error"><?php echo $errors['telephone']; ?></p>
                <?php } ?>

                <h3>Adresse de livraison et facturation</h3>

                <article>
                    <h3>Adresse</h3>
                    <input type="text" name="adresse" id="adresse"
                        value="<?php echo isset($_POST['adresse']) ? htmlentities($_POST['adresse']) : htmlentities($profil['adresse'] ?? ''); ?>">
                </article>

                <article>
                    <h3>Code postal</h3>
                    <input type="text" name="code_postal" id="code_postal"
                        value="<?php echo isset($_POST['code_postal']) ? htmlentities($_POST['code_postal']) : htmlentities($profil['code_postal'] ?? ''); ?>">
                </article>
                <?php if (isset($errors['code_postal'])) { ?>
                    <p class="error"><?php echo $errors['code_postal']; ?></p>
                <?php } ?>

                <article>
                    <h3>Ville</h3>
                    <input type="text" name="ville" id="ville"
                        value="<?php echo isset($_POST['ville']) ? htmlentities($_POST['ville']) : htmlentities($profil['ville'] ?? ''); ?>">
                </article>

                <article>
                    <h3>Pays</h3>
                    <input type="text" name="pays" id="pays"
                        value="<?php echo isset($_POST['pays']) ? htmlentities($_POST['pays']) : htmlentities($profil['pays'] ?? ''); ?>">
                </article>

                <div class="btn-modif">
                    <input type="submit" name="confirmer" class="confirmer" value="Confirmer">
                    <a href="consulterProfilClient.php" class="annuler">Annuler</a>
                </div>
            </form>
        </section>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>

    <script>
        document.getElementById("toggle-password").addEventListener("click", () => {
            const bloc = document.getElementById("password-edit");
            const arrow = document.getElementById("arrow");

            bloc.classList.toggle("visible");
            arrow.classList.toggle("rotate");
        });
    </script>
</body>
</html>
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
    <header>
        <nav>
            <nav>
                <a href="/index.php"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><svg width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 0c-13.3 0-24 10.7-24 24l0 9.7C118.6 45.3 56 115.4 56 200l0 14.5c0 37.7-10 74.7-29 107.3L5.1 359.2C1.8 365 0 371.5 0 378.2 0 399.1 16.9 416 37.8 416l372.4 0c20.9 0 37.8-16.9 37.8-37.8 0-6.7-1.8-13.3-5.1-19L421 321.7c-19-32.6-29-69.6-29-107.3l0-14.5c0-84.6-62.6-154.7-144-166.3l0-9.7c0-13.3-10.7-24-24-24zM392.4 368l-336.9 0 12.9-22.1C91.7 306 104 260.6 104 214.5l0-14.5c0-66.3 53.7-120 120-120s120 53.7 120 120l0 14.5c0 46.2 12.3 91.5 35.5 131.4L392.4 368zM156.1 464c9.9 28 36.6 48 67.9 48s58-20 67.9-48l-135.8 0z"/></svg></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php" data-panier><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>Panier</a>
            </nav>
            <nav>
                <div>
                <?php
                $categorie = $pdo->query('SELECT * FROM categorie');
                while ($cat = $categorie->fetch()){ 
                    $libelle = urlencode($cat['libelle']); 
                    ?>
                    <a href="../../../index.php?categorie=<?php echo $libelle; ?>">
                        <?php echo $cat['libelle']; ?>
                    </a>
                <?php } ?>
                </div>
                <a href="compte.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg>Compte</a>
            </nav>
        </nav>
    </header>

    <div class="compte__header">
        <a href="consulterProfilClient.php">← </a>Modifier mon profil
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
        <a href="/index.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M277.8 8.6c-12.3-11.4-31.3-11.4-43.5 0l-224 208c-9.6 9-12.8 22.9-8 35.1S18.8 272 32 272l16 0 0 176c0 35.3 28.7 64 64 64l288 0c35.3 0 64-28.7 64-64l0-176 16 0c13.2 0 25-8.1 29.8-20.3s1.6-26.2-8-35.1l-224-208zM240 320l32 0c26.5 0 48 21.5 48 48l0 96-128 0 0-96c0-26.5 21.5-48 48-48z"/></svg></a>
        <a class="recherche" href="recherche.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376C296.3 401.1 253.9 416 208 416 93.1 416 0 322.9 0 208S93.1 0 208 0 416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg></a>
        <a href="panier.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg></a>
        <a class="notif" href="notification.html"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 0c-13.3 0-24 10.7-24 24l0 9.7C118.6 45.3 56 115.4 56 200l0 14.5c0 37.7-10 74.7-29 107.3L5.1 359.2C1.8 365 0 371.5 0 378.2 0 399.1 16.9 416 37.8 416l372.4 0c20.9 0 37.8-16.9 37.8-37.8 0-6.7-1.8-13.3-5.1-19L421 321.7c-19-32.6-29-69.6-29-107.3l0-14.5c0-84.6-62.6-154.7-144-166.3l0-9.7c0-13.3-10.7-24-24-24zM392.4 368l-336.9 0 12.9-22.1C91.7 306 104 260.6 104 214.5l0-14.5c0-66.3 53.7-120 120-120s120 53.7 120 120l0 14.5c0 46.2 12.3 91.5 35.5 131.4L392.4 368zM156.1 464c9.9 28 36.6 48 67.9 48s58-20 67.9-48l-135.8 0z"/></svg></a>
        <a href="compte.php"><svg width="48" height="48" class="icone" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg></a>
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
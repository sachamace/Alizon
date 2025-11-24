<?php
include 'config.php';
include 'session.php';

$id_client_connecte = $_SESSION['id'];


try {
    $stmt = $pdo->prepare("
        SELECT 
            cc.nom,
            cc.prenom,
            cc.date_naissance,
            cc.adresse_mail AS email,
            cc.num_tel AS telephone,
            id.mdp,
            id.login,
            id.id_num,
            a.adresse,
            a.code_postal,
            a.ville,
            a.pays
        FROM compte_client cc
        LEFT JOIN identifiants id ON cc.id_num = id.id_num
        LEFT JOIN adresse a ON cc.id_client = a.id_client
        WHERE cc.id_client = :id_client
    ");

    $stmt->execute(['id_client' => $id_client_connecte]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Utilisateur introuvable.");
    }

    // 🔹 On met à jour la session uniquement avec les nouvelles données
    //   mais on NE RÉÉCRASE PAS toute la session si elle existe
    $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $user);

} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Détaillée</title>
    <meta name="description" content="Ceci est le profil détaillée de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">
</head>
<body class="body_profilClient">
    <header class = "disabled">
        <nav>
            <nav>
                <a href="/index.php"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.php"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.php" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input disabled type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.php" data-panier><i class="fa-solid fa-cart-shopping icone" ></i>Panier</a>
            </nav>
            <nav>
                <div>
                <?php
                // On récupère tout le contenu de la table produit
                $categorie = $pdo->query('SELECT * FROM categorie');
                // On affiche chaque entrée une à une
                while ($cat = $categorie->fetch()){ 
                    $libelle = urlencode($cat['libelle']); 
                    ?>
                    <a href="/index.php?categorie=<?php echo $libelle; ?>">
                        <?php echo $cat['libelle']; ?>
                    </a>
                <?php } ?>
                </div>
                <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
                <?php else: ?><a href="seconnecter.php"></i>S'identifier</a>
                <?php endif; ?>
            </nav>
        </nav>
    </header>
    <div class="compte__header disabled"><a href="compte.php">←  </a>Vos données</div> 
    <main class="main_profilClient" >
        <section class="bloc infoG">
                <h2>Informations générales</h2>
                <p>Prénom<span><?= htmlspecialchars($user['prenom']) ?><a href="modifierPrenom.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
                <p>Nom<span><?= htmlspecialchars($user['nom']) ?><a href="modifierNom.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
                <p>Date de naissance<span><?= htmlspecialchars($user['date_naissance']) ?><a href="modifierDateNaissance.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
        </section>

        <section class="bloc coordonneeMdp">
            <h2>Coordonnées et mot de passe</h2>
            <p>Adresse e-mail<span><?= htmlspecialchars($user['email']) ?><a href="modifierEmail.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
            <p>Téléphone<span><?= htmlspecialchars($user['telephone']) ?><a href="modifierTel.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
            <p>Mot de passe<span><?= htmlentities(str_repeat('*', strlen($user['mdp']))) ?><a href="modifierMdp.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="20px" width="20px" class="ModifierDonnée"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a></span></p>
        </section>

        <section class="bloc livraisonFacturation">
            <h2>Adresse livraison et facturation
                <a href="modifierLivraisonFacturation.php"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" height="40px" width="40px" id="modify"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M505 122.9L517.1 135C526.5 144.4 526.5 159.6 517.1 168.9L488 198.1L441.9 152L471 122.9C480.4 113.5 495.6 113.5 504.9 122.9zM273.8 320.2L408 185.9L454.1 232L319.8 366.2C316.9 369.1 313.3 371.2 309.4 372.3L250.9 389L267.6 330.5C268.7 326.6 270.8 323 273.7 320.1zM437.1 89L239.8 286.2C231.1 294.9 224.8 305.6 221.5 317.3L192.9 417.3C190.5 425.7 192.8 434.7 199 440.9C205.2 447.1 214.2 449.4 222.6 447L322.6 418.4C334.4 415 345.1 408.7 353.7 400.1L551 202.9C579.1 174.8 579.1 129.2 551 101.1L538.9 89C510.8 60.9 465.2 60.9 437.1 89zM152 128C103.4 128 64 167.4 64 216L64 488C64 536.6 103.4 576 152 576L424 576C472.6 576 512 536.6 512 488L512 376C512 362.7 501.3 352 488 352C474.7 352 464 362.7 464 376L464 488C464 510.1 446.1 528 424 528L152 528C129.9 528 112 510.1 112 488L112 216C112 193.9 129.9 176 152 176L264 176C277.3 176 288 165.3 288 152C288 138.7 277.3 128 264 128L152 128z"/></svg></a>   
            </h2>
            <p>Adresse<span><?= htmlspecialchars($user['adresse']) ?></span></p>
            <p>Code Postal<span><?= htmlspecialchars($user['code_postal']) ?></span></p>
            <p>Ville<span><?= htmlspecialchars($user['ville']) ?></span></p>
            <p>Pays<span><?= htmlspecialchars($user['pays']) ?></span></p>
        </section>
    </main>
    <footer class="footer mobile">
        <a href="/index.php"><i class="fa-solid fa-house icone"></i></a>
        <a class="recherche disabled" href="recherche.php"><i class="fa-solid fa-magnifying-glass icone"></i></a>
        <a href="panier.php"><i class="fa-solid fa-cart-shopping icone"></i></a>
        <a class="notif disabled" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
        <?php if($isLogged):?><a href="compte.php"><i class="fa-regular fa-user icone"></i></a>
                <?php else: ?><a href="seconnecter.php"><i class="fa-regular fa-user icone"></i></a>
                <?php endif; ?>
    </footer>
</body>
</html>
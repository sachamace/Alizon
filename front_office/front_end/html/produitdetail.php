<?php
include 'config.php';
include 'sessionindex.php';
$button = true;
try {
    if (isset($_GET['article'])) {
        $id_produit = $_GET['article'];
    } else {
        $id_produit = null;
    }
    
    // Requête avec calcul du prix TTC ET remises actives
    $stmt2 = $pdo->prepare("
        SELECT p.*, 
               ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) AS prix_ttc,
               r.id_remise, 
               r.nom_remise, 
               r.type_remise, 
               r.valeur_remise,
               r.date_debut,
               r.date_fin
        FROM produit p
        LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
        LEFT JOIN remise r ON (
            r.id_vendeur = p.id_vendeur
            AND r.est_actif = true
            AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
            AND (
                -- Cas 1: Remise sur CE produit spécifique (via id_produit)
                r.id_produit = p.id_produit
                -- Cas 2: Remise sur CE produit spécifique (via table remise_produit)
                OR EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise AND rp.id_produit = p.id_produit)
                -- Cas 3: Remise sur TOUS les produits (pas de produit spécifique, pas de catégorie)
                OR (r.id_produit IS NULL AND r.categorie IS NULL AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                -- Cas 4: Remise sur CATÉGORIE spécifique (pas de produit spécifique, catégorie correspond)
                OR (r.id_produit IS NULL AND r.categorie = p.categorie AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
            )
        )
        WHERE p.id_produit = ?
    ");
    $stmt2->execute([$id_produit]);
    $infos = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    // Calculer le prix avec remise si applicable
    $id_vendeur = $infos['id_vendeur'];
    $prix_final = $infos['prix_ttc'];
    $prix_ht_final = $infos['prix_unitaire_ht'];
    $a_une_remise = false;
    
    if ($infos['id_remise']) {
        $a_une_remise = true;
        if ($infos['type_remise'] === 'pourcentage') {
            $prix_final = $infos['prix_ttc'] * (1 - $infos['valeur_remise'] / 100);
            $prix_ht_final = $infos['prix_unitaire_ht'] * (1 - $infos['valeur_remise'] / 100);
        } else {
            $prix_final = $infos['prix_ttc'] - $infos['valeur_remise'];
            $ratio = $prix_final / $infos['prix_ttc'];
            $prix_ht_final = $infos['prix_unitaire_ht'] * $ratio;
        }
        if ($prix_final < 0) $prix_final = 0;
        if ($prix_ht_final < 0) $prix_ht_final = 0;
    }

} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage();
}


// pose des variables 
if(isset($_SESSION['id_panier'])){
    $id_panier = $_SESSION['id_panier'];
}

// recuperation du stock
$stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
$stmt_stock->execute([':id_produit' => $id_produit]);
$stock_dispo = (int) $stmt_stock->fetchColumn();

if($stock_dispo == 0){
    $button = false;
}

// php avis du produit
$requete_avis = $pdo->prepare("
    SELECT *
    FROM avis
    WHERE id_produit = :id_produit 
    ORDER BY id_produit ASC
");
$requete_avis->execute([':id_produit' => $id_produit]);
$avis = $requete_avis->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour générer les étoiles d'affichage
function genererEtoiles($note) {
    $note_arrondie = round($note * 2) / 2; // Arrondir au 0.5
    $etoiles_pleines = floor($note_arrondie);
    $demi_etoile = ($note_arrondie - $etoiles_pleines) >= 0.5;
    $etoiles_vides = 5 - $etoiles_pleines - ($demi_etoile ? 1 : 0);
    
    $html = str_repeat('★', $etoiles_pleines);
    if ($demi_etoile) {
        $html .= '⯨'; // Demi-étoile
    }
    $html .= str_repeat('☆', $etoiles_vides);
    
    return $html;
}

// Calcul de la moyenne des notes (avec décimales)
$moyenne = 0;
$moyenne_arrondie = 0;
if (count($avis) > 0) {
    $total_notes = 0;
    foreach ($avis as $un_avis) {
        $total_notes += floatval($un_avis['note']);
    }
    $moyenne = $total_notes / count($avis);
    $moyenne_arrondie = round($moyenne * 2) / 2;
}


// Traitement du formulaire d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_avis') {
    $note = floatval($_POST['note']);
    $description = trim($_POST['description']);
    if ($note >= 0.5 && $note <= 5 && !empty($description) && isset($_SESSION['id_panier'])) {
        $id_client = $_SESSION['id_client'];

        try {
            $requete_ajout_avis = $pdo->prepare("
                INSERT INTO avis (id_client, id_produit, note, description)
                VALUES (:id_client, :id_produit, :note, :description)
            ");
            $requete_ajout_avis->execute([
                ':id_client' => $id_client,
                ':id_produit' => $id_produit,
                ':note' => $note,
                ':description' => $description
            ]);

            // Traitement des images de l'avis
            if (isset($_FILES['nouvelle_image']) && !empty($_FILES['nouvelle_image']['name'][0])) {

                $uploadDir  = __DIR__ . "/../assets/image_avis/";   // chemin physique
                $uploadName = "/front_office/front_end/assets/image_avis/"; // chemin BDD

                // Créer le dossier s'il n'existe pas encore
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['nouvelle_image']['name'] as $index => $name) {
                    if ($_FILES['nouvelle_image']['error'][$index] === UPLOAD_ERR_OK) {

                        $tmp       = $_FILES['nouvelle_image']['tmp_name'][$index];
                        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                        // Sécurité : n'accepter que les images
                        $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];
                        if (!in_array($extension, $extensions_autorisees)) {
                            continue;
                        }

                        $fileName = uniqid("avis_{$id_client}_") . "." . $extension;
                        $filePath = $uploadDir . $fileName;
                        $cheminBDD = "/front_office/front_end/assets/image_avis/" . $fileName;

                        if (!is_writable($uploadDir)) {
                            die("ERREUR : le dossier $uploadDir n'est pas accessible en écriture.");
                        }
                        if (move_uploaded_file($tmp, $filePath)) {
                            $stmt_img = $pdo->prepare("
                                INSERT INTO media_avis (id_client, id_produit, chemin_image)
                                VALUES (:id_client, :id_produit, :chemin)
                            ");
                            $stmt_img->execute([
                                ':id_client'  => $id_client,
                                ':id_produit' => $id_produit,
                                ':chemin'     => $cheminBDD
                            ]);
                        }
                    }
                }
            }
            
            echo "<script>
                window.location.href = '" . $_SERVER['REQUEST_URI'] . "';
            </script>";
            exit();
        } catch (PDOException $e) {
            $erreur_avis = "Vous avez déjà rentré un avis";
        }
    } else {
        $erreur_avis = "Veuillez entrer une note entre 0.5 et 5 et une description.";
    }
}
// traitement formulaire signalement
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signaler_avis') {
    $id_client_avis = $_POST['id_client_cible'];
    if (!isset($_SESSION['avis_signales'])){
        $_SESSION['avis_signales'] = [$id_client_avis];
    }
    else {
        $_SESSION['avis_signales'][] = $id_client_avis;
    }
    $requete_signalement = $pdo->prepare("
        UPDATE avis 
        SET nbr_signalement = nbr_signalement + 1
        WHERE id_client = :id_client
        AND id_produit = :id_produit
    ");
    $requete_signalement->execute([
            ':id_client'  => $id_client_avis,
            ':id_produit' => $id_produit
        ]);

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'voter_avis') {
    header('Content-Type: application/json');

    $id_client_votant = $_SESSION['id_client'];
    $id_client_auteur = (int) $_POST['id_auteur'];
    $id_produit = (int) $_POST['id_produit'];
    $valeur_vote = (int) $_POST['valeur_vote'];

    try {

        $stmt_check = $pdo->prepare("SELECT valeur_vote FROM vote_avis WHERE id_client_votant = ? AND id_client_auteur = ? AND id_produit = ?");
        $stmt_check->execute([$id_client_votant, $id_client_auteur, $id_produit]);
        $vote_existant = $stmt_check->fetchColumn();
        $mon_vote = 0;
        if ($vote_existant !== false){
            if($vote_existant == $valeur_vote){
                $stmt_delete = $pdo->prepare("DELETE FROM vote_avis WHERE id_client_votant = ? AND id_client_auteur = ? AND id_produit = ?");
                $stmt_delete->execute([$id_client_votant, $id_client_auteur, $id_produit]);
                $mon_vote = 0;
            }
            else{
                $stmt_delete = $pdo->prepare("DELETE FROM vote_avis WHERE id_client_votant = ? AND id_client_auteur = ? AND id_produit = ?");
                $stmt_delete->execute([$id_client_votant, $id_client_auteur, $id_produit]);
                $stmt_insert = $pdo->prepare("INSERT INTO vote_avis (id_client_votant, id_client_auteur, id_produit, valeur_vote) VALUES (?, ?, ?, ?)");
                $stmt_insert->execute([$id_client_votant, $id_client_auteur, $id_produit, $valeur_vote]);
                $mon_vote = $valeur_vote;
            }
        }
        else {
            $stmt_insert = $pdo->prepare("INSERT INTO vote_avis (id_client_votant, id_client_auteur, id_produit, valeur_vote) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$id_client_votant, $id_client_auteur, $id_produit, $valeur_vote]);
            $mon_vote = $valeur_vote;
        }

        $stmt_count = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN valeur_vote = 1 THEN 1 ELSE 0 END), 0) as total_positif,
                COALESCE(SUM(CASE WHEN valeur_vote = -1 THEN 1 ELSE 0 END), 0) as total_negatif
            FROM vote_avis 
            WHERE id_client_auteur = ? AND id_produit = ?
        ");
        $stmt_count->execute([$id_client_auteur, $id_produit]);
        $totaux = $stmt_count->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'mon_vote' => $mon_vote,
            'total_positif' => $totaux['total_positif'],
            'total_negatif' => $totaux['total_negatif']
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
        exit();
    }
}

//traitement ajout favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favori') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['id_client'])) {
        echo json_encode(['success' => false, 'redirect' => 'seconnecter.php']);
        exit();
    }

    $id_client = $_SESSION['id_client'];
    $id_produit_fav = (int) $_POST['id_produit'];

    try {
        // verification si le produit est dans les favoris du client
        $stmt_check_fav = $pdo->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_produit = ?");
        $stmt_check_fav->execute([$id_client, $id_produit_fav]);
        $est_favori = $stmt_check_fav->fetchColumn();

        if ($est_favori) {
            $stmt_del_fav = $pdo->prepare("DELETE FROM favoris WHERE id_client = ? AND id_produit = ?");
            $stmt_del_fav->execute([$id_client, $id_produit_fav]);
            echo json_encode(['success' => true, 'etat' => 'retire']);
        } else {
            // S'il n'y est pas, on l'ajoute
            $stmt_add_fav = $pdo->prepare("INSERT INTO favoris (id_client, id_produit) VALUES (?, ?)");
            $stmt_add_fav->execute([$id_client, $id_produit_fav]);
            echo json_encode(['success' => true, 'etat' => 'ajoute']);
        }
        exit();

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur BDD : ' . $e->getMessage()]);
        exit();
    }
}

// traitement des autres actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    if (isset($_GET['article'])) {
        $id_produit = $_GET['article'];
    } else {
        $id_produit = null;
    }
    if(!isset($_SESSION['id_panier']) && $action !== "signaler_avis") {
        echo "<script>
           window.location.href = 'seconnecter.php';
        </script>";
        exit();
    }
    else{
        $id_panier = $_SESSION['id_panier'];
        if ($action === 'supprimer_avis') {
            $id_client = $_SESSION['id_client'];

            // Supprimer les fichiers physiques des images
            $req_imgs = $pdo->prepare("SELECT chemin_image FROM media_avis WHERE id_client = :id_client AND id_produit = :id_produit");
            $req_imgs->execute([':id_client' => $id_client, ':id_produit' => $id_produit]);
            $imgs = $req_imgs->fetchAll(PDO::FETCH_ASSOC);

            foreach ($imgs as $img) {
                $chemin_physique = $_SERVER['DOCUMENT_ROOT'] . $img['chemin_image'];
                if (file_exists($chemin_physique)) {
                    unlink($chemin_physique);
                }
            }

            // Supprimer les images en BDD
            $req_suppr_imgs = $pdo->prepare("DELETE FROM media_avis WHERE id_client = :id_client AND id_produit = :id_produit");
            $req_suppr_imgs->execute([':id_client' => $id_client, ':id_produit' => $id_produit]);

            // Supprimer l'avis
            $requete_suppr = $pdo->prepare("
                DELETE FROM avis 
                WHERE id_produit = :id_produit AND id_client = :id_client
            ");
            $requete_suppr->execute([
                ':id_produit' => $id_produit,
                ':id_client' => $id_client  
            ]);

            echo "<script>
                window.location.href = '" . $_SERVER['REQUEST_URI'] . "';
            </script>";
            exit();
        }
        if ($action === 'panier' ) {
        $stmt = $pdo->prepare('SELECT * FROM panier_produit WHERE id_produit = :id_produit AND id_panier = :id_panier');
        $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
        $verif = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
        $stmt_stock->execute([':id_produit' => $id_produit]);
        $stock_dispo = (int) $stmt_stock->fetchColumn();

        if ($verif) {
            $stmt_info = $pdo->prepare("SELECT pp.quantite
                FROM panier_produit pp
                WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
            ");
            $stmt_info->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $quantite_actuelle = (int) $info['quantite'];
            if ($quantite_actuelle < $stock_dispo) {
                $augmente = "UPDATE panier_produit 
                SET quantite = quantite + 1 
                WHERE id_produit = :id_produit AND id_panier = :id_panier";
                $requete_augmente = $pdo->prepare($augmente);
                $requete_augmente->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
                $_SESSION["message_success"] = "Article ajouté au panier !";
            }
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
                $_SESSION["message_success"] = "Article ajouté au panier !";
            }
        }

        }
        else if ($action === 'payer'){
            $stmt = $pdo->prepare('SELECT * FROM panier_produit WHERE id_produit = :id_produit AND id_panier = :id_panier');
            $stmt->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
            $verif = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($verif) {
                $stmt_info = $pdo->prepare("SELECT pp.quantite
                    FROM panier_produit pp
                    WHERE pp.id_produit = :id_produit AND pp.id_panier = :id_panier
                ");
                $stmt_info->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
                $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
                $quantite_actuelle = (int) $info['quantite'];
                if ($quantite_actuelle < $stock_dispo) {
                    $augmente = "UPDATE panier_produit 
                    SET quantite = quantite + 1 
                    WHERE id_produit = :id_produit AND id_panier = :id_panier";
                    $requete_augmente = $pdo->prepare($augmente);
                    $requete_augmente->execute([':id_produit' => $id_produit, ':id_panier' => $id_panier]);
                }
                $_SESSION["message_success"] = "Article ajouté au panier !";
            }else{
                if ($stock_dispo > 0) {
                    $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                    $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
                    $_SESSION["message_success"] = "Article ajouté au panier !";
                }
            }
    
        $newImagesCount = 0;
        if (isset($_FILES['nouvelle_image']) && !empty($_FILES['nouvelle_image']['name'][0])) {
            $newImagesCount = count(array_filter($_FILES['nouvelle_image']['name']));
        }

        if ($newImagesCount < 1) {
            $errors['images'] = "Vous devez avoir au moins une image pour ce produit.";
        }

        if ($newImagesCount > 3) {
            $errors['images'] = "Vous ne pouvez pas avoir plus de 3 images par produit.";
        }

        echo "<script>
                window.location.href = 'panier.php';
            </script>";
            exit();
        }
    }
}
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail produit - <?= htmlspecialchars($infos['nom_produit']) ?></title>
    <meta name="description" content="Page ou tu vois un produit avec son détail !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
</head>
<body>
    <header>
        <?php include 'header.php'?>
    </header>
    <main class="main_produit" style="padding-top: 50px;">
        <section class="fiche-produit">

            <div class="fiche-container">
                <div class="images-produit">
                    <?php
                    $requete_img = $pdo->prepare('SELECT chemin_image FROM media_produit WHERE id_produit = :id_produit LIMIT 1');
                    $requete_img->execute([':id_produit' => $id_produit]);
                    $img = $requete_img->fetch();
                    ?>
                    <img src="<?= $img['chemin_image'] ? htmlentities($img['chemin_image']) : 'front_end/assets/images_produits/' ?>" alt="Kouign Amann" class="image-principale">

                    <div class="miniatures">
                        <?php
                            $requete_img = $pdo->prepare('SELECT chemin_image FROM media_produit WHERE id_produit = :id_produit');
                            $requete_img->execute([':id_produit' => $id_produit]);
                            $img = $requete_img->fetchAll();
                            $imgprincipale = true;

                            foreach ($img as $minia) {
                                if ($imgprincipale) {
                                    $imgprincipale = false;
                                }
                                else{
                                    $chemin = !empty($minia["chemin_image"])
                                    ? htmlentities($minia["chemin_image"])
                                    : "front_end/assets/images_produits/default.png"; // mets une image par défaut si tu veux

                                    echo '<img src="' . $chemin . '" alt="Miniature">';
                                }
                            }
                        ?>

                    </div>
                    <?php
                    $couleur_fill_heart = "#e8e8e8";
                    $couleur_stroke_heart = "black";

                    // Si l'utilisateur est connecté, on vérifie s'il a déjà liké ce produit
                    if (isset($_SESSION['id_client'])) {
                        $stmt_check_initial = $pdo->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_produit = ?");
                        $stmt_check_initial->execute([$_SESSION['id_client'], $id_produit]);
                        if ($stmt_check_initial->fetchColumn()) {
                            $couleur_fill_heart = "red";
                            $couleur_stroke_heart = "red";
                        }
                    }
                    ?>
                    <button class="btn-favoris" data-id-produit="<?= $id_produit ?>">
                        <svg width="3.25em" height="3.25em" viewBox="0 0 24 24" 
                            fill="<?= $couleur_fill_heart ?>" 
                            stroke="<?= $couleur_stroke_heart ?>" 
                            stroke-width="2" 
                            stroke-linecap="round" 
                            stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </button>
                </div>

                <div class="infos-produit">
                    <div class="titre-prix-boutons">
                        <div class="titre-prix">
                            <div class="titre-ligne">
                                <h1><?= htmlspecialchars($infos['nom_produit']) ?></h1>
                            </div>
                            
                            <?php if ($a_une_remise): ?>
                                <!-- Affichage avec remise -->
                                <div class="remise-detail-container">
                                    <div class="remise-badge-detail">
                                        <?php if ($infos['type_remise'] === 'pourcentage'): ?>
                                            -<?= number_format($infos['valeur_remise'], 0) ?>%
                                        <?php else: ?>
                                            -<?= number_format($infos['valeur_remise'], 2, ',', ' ') ?>€
                                        <?php endif; ?>
                                    </div>
                                    <span class="remise-nom-detail"><?= htmlentities($infos['nom_remise']) ?></span>
                                </div>
                                
                                <div class="prix-container-detail">
                                    <p class="prix-original-detail"><?= number_format($infos['prix_ttc'], 2, ',', ' ') ?>€</p>
                                    <p class="prix-final-detail"><?= number_format($prix_final, 2, ',', ' ') ?>€</p>
                                </div>
                                
                                <p class="prix-ht-detail">Prix HT avec remise : <?= number_format($prix_ht_final, 2, ',', ' ') ?>€</p>
                                
                                <div class="remise-periode">
                                    <small>
                                        <?php
                                        $debut = new DateTime($infos['date_debut']);
                                        $fin = new DateTime($infos['date_fin']);
                                        echo "Offre valable du " . $debut->format('d/m/Y') . " au " . $fin->format('d/m/Y');
                                        ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                                        <!-- Affichage sans remise -->
                            <p class="prix"><?= number_format($infos['prix_ttc'], 2, ',', ' ') ?>€</p>
                            <p class="prix-ht">Prix HT : <?= number_format($infos['prix_unitaire_ht'], 2, ',', ' ') ?>€</p>
                        <?php endif; ?>
                        </div>
                        <div class="boutons" > 
                        <?php if($button){?>
                            <form action="" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="panier">
                                <button type="submit">Ajouter au panier</button>
                            </form>
                            
                            <form action="" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="payer">
                                <button type="submit">Payer maintenant</button>
                            </form>
                            
                        <?php } else { ?>
                                <form action="" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="panier">
                                    <button type="submit" style ="background-color : gray;" disabled>Ajouter au panier</button>
                                </form>
                                
                                <form action="" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="payer">
                                    <button type="submit" style ="background-color : gray;" disabled>Payer maintenant</button>
                                </form>
                        <?php } ?>
                        </div>
                    </div>
                    <p class="description">
                        <?= htmlspecialchars($infos['description_produit']) ?>
                    </p>
                    <div class="stock-avis">
                        <span class="stock-dispo" style="color: <?= $stock_dispo > 0 ? 'green' : 'red' ?>">
                            <?php
                                if ($stock_dispo > 0) {
                                    echo 'Stock disponible : ' . $stock_dispo .'';
                                }
                                else {
                                    echo 'Rupture de stock';
                                }
                            ?>
                        </span>

                        <div class="avis">
                            <?php
                                if (count($avis) > 0) {
                                    echo '<span class="avis-etoiles">' . genererEtoiles($moyenne) . '</span>';
                                    echo '<span class="note">' . number_format($moyenne, 1, ',', '') . '/5</span>';
                                    echo '<a href="#avis-section">Voir les ' . count($avis) . ' avis</a>';
                                } else {
                                    echo '<span class="avis-etoiles">☆☆☆☆☆</span>';
                                    echo '<span class="note">Aucune note</span>';
                                }
                            ?>
                        </div>        
                    </div>
                </div>
            </div>
        </section>
        <hr class="separateur-avis">
        <section class="avis" id="avis-section">
            
            <?php
                echo '<h1>' . count($avis) . ' avis</h1>';
                if (count($avis) > 0) {
                    $req_client = $pdo->prepare("SELECT prenom, nom FROM compte_client WHERE id_client = ?");
                    foreach ($avis as $un_avis) {
                        $id_client = (int) $un_avis['id_client'];
                        $est_signale = isset($_SESSION['avis_signales']) && in_array($id_client, $_SESSION['avis_signales']);
                        $req_reponse = $pdo->prepare("SELECT description FROM reponse WHERE id_client = :id_client AND id_produit = :id_produit AND id_vendeur = :id_vendeur");
                        $req_reponse->execute([
                            ':id_client' => $id_client,
                            ':id_produit' => $id_produit,
                            ':id_vendeur' => $id_vendeur
                        ]);
                        $req_vote = $pdo->prepare("SELECT id_client_votant, valeur_vote FROM vote_avis WHERE id_client_auteur = :id_client_auteur AND id_produit = :id_produit");
                        $req_vote->execute([
                            ':id_client_auteur' => $id_client,
                            ':id_produit' => $id_produit
                        ]);
                        $vote_pos = 0;
                        $vote_neg = 0;
                        $voteval = 0;
                        while ($vote = $req_vote->fetch(PDO::FETCH_ASSOC)){
                            if ($vote['valeur_vote'] == -1){
                                $vote_neg++;
                                if (isset($_SESSION['id_client'])){
                                    if ($vote['id_client_votant'] == $_SESSION['id_client']){
                                        $voteval = -1;
                                    }
                                }
                            }
                            if ($vote['valeur_vote'] == 1){
                                $vote_pos++;
                                if (isset($_SESSION['id_client'])){
                                    if ($vote['id_client_votant'] == $_SESSION['id_client']){
                                        $voteval = 1;
                                    }
                                }
                            }
                        }
                        // Si signalé : Remplissage ROUGE, Bordure ROUGE
                        // Si pas signalé : Remplissage BLANC, Bordure NOIRE (pour qu'on voie la forme)
                        $couleur_fill   = $est_signale ? 'red' : 'white';
                        $couleur_stroke = $est_signale ? 'red' : 'black';
                        $deactiver = $est_signale ? 'disabled' : '';
                        $req_client->execute([$id_client]);
                        $client = $req_client->fetch(PDO::FETCH_ASSOC);
                        // Génération des étoiles selon la note
                        $note = floatval($un_avis['note']);
                        $etoiles = genererEtoiles($note);

                        echo '
                        <div class="avis-item"> 
                            <div class="avis-header">
                                <strong>' . htmlspecialchars($client['prenom']) . ' ' . htmlspecialchars($client['nom']) . '</strong>
                                <span class="avis-etoiles">' . $etoiles . '</span>
                            </div>
                            <p class="avis-commentaire">' . htmlspecialchars($un_avis['description']) . '</p>';
                        // Images de l'avis
                        $req_images_avis = $pdo->prepare("SELECT id_media, chemin_image FROM media_avis WHERE id_client = :id_client AND id_produit = :id_produit");
                        $req_images_avis->execute([':id_client' => $id_client, ':id_produit' => $id_produit]);
                        $images_avis = $req_images_avis->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($images_avis)) {
                            echo '<div class="avis-images">';
                            foreach ($images_avis as $img_avis) {
                                echo '<div class="avis-image-wrapper">';
                                echo '<img src="' . htmlspecialchars($img_avis['chemin_image']) . '" alt="Photo avis" class="avis-img">';
                                if (isset($_SESSION["id_client"]) && $_SESSION["id_client"] == $id_client) {
                                    echo '
                                    <form method="post">
                                        <input type="hidden" name="action" value="supprimer_image_avis">
                                        <input type="hidden" name="id_media" value="' . $img_avis['id_media'] . '">
                                    </form>';
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '<div class="avis-button">';
                        if ($reponse = $req_reponse->fetch()){
                            ?>
                            <button class="toggle-view-reponse">voir la réponse</button>
                            <?php
                        }
                        if (isset($_SESSION["id_client"])){
                            if ($_SESSION["id_client"] == $un_avis["id_client"]) {
                                ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="supprimer_avis">
                                    <button type="submit" class="btn-supprimer-avis">Supprimer mon avis</button>
                                </form>
                                <div class="avis-votes">
                                    <button type="button" class="btn-vote btn-upvote <?= ($vote === 1) ? 'active' : '' ?>" aria-label="Avis utile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_pos ?></span> </button>

                                    <button type="button" class="btn-vote btn-downvote <?= ($vote === -1) ? 'active' : '' ?>" aria-label="Avis inutile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_neg ?></span> </button>
                                </div>
                                <?php
                                
                            }
                            else{
                                ?>
                                <button class="btn-signaler-avis" 
                                data-id-client="<?= $un_avis['id_client'] ?>"
                                <?= $deactiver ?>>
                                    <svg width="24" height="24" viewBox="0 0 24 24" 
                                        fill="<?= $couleur_fill ?>" 
                                        stroke="<?= $couleur_stroke ?>" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round">
                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                        <line x1="4" y1="22" x2="4" y2="15"></line>
                                    </svg>
                                </button>
                                <div class="avis-votes">
                                    <button type="button" class="btn-vote btn-upvote <?= ($vote === 1) ? 'active' : '' ?>" aria-label="Avis utile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_pos ?></span> </button>

                                    <button type="button" class="btn-vote btn-downvote <?= ($vote === -1) ? 'active' : '' ?>" aria-label="Avis inutile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_neg ?></span> </button>
                                </div>
                                <?php
                            }
                        }
                        else{
                            ?>
                            <button class="btn-signaler-avis" 
                            data-id-client="<?= $un_avis['id_client'] ?>"
                            <?= $deactiver ?>>
                                <svg width="24" height="24" viewBox="0 0 24 24" 
                                    fill="<?= $couleur_fill ?>" 
                                    stroke="<?= $couleur_stroke ?>" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round">
                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                    <line x1="4" y1="22" x2="4" y2="15"></line>
                                </svg>
                            </button>
                                <div class="avis-votes">
                                    <button type="button" class="btn-vote btn-upvote <?= ($vote === 1) ? 'active' : '' ?>" aria-label="Avis utile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_pos ?></span> </button>

                                    <button type="button" class="btn-vote btn-downvote <?= ($vote === -1) ? 'active' : '' ?>" aria-label="Avis inutile" data-id-avis="<?= $un_avis['id_client'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                                        </svg>
                                        <span class="vote-count"><?php echo $vote_neg ?></span> </button>
                                </div>
                            <?php
                        }
                        ?>
                            </div>
                            <p class="view-reponse">
                                <?php echo $reponse['description']; ?>
                            </p>
                        </div>
                        <?php
                    }
                    
                } else {
                    echo '<p>Aucun avis pour ce produit pour le moment.</p>';
                }
            ?>
            <div class="form-avis">
                <h2>Écrire un avis</h2>

                <?php if (isset($erreur_avis)) echo "<p class='erreur'>$erreur_avis</p>"; ?>

                <form action="" method="post" id="avisForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="ajouter_avis">
                    <input type="hidden" name="note" id="noteInput" value="">

                    <div class="rating-container">
                        <label>Votre note :</label>
                        <div class="stars-rating">
                            <span class="star" data-value="1">★</span>
                            <span class="star" data-value="2">★</span>
                            <span class="star" data-value="3">★</span>
                            <span class="star" data-value="4">★</span>
                            <span class="star" data-value="5">★</span>
                            <span class="rating-text" id="ratingText">Sélectionnez une note</span>
                        </div>
                    </div>

                    <label for="description">Votre avis :</label>
                    <textarea name="description" id="description" rows="4" placeholder="Partagez votre expérience..." required></textarea>

                    <article>
                        <h3>Médias (<span id="imageCount">0</span>/3)</h3>

                        <div id="mediaGallery" class="media-gallery"></div>

                        <div class="media-upload">
                            <label for="nouvelle_image">Ajouter images :</label>
                            <input type="file" id="nouvelle_image" name="nouvelle_image" accept="image/*">
                        </div>

                        <?php if (isset($errors['images'])) { ?>
                            <p class="error"><?php echo $errors['images']; ?></p>
                        <?php } ?>
                    </article>

                    <button type="submit">Envoyer mon avis</button>
                </form>
            </div>
        </section>
        <div id="popup-image" class="popup">
            <span class="close">&times;</span>
            <img class="popup-content" id="popup-img" src="">
        </div>
        <div id="popup-signalement" class="popup">
            <span class="close">&times;</span>
            <form action="" method="post">
                <input type="hidden" name="action" value="signaler_avis">
                <input type="hidden" name="id_client_cible" id="input_id_client_cible" value="">
                
                <label for="raison">Raison du signalement ?</label>
                <select name="raison" id="raison" class="input-style" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="spam">Spam ou publicité</option>
                    <option value="haine">Contenu haineux ou offensant</option>
                    <option value="sexuel">Contenu à caractère sexuel ou violent</option>
                    <option value="hors-sujet">Hors sujet</option>
                    <option value="autre">Autre</option>
                </select>

                <label for="details">Précisions (optionnel) :</label>
                <textarea name="details" id="details" class="input-style" rows="4" placeholder="Expliquez le problème..."></textarea>
                <label class="form_communaute">Nous vérifierons si cet avis est conforme aux règles de notre communauté. Si ce n'est pas le cas, nous le supprimerons.</label>

                <div class="actions">
                    <button type="button" class="btn-cancel" onclick="closePopup()">Annuler</button>
                    <button type="submit" class="btn-confirm">Signaler</button>
                </div>
            </form>
        </div>
    </main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
    <div id="toast-global" class="toast"></div> 
    <script>
        // Sélection des éléments
        const miniatures = document.querySelectorAll('.miniatures img');
        const popup = document.getElementById('popup-image');
        const popupImg = document.getElementById('popup-img');
        const closeBtn = document.querySelector('.popup .close');

        // Quand on clique sur une miniature
        miniatures.forEach(img => {
            img.addEventListener('click', () => {
                popup.style.display = 'block';
                popupImg.src = img.src; // affiche la bonne image
            });
        });

        // Quand on clique sur la croix
        closeBtn.addEventListener('click', () => {
            popup.style.display = 'none';
        });

        // Quand on clique en dehors de l’image
        popup.addEventListener('click', (event) => {
            if (event.target === popup) {
                popup.style.display = 'none';
            }
        });

        // popup signalement
        const boutonsSignalement = document.querySelectorAll('.btn-signaler-avis');
        const popupSignalement = document.getElementById('popup-signalement');
        const closeBtnSignalement = popupSignalement.querySelector('.close');
        const cancelBtnSignalement = popupSignalement.querySelector('.btn-cancel');


        boutonsSignalement.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idClient = e.currentTarget.getAttribute('data-id-client');

                // injecte l'id du client dans le  formulaire
                document.getElementById('input_id_client_cible').value = idClient;
                popupSignalement.style.display = 'flex';
            });
        });

        function fermerSignalement() {
            popupSignalement.style.display = 'none';
        }

        closeBtnSignalement.addEventListener('click', fermerSignalement);
        if(cancelBtnSignalement) {
            cancelBtnSignalement.addEventListener('click', fermerSignalement);
        }
        
        let newImagesPreview = [];

        function updateImageCount() {
            const newImages = newImagesPreview.length;

            document.getElementById('imageCount').textContent = newImages;

            const fileInput = document.getElementById('nouvelle_image');
            if (newImages >= 3) {
                fileInput.disabled = true;
                fileInput.parentElement.style.opacity = '0.5';
            } else {
                fileInput.disabled = false;
                fileInput.parentElement.style.opacity = '1';
            }
        }
        
        const MAX_SIZE = 2 * 1024 * 1024;
        const MAX_WIDTH = 300;
        const MAX_HEIGHT = 300;
        const QUALITY = 0.8;

        document.getElementById("nouvelle_image").addEventListener("change", function (e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > MAX_SIZE) {
                alert("L'image est trop lourde (max 2 Mo).");
                e.target.value = "";
                return;
            }

            const newImages = newImagesPreview.length;
            if (newImages >= 3) {
                alert("Maximum 3 images !");
                e.target.value = "";
                return;
            }

            const reader = new FileReader();
            reader.onload = ev => {
                const imgEl = new Image();
                imgEl.onload = () => {
                    // Calcul des nouvelles dimensions en gardant le ratio
                    let width = imgEl.width;
                    let height = imgEl.height;
                    if (width > MAX_WIDTH || height > MAX_HEIGHT) {
                        const ratio = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }

                    // Redimensionnement via canvas
                    const canvas = document.createElement("canvas");
                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext("2d").drawImage(imgEl, 0, 0, width, height);

                    // Conversion en Blob compressé
                    canvas.toBlob(blob => {
                        const resizedFile = new File([blob], file.name, { type: "image/jpeg" });

                        const div = document.createElement("div");
                        div.classList.add("media-item", "preview");

                        const preview = document.createElement("img");
                        preview.src = canvas.toDataURL("image/jpeg", QUALITY);

                        const btn = document.createElement("button");
                        btn.textContent = "Supprimer";
                        btn.style = "button";
                        btn.addEventListener("click", function () {
                            div.remove();
                            newImagesPreview = newImagesPreview.filter(f => f !== resizedFile);
                            fileInput.remove();
                            updateImageCount();
                        });

                        div.appendChild(preview);
                        div.appendChild(btn);
                        document.getElementById("mediaGallery").appendChild(div);

                        // Injection du fichier redimensionné dans le formulaire
                        const fileInput = document.createElement("input");
                        fileInput.type = "file";
                        fileInput.name = "nouvelle_image[]";
                        fileInput.hidden = true;
                        document.getElementById('avisForm').appendChild(fileInput);

                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(resizedFile);
                        fileInput.files = dataTransfer.files;
                        newImagesPreview.push(resizedFile);
                        updateImageCount();
                    }, "image/jpeg", QUALITY);
                };
                imgEl.src = ev.target.result;
            };
            reader.readAsDataURL(file);
            e.target.value = "";
        });
        updateImageCount();
    </script>
    <script src="/front_office/front_end/assets/js/AvisLike.js"></script>
    <script src="/front_office/front_end/assets/js/noteEtoile.js"></script>
    <script src="/front_office/front_end/assets/js/reponseVendeur.js"></script>
    <script src="../assets/js/toast.js"></script>
    <?php if (isset($_SESSION['message_success'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                afficherToast("<?php echo addslashes($_SESSION['message_success']); ?>", "succes");
            });
        </script>
        <?php 
            unset($_SESSION['message_success']); 
        ?>
    <?php endif; ?>
    <script src="/front_office/front_end/assets/js/favoris.js"></script>
</body>
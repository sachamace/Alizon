<?php
include 'config.php';
include 'sessionindex.php';
try {
    if (isset($_GET['article'])) {
        $id_produit = $_GET['article'];
    } else {
        $id_produit = null; // ou une valeur par défaut
    }
    $stmt2 = $pdo->query("SELECT * FROM produit WHERE id_produit = $id_produit;");
    $infos = $stmt2->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage();
}
if(isset($_SESSION['id_panier'])){
    $id_panier = $_SESSION['id_panier']; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique
}
// recuperation du stock
$stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
$stmt_stock->execute([':id_produit' => $id_produit]);
$stock_dispo = (int) $stmt_stock->fetchColumn();
// recuperation du chemin vers les images
// php avis du produit
$requete_avis = $pdo->prepare("
    SELECT * 
    FROM avis
    WHERE id_produit = :id_produit 
    ORDER BY id_produit ASC
");
$requete_avis->execute([':id_produit' => $id_produit]);
$avis = $requete_avis->fetchAll(PDO::FETCH_ASSOC);

// Calcul de la moyenne des notes des avis
$moyenne = 0;
if (count($avis) > 0) {
    $total_notes = 0;
    foreach ($avis as $un_avis) {
        $total_notes += (int)$un_avis['note'];
    }
    $moyenne = round($total_notes / count($avis)); // arrondi à l'entier le plus proche
}
// Traitement du formulaire d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_avis') {
    $note = (int)$_POST['note'];
    $description = trim($_POST['description']);
    if ($note >= 1 && $note <= 5 && !empty($description) && isset($_SESSION['id_panier'])) {
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
            
            // On recharge la page pour voir le nouvel avis
            echo "<script>
                window.location.href = '" . $_SERVER['REQUEST_URI'] . "';
            </script>";
            exit();
        } catch (PDOException $e) {
            $erreur_avis = "Vous avez déjà rentré un avis";
        }
    } else {
        $erreur_avis = "Veuillez entrer une note entre 1 et 5 et une description.";
    }
}

// traitement des autres actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'];
    if (isset($_GET['article'])) {
        $id_produit = $_GET['article'];
    } else {
        $id_produit = null; // ou une valeur par défaut
    }
    if(!isset($_SESSION['id_panier'])) {
        echo "<script>
           window.location.href = 'seconnecter.php';
        </script>";
        exit();
    }
    else{
        $id_panier = $_SESSION['id_panier']; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique
        if ($action === 'supprimer_avis') {
            $id_client = $_SESSION['id_client'];

            // Sécurisé : on supprime uniquement si l'avis appartient à l'utilisateur
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
        if ($action === 'panier' ) { // traitement ajouter panier
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
            }
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
            }
        }

        }else if ($action === 'payer') { // traitement payer immediatement
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
        }else{
            if ($stock_dispo > 0) {
                $requete_ajout = $pdo->prepare("INSERT INTO panier_produit(id_panier,id_produit,quantite) VALUES(:id_panier, :id_produit, 1);");
                $requete_ajout->execute([":id_produit"=> $id_produit, ":id_panier"=> $id_panier]);
            }
        }
        echo "<script>
            window.location.href = 'panier.php';
        </script>";
        exit(); // très important pour arrêter le scrip
        }
    }
    }
?>


<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produit Détail - Compte CLient</title>
    <meta name="description" content="Page ou tu vois un produit avec son détail !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
    <style>
        .rating-container {
            margin: 1rem 0;
        }

        .rating-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .stars-rating {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .star {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .star.active {
            color: #ffd700;
            animation: starPulse 0.3s ease;
        }

        .star.hover {
            color: #ffed4e;
        }

        @keyframes starPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .rating-text {
            margin-left: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #666;
            min-width: 150px;
        }
    </style>
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
                </div>

                <div class="infos-produit">
                    <div class="titre-prix-boutons">
                        <div class="titre-prix">
                            <div class="titre-ligne">
                                <h1><?= htmlspecialchars($infos['nom_produit']) ?></h1>
                            </div>
                            
                            <div class="prix">
                                <span class="prix-valeur"><?= number_format($infos['prix_ttc'], 2, '.', ' ') ?>€</span>
                            </div>
                        </div>
                        <div class="boutons">
                                <?php echo '
                                <form action="" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="panier">
                                    <button type="submit">Ajouter au panier</button>
                                </form>
                                
                                <form action="" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="payer">
                                    <button type="submit">Payer maintenant</button>
                                </form>' ?>
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
                                    $etoiles_moyenne = str_repeat('★', $moyenne) . str_repeat('☆', 5 - $moyenne);
                                    echo '<span class="etoiles">' . $etoiles_moyenne . '</span>';
                                    echo '<span class="note">' . $moyenne . '/5</span>';
                                    echo '<a href="#avis-section">Voir les ' . count($avis) . ' avis</a>';
                                } else {
                                    echo '<span class="etoiles">☆☆☆☆☆</span>';
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
                    foreach ($avis as $un_avis) {
                        $id_client = (int) $un_avis['id_client'];
                        $client = $pdo->query("SELECT * FROM compte_client WHERE id_client = $id_client")->fetch(PDO::FETCH_ASSOC);
                        // Génération des étoiles selon la note
                        $note = (int)$un_avis['note'];
                        $etoiles = str_repeat('★', $note) . str_repeat('☆', 5 - $note);

                        echo '
                        <div class="avis-item"> 
                            <div class="avis-header">
                                <strong>' . htmlspecialchars($client['prenom']) . ' ' . htmlspecialchars($client['nom']) . '</strong>
                                <span class="avis-etoiles">' . $etoiles . '</span>
                            </div>
                            <p class="avis-commentaire">' . htmlspecialchars($un_avis['description']) . '</p>';
                        echo '<div class="avis-button">';
                        if (isset($_SESSION["id_client"])){
                            if ($_SESSION["id_client"] == $un_avis["id_client"]) {
                                echo '
                                <form method="post">
                                    <input type="hidden" name="action" value="supprimer_avis">
                                    <button type="submit" class="btn-supprimer-avis">Supprimer mon avis</button>
                                </form>';
                            }
                            else{
                                ?>
                                <button class="btn-signaler-avis">Signaler cet avis</button>
                                <?php
                            }
                        }
                        else{
                            ?>
                            <button class="btn-signaler-avis">Signaler cet avis</button>
                            <?php
                        }
                        
                        echo '
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>Aucun avis pour ce produit pour le moment.</p>';
                }
            ?>
            <div class="form-avis">
                <h2>✏️ Écrire un avis</h2>

                <?php if (isset($erreur_avis)) echo "<p class='erreur'>$erreur_avis</p>"; ?>

                <form action="" method="post" id="avisForm">
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
                
                <label for="raison">Raison du signalement ?</label>
                <select name="raison" id="raison" required>
                    <option value="">-- Sélectionnez --</option>
                    <option value="spam">Spam ou publicité</option>
                    <option value="haine">Contenu haineux ou offensant</option>
                    <option value="sexuel">Contenu à caractère sexuel ou violent</option>
                    <option value="hors-sujet">Hors sujet</option>
                    <option value="autre">Autre</option>
                </select>

                <label for="details">Précisions (optionnel) :</label>
                <textarea name="details" id="details" rows="3" placeholder="Expliquez le problème..."></textarea>

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

    <script> // PARTI JAVASCRIPT
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
        const boutton_signalement =document.querySelector('.btn-signaler-avis');
        const popupSignalement = document.getElementById('popup-signalement');

        boutton_signalement.addEventListener('click', () => {
            popupSignalement.style.display = 'block';
        })

    </script>
    <script src="../assets/js/noteEtoile.js"></script>
</body>
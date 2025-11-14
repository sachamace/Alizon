<?php
include 'config.php';
include 'session.php';
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

if (isset($_GET['article'])) {
    $id_produit = $_GET['article'];
} else {
    $id_produit = null; // valeur par défaut
}
$id_panier = 2; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique

$stmt_stock = $pdo->prepare("SELECT stock_disponible FROM produit WHERE id_produit = :id_produit");
$stmt_stock->execute([':id_produit' => $id_produit]);
$stock_dispo = (int) $stmt_stock->fetchColumn();

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
    $id_client = 1; // À remplacer par $_SESSION['id_client'] quand tu auras le système de connexion  ATTENTION !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    if ($note >= 1 && $note <= 5 && !empty($description)) {
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
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } catch (PDOException $e) {
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
    $id_panier = 2; // à remplacer par $_SESSION['id_panier'] si on veux le rendre dynamique

    if ($action === 'panier') { // traitement ajouter panier
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
        header('Location: panier.php');
        exit(); // très important pour arrêter le scrip
        }
    }
?>


<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Récapitulatif du panier</title>
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity=""
        crossorigin="anonymous">
</head>
<body>
    <header>
        <nav>
            <nav>
                <a href="accueil.html"><img src="../assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
                <a class="notif" href="notification.html"><i class="fa-regular fa-bell icone"></i></a>
                <form action="recherche.html" method="get" role="search" aria-label="Site search">
                    <label for="site-search"></label>
                    <input type="search" id="site-search" name="q" placeholder="Recherche un produit, une marque..." />
                    <button type="submit">Search</button>
                </form>
                <a href="panier.html"><i class="fa-solid fa-cart-shopping icone"></i>Panier</a>
            </nav>
            <nav>
                <div>
                    <a href="accueil.html">Accueil</a>
                    <a href="produitTerroir.html">Produit du Terroir</a>
                    <a href="modeBretonne.html">Mode Bretonne</a>
                    <a href="">Artisanat Local</a>
                    <a href="">Décoration Intérieure</a>
                    <a href="">Epicerie FIne</a>
                </div>
                <a href="seconnecter.html"><i class="fa-regular fa-user icone"></i>Mon Compte</a>
            </nav>
        </nav>
    </header>
    <main class="main_produit">
        <section class="fiche-produit">

            <div class="fiche-container">
                <div class="images-produit">
                    <img src="../assets/images/Tel.jpg" alt="Kouign Amann" class="image-principale">

                    <div class="miniatures">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 1">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 2">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 3">
                        <img src="../assets/images/Tel.jpg" alt="Miniature 4">
                    </div>
                </div>

                <div class="infos-produit">
                    <div class="titre-ligne">
                        <h1><?= htmlspecialchars($infos['nom_produit']) ?></h1>
                        <i class="fa-regular fa-heart"></i>
                    </div>
                    

                    <div class="prix">
                        <span class="prix-valeur"><?= number_format($infos['prix_ttc'], 2, ',', ' ') ?>€</span>
                    </div>


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

                    <p class="description">
                        <?= htmlspecialchars($infos['description_produit']) ?>
                    </p>



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
                            <p class="avis-commentaire">' . htmlspecialchars($un_avis['description']) . '</p>
                        </div>';
                    }
                } else {
                    echo '<p>Aucun avis pour ce produit pour le moment.</p>';
                }
            ?>
            <div class="form-avis">
                <h2>✏️ Écrire un avis</h2>

                <?php if (isset($erreur_avis)) echo "<p class='erreur'>$erreur_avis</p>"; ?>

                <form action="" method="post">
                    <input type="hidden" name="action" value="ajouter_avis">

                    <label for="note">Note (1 à 5) :</label>
                    <select name="note" id="note" required>
                        <option value="">-- Sélectionnez --</option>
                        <option value="1">★☆☆☆☆ - 1</option>
                        <option value="2">★★☆☆☆ - 2</option>
                        <option value="3">★★★☆☆ - 3</option>
                        <option value="4">★★★★☆ - 4</option>
                        <option value="5">★★★★★ - 5</option>
                    </select>

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
    </main>

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
    </script>
</body>
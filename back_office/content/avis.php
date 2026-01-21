<?php
    $id_vendeur = $_SESSION['vendeur_id'];
    $id_produit = $_GET['id'];
    $requete_avis = $pdo->prepare("
        SELECT description, note, id_client
        FROM avis
        WHERE id_produit = :id_produit 
        ORDER BY id_produit ASC
    ");
    $requete_avis->execute([':id_produit' => $id_produit]);
    $avis = $requete_avis->fetchAll(PDO::FETCH_ASSOC);


    // focntion pour generer Etoiles
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

    // formulaire réponse

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_reponse'){
        $description = trim($_POST['description']);
        $id_client_avis = $_POST['id_client_cible'];
        if (!empty($description)){
            $requete_ajout_reponse = $pdo->prepare("
                INSERT INTO reponse (id_client, id_produit, id_vendeur, description)
                VALUES (:id_client, :id_produit, :id_vendeur, :description)
            ");
            $requete_ajout_reponse->execute([
                ':id_client' => $id_client_avis,
                ':id_produit' => $id_produit,
                ':id_vendeur' => $id_vendeur,   
                ':description' => $description
            ]);

            echo "<script>
                window.location.href = '" . $_SERVER['REQUEST_URI'] . "';
            </script>";
            exit();
        }
    }
?>

<hr class="separateur-avis">
        <section class="avis" id="avis-section">
            <?php
                echo '<h1>' . count($avis) . ' avis</h1>';
                if (count($avis) > 0) {
                    $req_client = $pdo->prepare("SELECT prenom, nom FROM compte_client WHERE id_client = ?"); // test changement
                    foreach ($avis as $un_avis) {
                        $id_client = (int) $un_avis['id_client'];

                        $req_client->execute([$id_client]);
                        $client = $req_client->fetch(PDO::FETCH_ASSOC);

                        $req_reponse = $pdo->prepare("SELECT description FROM reponse WHERE id_client = :id_client AND id_produit = :id_produit AND id_vendeur = :id_vendeur");
                        $req_reponse->execute([
                            ':id_client' => $id_client,
                            ':id_produit' => $id_produit,
                            ':id_vendeur' => $id_vendeur
                        ]);
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
                            if ($reponse = $req_reponse->fetch()){
                                ?>
                                <div class="avis-button">
                                    <button class="toggle-view-reponse">voir la réponse</button>
                                </div>
                                <p class="view-reponse">
                                    <?php echo $reponse['description']; ?>
                                </p>
                                <?php
                            }
                            else{
                        ?>
                            <div class="avis-button">
                                <button class="toggle-reponse">Répondre au client</button>
                            </div>
                            <form action="" method="post" class="reponse-vendeur">
                                <input type="hidden" name="action" value="ajouter_reponse">
                                <input type="hidden" name="id_client_cible" id="input_id_client_cible" value="<?php echo $id_client; ?>">
                                <textarea name="description" class="description" rows="5" placeholder="répondre à l'avis..." required></textarea>
                                <button type="submit">Envoyer ma réponse</button>
                            </form>
                        <?php
                            }
                        ?>
                        </div> <?php
                    }
                } else {
                    echo '<p>Aucun avis pour ce produit pour le moment.</p>';
                }
            ?>
            <script src="/front_office/front_end/assets/js/reponseVendeur.js"></script>
<?php
    $requete_avis = $pdo->prepare("
        SELECT description, note, id_client
        FROM avis
        WHERE id_produit = :id_produit 
        ORDER BY id_produit ASC
    ");
    $requete_avis->execute([':id_produit' => $_GET['id']]);
    $avis = $requete_avis->fetchAll(PDO::FETCH_ASSOC);
?>

<hr class="separateur-avis">
        <section class="avis" id="avis-section">
            <?php
                echo '<h1>' . count($avis) . ' avis</h1>';
                if (count($avis) > 0) {
                    $req_client = $pdo->prepare("SELECT * FROM compte_client WHERE id_client = ?");
                    foreach ($avis as $un_avis) {
                        $id_client = (int) $un_avis['id_client'];

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
                        
                        echo '<div class="avis-button">';
                        
                        echo '
                            </div>
                        </div>';
                    }
                } else {
                    echo '<p>Aucun avis pour ce produit pour le moment.</p>';
                }
            ?>
<?php
    include 'config.php';
    include 'session.php';
    // include 'sessionindex.php'; // À activer si besoin

    $id_client_connecte = $_SESSION['id'];

    function envoyer_au_c($message) {
        $host = "10.253.5.108";
        $port = 8080;
        $response = "";
        $login = "alizon";
        $hash_md5 = "e54d588ca06c9f379b36d0b616421376"; 

        // On préfixe le message original (qui contient déjà "CMD;ARGS")
        $message_complet = "$login;$hash_md5;$message";
        // ------------------------------

        $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        if (!$socket) {
            echo "Erreur connexion C: $errstr ($errno)\n";
            return null;
        }

        // On envoie le message formaté avec l'auth
        fwrite($socket, $message_complet);

        // Si on demande la liste, on attend une grosse réponse
        if ($message === "GET_LIST") {
            while (!feof($socket)) {
                $response .= fread($socket, 4096);
            }
        }
    
        fclose($socket);
        return $response;
    }

    // Vérification de l'ID commande dans l'URL
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: commandes.php'); // Redirection si pas d'ID
        exit();
    }

    $id_commande = $_GET['id'];

    try {
        $raw_commande = envoyer_au_c('CHECK'. ";" . $id_commande);
        $all_commande = explode(';', $raw_commande);
        $commande = [
            'id_commande'       => $id_commande,
            'statut'            => $all_commande[1],
            'etape'             => $all_commande[2],
            'date_maj'          => $all_commande[3],
            'details_etape'     => $all_commande[4],
            'date_commande'     => $all_commande[6],
            'montant_total_ht'  => $all_commande[7],
            'montant_total_ttc' => $all_commande[8]
        ];
        //  Récupération des infos principales de la commande
        /*$stmt = $pdo->prepare("
            SELECT 
                id_commande, 
                date_commande, 
                montant_total_ht, 
                montant_total_ttc, 
                statut,
                etape,
                date_maj,
                details_etape  
            FROM commande
            WHERE id_commande = :id_cmd AND id_client = :id_client
        ");
        $stmt->execute([':id_cmd' => $id_commande, ':id_client' => $id_client_connecte]);
        $commande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$commande) {
            die("Commande introuvable ou vous n'avez pas les droits.");
        }*/

        // Récupération des articles (lignes de commande)
        $stmt_lignes = $pdo->prepare("
            SELECT 
                lc.quantite, 
                lc.prix_unitaire_ht,
                p.nom_produit, 
                m.chemin_image,
                v.raison_sociale as nom_vendeur
            FROM ligne_commande lc
            JOIN produit p ON lc.id_produit = p.id_produit
            LEFT JOIN compte_vendeur v ON p.id_vendeur = v.id_vendeur
            LEFT JOIN media_produit m ON p.id_produit = m.id_produit
            WHERE lc.id_commande = :id_cmd
        ");
        $stmt_lignes->execute([':id_cmd' => $id_commande]);
        $all_lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

        $articles_par_vendeur = [];
        $nb_articles_total = 0;
        $numero_commande = "CMD-" . date('Ymd', strtotime($commande['date_commande'])) . "-" . str_pad($commande['id_commande'], 5, '0', STR_PAD_LEFT);
        // Calcul du nombre total d'articles
        $nb_articles_total = 0;
        foreach ($all_lignes as $ligne) {
            // Si pas de vendeur trouvé, on met un nom par défaut
            $nom_vendeur = !empty($ligne['nom_vendeur']) ? $ligne['nom_vendeur'] : 'Vendu par notre boutique';
            
            // On ajoute la ligne dans le tableau correspondant à ce vendeur
            $articles_par_vendeur[$nom_vendeur][] = $ligne;
            
            $nb_articles_total += $ligne['quantite'];
        }

        // --- Logique pour la Timeline (Barre de progression) ---
        // On définit les étapes logiques
        $etapes_visuelles = ['Validée', 'En préparation', 'Expédiée', 'Livrée'];
        $etape_actuelle = (int)$commande['etape'];
        // On trouve l'index de l'étape actuelle (basé sur le statut en BDD)
        // Note : Cela suppose que $commande['statut'] correspond exactement à l'un des mots clés
        $current_status = $commande['statut']; 
        $current_step_index = 0;
        
        if ($etape_actuelle >= 9) {
            $current_step_index = 3; // Livrée
        } elseif ($etape_actuelle >= 5) {
            $current_step_index = 2; // Expédiée (5, 6, 7, 8)
        } elseif ($etape_actuelle >= 2) {
            $current_step_index = 1; // En préparation (2, 3, 4)
        } else {
            $current_step_index = 0; // Validée (1)
        }

    } catch (PDOException $e) {
        die("Erreur technique : " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail Commande #<?= $commande['id_commande'] ?></title>
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="stylesheet" href="../assets/csss/detail_commande.css">
</head>

<body class="body_profilClient">
    <header class="disabled">
        <?php include 'header.php'?>
    </header>

    <div class="compte__header disabled">
        <a href="commandes.php">← Retour à mes commandes</a>
    </div>

    <main class="detail-container">
        
        <section class="detail-header">
            <div class="header-left">
                <h1>Commande <span>#<?= str_pad($numero_commande, 5, '0', STR_PAD_LEFT) ?></span></h1>
                <div class="statut-simple">
                    Statut actuel : <strong><?= htmlspecialchars($commande['statut']) ?></strong>
                </div>
                <p class="date-commande">Passée le <?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></p>
            </div>
            <div class="header-right">
                <div class="last-update">
                    <span class="label">Dernière mise à jour :</span>
                    <span class="valeur">
                        <?= !empty($commande['date_maj']) ? date('d/m/Y', strtotime($commande['date_maj'])) : '-' ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="suivi-timeline">
            <?php foreach($etapes_visuelles as $index => $nom_etape): 
                $isActive = ($index <= $current_step_index);
                $isCurrent = ($index === $current_step_index);
            ?>
                <div class="step <?= $isActive ? 'active' : '' ?>">
                    <div class="step-icon">
                        <?php if($index < $current_step_index): ?>✓<?php else: echo $index + 1; endif; ?>
                    </div>
                    <div class="step-label"><?= $nom_etape ?></div>
                    
                    <?php if($isCurrent && !empty($commande['details_etape'])): ?>
                        <div class="step-detail-bulle">
                            <?= htmlspecialchars($commande['details_etape']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if($index < count($etapes_visuelles) - 1): ?>
                    <div class="step-line <?= ($index < $current_step_index) ? 'full' : '' ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <section class="detail-content">
            <div class="articles-box">
                <h3>Articles commandés (<?= $nb_articles_total ?>)</h3>

                <?php foreach ($articles_par_vendeur as $vendeur => $lignes_du_vendeur): ?>
                    
                    <div class="vendeur-group">
                        <div class="vendeur-header">
                            Vendu et expédié par : <strong><?= htmlspecialchars($vendeur) ?></strong>
                        </div>

                        <div class="table-responsive">
                            <table class="table-articles">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th class="text-right">Prix Unitaire</th>
                                        <th class="text-center">Qté</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes_du_vendeur as $ligne): 
                                        $total_ligne = $ligne['prix_unitaire_ht'] * $ligne['quantite'];
                                    ?>
                                    <tr>
                                        <td class="col-produit">
                                            <div class="produit-wrapper">
                                                <img src="<?= htmlspecialchars($ligne['chemin_image'] ?? '/assets/images/default.png') ?>" alt="Img">
                                                <div>
                                                    <p class="nom"><?= htmlspecialchars($ligne['nom_produit']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right"><?= number_format($ligne['prix_unitaire_ht'], 2, ',', ' ') ?> €</td>
                                        <td class="text-center">x<?= $ligne['quantite'] ?></td>
                                        <td class="text-right"><strong><?= number_format($total_ligne, 2, ',', ' ') ?> €</strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($vendeur !== array_key_last($articles_par_vendeur)): ?>
                        <hr class="separator-vendeur">
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>

            <div class="recap-box">
                <h3>Récapitulatif</h3>
                <div class="ligne-recap">
                    <span>Total HT</span>
                    <span><?= number_format($commande['montant_total_ht'], 2, ',', ' ') ?> €</span>
                </div>
                <div class="ligne-recap">
                    <span>TVA (20%)</span> <span><?= number_format($commande['montant_total_ttc'] - $commande['montant_total_ht'], 2, ',', ' ') ?> €</span>
                </div>
                <div class="ligne-recap total-final">
                    <span>Total TTC</span>
                    <span><?= number_format($commande['montant_total_ttc'], 2, ',', ' ') ?> €</span>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
</body>
</html>
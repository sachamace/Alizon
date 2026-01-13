<?php
include 'config.php';
include 'session.php';
include 'sessionindex.php';

/* ---------------------------------------------------
   1. Vérification de la connexion client
--------------------------------------------------- */
$id_client_connecte = $_SESSION['id'];

/* ---------------------------------------------------
   2. Récupération de l'adresse du client
--------------------------------------------------- */
try {
    $stmt = $pdo->prepare("
        SELECT adresse, code_postal, ville, pays
        FROM public.adresse a
        WHERE id_client = :id_client
        ORDER BY id_adresse DESC 
        LIMIT 1
    ");
    $stmt->execute(['id_client' => $id_client_connecte]);
    $client = $stmt->fetch();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des infos client : " . $e->getMessage());
}

try {
    // ✅ REQUÊTE CORRIGÉE - Casting explicite pour PostgreSQL
    $stmt = $pdo->prepare("
        SELECT pp.quantite, 
               p.nom_produit, 
               p.prix_unitaire_ht,
               p.id_produit,
               t.taux AS taux_tva,
               -- Prix TTC sans remise (CAST en NUMERIC pour ROUND)
               ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2) AS prix_ttc_sans_remise,
               -- Informations sur la remise active
               r.id_remise,
               r.nom_remise,
               r.type_remise,
               r.valeur_remise,
               -- Prix TTC avec remise si applicable
               CASE 
                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'pourcentage' THEN
                       ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) * (1 - r.valeur_remise / 100) AS NUMERIC), 2)
                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'fixe' THEN
                       GREATEST(0, ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) - r.valeur_remise AS NUMERIC), 2))
                   ELSE
                       ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2)
               END AS prix_ttc,
               -- Prix HT avec remise pour la ligne de commande
               CASE 
                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'pourcentage' THEN
                       ROUND(CAST(p.prix_unitaire_ht * (1 - r.valeur_remise / 100) AS NUMERIC), 2)
                   WHEN r.id_remise IS NOT NULL AND r.type_remise = 'fixe' THEN
                       ROUND(CAST(p.prix_unitaire_ht * (GREATEST(0, ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) - r.valeur_remise AS NUMERIC), 2)) / NULLIF(ROUND(CAST(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100) AS NUMERIC), 2), 0)) AS NUMERIC), 2)
                   ELSE
                       p.prix_unitaire_ht
               END AS prix_unitaire_ht_final
        FROM panier_produit pp
        JOIN produit p ON pp.id_produit = p.id_produit
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
        WHERE pp.id_panier = :id_panier
    ");
    $stmt->execute([':id_panier' => $_SESSION['id_panier']]);
    $articles_panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ Calculer le total en utilisant les prix avec remise
    $total_ht = 0;
    $total_ttc = 0;
    $nb_articles = 0;
    $taxe = 0;

    foreach ($articles_panier as $article) {
        $total_ht += $article['prix_unitaire_ht_final'] * $article['quantite'];
        $total_ttc += $article['prix_ttc'] * $article['quantite'];
        $taxe += ($article['prix_ttc'] - $article['prix_unitaire_ht_final']) * $article['quantite'];
        $nb_articles += $article['quantite'];
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération du panier : " . $e->getMessage());
}

/* ---------------------------------------------------
   3. Variables par défaut
--------------------------------------------------- */
$erreurs = [];
$numero = "";
$securite = "";
$expiration = "";
$nom = "";

/* ---------------------------------------------------
   4. Traitement du formulaire
--------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du type de paiement
    $type_paiement = $_POST['paiement'] ?? null;

    if ($type_paiement === "carte") {
        // Récupération des données
        $numero = str_replace(' ', '', $_POST['carte'] ?? '');
        $securite = str_replace('-', '', $_POST['cvv'] ?? '');
        $expiration = $_POST['expiration'] ?? '';
        $nom = $_POST['nom_titulaire'] ?? '';

        // Vérification numéro de carte (16 chiffres)
        if (!preg_match('/^[0-9]{16}$/', $numero)) {
            $erreurs['carte'] = "Numéro de carte invalide (16 chiffres requis).";
        } elseif (!verifLuhn($numero)) {
            $erreurs['carte'] = "Numéro de carte invalide (algorithme de Luhn).";
        }

        // Vérification CVV (3 chiffres)
        if (!preg_match('/^[0-9]{3}$/', $securite)) {
            $erreurs['cvv'] = "CVV invalide (3 chiffres).";
        }

        // Vérification expiration
        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $expiration)) {
            $erreurs['expiration'] = "Format invalide (AAAA-MM).";
        } else {
            list($annee, $mois) = explode("-", $expiration);
            $timestampExpiration = mktime(23, 59, 59, $mois + 1, 0, $annee);
            
            if ($timestampExpiration < time()) {
                $erreurs['expiration'] = "Votre carte est expirée.";
            }
        }
    }

    // Si aucune erreur de validation de formulaire
    if (empty($erreurs)) {
        // ⚠️ VÉRIFICATION FINALE DU STOCK AVANT VALIDATION
        try {
            $stmt_verif_stock = $pdo->prepare("
                SELECT pp.id_produit, pp.quantite, p.nom_produit, p.stock_disponible
                FROM panier_produit pp
                JOIN produit p ON pp.id_produit = p.id_produit
                WHERE pp.id_panier = :id_panier
            ");
            $stmt_verif_stock->execute([':id_panier' => $_SESSION['id_panier']]);
            $articles_a_verifier = $stmt_verif_stock->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier chaque article
            foreach ($articles_a_verifier as $article) {
                if ($article['quantite'] > $article['stock_disponible']) {
                    $erreurs['stock'] = "Stock insuffisant pour " . htmlentities($article['nom_produit']) . 
                                       " (demandé: " . $article['quantite'] . ", disponible: " . $article['stock_disponible'] . ")";
                    break;
                }
            }
        } catch (PDOException $e) {
            $erreurs['stock'] = "Erreur lors de la vérification du stock.";
        }
        
        // ✅ SI TOUT EST OK : CRÉER LA COMMANDE ET TRAITER LE PAIEMENT
        if (empty($erreurs)) {
            try {
                // 🔥 DÉBUT DE LA TRANSACTION
                $pdo->beginTransaction();

                // 1️⃣ CRÉER LA COMMANDE
                $stmt_commande = $pdo->prepare("
                    INSERT INTO commande (id_client, date_commande, montant_total_ht, montant_total_ttc, statut)
                    VALUES (:id_client, NOW(), :montant_ht, :montant_ttc, 'validée')
                    RETURNING id_commande
                ");
                $stmt_commande->execute([
                    ':id_client' => $id_client_connecte,
                    ':montant_ht' => $total_ht,
                    ':montant_ttc' => $total_ttc
                ]);
                $id_commande = $stmt_commande->fetchColumn();

                // 2️⃣ INSÉRER LES LIGNES DE COMMANDE - ✅ Avec prix incluant les remises
                $stmt_ligne = $pdo->prepare("
                    INSERT INTO ligne_commande (id_commande, id_produit, quantite, prix_unitaire_ht, prix_unitaire_ttc)
                    VALUES (:id_commande, :id_produit, :quantite, :prix_ht, :prix_ttc)
                ");
                
                foreach ($articles_panier as $article) {
                    $stmt_ligne->execute([
                        ':id_commande' => $id_commande,
                        ':id_produit' => $article['id_produit'],
                        ':quantite' => $article['quantite'],
                        ':prix_ht' => $article['prix_unitaire_ht_final'], // ✅ Prix avec remise
                        ':prix_ttc' => $article['prix_ttc']                // ✅ Prix avec remise
                    ]);
                }

                // 3️⃣ DÉCRÉMENTER LE STOCK
                $stmt_update_stock = $pdo->prepare("
                    UPDATE produit 
                    SET stock_disponible = stock_disponible - :quantite 
                    WHERE id_produit = :id_produit 
                    AND stock_disponible >= :quantite
                ");
                
                foreach ($articles_panier as $article) {
                    $stmt_update_stock->execute([
                        ':quantite' => $article['quantite'],
                        ':id_produit' => $article['id_produit']
                    ]);
                    
                    if ($stmt_update_stock->rowCount() == 0) {
                        throw new Exception("Stock insuffisant pour le produit ID " . $article['id_produit']);
                    }
                }

                // 4️⃣ VIDER LE PANIER
                $stmt_vider = $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = :id_panier");
                $stmt_vider->execute([':id_panier' => $_SESSION['id_panier']]);

                // 🔥 VALIDER LA TRANSACTION
                $pdo->commit();

                // ✅ SAUVEGARDER L'ID DE COMMANDE EN SESSION
                $_SESSION['derniere_commande'] = $id_commande;

                // 🎉 REDIRECTION VERS LA PAGE DE CONFIRMATION
                header("Location: confirmation_achat.php");
                exit();

            } catch (Exception $e) {
                // ❌ ANNULER LA TRANSACTION EN CAS D'ERREUR
                $pdo->rollBack();
                $erreurs['general'] = "Erreur lors du traitement de la commande : " . $e->getMessage();
            }
        }
    }
}

function verifLuhn($numero) {
    $sum = 0;
    $shouldDouble = false;

    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $digit = (intval($numero[$i]));

        if ($shouldDouble) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }

        $sum += $digit;
        $shouldDouble = !$shouldDouble;
    }
    return $sum % 10 === 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page de paiement - Compte Client</title>
    <meta name="description" content="Page lors du paiement de ton panier côté client!">
    <meta name="keywords" content="MarketPlace, Shopping, Ventes, Breton, Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css?v=<?php echo time(); ?>" id="main-css">
</head>
<body class="body_paiement">
    <header class="disabled">
        <?php include 'header.php'?>
    </header>
    <div class="compte__header disabled"><a href="panier.php">← </a>Passer la commande</div>
    <main class="main_paiement">
        <!-- ADRESSE CLIENT -->
        <div class="bloc recap">
            <h2>Adresse de livraison</h2>
            <?php if ($client) { ?>
                <p>
                    <span>Adresse :</span>
                    <span><?= htmlentities($client['adresse']) ?></span>
                </p>
                <p>
                    <span>Ville :</span>
                    <span><?= htmlentities($client['code_postal']) ?> <?= htmlentities($client['ville']) ?></span>
                </p>
                <p>
                    <span>Pays :</span>
                    <span><?= htmlentities($client['pays']) ?></span>
                </p>
            <?php } else { ?>
                <p>Aucune adresse enregistrée.</p>
            <?php } ?>
        </div>

        <div class="bloc recap">
            <h2>Récapitulatif de la commande</h2>
            <?php if ($articles_panier) { ?>
                <p>
                    <span>Articles :</span>
                    <span><?= $nb_articles ?></span>
                </p>
                <div class="liste-produit">
                    <ul>
                        <?php foreach ($articles_panier as $article): ?>
                        <p>
                            <li>
                                <?= htmlentities($article['nom_produit']) ?> – x<?= $article['quantite'] ?>
                                <?php if ($article['id_remise']): ?>
                                    <span class="badge-mini-remise">
                                        <?php if ($article['type_remise'] === 'pourcentage'): ?>
                                            -<?= number_format($article['valeur_remise'], 0) ?>%
                                        <?php else: ?>
                                            -<?= number_format($article['valeur_remise'], 2, ',', ' ') ?>€
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <span>
                                <?php if ($article['id_remise']): ?>
                                    <s style="color: #999; font-size: 0.9em;"><?= number_format($article['prix_ttc_sans_remise'], 2, ',', ' ') ?>€</s>
                                <?php endif; ?>
                                <?= number_format($article['prix_ttc'], 2, ',', ' ') ?>€ 
                                (Total: <?= number_format($article['prix_ttc'] * $article['quantite'], 2, ',', ' ') ?>€)
                            </span>
                        </p>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <p>
                    <span>Total HT :</span>
                    <span><?= number_format($total_ht, 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <span>Taxe :</span>
                    <span><?= number_format($taxe, 2, ',', ' ') ?> €</span>
                </p>
                <p>
                    <span>Total TTC :</span>
                    <span><?= number_format($total_ttc, 2, ',', ' ') ?> €</span>
                </p>
            <?php } else { ?>
                <p>Votre panier est vide.</p>
            <?php } ?>
        </div>

        <!-- FORMULAIRE PAIEMENT -->
        <form method="POST" class="paiement">
            <div class="bloc">
                <h2>Méthode de paiement</h2>
                
                <!-- ERREUR GÉNÉRALE -->
                <?php if (isset($erreurs['general'])) { ?>
                    <div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #c62828;">
                        <strong>⚠️ Erreur :</strong><br>
                        <?= htmlentities($erreurs['general']) ?>
                    </div>
                <?php } ?>
                
                <!-- ERREUR DE STOCK -->
                <?php if (isset($erreurs['stock'])) { ?>
                    <div style="background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #c62828;">
                        <strong>⚠️ Erreur de stock :</strong><br>
                        <?= htmlentities($erreurs['stock']) ?>
                        <br><br>
                        <a href="panier.php" style="color: #c62828; text-decoration: underline;">Retourner au panier pour ajuster les quantités</a>
                    </div>
                <?php } ?>

                <div class="options">
                    <!-- OPTION CARTE -->
                    <label class="option">
                        <input type="radio" name="paiement" id="radio-carte" value="carte" <?= empty($articles_panier) ? 'disabled' : '' ?>>
                        <span style="font-size: 1rem;">Carte bancaire</span>
                    </label>

                    <?php if (empty($articles_panier)) { ?>
                        <p style="color: red;">Votre panier est vide. Impossible de payer.</p>
                    <?php } ?>

                    <!-- FORMULAIRE CARTE -->
                    <div class="formulaire hidden" id="form-carte">
                        <input type="text" name="carte" id="carte" placeholder="Numéro de carte (16 chiffres)" 
                            value="<?= htmlentities($numero) ?>">
                        <p class="required"><?= htmlentities($erreurs['carte'] ?? '') ?></p>
                        <input type="month" name="expiration" placeholder="AAAA-MM" 
                            value="<?= htmlentities($expiration) ?>">
                        <p class="required"><?= htmlentities($erreurs['expiration'] ?? '') ?></p>
                        <input type="text" name="cvv" placeholder="CVV" 
                            value="<?= htmlentities($securite) ?>">
                        <p class="required"><?= htmlentities($erreurs['cvv'] ?? '') ?></p>
                        <input type="text" name="nom_titulaire" placeholder="Nom du titulaire"
                            value="<?= htmlentities($nom) ?>">
                    </div>

                    <!-- OPTION PAYPAL -->
                    <label class="option">
                        <input type="radio" name="paiement" id="radio-paypal" value="paypal" disabled>
                        <span style="opacity: 0.5; font-size: 1rem;">PayPal</span>
                    </label>

                    <div class="formulaire hidden" id="form-paypal">
                        <p>PayPal est désactivé.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="payer-btn">Payer</button>
        </form>
    </main>
    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>
    <script src="../assets/js/paiement.js"></script>
</body>
</html>
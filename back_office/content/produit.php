<?php
if (isset($_GET['page']) && $_GET['page'] === 'produit') {
    if (isset($_GET['id']) && isset($_GET['type'])) {
        $id = $_GET['id'];
        $type = $_GET['type'];
        $id_vendeur_connecte = $_SESSION['vendeur_id'];

        $stmt = $pdo->prepare("
            SELECT p.*, 
                   ROUND(p.prix_unitaire_ht * (1 + t.taux / 100), 2) AS prix_ttc,
                   t.taux AS taux_tva,
                   t.nom_tva,
                   -- Calcul du prix au kilo/litre
                   CASE 
                       WHEN p.poids_unite IS NOT NULL AND p.poids_unite > 0 THEN
                           ROUND((p.prix_unitaire_ht * (1 + t.taux / 100)) / p.poids_unite, 2)
                       ELSE NULL
                   END AS prix_ttc_par_unite
            FROM produit p
            LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
            WHERE p.id_produit = :id
        ");
        $stmt->execute(['id' => $id]);
        $produit = $stmt->fetch();

        if (!$produit) {
            echo "<p>Produit introuvable.</p>";
            exit;
        }

        $stmt = $pdo->prepare("SELECT libelle FROM categorie WHERE libelle= :libelle");
        $stmt->execute(['libelle' => $produit['categorie']]);
        $categorie = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM media_produit WHERE id_produit = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM media_produit WHERE id_produit = ?");
        $stmtCheck->execute([$id]);
        $imageCount = $stmtCheck->fetchColumn();

        // Récupérer les remises actives pour ce produit - REQUÊTE CORRIGÉE
        $stmtRemises = $pdo->prepare("
            SELECT r.id_remise, r.nom_remise, r.type_remise, r.valeur_remise, 
                   r.date_debut, r.date_fin, r.categorie
            FROM remise r
            WHERE r.id_vendeur = :id_vendeur
              AND r.est_actif = true
              AND CURRENT_DATE BETWEEN r.date_debut AND r.date_fin
              AND (
                  -- Cas 1: Remise sur CE produit spécifique (via id_produit)
                  r.id_produit = :id_produit
                  -- Cas 2: Remise sur CE produit spécifique (via table remise_produit)
                  OR EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise AND rp.id_produit = :id_produit2)
                  -- Cas 3: Remise sur TOUS les produits (pas de produit spécifique, pas de catégorie)
                  OR (r.id_produit IS NULL AND r.categorie IS NULL AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
                  -- Cas 4: Remise sur CATÉGORIE spécifique (pas de produit spécifique, catégorie correspond)
                  OR (r.id_produit IS NULL AND r.categorie = :categorie_produit AND NOT EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise))
              )
            ORDER BY 
                -- Priorité: 1. Produit spécifique, 2. Catégorie, 3. Tous produits
                CASE 
                    WHEN r.id_produit IS NOT NULL THEN 1
                    WHEN EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise) THEN 1
                    WHEN r.categorie IS NOT NULL THEN 2
                    ELSE 3
                END
            LIMIT 1
        ");
        $stmtRemises->execute([
            'id_vendeur' => $id_vendeur_connecte,
            'id_produit' => $id,
            'id_produit2' => $id,
            'categorie_produit' => $produit['categorie']
        ]);
        $remise_active = $stmtRemises->fetch();

        // Calculer le prix avec remise si applicable
        $prix_final = $produit['prix_ttc'];
        $prix_ht_final = $produit['prix_unitaire_ht'];
        $prix_par_unite_final = $produit['prix_ttc_par_unite']; // Prix au kilo/litre
        
        if ($remise_active) {
            if ($remise_active['type_remise'] === 'pourcentage') {
                $prix_final = $produit['prix_ttc'] * (1 - $remise_active['valeur_remise'] / 100);
                $prix_ht_final = $produit['prix_unitaire_ht'] * (1 - $remise_active['valeur_remise'] / 100);
                // Prix au kilo avec remise
                if ($prix_par_unite_final) {
                    $prix_par_unite_final = $prix_par_unite_final * (1 - $remise_active['valeur_remise'] / 100);
                }
            } else {
                $prix_final = $produit['prix_ttc'] - $remise_active['valeur_remise'];
                // Calculer le HT proportionnellement
                $ratio = $prix_final / $produit['prix_ttc'];
                $prix_ht_final = $produit['prix_unitaire_ht'] * $ratio;
                // Prix au kilo avec remise
                if ($prix_par_unite_final) {
                    $prix_par_unite_final = $prix_par_unite_final * $ratio;
                }
            }
            // S'assurer que les prix ne soient pas négatifs
            if ($prix_final < 0) $prix_final = 0;
            if ($prix_ht_final < 0) $prix_ht_final = 0;
            if ($prix_par_unite_final < 0) $prix_par_unite_final = 0;
        }

        if ($_GET['page'] == "produit" && $_GET['type'] == "consulter") {
            include 'produit_consulter.php';
        } else if ($_GET['page'] == "produit" && $_GET['type'] == 'modifier') {
            include 'produit_modifier.php';
        }
    } else if (isset($_GET['type']) && $_GET['type'] === 'creer') {
        $stmtCat = $pdo->query("SELECT libelle FROM categorie");
        $categorie = $stmtCat->fetchAll();
        include 'produit_creer.php';

    }
} ?>
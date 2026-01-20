<?php
if (isset($_GET['page']) && $_GET['page'] === 'promotion') {
    $id_vendeur_connecte = $_SESSION['vendeur_id'];
    
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        
        if ($type === 'creer') {
            // Récupérer les produits du vendeur pour la création
            $stmtProduits = $pdo->prepare("
                SELECT 
                    p.id_produit, 
                    p.nom_produit, 
                    p.categorie,
                    p.est_actif, 
                    p.stock_disponible,
                    ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) as prix_ttc_base
                FROM produit p 
                LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                WHERE p.id_vendeur = ? 
                ORDER BY p.nom_produit
            ");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            include __DIR__ . '/promotion_creer.php';
            
        } elseif ($type === 'consulter' && isset($_GET['id'])) {
            $id_promotion = $_GET['id'];
            
            // Récupérer la promotion avec ses produits
            $stmt = $pdo->prepare("
                SELECT p.*,
                       cv.raison_sociale
                FROM promotion p
                LEFT JOIN compte_vendeur cv ON p.id_vendeur = cv.id_vendeur
                WHERE p.id_promotion = ? AND p.id_vendeur = ?
            ");
            $stmt->execute([$id_promotion, $id_vendeur_connecte]);
            $promotion = $stmt->fetch();
            
            if (!$promotion) {
                echo "<p>Promotion introuvable.</p>";
                exit;
            }
            
            // Récupérer les produits de la promotion
            $stmtProduits = $pdo->prepare("
                SELECT prod.*, pp.ordre_produit,
                       ROUND(prod.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) as prix_ttc,
                       (SELECT chemin_image FROM media_produit WHERE id_produit = prod.id_produit LIMIT 1) as image_path
                FROM promotion_produit pp
                JOIN produit prod ON pp.id_produit = prod.id_produit
                LEFT JOIN taux_tva t ON prod.id_taux_tva = t.id_taux_tva
                WHERE pp.id_promotion = ?
                ORDER BY pp.ordre_produit, prod.nom_produit
            ");
            $stmtProduits->execute([$id_promotion]);
            $produits_promotion = $stmtProduits->fetchAll();
            
            include __DIR__ . '/promotion_consulter.php';
            
        } elseif ($type === 'modifier' && isset($_GET['id'])) {
            $id_promotion = $_GET['id'];
            
            // Récupérer la promotion
            $stmt = $pdo->prepare("
                SELECT p.* 
                FROM promotion p
                WHERE p.id_promotion = ? AND p.id_vendeur = ?
            ");
            $stmt->execute([$id_promotion, $id_vendeur_connecte]);
            $promotion = $stmt->fetch();
            
            if (!$promotion) {
                echo "<p>Promotion introuvable.</p>";
                exit;
            }
            
            // Récupérer les produits du vendeur
            $stmtProduits = $pdo->prepare("
                SELECT 
                    p.id_produit, 
                    p.nom_produit, 
                    p.categorie,
                    p.est_actif, 
                    p.stock_disponible,
                    ROUND(p.prix_unitaire_ht * (1 + COALESCE(t.taux, 0) / 100), 2) as prix_ttc_base
                FROM produit p 
                LEFT JOIN taux_tva t ON p.id_taux_tva = t.id_taux_tva
                WHERE p.id_vendeur = ? 
                ORDER BY p.nom_produit
            ");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            // Récupérer les produits déjà sélectionnés
            $stmtSelected = $pdo->prepare("
                SELECT id_produit 
                FROM promotion_produit 
                WHERE id_promotion = ?
            ");
            $stmtSelected->execute([$id_promotion]);
            $produits_selectionnes = $stmtSelected->fetchAll(PDO::FETCH_COLUMN);
            
            include __DIR__ . '/promotion_modifier.php';
            
        } elseif ($type === 'liste') {
            // Liste de toutes les promotions du vendeur
            include __DIR__ . '/promotion_liste.php';
            
        } elseif ($type === 'supprimer' && isset($_GET['id'])) {
            // Suppression d'une promotion
            $id_promotion = intval($_GET['id']);
            
            try {
                // Vérifier que la promotion appartient bien au vendeur
                $stmt = $pdo->prepare("SELECT id_promotion FROM promotion WHERE id_promotion = ? AND id_vendeur = ?");
                $stmt->execute([$id_promotion, $id_vendeur_connecte]);
                
                if ($stmt->fetch()) {
                    // Supprimer la promotion (CASCADE supprimera automatiquement les lignes dans promotion_produit)
                    $stmtDelete = $pdo->prepare("DELETE FROM promotion WHERE id_promotion = ? AND id_vendeur = ?");
                    $stmtDelete->execute([$id_promotion, $id_vendeur_connecte]);
                    
                    echo "<script>
                        alert('Promotion supprimée avec succès');
                        window.location.href = 'index.php?page=promotion&type=liste';
                    </script>";
                    exit();
                } else {
                    echo "<script>
                        alert('Promotion introuvable ou vous n\\'avez pas les droits');
                        window.location.href = 'index.php?page=promotion&type=liste';
                    </script>";
                    exit();
                }
            } catch (PDOException $e) {
                echo "<script>
                    alert('Erreur lors de la suppression : " . addslashes($e->getMessage()) . "');
                    window.location.href = 'index.php?page=promotion&type=liste';
                </script>";
                exit();
            }
        }
    } else {
        // Page d'accueil : hub des promotions
        include __DIR__ . '/promo_hub.php';
    }
}
?>
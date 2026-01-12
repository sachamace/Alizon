<?php
if (isset($_GET['page']) && $_GET['page'] === 'remise') {
    $id_vendeur_connecte = $_SESSION['vendeur_id'];
    
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        
        if ($type === 'creer') {
            // Récupérer les produits du vendeur pour la création
            $stmtProduits = $pdo->prepare("SELECT id_produit, nom_produit FROM produit WHERE id_vendeur = ? ORDER BY nom_produit");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            include __DIR__ . '/remise_creer.php';
            
        } elseif ($type === 'consulter' && isset($_GET['id'])) {
            $id_remise = $_GET['id'];
            
            // Récupérer la remise
            $stmt = $pdo->prepare("
                SELECT r.*, p.nom_produit 
                FROM remise r
                LEFT JOIN produit p ON r.id_produit = p.id_produit
                WHERE r.id_remise = ? AND r.id_vendeur = ?
            ");
            $stmt->execute([$id_remise, $id_vendeur_connecte]);
            $remise = $stmt->fetch();
            
            if (!$remise) {
                echo "<p>Remise introuvable.</p>";
                exit;
            }
            
            include __DIR__ . '/remise_consulter.php';
            
        } elseif ($type === 'modifier' && isset($_GET['id'])) {
            $id_remise = $_GET['id'];
            
            // Récupérer la remise
            $stmt = $pdo->prepare("
                SELECT r.* 
                FROM remise r
                WHERE r.id_remise = ? AND r.id_vendeur = ?
            ");
            $stmt->execute([$id_remise, $id_vendeur_connecte]);
            $remise = $stmt->fetch();
            
            if (!$remise) {
                echo "<p>Remise introuvable.</p>";
                exit;
            }
            
            // Récupérer les produits du vendeur
            $stmtProduits = $pdo->prepare("SELECT id_produit, nom_produit FROM produit WHERE id_vendeur = ? ORDER BY nom_produit");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            include __DIR__ . '/remise_modifier.php';
            
        } elseif ($type === 'liste') {
            // Récupérer toutes les remises du vendeur
            $stmt = $pdo->prepare("
                SELECT r.*, p.nom_produit,
                       CASE 
                           WHEN r.date_fin < CURRENT_DATE THEN 'Expirée'
                           WHEN r.date_debut > CURRENT_DATE THEN 'À venir'
                           WHEN r.est_actif = false THEN 'Inactive'
                           ELSE 'Active'
                       END as statut_calcule
                FROM remise r
                LEFT JOIN produit p ON r.id_produit = p.id_produit
                WHERE r.id_vendeur = ?
                ORDER BY r.date_debut DESC, r.id_remise DESC
            ");
            $stmt->execute([$id_vendeur_connecte]);
            $remises = $stmt->fetchAll();
            
            include __DIR__ . '/remise_liste.php';
        }
    } else {
        // Page d'accueil : hub des promotions/remises
        include __DIR__ . '/promo_hub.php';
    }
}

// Page produits en promotion (accessible via ?page=produits_promo)
if (isset($_GET['page']) && $_GET['page'] === 'produits_promo') {
    include __DIR__ . '/produits_promo.php';
}
?>
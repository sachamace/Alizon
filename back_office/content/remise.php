
<?php
if (isset($_GET['page']) && $_GET['page'] === 'remise') {
    $id_vendeur_connecte = $_SESSION['vendeur_id'];
    
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        
        if ($type === 'creer') {
            // Récupérer les produits du vendeur pour la création
            $stmtProduits = $pdo->prepare("
                SELECT p.id_produit, p.nom_produit, p.categorie 
                FROM produit p 
                WHERE p.id_vendeur = ? 
                ORDER BY p.categorie, p.nom_produit
            ");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            include __DIR__ . '/remise_creer.php';
            
        } elseif ($type === 'consulter' && isset($_GET['id'])) {
            $id_remise = $_GET['id'];
            
            // Récupérer la remise avec informations complètes
            $stmt = $pdo->prepare("
                SELECT r.*,
                       p.nom_produit,
                       -- Information sur le type d'application
                       CASE 
                           WHEN r.id_produit IS NOT NULL THEN 'Produit spécifique'
                           WHEN EXISTS (SELECT 1 FROM remise_produit rp WHERE rp.id_remise = r.id_remise) THEN 'Produits multiples'
                           WHEN r.categorie IS NOT NULL THEN 'Catégorie'
                           ELSE 'Tous les produits'
                       END as type_application
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
            $stmtProduits = $pdo->prepare("
                SELECT p.id_produit, p.nom_produit, p.categorie 
                FROM produit p 
                WHERE p.id_vendeur = ? 
                ORDER BY p.categorie, p.nom_produit
            ");
            $stmtProduits->execute([$id_vendeur_connecte]);
            $produits = $stmtProduits->fetchAll();
            
            include __DIR__ . '/remise_modifier.php';
            
        } elseif ($type === 'liste') {
            // Inclure le fichier remise_liste.php avec la nouvelle requête
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

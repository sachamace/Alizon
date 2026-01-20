<?php
include 'config.php';
include 'session.php';
include 'sessionindex.php';

// on vérifie qu'on a un ID
if (!isset($_GET['id'])) {
    echo "<script>alert('Commande non trouvée'); window.location.href='commande.php';</script>";
    exit();
}

$id_commande = (int)$_GET['id'];
$id_client = $_SESSION['id'];
$id_panier = $_SESSION['id_panier'];

try {
    // Vérifier que la commande appartient au client
    $verif = $pdo->prepare("SELECT id_commande FROM commande WHERE id_commande = ? AND id_client = ?");
    $verif->execute([$id_commande, $id_client]);
    
    if (!$verif->fetch()) {
        echo "<script>alert('Commande non trouvée'); window.location.href='commande.php';</script>";
        exit();
    }
    
    // on vide le panier pour eviter de mélanger avec les autres produits
    $pdo->prepare("DELETE FROM panier_produit WHERE id_panier = ?")->execute([$id_panier]);
    
    // on récupérer les produits de la commande qu'on veut recommander
    $produits = $pdo->prepare("
        SELECT lc.id_produit, lc.quantite, p.stock_disponible
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        WHERE lc.id_commande = ?
    ");
    $produits->execute([$id_commande]);
    
    // on ajoute ces produits au panier
    $ajout = $pdo->prepare("INSERT INTO panier_produit (id_panier, id_produit, quantite) VALUES (?, ?, ?)");
    
    while ($prod = $produits->fetch()) {
        $qte = min($prod['quantite'], $prod['stock_disponible']);
        if ($qte > 0) {
            $ajout->execute([$id_panier, $prod['id_produit'], $qte]);
        }
    }
    
    // 4. on redirige vers panier pour ensuite payer
    echo "<script>window.location.href='panier.php';</script>";
    exit();
    
} catch (Exception $e) {
    echo "<script>alert('Erreur: " . addslashes($e->getMessage()) . "'); window.location.href='commande.php';</script>";
    exit();
}
?>
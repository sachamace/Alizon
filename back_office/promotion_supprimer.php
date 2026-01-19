<?php
session_start();
include 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['est_connecte']) || $_SESSION['est_connecte'] !== true) {
    echo "<script>
        window.location.href = 'connecter.php';
    </script>";
    exit();
}

$id_vendeur_connecte = $_SESSION['vendeur_id'];

if (isset($_GET['id'])) {
    $id_promotion = intval($_GET['id']);
    
    try {
        // Vérifier que la promotion appartient bien au vendeur
        $stmt = $pdo->prepare("SELECT id_promotion FROM promotion WHERE id_promotion = ? AND id_vendeur = ?");
        $stmt->execute([$id_promotion, $id_vendeur_connecte]);
        
        if ($stmt->fetch()) {
            // Supprimer la promotion (les produits liés seront supprimés automatiquement grâce à ON DELETE CASCADE)
            $stmtDelete = $pdo->prepare("DELETE FROM promotion WHERE id_promotion = ? AND id_vendeur = ?");
            $stmtDelete->execute([$id_promotion, $id_vendeur_connecte]);
            
            echo "<script>
                alert('Promotion supprimée avec succès');
                window.location.href = 'index.php?page=promotion&type=liste';
            </script>";
        } else {
            echo "<script>
                alert('Promotion introuvable ou vous n\\'avez pas les droits pour la supprimer');
                window.location.href = 'index.php?page=promotion&type=liste';
            </script>";
        }
    } catch (PDOException $e) {
        echo "<script>
            alert('Erreur lors de la suppression : " . addslashes($e->getMessage()) . "');
            window.location.href = 'index.php?page=promotion&type=liste';
        </script>";
    }
} else {
    echo "<script>
        window.location.href = 'index.php?page=promotion&type=liste';
    </script>";
}
exit();
?>
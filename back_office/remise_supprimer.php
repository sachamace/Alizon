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
    $id_remise = intval($_GET['id']);
    
    try {
        // Vérifier que la remise appartient bien au vendeur
        $stmt = $pdo->prepare("SELECT id_remise FROM remise WHERE id_remise = ? AND id_vendeur = ?");
        $stmt->execute([$id_remise, $id_vendeur_connecte]);
        
        if ($stmt->fetch()) {
            // Supprimer la remise
            $stmtDelete = $pdo->prepare("DELETE FROM remise WHERE id_remise = ? AND id_vendeur = ?");
            $stmtDelete->execute([$id_remise, $id_vendeur_connecte]);
            
            echo "<script>
                alert('Remise supprimée avec succès');
                window.location.href = 'index.php?page=remise&type=liste';
            </script>";
        } else {
            echo "<script>
                alert('Remise introuvable ou vous n\\'avez pas les droits pour la supprimer');
                window.location.href = 'index.php?page=remise&type=liste';
            </script>";
        }
    } catch (PDOException $e) {
        echo "<script>
            alert('Erreur lors de la suppression : " . addslashes($e->getMessage()) . "');
            window.location.href = 'index.php?page=remise&type=liste';
        </script>";
    }
} else {
    echo "<script>
        window.location.href = 'index.php?page=remise&type=liste';
    </script>";
}
exit();
?>
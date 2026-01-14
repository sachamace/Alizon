<?php
include 'config.php';
// fichier appeler par le JS autocompletion
if (isset($_POST['search']) && !empty($_POST['search'])) {
    
    $search = htmlspecialchars($_POST['search']);

    $sql = "SELECT id_produit, nom_produit 
            FROM produit 
            WHERE est_actif = true 
            AND LOWER(nom_produit) LIKE LOWER(:query)
            LIMIT 5";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => '%' . $search . '%']);
    
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultats);
}
?>
<?php
if (isset($_GET['page']) && $_GET['page'] === 'profil') {
    if (isset($_GET["type"])) {
        $type = $_GET['type'];
        $id_vendeur_connecte = $_SESSION['vendeur_id'];

        $stmt = $pdo->prepare("SELECT * FROM compte_vendeur WHERE id_vendeur= :id");
        $stmt->execute(['id' => $id_vendeur_connecte]);
        $profil = $stmt->fetch();

        if ($type == "consulter") {
            include 'profil_consulter.php';
        } else if ($type == 'modifier') {
            include 'profil_modifier.php';
        }
    }
}
?>
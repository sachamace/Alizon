<?php
if (isset($_GET['page']) && $_GET['page'] === 'profil') {
    if (isset($_GET["type"])) {
        $type = $_GET['type'];
        $id_vendeur_connecte = $_SESSION['vendeur_id'];

        $stmt = $pdo->prepare("SELECT * FROM compte_vendeur WHERE id_vendeur= :id");
        $stmt->execute(['id' => $id_vendeur_connecte]);
        $profil = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT mdp FROM identifiants WHERE id_num= :id");
        $stmt->execute(['id' => $profil['id_num']]);
        $profil_mdp = $stmt->fetchColumn();

        $stmt_adr = $pdo->prepare("SELECT adresse, latitude, longitude FROM adresse_vendeur WHERE id_vendeur = :id");
        $stmt_adr->execute(['id' => $id_vendeur_connecte]);
        $profil_adresse = $stmt_adr->fetch() ?: [];

        if ($type == "consulter") {
            include 'profil_consulter.php';
        } else if ($type == 'modifier') {
            include 'profil_modifier.php';
        }
    }
}
?>
<?php
$id_vendeur_connecte = $_SESSION['vendeur_id'];

try {
    $stmt = $pdo->prepare("
        SELECT cv.raison_sociale, cv.statut_juridique, i.login 
        FROM public.compte_vendeur cv 
        JOIN public.identifiants i ON cv.id_num = i.id_num 
        WHERE cv.id_vendeur = ?
    ");
    $stmt->execute([$id_vendeur_connecte]);
    $vendeur = $stmt->fetch();
    
    
} catch (PDOException $e) {
    die("Erreur lors de la récupération des infos vendeur : " . $e->getMessage());
}
?>

<header class="topbar">
    <a href="index.php?page=profil&type=consulter" class="profil-link">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-person-fill"
                viewBox="0 0 16 16">
                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6" />
            </svg>
            <span><?= htmlentities($vendeur['raison_sociale']) ?> <?= htmlentities($vendeur['statut_juridique']) ?></span>
        </div>
    </a>

    <a href="deconnecter.php" class="btn-deconnexion">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
            <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
        </svg>
        Déconnexion
    </a>
</header>
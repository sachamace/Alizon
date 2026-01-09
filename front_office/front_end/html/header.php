<?php
    include 'front_office/front_end/html/config.php';
    include 'front_office/front_end/html/sessionindex.php';
    $stmt = $pdo->query("SELECT version();");
    if (isset($_POST['texte-recherche']) && !empty($_POST['texte-recherche'])) {
        $pageActuelle = basename($_SERVER['SCRIPT_NAME']);
        $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : null;
        

        $recherche = htmlspecialchars(string: $_POST['texte-recherche']);
        
        
        /*$sql = "SELECT * FROM produit WHERE nom_produit LIKE :query OR description_produit LIKE :query";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['query' => '%' . $recherche . '%']);
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);*/
        if ($pageActuelle === 'index.php' && $categorie !== null) {
            header('Location: ' . $_SERVER['REQUEST_URI'] . '&search=' . $recherche);
            exit();
        }
        else{
            header('Location: /index.php?search=' . urlencode($recherche));
            exit();
        }
    }
    

?>
<nav>
    <nav>
        <a href="/index.php"><img src="/front_office/front_end/assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
        <a class="notif" href="notification.php"><svg width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><<path d="M224 0c-13.3 0-24 10.7-24 24l0 9.7C118.6 45.3 56 115.4 56 200l0 14.5c0 37.7-10 74.7-29 107.3L5.1 359.2C1.8 365 0 371.5 0 378.2 0 399.1 16.9 416 37.8 416l372.4 0c20.9 0 37.8-16.9 37.8-37.8 0-6.7-1.8-13.3-5.1-19L421 321.7c-19-32.6-29-69.6-29-107.3l0-14.5c0-84.6-62.6-154.7-144-166.3l0-9.7c0-13.3-10.7-24-24-24zM392.4 368l-336.9 0 12.9-22.1C91.7 306 104 260.6 104 214.5l0-14.5c0-66.3 53.7-120 120-120s120 53.7 120 120l0 14.5c0 46.2 12.3 91.5 35.5 131.4L392.4 368zM156.1 464c9.9 28 36.6 48 67.9 48s58-20 67.9-48l-135.8 0z"/></svg></a>
        <form action="" method="POST" role="search" aria-label="Site search">
            <label for="site-search"></label>
            <input type="search" id="site-search" name="texte-recherche" placeholder="Recherche un produit, une marque..." />
            <button type="submit">Search</button>
        </form>
        <a href="/front_office/front_end/html/panier.php" data-panier><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M24-16C10.7-16 0-5.3 0 8S10.7 32 24 32l45.3 0c3.9 0 7.2 2.8 7.9 6.6l52.1 286.3c6.2 34.2 36 59.1 70.8 59.1L456 384c13.3 0 24-10.7 24-24s-10.7-24-24-24l-255.9 0c-11.6 0-21.5-8.3-23.6-19.7l-5.1-28.3 303.6 0c30.8 0 57.2-21.9 62.9-52.2L568.9 69.9C572.6 50.2 557.5 32 537.4 32l-412.7 0-.4-2c-4.8-26.6-28-46-55.1-46L24-16zM208 512a48 48 0 1 0 0-96 48 48 0 1 0 0 96zm224 0a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>Panier</a>
    </nav>
    <nav>
        <div>
        <?php
        // On récupère tout le contenu de la table 
        $categorie = $pdo->query('SELECT * FROM categorie');
        // On affiche chaque entrée une à une
        while ($cat = $categorie->fetch()){ 
            $libelle = urlencode($cat['libelle']); 
            ?>
            <a href="/index.php?categorie=<?php echo $libelle; ?>">
                <?php echo $cat['libelle']; ?>
            </a>
        <?php } ?>
        </div>
        <button id="openFilter" class="filter-btn" aria-label="Filtres">
            <svg class="icone" width="30" height="30" viewBox="0 0 640 640">
                <path d="M96 128C83.1 128 71.4 135.8 66.4 147.8C61.4 159.8 64.2 173.5 73.4 182.6L256 365.3L256 480C256 488.5 259.4 496.6 265.4 502.6L329.4 566.6C338.6 575.8 352.3 578.5 364.3 573.5C376.3 568.5 384 556.9 384 544L384 365.3L566.6 182.7C575.8 173.5 578.5 159.8 573.5 147.8C568.5 135.8 556.9 128 544 128L96 128z"/>
            </svg>
        </button> 
        <?php if($isLogged):?><a href="/front_office/front_end/html/compte.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg>Compte</a>
        <?php else: ?><a href="/front_office/front_end/html/seconnecter.php">S'identifier</a>
        <?php endif; ?>
    </nav>
</nav>
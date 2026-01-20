<?php
    include __DIR__ . '/config.php';
    include __DIR__ . '/sessionindex.php';
    $parametres = $_GET;// On récupère d'abord TOUS les filtres qui sont déjà dans l'URL ($_GET)
    

?>
<nav>
    <nav>
        <a href="/index.php"><img src="/front_office/front_end/assets/images/Logo_TABLETTE.png" height="61" width="110"></a>
        <form action="" method="GET" role="search" aria-label="Site search" style="position: relative;">
            <input type="search" id="site-search" name="search" autocomplete="off" 
            placeholder="Recherche un produit, une marque..." 
            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />

            <?php 
                foreach($_GET as $key => $value){
                    if($key === 'search') continue;

                    if(is_array($value)){
                        foreach($value as $val){
                            echo '<input type="hidden" name="'.htmlspecialchars($key).'[]" value="'.htmlspecialchars($val).'">';
                        }
                    }
                    else {
                        echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
                    }
                }
            ?>
            
            <button type="submit">Search</button>

            <div id="resultats-recherche"></div>
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
            $libelle = $cat['libelle'];
            $parametres['categorie'] = htmlspecialchars($libelle);
            $queryString = http_build_query($parametres);
            ?>
            <a href="/index.php?<?php echo $queryString; ?>">
                <?php echo $cat['libelle']; ?>
            </a>
        <?php } ?>
        <button id="openFilter" class="filter-btn" aria-label="Filtres"><img src="/front_office/front_end/assets/images/filtre.png" alt="filtre" width="30" height="30"></button>
        </div>
        <?php if($isLogged):?><a href="/front_office/front_end/html/compte.php"><svg class="icone" width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M144 128a80 80 0 1 1 160 0 80 80 0 1 1 -160 0zm208 0a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM48 480c0-70.7 57.3-128 128-128l96 0c70.7 0 128 57.3 128 128l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c0-97.2-78.8-176-176-176l-96 0C78.8 304 0 382.8 0 480l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8z"/></svg>Compte</a>
        <?php else: ?><a href="/front_office/front_end/html/seconnecter.php">S'identifier</a>
        <?php endif; ?>
    </nav>
</nav>

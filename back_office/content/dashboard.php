<section class="content">
    <?php
    $stmt = $pdo->query("SELECT * FROM produit");
    foreach ($stmt as $p) { ?>
        <a href="?page=produit&id=<?php echo htmlentities($p['id_produit'])?>&type=consulter">
        <article>
            <img src="front_end/assets/images/template.jpg" alt="" width="350" height="225">
            <h2 class="titre"><?php echo htmlentities($p['nom_produit'])?></h2>
            <p class="description"><?php echo htmlentities($p['description_produit'])?></p>
            <p class="prix"><?php echo htmlentities($p['prix_ttc'])?>€</p>
        </article>
    </a>
    <?php } ?>
    
    <a href="?page=produit&type=creer">
        
    </a>

</section>
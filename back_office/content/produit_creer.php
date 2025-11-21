<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $nom = $_POST['nom_produit'];
    $description = $_POST['description_produit'];
    $categorie = $_POST['categorie'];
    $prix_ht = str_replace(',', '.', $_POST['prix_unitaire_ht_produit']);
    $tva = str_replace(',', '.', $_POST['taux_tva_produit']);
    $stock = $_POST['stock_disponible_produit'];
    $actif = $_POST['visibilite'];

    if (empty(trim($nom))) {
        $errors['nom_produit'] = "Veuillez saisir un nom de produit.";
    }

    if (empty(trim($description))) {
        $errors['description_produit'] = "Veuillez saisir une description.";
    }

    if (!preg_match('/^\d+([.,]\d{1,2})?$/', $_POST['prix_unitaire_ht_produit'])) {
        $errors['prix_ht'] = "Le prix HT doit contenir uniquement des chiffres et une virgule (ex : 10,50).";
    }

    if (!preg_match('/^(100([.,]0{1,2})?|[0-9]{1,2}([.,]\d{1,2})?)$/', $_POST['taux_tva_produit'])) {
        $errors['tva'] = "La TVA doit être un nombre entre 0 et 100 (ex : 20 ou 20,50).";
    }

    if (!preg_match('/^\d+$/', $stock)) {
        $errors['stock'] = "Le stock doit être un nombre entier positif.";
    }

    if (empty($categorie)) {
        $errors['categorie'] = "Veuillez sélectionner une catégorie.";
    }

    $newImagesCount = 0;
    if (isset($_FILES['nouvelle_image']) && !empty($_FILES['nouvelle_image']['name'][0])) {
        $newImagesCount = count(array_filter($_FILES['nouvelle_image']['name']));
    }

    if ($newImagesCount < 1) {
        $errors['images'] = "Vous devez avoir au moins une image pour ce produit.";
    }

    if ($newImagesCount > 3) {
        $errors['images'] = "Vous ne pouvez pas avoir plus de 3 images par produit.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO produit (
            nom_produit, 
            description_produit, 
            prix_unitaire_ht, 
            taux_tva, 
            stock_disponible,
            est_actif,
            categorie,
            id_vendeur
        ) VALUES (
            :nom,
            :description_prod,
            :prix_ht,
            :tva,
            :stock,
            :actif,
            :categorie,
            :id_vendeur
        )");

        $stmt->execute([
            'nom' => $nom,
            'description_prod' => $description,
            'categorie' => $categorie,
            'prix_ht' => $prix_ht,
            'tva' => $tva,
            'stock' => $stock,
            'actif' => $actif,
            'id_vendeur' => $id_vendeur_connecte
        ]);

        $id_produit = $pdo->lastInsertId();

        if (!empty($_FILES['nouvelle_image']['name'])) {

            $uploadDir = "front_end/assets/images_produits/";
            $uploadName = "/back_office/front_end/assets/images_produits/";

            foreach ($_FILES['nouvelle_image']['name'] as $index => $name) {

                if ($_FILES['nouvelle_image']['error'][$index] === UPLOAD_ERR_OK) {

                    $tmp = $_FILES['nouvelle_image']['tmp_name'][$index];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);

                    $fileName = uniqid("prod_{$id}_") . "." . $extension;
                    $filePath = $uploadDir . $fileName;
                    $fileName = $uploadName . $fileName;

                    move_uploaded_file($tmp, $filePath);

                    $stmt = $pdo->prepare("
                INSERT INTO media_produit (id_produit, chemin_image)
                VALUES (?, ?)
            ");
                    $stmt->execute([$id_produit, $fileName]);
                }
            }
        }



        echo "<script>
            window.location.href = 'index.php?page=dashboard';
        </script>";
        exit;
    }
}
?>

<section class="produit-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <h2>Créer produit</h2>

        <div class="produit-content">
            <!-- Partie gauche : informations générales -->
            <div class="produit-infos">
                <h3>Informations générales</h3>
                <article>
                    <h3>Nom du produit</h3>
                    <input type="text" id="nom_produit" name="nom_produit" value="<?php echo $nom ?>">
                    <?php if (isset($errors['nom_produit'])) { ?>
                        <p class="error"><?php echo $errors['nom_produit']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Description</h3>
                    <input type="text" id="description_produit" name="description_produit"
                        value="<?php echo $description ?>">
                    <?php if (isset($errors['description_produit'])) { ?>
                        <p class="error"><?php echo $errors['description_produit']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Catégorie</h3>

                    <select name="categorie" id="select-categorie" value="<?php echo $categorie ?>">
                        <option value="">-- Choisir une catégorie --</option>
                        <?php
                        $stmtAllCat = $pdo->query("SELECT libelle FROM categorie");

                        foreach ($stmtAllCat as $cat) { ?>
                            <option value="<?php echo htmlentities($cat['libelle']) ?>">
                                <?php echo htmlentities($cat['libelle']) ?>
                            </option>
                        <?php } ?>
                    </select>

                    <?php if (isset($errors['categorie'])) { ?>
                        <p class="error"><?php echo $errors['categorie']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Médias (<span id="imageCount">0</span>/3)</h3>

                    <div id="mediaGallery" class="media-gallery"></div>

                    <div class="media-upload">
                        <label for="nouvelle_image">Ajouter images :</label>
                        <input type="file" id="nouvelle_image" name="nouvelle_image" accept="image/*">
                    </div>

                    <?php if (isset($errors['images'])) { ?>
                        <p class="error"><?php echo $errors['images']; ?></p>
                    <?php } ?>
                </article>
            </div>

            <!-- Partie droite : prix, stock, TVA -->
            <div class="produit-prix-stock">
                <article>
                    <h3>Prix</h3>

                    <h4>Prix TTC</h4>
                    <input type="text" id="prix_ttc_produit" name="prix_ttc_produit" value="" readonly>

                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value="<?php echo $prix_ht ?>" pattern="^\d+([.,]\d{1,2})?$"
                        title="Uniquement chiffres et virgule (ex : 10,50)">
                    <?php if (isset($errors['prix_ht'])) { ?>
                        <p class="error"><?php echo $errors['prix_ht']; ?></p>
                    <?php } ?>


                    <h4>TVA (%)</h4>
                    <input type="text" id="taux_tva_produit" name="taux_tva_produit" value="<?php echo $tva ?>"
                        pattern="^(100([.,]0{1,2})?|[0-9]{1,2}([.,]\d{1,2})?)$"
                        title="Uniquement un nombre entre 0 et 100 (ex : 20 ou 5,5)">
                    <?php if (isset($errors['tva'])) { ?>
                        <p class="error"><?php echo $errors['tva']; ?></p>
                    <?php } ?>
                </article>

                <article>
                    <h3>Stock</h3>
                    <input type="text" id="stock_disponible_produit" name="stock_disponible_produit"
                        value="<?php echo $stock ?>" pattern="^\d+$" title="Uniquement chiffres entiers">
                    <?php if (isset($errors['stock'])) { ?>
                        <p class="error"><?php echo $errors['stock']; ?></p>
                    <?php } ?>
                </article>

                <article>
                    <h3>Visibilité</h3>

                    <div class="visibility-option">
                        <input type="radio" id="visible" name="visibilite" value="1" CHECKED>
                        <label for="visible">Visible</label>
                    </div>

                    <div class="visibility-option">
                        <input type="radio" id="cache" name="visibilite" value="0">
                        <label for="cache">Caché</label>
                    </div>
                </article>
            </div>
        </div>


        <div class="produit-actions">
            <input type="submit" name="confirmer" classe="confirmer" value="Confirmer">
            <a href="index.php?page=dashboard" class="annuler">Annuler</a>
        </div>
    </form>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prixHT = document.getElementById('prix_unitaire_ht_produit');
        const tauxTVA = document.getElementById('taux_tva_produit');
        const prixTTC = document.getElementById('prix_ttc_produit');

        prixTTC.disabled = true;
        prixTTC.style.backgroundColor = '#e9ecef';
        prixTTC.style.cursor = 'not-allowed';

        function calculerPrixTTC() {
            let ht = parseFloat(prixHT.value.replace(',', '.').replace(/\s/g, ''));
            let tva = parseFloat(tauxTVA.value.replace(',', '.').replace(/\s/g, ''));

            if (!isNaN(ht) && !isNaN(tva) && ht >= 0 && tva >= 0 && tva <= 100) {
                let ttc = ht * (1 + tva / 100);

                prixTTC.value = ttc.toFixed(2).replace('.', ',');
            } else {
                prixTTC.value = '';
            }
        }

        calculerPrixTTC();

        prixHT.addEventListener('input', calculerPrixTTC);
        tauxTVA.addEventListener('input', calculerPrixTTC);
    });

    let newImagesPreview = [];

    function updateImageCount() {
        const newImages = newImagesPreview.length;

        document.getElementById('imageCount').textContent = newImages;

        const fileInput = document.getElementById('nouvelle_image');
        if (newImages >= 3) {
            fileInput.disabled = true;
            fileInput.parentElement.style.opacity = '0.5';
        } else {
            fileInput.disabled = false;
            fileInput.parentElement.style.opacity = '1';
        }
    }

    const MAX_SIZE = 2 * 1024 * 1024;

    document.getElementById("nouvelle_image").addEventListener("change", function (e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > MAX_SIZE) {
            alert("L'image est trop lourde (max 2 Mo).");
            e.target.value = "";
            return;
        }

        const newImages = newImagesPreview.length;

        if (newImages >= 3) {
            alert("Maximum 3 images !");
            e.target.value = "";
            return;
        }

        const reader = new FileReader();
        reader.onload = ev => {
            const div = document.createElement("div");
            div.classList.add("media-item", "preview");

            const img = document.createElement("img");
            img.src = ev.target.result;

            const btn = document.createElement("button");
            btn.textContent = "Supprimer";
            btn.classList.add("delete-btn");
            btn.type = "button";

            btn.addEventListener("click", function () {

                div.remove();

                newImagesPreview = newImagesPreview.filter(f => f !== file);

                fileInput.remove();

                updateImageCount();
            });

            div.appendChild(img);
            div.appendChild(btn);
            document.getElementById("mediaGallery").appendChild(div);
        };
        reader.readAsDataURL(file);

        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.name = "nouvelle_image[]";
        fileInput.hidden = true;
        document.forms[0].appendChild(fileInput);

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        newImagesPreview.push(file);
        updateImageCount();

        e.target.value = "";
    });


    updateImageCount();

</script>
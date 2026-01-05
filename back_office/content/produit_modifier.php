<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];

    $nom = $_POST['nom_produit'];
    $description = $_POST['description_produit'];
    $categorie = $_POST['categorie'];
    $prix_ht = str_replace(',', '.', $_POST['prix_unitaire_ht_produit']);
    $id_taux_tva = $_POST['id_taux_tva']; // Changement ici
    $stock = $_POST['stock_disponible_produit'];
    $actif = $_POST['visibilite'];
    $poids_unite = !empty($_POST['poids_unite']) ? str_replace(',', '.', $_POST['poids_unite']) : null;
    $unite_mesure = !empty($_POST['unite_mesure']) ? $_POST['unite_mesure'] : null;

    if (empty(trim($nom))) {
        $errors['nom_produit'] = "Veuillez saisir un nom de produit.";
    }

    if (empty(trim($description))) {
        $errors['description_produit'] = "Veuillez saisir une description.";
    }

    if (empty($categorie)) {
        $errors['categorie'] = "Veuillez sélectionner une catégorie.";
    }

    if (!preg_match('/^\d+([.,]\d{1,2})?$/', $_POST['prix_unitaire_ht_produit'])) {
        $errors['prix_ht'] = "Le prix HT doit contenir uniquement des chiffres et une virgule (ex : 10,50).";
    }

    if (empty($id_taux_tva)) {
        $errors['tva'] = "Veuillez sélectionner un taux de TVA.";
    }

    if (!preg_match('/^\d+$/', $stock)) {
        $errors['stock'] = "Le stock doit être un nombre entier positif.";
    }

    // Validation optionnelle pour poids et unité (doivent être renseignés ensemble ou pas du tout)
    if (!empty($poids_unite) && empty($unite_mesure)) {
        $errors['unite_mesure'] = "Veuillez sélectionner une unité de mesure.";
    }
    if (empty($poids_unite) && !empty($unite_mesure)) {
        $errors['poids_unite'] = "Veuillez saisir un poids/quantité.";
    }

    $imagesToDelete = !empty($_POST['images_to_delete']) ? json_decode($_POST['images_to_delete'], true) : [];
    $currentImagesCount = $imageCount - count($imagesToDelete);

    $newImagesCount = 0;
    if (isset($_FILES['nouvelle_image']) && !empty($_FILES['nouvelle_image']['name'][0])) {
        $newImagesCount = count(array_filter($_FILES['nouvelle_image']['name']));
    }

    $totalImages = $currentImagesCount + $newImagesCount;
    if ($totalImages < 1) {
        $errors['images'] = "Vous devez avoir au moins une image pour ce produit.";
    }

    if ($totalImages > 3) {
        $errors['images'] = "Vous ne pouvez pas avoir plus de 3 images par produit.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE produit 
        SET nom_produit = :nom, 
            description_produit = :description_prod, 
            prix_unitaire_ht = :prix_ht, 
            id_taux_tva = :id_taux_tva, 
            stock_disponible = :stock, 
            est_actif = :actif, 
            categorie = :categorie,
            poids_unite = :poids_unite,
            unite_mesure = :unite_mesure
        WHERE id_produit = :id_produit");

        $stmt->execute([
            'id_produit' => $id,
            'nom' => $nom,
            'description_prod' => $description,
            'categorie' => $categorie,
            'prix_ht' => $prix_ht,
            'id_taux_tva' => $id_taux_tva,
            'stock' => $stock,
            'actif' => $actif,
            'poids_unite' => $poids_unite,
            'unite_mesure' => $unite_mesure
        ]);

        if (!empty($imagesToDelete)) {
            foreach ($imagesToDelete as $id_media) {

                $stmt = $pdo->prepare("SELECT chemin_image FROM media_produit WHERE id_media = ?");
                $stmt->execute([$id_media]);
                $media = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($media && file_exists($media['chemin_image'])) {
                    unlink($media['chemin_image']);
                }

                $deleteStmt = $pdo->prepare("DELETE FROM media_produit WHERE id_media = ?");
                $deleteStmt->execute([$id_media]);
            }
        }

        if (!empty($_FILES['nouvelle_image']['name'])) {

            $uploadDir = __DIR__ . "/../front_end/assets/images_produits/"; 
            $uploadName = "/back_office/front_end/assets/images_produits/";

            foreach ($_FILES['nouvelle_image']['name'] as $index => $name) {

                if ($_FILES['nouvelle_image']['error'][$index] === UPLOAD_ERR_OK) {

                    $tmp = $_FILES['nouvelle_image']['tmp_name'][$index];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);

                    $fileName = uniqid("prod_{$id}_") . "." . $extension;
                    $filePath = $uploadDir . $fileName;
                    $filePathName = $uploadName . $fileName;

                    move_uploaded_file($tmp, $filePath);

                    $stmt = $pdo->prepare("
                INSERT INTO media_produit (id_produit, chemin_image)
                VALUES (?, ?)
            ");
                    $stmt->execute([$id, $filePathName]);
                }
            }
        }


        echo "<script>
            window.location.href = 'index.php?page=produit&id=$id&type=consulter';
        </script>";
        exit;
    }
}

// Récupération des taux de TVA
$stmt_tva = $pdo->query("SELECT id_taux_tva, nom_tva, taux FROM taux_tva ORDER BY taux DESC");
$taux_tva_list = $stmt_tva->fetchAll();
?>

<section class="produit-container">
    <form action="" method="POST" enctype="multipart/form-data">
        <h2>Modifier produit</h2>

        <div class="produit-content">
            <!-- Partie gauche : informations générales -->
            <div class="produit-infos">
                <h3>Informations générales</h3>
                <article>
                    <h3>Nom du produit</h3>
                    <input type="text" id="nom_produit" name="nom_produit"
                        value="<?php echo isset($_POST['nom_produit']) ? htmlentities($_POST['nom_produit']) : htmlentities($produit['nom_produit']); ?>">
                    <?php if (isset($errors['nom_produit'])) { ?>
                        <p class="error"><?php echo $errors['nom_produit']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Description</h3>
                    <input type="text" id="description_produit" name="description_produit"
                        value="<?php echo isset($_POST['description_produit']) ? htmlentities($_POST['description_produit']) : htmlentities($produit['description_produit']); ?>">
                    <?php if (isset($errors['description_produit'])) { ?>
                        <p class="error"><?php echo $errors['description_produit']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Catégorie</h3>

                    <select name="categorie" id="select-categorie">
                        <?php
                        $stmtAllCat = $pdo->query("SELECT libelle FROM categorie");
                        foreach ($stmtAllCat as $cat) { ?>
                            <option value="<?php echo htmlentities($cat['libelle']); ?>"
                                <?php echo (isset($_POST['categorie']) && $_POST['categorie'] == $cat['libelle']) || $produit['categorie'] == $cat['libelle'] ? 'selected' : ''; ?>>
                                <?php echo htmlentities($cat['libelle']); ?>
                            </option>
                        <?php } ?>
                    </select>
                        <?php if (isset($errors['categorie'])) { ?>
                        <p class="error"><?php echo $errors['categorie']; ?></p>
                    <?php } ?>
                </article>
                <article>
                    <h3>Média (<span id="imageCount"><?php echo count($images); ?></span>/3 images)</h3>

                    <div class="media-gallery" id="mediaGallery">

                        <?php foreach ($images as $image): ?>
                            <div class="media-item" data-id="<?php echo $image['id_media']; ?>">
                                <img src="<?php echo $image['chemin_image']; ?>" class="produit-image">

                                <button type="button" class="delete-btn"
                                    onclick="deleteImageLocal(<?php echo $image['id_media']; ?>)">
                                    Supprimer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" id="images_to_delete" name="images_to_delete" value="">

                    <div class="media-upload">
                        <label for="nouvelle_image">Ajouter une image :</label>
                        <input type="file" name="nouvelle_image" id="nouvelle_image" accept="image/*">
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
                    <input type="text" id="prix_ttc_produit" name="prix_ttc_produit"
                        value="<?php echo htmlentities(number_format($produit['prix_ttc'], 2, ',', ' ')) ?>" readonly>
                        
                    <h4>Prix HT</h4>
                    <input type="text" id="prix_unitaire_ht_produit" name="prix_unitaire_ht_produit"
                        value="<?php echo isset($_POST['prix_unitaire_ht_produit']) ? htmlentities($_POST['prix_unitaire_ht_produit']) : htmlentities(number_format($produit['prix_unitaire_ht'], 2, ',', ' ')); ?>"
                        pattern="^\d+([,]\d{1,2})?$" title="Uniquement chiffres et virgule (ex : 10,50)">
                        <?php if (isset($errors['prix_ht'])) { ?>
                        <p class="error"><?php echo $errors['prix_ht']; ?></p>
                    <?php } ?>

                    <h4>TVA</h4>
                    <select id="id_taux_tva" name="id_taux_tva">
                        <option value="">-- Sélectionner un taux de TVA --</option>
                        <?php foreach ($taux_tva_list as $tva): ?>
                            <option value="<?php echo $tva['id_taux_tva']; ?>" 
                                    data-taux="<?php echo $tva['taux']; ?>"
                                    <?php echo (isset($_POST['id_taux_tva']) && $_POST['id_taux_tva'] == $tva['id_taux_tva']) || $produit['id_taux_tva'] == $tva['id_taux_tva'] ? 'selected' : ''; ?>>
                                <?php echo htmlentities($tva['nom_tva']) . ' - ' . number_format($tva['taux'], 2, ',', '') . '%'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['tva'])) { ?>
                        <p class="error"><?php echo $errors['tva']; ?></p>
                    <?php } ?>
                </article>

                <article>
                    <h3>Poids / Quantité (optionnel)</h3>
                    
                    <h4>Unité de mesure</h4>
                    <select id="unite_mesure" name="unite_mesure">
                        <option value="">-- Sélectionner une unité --</option>
                        <option value="kg" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'kg') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'kg') ? 'selected' : ''; ?>>Kilogramme (kg)</option>
                        <option value="g" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'g') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'g') ? 'selected' : ''; ?>>Gramme (g)</option>
                        <option value="L" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'L') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'L') ? 'selected' : ''; ?>>Litre (L)</option>
                        <option value="cl" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'cl') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'cl') ? 'selected' : ''; ?>>Centilitre (cl)</option>
                        <option value="ml" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'ml') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'ml') ? 'selected' : ''; ?>>Millilitre (ml)</option>
                        <option value="pièce" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'pièce') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'pièce') ? 'selected' : ''; ?>>Pièce</option>
                        <option value="unité" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'unité') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'unité') ? 'selected' : ''; ?>>Unité</option>
                        <option value="set" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'set') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'set') ? 'selected' : ''; ?>>Set</option>
                        <option value="paire" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'paire') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'paire') ? 'selected' : ''; ?>>Paire</option>
                        <option value="portion" <?php echo (isset($_POST['unite_mesure']) && $_POST['unite_mesure'] == 'portion') || (!isset($_POST['unite_mesure']) && $produit['unite_mesure'] == 'portion') ? 'selected' : ''; ?>>Portion</option>
                    </select>
                    <?php if (isset($errors['unite_mesure'])) { ?>
                        <p class="error"><?php echo $errors['unite_mesure']; ?></p>
                    <?php } ?>

                    <h4>Quantité</h4>
                    <select id="poids_unite" name="poids_unite">
                        <option value="">-- Sélectionner une quantité --</option>
                        <!-- Poids en kg/g -->
                        <optgroup label="Poids">
                            <option value="0.100" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.100') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.100') ? 'selected' : ''; ?>>100g</option>
                            <option value="0.125" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.125') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.125') ? 'selected' : ''; ?>>125g</option>
                            <option value="0.150" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.150') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.150') ? 'selected' : ''; ?>>150g</option>
                            <option value="0.200" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.200') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.200') ? 'selected' : ''; ?>>200g</option>
                            <option value="0.250" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.250') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.250') ? 'selected' : ''; ?>>250g</option>
                            <option value="0.300" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.300') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.300') ? 'selected' : ''; ?>>300g</option>
                            <option value="0.400" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.400') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.400') ? 'selected' : ''; ?>>400g</option>
                            <option value="0.500" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.500') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.500') ? 'selected' : ''; ?>>500g</option>
                            <option value="0.750" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.750') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.750') ? 'selected' : ''; ?>>750g</option>
                            <option value="1.000" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '1.000') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '1.000') ? 'selected' : ''; ?>>1 kg</option>
                            <option value="1.500" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '1.500') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '1.500') ? 'selected' : ''; ?>>1,5 kg</option>
                            <option value="2.000" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '2.000') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '2.000') ? 'selected' : ''; ?>>2 kg</option>
                        </optgroup>
                        <!-- Volume en L/cl -->
                        <optgroup label="Volume">
                            <option value="0.250" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.250') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.250') ? 'selected' : ''; ?>>25cl</option>
                            <option value="0.330" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.330') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.330') ? 'selected' : ''; ?>>33cl</option>
                            <option value="0.500" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.500') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.500') ? 'selected' : ''; ?>>50cl</option>
                            <option value="0.750" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '0.750') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '0.750') ? 'selected' : ''; ?>>75cl</option>
                            <option value="1.000" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '1.000') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '1.000') ? 'selected' : ''; ?>>1 L</option>
                            <option value="1.500" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '1.500') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '1.500') ? 'selected' : ''; ?>>1,5 L</option>
                            <option value="2.000" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '2.000') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '2.000') ? 'selected' : ''; ?>>2 L</option>
                        </optgroup>
                        <!-- Quantités unitaires -->
                        <optgroup label="Unité">
                            <option value="1" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '1') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '1') ? 'selected' : ''; ?>>1</option>
                            <option value="2" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '2') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '2') ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '3') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '3') ? 'selected' : ''; ?>>3</option>
                            <option value="4" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '4') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '4') ? 'selected' : ''; ?>>4</option>
                            <option value="5" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '5') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '5') ? 'selected' : ''; ?>>5</option>
                            <option value="6" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '6') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '6') ? 'selected' : ''; ?>>6</option>
                            <option value="8" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '8') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '8') ? 'selected' : ''; ?>>8</option>
                            <option value="10" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '10') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '10') ? 'selected' : ''; ?>>10</option>
                            <option value="12" <?php echo (isset($_POST['poids_unite']) && $_POST['poids_unite'] == '12') || (!isset($_POST['poids_unite']) && $produit['poids_unite'] == '12') ? 'selected' : ''; ?>>12</option>
                        </optgroup>
                    </select>
                    <?php if (isset($errors['poids_unite'])) { ?>
                        <p class="error"><?php echo $errors['poids_unite']; ?></p>
                    <?php } ?>
                    <small style="display: block; margin-top: 5px; color: #666;">Ces champs permettent de calculer automatiquement le prix au kilo/litre</small>
                </article>

                <article>
                    <h3>Stock</h3>
                    <input type="text" id="stock_disponible_produit" name="stock_disponible_produit"
                        value="<?php echo isset($_POST['stock_disponible_produit']) ? htmlentities($_POST['stock_disponible_produit']) : htmlentities($produit['stock_disponible']); ?>"
                        pattern="^\d+$"
                        title="Uniquement chiffres entiers">
                        <?php if (isset($errors['stock'])) { ?>
                        <p class="error"><?php echo $errors['stock']; ?></p>
                    <?php } ?>
                </article>

                <article>
                    <h3>Visibilité</h3>

                    <div class="visibility-option">
                        <input type="radio" id="visible" name="visibilite" value="1" <?php echo (isset($_POST['visibilite']) ? $_POST['visibilite'] == "1" : $produit['est_actif']) ? 'checked' : ''; ?>>
                        <label for="visible">Visible</label>
                    </div>

                    <div class="visibility-option">
                        <input type="radio" id="cache" name="visibilite" value="0" <?php echo (isset($_POST['visibilite']) ? $_POST['visibilite'] == "0" : !$produit['est_actif']) ? 'checked' : ''; ?>>
                        <label for="cache">Caché</label>
                    </div>
                </article>
            </div>
        </div>


        <div class="produit-actions">
            <input type="submit" name="confirmer" class="confirmer" value="Confirmer">
            <a href="index.php?page=produit&id=<?php echo htmlentities($id) ?>&type=consulter"
                class="annuler">Annuler</a>
        </div>
    </form>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prixHT = document.getElementById('prix_unitaire_ht_produit');
        const selectTVA = document.getElementById('id_taux_tva');
        const prixTTC = document.getElementById('prix_ttc_produit');

        prixTTC.disabled = true;
        prixTTC.style.backgroundColor = '#e9ecef';
        prixTTC.style.cursor = 'not-allowed';

        function calculerPrixTTC() {
            let ht = parseFloat(prixHT.value.replace(',', '.').replace(/\s/g, ''));
            let tva = 0;
            
            // Récupérer le taux de TVA depuis l'option sélectionnée
            if (selectTVA.value) {
                const selectedOption = selectTVA.options[selectTVA.selectedIndex];
                tva = parseFloat(selectedOption.getAttribute('data-taux'));
            }

            if (!isNaN(ht) && !isNaN(tva) && ht >= 0 && tva >= 0) {
                let ttc = ht * (1 + tva / 100);
                prixTTC.value = ttc.toFixed(2).replace('.', ',');
            } else {
                prixTTC.value = '';
            }
        }

        calculerPrixTTC();

        prixHT.addEventListener('input', calculerPrixTTC);
        selectTVA.addEventListener('change', calculerPrixTTC);
    });

    let imagesToDelete = [];
    let newImagesPreview = [];

    function updateImageCount() {
        const existingImages = <?php echo $imageCount ?>;
        const deletedImages = imagesToDelete.length;
        const newImages = newImagesPreview.length;

        const totalImages = existingImages - deletedImages + newImages;
        document.getElementById('imageCount').textContent = totalImages;

        const fileInput = document.getElementById('nouvelle_image');
        if (totalImages >= 3) {
            fileInput.disabled = true;
            fileInput.parentElement.style.opacity = '0.5';
        } else {
            fileInput.disabled = false;
            fileInput.parentElement.style.opacity = '1';
        }
    }

    function deleteImageLocal(id) {
        const item = document.querySelector(`.media-item[data-id="${id}"]`);

        if (!imagesToDelete.includes(id)) {
            imagesToDelete.push(id);
            item.remove();
        }

        document.getElementById("images_to_delete").value = JSON.stringify(imagesToDelete);
        updateImageCount();
    }

    const MAX_SIZE = 2 * 1024 * 1024;

    document.getElementById("nouvelle_image").addEventListener("change", function (e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > MAX_SIZE) {
            alert("L'image est trop lourde (max 2 Mo).");
            e.target.value = ""; // reset input
            return;
        }

        // Limite 3
        const existingImages = <?php echo $imageCount ?>;
        const deletedImages = imagesToDelete.length;
        const newImages = newImagesPreview.length;

        if (existingImages - deletedImages + newImages >= 3) {
            alert("Maximum 3 images !");
            e.target.value = "";
            return;
        }

        // Preview
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

            // Bouton supprimer
            btn.addEventListener("click", function () {

                // Supprimer preview
                div.remove();

                // Supprimer du tableau
                newImagesPreview = newImagesPreview.filter(f => f !== file);

                // Supprimer le fichier de son input hidden
                fileInput.remove();

                updateImageCount();
            });

            div.appendChild(img);
            div.appendChild(btn);
            document.getElementById("mediaGallery").appendChild(div);
        };
        reader.readAsDataURL(file);

        // 👉 Création d'un vrai input file qui sera envoyé au serveur
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.name = "nouvelle_image[]";
        fileInput.hidden = true;
        document.forms[0].appendChild(fileInput);

        // Hack pour cloner le FileList
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        newImagesPreview.push(file);
        updateImageCount();

        // Reset input principal
        e.target.value = "";
    });


    updateImageCount();
</script>
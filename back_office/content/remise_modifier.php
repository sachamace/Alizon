
<?php
$erreurs = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom_remise = trim($_POST['nom_remise']);
    $description = trim($_POST['description']);
    $type_remise = $_POST['type_remise'];
    $valeur_remise = floatval($_POST['valeur_remise']);
    $condition_min_achat = floatval($_POST['condition_min_achat']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    
    // Récupérer le type d'application
    $type_application = $_POST['type_application'] ?? 'tous';
    $categorie = ($type_application === 'categorie') ? $_POST['categorie'] : null;
    $produits_ids = ($type_application === 'produits') ? ($_POST['produits'] ?? []) : [];
    
    // Pour les produits spécifiques, NE PAS mettre id_produit dans la table remise
    // On utilisera uniquement la table remise_produit
    $id_produit_remise = ($type_application === 'produits' && count($produits_ids) === 1) 
        ? intval($produits_ids[0]) 
        : null;
    
    // Validations
    if (empty($nom_remise)) {
        $erreurs['nom_remise'] = "Le nom de la remise est obligatoire";
    }
    
    if ($valeur_remise <= 0) {
        $erreurs['valeur_remise'] = "La valeur de la remise doit être supérieure à 0";
    }
    
    if ($type_remise === 'pourcentage' && $valeur_remise > 100) {
        $erreurs['valeur_remise'] = "Le pourcentage ne peut pas dépasser 100%";
    }
    
    if (empty($date_debut) || empty($date_fin)) {
        $erreurs['dates'] = "Les dates de début et de fin sont obligatoires";
    } elseif (strtotime($date_fin) < strtotime($date_debut)) {
        $erreurs['dates'] = "La date de fin doit être après la date de début";
    }
    
    if ($type_application === 'categorie' && empty($categorie)) {
        $erreurs['application'] = "Veuillez sélectionner une catégorie";
    }
    
    if ($type_application === 'produits' && empty($produits_ids)) {
        $erreurs['application'] = "Veuillez sélectionner au moins un produit";
    }
    
    if (empty($erreurs)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Mettre à jour la remise
            $sql = "UPDATE remise SET 
                    nom_remise = ?, 
                    description = ?, 
                    type_remise = ?, 
                    valeur_remise = ?, 
                    condition_min_achat = ?, 
                    date_debut = ?, 
                    date_fin = ?, 
                    categorie = ?, 
                    est_actif = ?,
                    id_produit = ?
                    WHERE id_remise = ? AND id_vendeur = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nom_remise,
                $description,
                $type_remise,
                $valeur_remise,
                $condition_min_achat,
                $date_debut,
                $date_fin,
                $categorie,
                $est_actif,
                $id_produit_remise,
                $id_remise,
                $id_vendeur_connecte
            ]);
            
            // 2. Supprimer les anciennes associations produits
            $stmt = $pdo->prepare("DELETE FROM remise_produit WHERE id_remise = ?");
            $stmt->execute([$id_remise]);
            
            // 3. Si application sur des produits spécifiques, insérer dans remise_produit
            if ($type_application === 'produits' && !empty($produits_ids)) {
                $stmt = $pdo->prepare("INSERT INTO remise_produit (id_remise, id_produit) VALUES (?, ?)");
                foreach ($produits_ids as $id_produit) {
                    $stmt->execute([$id_remise, intval($id_produit)]);
                }
            }
            
            $pdo->commit();
            
            echo "<script>
                window.location.href = 'index.php?page=remise&type=consulter&id=$id_remise';
            </script>";
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreurs['general'] = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Déterminer le type d'application actuel
$type_application_actuel = 'tous';
if (!empty($remise['categorie'])) {
    $type_application_actuel = 'categorie';
} else {
    // Vérifier s'il y a des produits spécifiques
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) as nb FROM remise_produit WHERE id_remise = ?");
    $stmtCheck->execute([$id_remise]);
    if ($stmtCheck->fetch()['nb'] > 0) {
        $type_application_actuel = 'produits';
    }
}

// Récupérer les produits sélectionnés si c'est une remise sur produits spécifiques
$produits_selectionnes = [];
if ($type_application_actuel === 'produits') {
    $stmtProdSel = $pdo->prepare("SELECT id_produit FROM remise_produit WHERE id_remise = ?");
    $stmtProdSel->execute([$id_remise]);
    $produits_selectionnes = $stmtProdSel->fetchAll(PDO::FETCH_COLUMN);
}

// Utiliser les valeurs POST si disponibles, sinon les valeurs de la BDD
$form_data = $_SERVER["REQUEST_METHOD"] === "POST" ? $_POST : $remise;
$type_application = $_SERVER["REQUEST_METHOD"] === "POST" ? ($_POST['type_application'] ?? 'tous') : $type_application_actuel;
?>

<section class="remise-form-container">
    <div class="remise-form-header">
        <h2>Modifier la remise</h2>
        <a href="?page=remise" class="btn-retour">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Retour au menu
        </a>
    </div>

    <?php if (!empty($erreurs['general'])): ?>
        <div class="error-message"><?= htmlentities($erreurs['general']) ?></div>
    <?php endif; ?>

    <form method="POST" class="remise-form" id="remiseForm">
        <div class="form-section">
            <h3>Informations générales</h3>
            
            <div class="input-group">
                <label for="nom_remise">Nom de la remise *</label>
                <input type="text" id="nom_remise" name="nom_remise" 
                       value="<?= htmlentities($form_data['nom_remise']) ?>" 
                       placeholder="Ex: Soldes d'hiver" required>
                <?php if (isset($erreurs['nom_remise'])): ?>
                    <span class="error"><?= $erreurs['nom_remise'] ?></span>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Description de la remise (optionnel)"><?= htmlentities($form_data['description'] ?? '') ?></textarea>
            </div>

            <div class="input-group">
                <label>
                    <input type="checkbox" name="est_actif" value="1" 
                           <?= ($form_data['est_actif']) ? 'checked' : '' ?>>
                    Remise active
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>Valeur de la remise</h3>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="type_remise">Type de remise *</label>
                    <select id="type_remise" name="type_remise" required>
                        <option value="pourcentage" <?= ($form_data['type_remise'] === 'pourcentage') ? 'selected' : '' ?>>
                            Pourcentage (%)
                        </option>
                        <option value="fixe" <?= ($form_data['type_remise'] === 'fixe') ? 'selected' : '' ?>>
                            Montant fixe (€)
                        </option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="valeur_remise">Valeur *</label>
                    <input type="number" id="valeur_remise" name="valeur_remise" 
                           value="<?= $form_data['valeur_remise'] ?>" 
                           step="0.01" min="0" placeholder="10" required>
                    <?php if (isset($erreurs['valeur_remise'])): ?>
                        <span class="error"><?= $erreurs['valeur_remise'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="input-group">
                <label for="condition_min_achat">Montant minimum d'achat (€)</label>
                <input type="number" id="condition_min_achat" name="condition_min_achat" 
                       value="<?= $form_data['condition_min_achat'] ?>" 
                       step="0.01" min="0" placeholder="0">
                <small>Laissez 0 pour aucune condition minimum</small>
            </div>
        </div>

        <div class="form-section">
            <h3>Période de validité</h3>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" id="date_debut" name="date_debut" 
                           value="<?= $form_data['date_debut'] ?>" 
                           required>
                </div>

                <div class="input-group">
                    <label for="date_fin">Date de fin *</label>
                    <input type="date" id="date_fin" name="date_fin" 
                           value="<?= $form_data['date_fin'] ?>" 
                           required>
                </div>
            </div>
            <?php if (isset($erreurs['dates'])): ?>
                <span class="error"><?= $erreurs['dates'] ?></span>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <h3>Application de la remise</h3>
            
            <?php if (isset($erreurs['application'])): ?>
                <div class="error-message"><?= $erreurs['application'] ?></div>
            <?php endif; ?>
            
            <div class="radio-group">
                <label class="radio-option">
                    <input type="radio" name="type_application" value="tous" 
                           <?= ($type_application === 'tous') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong>Tous les produits</strong>
                        <small>La remise s'applique sur l'ensemble du catalogue</small>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="type_application" value="categorie" 
                           <?= ($type_application === 'categorie') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong>Une catégorie entière</strong>
                        <small>Tous les produits d'une catégorie spécifique</small>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="type_application" value="produits" 
                           <?= ($type_application === 'produits') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong>Produits spécifiques</strong>
                        <small>Sélectionnez un ou plusieurs produits</small>
                    </div>
                </label>
            </div>

            <!-- Section catégorie -->
            <div id="section-categorie" class="application-section" style="display: none;">
                <label for="categorie">Sélectionner une catégorie *</label>
                <select id="categorie" name="categorie">
                    <option value="">-- Choisir une catégorie --</option>
                    <?php
                    $stmtCat = $pdo->prepare("SELECT DISTINCT categorie FROM produit WHERE id_vendeur = ? ORDER BY categorie");
                    $stmtCat->execute([$id_vendeur_connecte]);
                    while ($cat = $stmtCat->fetch()):
                    ?>
                        <option value="<?= htmlentities($cat['categorie']) ?>" 
                                <?= (isset($form_data['categorie']) && $form_data['categorie'] === $cat['categorie']) ? 'selected' : '' ?>>
                            <?= htmlentities($cat['categorie']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Section produits spécifiques -->
            <div id="section-produits" class="application-section" style="display: none;">
                <div class="produits-selection">
                    <div class="selection-header">
                        <label>Sélectionner les produits *</label>
                        <div class="selection-actions">
                            <button type="button" id="selectAll">Tout sélectionner</button>
                            <button type="button" id="deselectAll">Tout désélectionner</button>
                        </div>
                    </div>
                    
                    <div class="produits-list">
                        <?php
                        $selectedProduits = $_SERVER["REQUEST_METHOD"] === "POST" ? ($_POST['produits'] ?? []) : $produits_selectionnes;
                        foreach ($produits as $produit):
                        ?>
                            <label class="produit-checkbox">
                                <input type="checkbox" name="produits[]" value="<?= $produit['id_produit'] ?>"
                                       <?= in_array($produit['id_produit'], $selectedProduits) ? 'checked' : '' ?>>
                                <div class="produit-info">
                                    <span class="produit-nom"><?= htmlentities($produit['nom_produit']) ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            <a href="?page=remise&type=consulter&id=<?= $remise['id_remise'] ?>" class="btn-secondary">Annuler</a>
        </div>
    </form>
</section>

<script>
// Gestion de l'affichage des sections selon le type d'application
const typeApplicationRadios = document.getElementsByName('type_application');
const sectionCategorie = document.getElementById('section-categorie');
const sectionProduits = document.getElementById('section-produits');
const categorieSelect = document.getElementById('categorie');

function updateApplicationSection() {
    const selectedType = document.querySelector('input[name="type_application"]:checked').value;
    
    // Cacher toutes les sections
    sectionCategorie.style.display = 'none';
    sectionProduits.style.display = 'none';
    categorieSelect.removeAttribute('required');
    
    // Afficher la section appropriée
    if (selectedType === 'categorie') {
        sectionCategorie.style.display = 'block';
        categorieSelect.setAttribute('required', 'required');
    } else if (selectedType === 'produits') {
        sectionProduits.style.display = 'block';
    }
}

typeApplicationRadios.forEach(radio => {
    radio.addEventListener('change', updateApplicationSection);
});

// Initialiser l'affichage au chargement
updateApplicationSection();

// Boutons sélectionner tout / désélectionner tout
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="produits[]"]').forEach(cb => cb.checked = true);
});

document.getElementById('deselectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="produits[]"]').forEach(cb => cb.checked = false);
});

// Mise à jour du placeholder selon le type de remise
document.getElementById('type_remise').addEventListener('change', function() {
    const valeurInput = document.getElementById('valeur_remise');
    if (this.value === 'pourcentage') {
        valeurInput.placeholder = '10';
        valeurInput.max = '100';
    } else {
        valeurInput.placeholder = '5.00';
        valeurInput.removeAttribute('max');
    }
});
</script>

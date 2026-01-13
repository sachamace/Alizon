
<?php
$erreurs = [];
$success = false;

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
    
    // Validation : vérifier que les champs numériques ne contiennent que des chiffres et virgules/points
    if (!empty($_POST['valeur_remise']) && !preg_match('/^[0-9.,]+$/', $_POST['valeur_remise'])) {
        $erreurs['valeur_remise'] = "La valeur ne peut contenir que des chiffres";
    }
    if (!empty($_POST['condition_min_achat']) && !preg_match('/^[0-9.,]+$/', $_POST['condition_min_achat'])) {
        $erreurs['condition_min_achat'] = "Le montant ne peut contenir que des chiffres";
    }
    
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
            
            // 1. Insérer la remise
            $sql = "INSERT INTO remise (nom_remise, description, type_remise, valeur_remise, 
                    condition_min_achat, date_debut, date_fin, categorie, 
                    id_vendeur, est_actif, id_produit) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
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
                $id_vendeur_connecte,
                $est_actif,
                $id_produit_remise // NULL pour plusieurs produits ou catégorie
            ]);
            
            $id_remise = $pdo->lastInsertId();
            
            // 2. Si application sur des produits spécifiques (un seul ou plusieurs), 
            // insérer dans remise_produit
            if ($type_application === 'produits' && !empty($produits_ids)) {
                $stmt = $pdo->prepare("INSERT INTO remise_produit (id_remise, id_produit) VALUES (?, ?)");
                foreach ($produits_ids as $id_produit) {
                    $stmt->execute([$id_remise, intval($id_produit)]);
                }
            }
            
            $pdo->commit();
            
            echo "<script>
                window.location.href = 'index.php?page=remise';
            </script>";
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreurs['general'] = "Erreur lors de la création de la remise : " . $e->getMessage();
        }
    }
}
?>

<section class="remise-form-container">
    <div class="remise-form-header">
        <h2>Créer une nouvelle remise</h2>
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
                       value="<?= isset($_POST['nom_remise']) ? htmlentities($_POST['nom_remise']) : '' ?>" 
                       placeholder="Ex: Soldes d'hiver" required>
                <?php if (isset($erreurs['nom_remise'])): ?>
                    <span class="error"><?= $erreurs['nom_remise'] ?></span>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Description de la remise (optionnel)"><?= isset($_POST['description']) ? htmlentities($_POST['description']) : '' ?></textarea>
            </div>

            <div class="input-group">
                <label>
                    <input type="checkbox" name="est_actif" value="1" 
                           <?= isset($_POST['est_actif']) ? 'checked' : 'checked' ?>>
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
                        <option value="pourcentage" <?= (!isset($_POST['type_remise']) || $_POST['type_remise'] === 'pourcentage') ? 'selected' : '' ?>>
                            Pourcentage (%)
                        </option>
                        <option value="fixe" <?= (isset($_POST['type_remise']) && $_POST['type_remise'] === 'fixe') ? 'selected' : '' ?>>
                            Montant fixe (€)
                        </option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="valeur_remise">Valeur *</label>
                    <input type="number" id="valeur_remise" name="valeur_remise" 
                           value="<?= isset($_POST['valeur_remise']) ? $_POST['valeur_remise'] : '' ?>" 
                           step="0.01" min="0.01" placeholder="10" required>
                    <?php if (isset($erreurs['valeur_remise'])): ?>
                        <span class="error"><?= $erreurs['valeur_remise'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="input-group">
                <label for="condition_min_achat">Montant minimum d'achat (€)</label>
                <input type="number" id="condition_min_achat" name="condition_min_achat" 
                       value="<?= isset($_POST['condition_min_achat']) ? $_POST['condition_min_achat'] : '0' ?>" 
                       step="0.01" min="0" placeholder="0">
                <small>Laissez 0 pour aucune condition minimum</small>
                <?php if (isset($erreurs['condition_min_achat'])): ?>
                    <span class="error"><?= $erreurs['condition_min_achat'] ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-section">
            <h3>Période de validité</h3>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" id="date_debut" name="date_debut" 
                           value="<?= isset($_POST['date_debut']) ? $_POST['date_debut'] : date('Y-m-d') ?>" 
                           required>
                </div>

                <div class="input-group">
                    <label for="date_fin">Date de fin *</label>
                    <input type="date" id="date_fin" name="date_fin" 
                           value="<?= isset($_POST['date_fin']) ? $_POST['date_fin'] : date('Y-m-d', strtotime('+1 month')) ?>" 
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
                           <?= (!isset($_POST['type_application']) || $_POST['type_application'] === 'tous') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong>Tous les produits</strong>
                        <small>La remise s'applique sur l'ensemble du catalogue</small>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="type_application" value="categorie" 
                           <?= (isset($_POST['type_application']) && $_POST['type_application'] === 'categorie') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong>Une catégorie entière</strong>
                        <small>Tous les produits d'une catégorie spécifique</small>
                    </div>
                </label>

                <label class="radio-option">
                    <input type="radio" name="type_application" value="produits" 
                           <?= (isset($_POST['type_application']) && $_POST['type_application'] === 'produits') ? 'checked' : '' ?>>
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
                    $stmtCat = $pdo->query("SELECT DISTINCT categorie FROM produit WHERE id_vendeur = $id_vendeur_connecte ORDER BY categorie");
                    while ($cat = $stmtCat->fetch()):
                    ?>
                        <option value="<?= htmlentities($cat['categorie']) ?>" 
                                <?= (isset($_POST['categorie']) && $_POST['categorie'] === $cat['categorie']) ? 'selected' : '' ?>>
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
                        $selectedProduits = $_POST['produits'] ?? [];
                        foreach ($produits as $produit):
                        ?>
                            <label class="produit-checkbox">
                                <input type="checkbox" name="produits[]" value="<?= $produit['id_produit'] ?>"
                                       <?= in_array($produit['id_produit'], $selectedProduits) ? 'checked' : '' ?>>
                                <div class="produit-info">
                                    <span class="produit-nom"><?= htmlentities($produit['nom_produit']) ?></span>
                                    <span class="produit-categorie"><?= htmlentities($produit['categorie'] ?? 'Sans catégorie') ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Créer la remise</button>
            <a href="?page=remise" class="btn-secondary">Annuler</a>
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

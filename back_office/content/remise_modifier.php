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
    $id_produit = !empty($_POST['id_produit']) ? intval($_POST['id_produit']) : null;
    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
    
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
    
    if (empty($erreurs)) {
        try {
            $sql = "UPDATE remise SET 
                    nom_remise = ?, 
                    description = ?, 
                    type_remise = ?, 
                    valeur_remise = ?, 
                    condition_min_achat = ?, 
                    date_debut = ?, 
                    date_fin = ?, 
                    id_produit = ?, 
                    est_actif = ?
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
                $id_produit,
                $est_actif,
                $id_remise,
                $id_vendeur_connecte
            ]);
            
            echo "<script>
                window.location.href = 'index.php?page=remise&type=consulter&id=$id_remise';
            </script>";
            exit();
        } catch (PDOException $e) {
            $erreurs['general'] = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Utiliser les valeurs POST si disponibles, sinon les valeurs de la BDD
$form_data = $_SERVER["REQUEST_METHOD"] === "POST" ? $_POST : $remise;
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

    <form method="POST" class="remise-form">
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
            
            <div class="input-group">
                <label for="id_produit">Produit concerné</label>
                <select id="id_produit" name="id_produit">
                    <option value="">Tous les produits</option>
                    <?php foreach ($produits as $produit): ?>
                        <option value="<?= $produit['id_produit'] ?>" 
                                <?= ($form_data['id_produit'] == $produit['id_produit']) ? 'selected' : '' ?>>
                            <?= htmlentities($produit['nom_produit']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Laissez vide pour appliquer la remise à tous vos produits</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            <a href="?page=remise&type=consulter&id=<?= $remise['id_remise'] ?>" class="btn-secondary">Annuler</a>
        </div>
    </form>
</section>

<script>
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
<?php
// Les produits sont déjà récupérés dans promotion.php avec prix_ttc_base

// Traitement du formulaire de création de promotion
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $errors = [];
    
    $nom_promotion = trim($_POST['nom_promotion']);
    $description = trim($_POST['description']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $est_actif = isset($_POST['est_actif']) ? true : false;
    $produits_selectionnes = $_POST['produits'] ?? [];
    
    // Validation
    if (empty($nom_promotion)) {
        $errors['nom_promotion'] = "Le nom de la promotion est obligatoire";
    }
    
    if (empty($date_debut)) {
        $errors['date_debut'] = "La date de début est obligatoire";
    }
    
    if (empty($date_fin)) {
        $errors['date_fin'] = "La date de fin est obligatoire";
    }
    
    if (!empty($date_debut) && !empty($date_fin) && $date_fin < $date_debut) {
        $errors['dates'] = "La date de fin doit être après la date de début";
    }
    
    if (empty($produits_selectionnes)) {
        $errors['produits'] = "Veuillez sélectionner au moins un produit";
    }
    
    // Si pas d'erreurs, créer la promotion
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insérer la promotion
            $stmt = $pdo->prepare("
                INSERT INTO public.promotion 
                (nom_promotion, description, date_debut, date_fin, est_actif, id_vendeur)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nom_promotion,
                $description,
                $date_debut,
                $date_fin,
                $est_actif,
                $id_vendeur_connecte
            ]);
            
            // Récupérer l'ID de la promotion créée
            $id_promotion = $pdo->lastInsertId();
            
            // Insérer les produits liés
            $stmt_produit = $pdo->prepare("
                INSERT INTO public.promotion_produit (id_promotion, id_produit, ordre_produit)
                VALUES (?, ?, ?)
            ");
            
            foreach ($produits_selectionnes as $index => $id_produit) {
                $stmt_produit->execute([$id_promotion, $id_produit, $index]);
            }
            
            $pdo->commit();
            
            echo "<script>
                alert('Promotion créée avec succès');
                window.location.href = 'index.php?page=promotion&type=consulter&id=" . $id_promotion . "';
            </script>";
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = "Erreur lors de la création : " . $e->getMessage();
        }
    }
}
?>

<section class="promotion-creer-container">
    <div class="promotion-header">
        <h2>Créer une promotion</h2>
        <a href="?page=promotion" class="btn-retour">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Retour au menu
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p class="error"><?= htmlentities($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="promotion-form">
        <div class="form-section">
            <h3>Informations générales</h3>
            
            <div class="form-group">
                <label for="nom_promotion">Nom de la promotion *</label>
                <input type="text" 
                       id="nom_promotion" 
                       name="nom_promotion" 
                       value="<?= htmlentities($_POST['nom_promotion'] ?? '') ?>"
                       placeholder="Ex: Produits du mois, Nouveautés printemps..."
                       required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" 
                          name="description" 
                          rows="4"
                          placeholder="Décrivez cette promotion..."><?= htmlentities($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>Période de validité</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" 
                           id="date_debut" 
                           name="date_debut" 
                           value="<?= htmlentities($_POST['date_debut'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="date_fin">Date de fin *</label>
                    <input type="date" 
                           id="date_fin" 
                           name="date_fin" 
                           value="<?= htmlentities($_POST['date_fin'] ?? '') ?>"
                           required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Paramètres d'affichage</h3>
            
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" 
                           name="est_actif" 
                           <?= isset($_POST['est_actif']) || !isset($_POST['nom_promotion']) ? 'checked' : '' ?>>
                    <span>Promotion active</span>
                </label>
                <small>Si décoché, la promotion ne sera pas visible sur le site</small>
            </div>
        </div>

        <div class="form-section">
            <h3>Produits mis en avant *</h3>
            <p class="section-description">Cochez les produits à mettre en avant dans cette promotion</p>
            
            <div class="produits-selection-simple">
                <div class="produits-liste">
                    <?php foreach ($produits as $produit): ?>
                        <label class="produit-item">
                            <input type="checkbox" 
                                   name="produits[]" 
                                   value="<?= $produit['id_produit'] ?>"
                                   <?= in_array($produit['id_produit'], $_POST['produits'] ?? []) ? 'checked' : '' ?>>
                            
                            <span class="produit-nom">
                                <?= htmlentities($produit['nom_produit']) ?>
                            </span>
                            
                            <span class="produit-prix">
                                <?= number_format($produit['prix_ttc_base'], 2, ',', ' ') ?> €
                            </span>
                            
                            <?php if (!$produit['est_actif']): ?>
                                <span class="badge-inactif-inline">Inactif</span>
                            <?php elseif ($produit['stock_disponible'] <= 0): ?>
                                <span class="badge-rupture-inline">Rupture</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="selection-count">
                    <span id="count-selected">0</span> produit(s) sélectionné(s)
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                </svg>
                Créer la promotion
            </button>
            <a href="?page=promotion" class="btn-secondary">Annuler</a>
        </div>
    </form>
</section>

<script>
// Compter les produits sélectionnés
function updateCount() {
    const checked = document.querySelectorAll('input[name="produits[]"]:checked').length;
    document.getElementById('count-selected').textContent = checked;
}

// Ajouter des listeners sur tous les checkboxes
document.querySelectorAll('input[name="produits[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', updateCount);
});

// Initialiser le compteur
updateCount();
</script>
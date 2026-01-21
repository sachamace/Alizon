<?php
include 'config.php';
include 'session.php';
include 'sessionindex.php';

$id_client_connecte = $_SESSION['id_client'];
$LIMITE_ADRESSES = 5; // Facilement modifiable (mettre 999 pour "illimité")

$message_succes = '';
$message_erreur = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // === AJOUTER UNE NOUVELLE ADRESSE ===
        if ($action === 'ajouter') {
            // Vérifier le nombre d'adresses existantes
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM adresse WHERE id_client = :id_client");
            $stmt_count->execute(['id_client' => $id_client_connecte]);
            $nb_adresses = $stmt_count->fetchColumn();
            
            if ($nb_adresses >= $LIMITE_ADRESSES) {
                $message_erreur = "Vous avez atteint la limite de $LIMITE_ADRESSES adresses.";
            } else {
                $adresse = trim($_POST['adresse']);
                $code_postal = trim($_POST['code_postal']);
                $ville = trim($_POST['ville']);
                $pays = trim($_POST['pays']);
                $num_tel = trim($_POST['num_tel'] ?? '');
                
                // Validation
                if (empty($adresse) || empty($code_postal) || empty($ville) || empty($pays)) {
                    $message_erreur = "Tous les champs obligatoires doivent être remplis.";
                } elseif (!preg_match("/^[0-9]{5}$/", $code_postal)) {
                    $message_erreur = "Code postal invalide (5 chiffres requis).";
                } else {
                    // Calculer le prochain numéro d'adresse
                    $numero_adresse = $nb_adresses + 1;
                    $libelle = "Adresse " . $numero_adresse;
                    
                    // C'est la première adresse ? Elle devient par défaut
                    $est_defaut = ($nb_adresses == 0) ? 'true' : 'false';
                    
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO adresse (id_client, adresse, code_postal, ville, pays, num_tel, libelle, est_defaut)
                        VALUES (:id_client, :adresse, :code_postal, :ville, :pays, :num_tel, :libelle, :est_defaut::boolean)
                    ");
                    $stmt_insert->execute([
                        'id_client' => $id_client_connecte,
                        'adresse' => $adresse,
                        'code_postal' => $code_postal,
                        'ville' => $ville,
                        'pays' => $pays,
                        'num_tel' => $num_tel ?: null,
                        'libelle' => $libelle,
                        'est_defaut' => $est_defaut
                    ]);
                    
                    $message_succes = "Adresse ajoutée avec succès !";
                }
            }
        }
        
        // === MODIFIER UNE ADRESSE ===
        elseif ($action === 'modifier') {
            $id_adresse = (int)$_POST['id_adresse'];
            $adresse = trim($_POST['adresse']);
            $code_postal = trim($_POST['code_postal']);
            $ville = trim($_POST['ville']);
            $pays = trim($_POST['pays']);
            $num_tel = trim($_POST['num_tel'] ?? '');
            
            // Vérifier que l'adresse appartient au client
            $stmt_verif = $pdo->prepare("SELECT id_adresse FROM adresse WHERE id_adresse = :id AND id_client = :client");
            $stmt_verif->execute(['id' => $id_adresse, 'client' => $id_client_connecte]);
            
            if (!$stmt_verif->fetch()) {
                $message_erreur = "Adresse non trouvée.";
            } elseif (empty($adresse) || empty($code_postal) || empty($ville) || empty($pays)) {
                $message_erreur = "Tous les champs obligatoires doivent être remplis.";
            } elseif (!preg_match("/^[0-9]{5}$/", $code_postal)) {
                $message_erreur = "Code postal invalide (5 chiffres requis).";
            } else {
                $stmt_update = $pdo->prepare("
                    UPDATE adresse 
                    SET adresse = :adresse, code_postal = :code_postal, ville = :ville, 
                        pays = :pays, num_tel = :num_tel
                    WHERE id_adresse = :id_adresse AND id_client = :id_client
                ");
                $stmt_update->execute([
                    'adresse' => $adresse,
                    'code_postal' => $code_postal,
                    'ville' => $ville,
                    'pays' => $pays,
                    'num_tel' => $num_tel,
                    'id_adresse' => $id_adresse,
                    'id_client' => $id_client_connecte
                ]);
                
                $message_succes = "Adresse modifiée avec succès !";
            }
        }
        
        // === DÉFINIR COMME ADRESSE PAR DÉFAUT ===
        elseif ($action === 'definir_defaut') {
            $id_adresse = (int)$_POST['id_adresse'];
            
            // Vérifier que l'adresse appartient au client
            $stmt_verif = $pdo->prepare("SELECT id_adresse FROM adresse WHERE id_adresse = :id AND id_client = :client");
            $stmt_verif->execute(['id' => $id_adresse, 'client' => $id_client_connecte]);
            
            if ($stmt_verif->fetch()) {
                // Le trigger se charge automatiquement de retirer le défaut des autres
                $stmt_defaut = $pdo->prepare("
                    UPDATE adresse 
                    SET est_defaut = TRUE 
                    WHERE id_adresse = :id_adresse AND id_client = :id_client
                ");
                $stmt_defaut->execute([
                    'id_adresse' => $id_adresse,
                    'id_client' => $id_client_connecte
                ]);
                
                $message_succes = "Adresse définie par défaut !";
            }
        }
        
        // === SUPPRIMER UNE ADRESSE ===
        elseif ($action === 'supprimer') {
            $id_adresse = (int)$_POST['id_adresse'];
            
            // Vérifier que l'adresse appartient au client
            $stmt_verif = $pdo->prepare("
                SELECT est_defaut 
                FROM adresse 
                WHERE id_adresse = :id AND id_client = :client
            ");
            $stmt_verif->execute(['id' => $id_adresse, 'client' => $id_client_connecte]);
            $adresse_data = $stmt_verif->fetch();
            
            if (!$adresse_data) {
                $message_erreur = "Adresse non trouvée.";
            } else {
                // Si c'est l'adresse par défaut, définir une autre comme défaut
                if ($adresse_data['est_defaut']) {
                    $stmt_autre = $pdo->prepare("
                        SELECT id_adresse 
                        FROM adresse 
                        WHERE id_client = :client AND id_adresse != :id 
                        LIMIT 1
                    ");
                    $stmt_autre->execute(['client' => $id_client_connecte, 'id' => $id_adresse]);
                    $autre_adresse = $stmt_autre->fetch();
                    
                    if ($autre_adresse) {
                        $pdo->prepare("UPDATE adresse SET est_defaut = TRUE WHERE id_adresse = ?")
                            ->execute([$autre_adresse['id_adresse']]);
                    }
                }
                
                // Supprimer l'adresse
                $stmt_delete = $pdo->prepare("DELETE FROM adresse WHERE id_adresse = :id AND id_client = :client");
                $stmt_delete->execute(['id' => $id_adresse, 'client' => $id_client_connecte]);
                
                $message_succes = "Adresse supprimée avec succès !";
            }
        }
        
    } catch (PDOException $e) {
        $message_erreur = "Erreur : " . $e->getMessage();
    }
}

// Récupération de toutes les adresses du client
try {
    $stmt_adresses = $pdo->prepare("
        SELECT * FROM adresse 
        WHERE id_client = :id_client 
        ORDER BY est_defaut DESC, date_creation ASC
    ");
    $stmt_adresses->execute(['id_client' => $id_client_connecte]);
    $adresses = $stmt_adresses->fetchAll(PDO::FETCH_ASSOC);
    $nb_adresses = count($adresses);
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mes adresses</title>
    <meta name="description" content="Gérez vos adresses de livraison">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <style>
        .adresses-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 150px 3rem 2rem 3rem;
        }

        .messages {
            margin-bottom: 1.5rem;
        }

        .message-succes {
            background: #d4edda;
            color: #155724;
            padding: 1.2rem;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .message-erreur {
            background: #f8d7da;
            color: #721c24;
            padding: 1.2rem;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .adresse-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
        }

        .adresse-card.defaut {
            border: 3px solid #ff6ce2;
        }

        .badge-defaut {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: #ff6ce2;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .adresse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
        }

        .adresse-header h3 {
            margin: 0;
            color: #333;
            font-size: 1.4rem;
        }

        .adresse-infos p {
            margin: 0.5rem 0;
            color: #666;
            font-size: 1.05rem;
        }

        .adresse-infos strong {
            font-weight: 600;
            color: #333;
            min-width: 120px;
            display: inline-block;
        }

        .adresse-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.7rem 1.3rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #ff6ce2;
            color: #000;
        }

        .btn-primary:hover {
            background: #ff5cd5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 108, 226, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #ff6ce2;
            color: #ff6ce2;
        }

        .btn-outline:hover {
            background: #ff6ce2;
            color: #000;
            transform: translateY(-2px);
        }

        .btn-profil,
        .btn-compte {
            background: #6c757d;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-profil:hover,
        .btn-compte:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        .form-ajout {
            background: #f9f9f9;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
        }

        .form-ajout h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1.05rem;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff6ce2;
            box-shadow: 0 0 0 3px rgba(255, 108, 226, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .limite-info {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f0f0f0;
            border-radius: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2.5rem;
            border-radius: 12px;
            max-width: 700px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-content h3 {
            margin-top: 0;
            font-size: 1.5rem;
            color: #333;
        }

        /* Responsive pour tablettes */
        @media (max-width: 1200px) {
            .adresses-container {
                max-width: 95%;
                padding: 150px 2rem 2rem 2rem;
            }
        }

        @media (max-width: 768px) {
            .adresses-container {
                padding: 120px 1rem 2rem 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }

            .adresse-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
            
            .badge-defaut {
                position: static;
                display: inline-block;
                margin-bottom: 1rem;
            }
            
            .adresse-card {
                padding: 1.5rem;
            }
            
            .modal-content {
                max-width: 90%;
                margin: 10% auto;
                padding: 1.5rem;
            }
        }

        h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }

        /* Style pour les boutons de retour */
        .navigation-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        @media (max-width: 428px) {
            .adresses-container{
                margin: 0;
                padding: 0 1.5rem 1rem 1.5rem;
            }
            .adresse-header h3 {
                font-size: 1.2rem;
            }
            
            .adresse-infos p {
                font-size: 0.95rem;
            }
            
            .btn {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }
            .btn-compte, .btn-profil{
                margin: auto;
                width: 75%;
            }
            .btn-compte{
                margin-bottom: 50px;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body class="body_profilClient">
    <header class="disabled">
        <?php include 'header.php'?>
    </header>

    <div class="compte__header disabled">
        <a href="compte.php">← </a>Gérer mes adresses
    </div>

    <main class="adresses-container">
        <!-- Messages -->
        <div class="messages">
            <?php if ($message_succes): ?>
                <div class="message-succes"><?= htmlspecialchars($message_succes) ?></div>
            <?php endif; ?>
            <?php if ($message_erreur): ?>
                <div class="message-erreur"><?= htmlspecialchars($message_erreur) ?></div>
            <?php endif; ?>
        </div>

        <!-- Info limite -->
        <div class="limite-info">
            Vous avez <?= $nb_adresses ?> / <?= $LIMITE_ADRESSES ?> adresses enregistrées
        </div>

        <!-- Formulaire d'ajout -->
        <?php if ($nb_adresses < $LIMITE_ADRESSES): ?>
        <div class="form-ajout">
            <h3>Ajouter une nouvelle adresse</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter">
                
                <div class="form-group">
                    <label>Adresse complète *</label>
                    <input type="text" name="adresse" required placeholder="12 Rue de la Liberté">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Code postal *</label>
                        <input type="text" name="code_postal" required placeholder="29000" pattern="[0-9]{5}">
                    </div>
                    <div class="form-group">
                        <label>Ville *</label>
                        <input type="text" name="ville" required placeholder="Quimper">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pays *</label>
                        <input type="text" name="pays" required placeholder="France">
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="num_tel" placeholder="0299123456">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Ajouter cette adresse</button>
            </form>
        </div>
        <?php else: ?>
        <div class="message-erreur">
            Vous avez atteint la limite de <?= $LIMITE_ADRESSES ?> adresses. Supprimez-en une pour en ajouter une nouvelle.
        </div>
        <?php endif; ?>

        <!-- Liste des adresses -->
        <h2>Mes adresses enregistrées</h2>

        <?php if (empty($adresses)): ?>
            <div class="adresse-card">
                <p>Vous n'avez pas encore d'adresse enregistrée.</p>
            </div>
        <?php else: ?>
            <?php foreach ($adresses as $adr): ?>
            <div class="adresse-card <?= $adr['est_defaut'] ? 'defaut' : '' ?>">
                <?php if ($adr['est_defaut']): ?>
                    <span class="badge-defaut">✓ Par défaut</span>
                <?php endif; ?>

                <div class="adresse-header">
                    <h3><?= htmlspecialchars($adr['libelle']) ?></h3>
                </div>

                <div class="adresse-infos">
                    <p><strong>Adresse :</strong> <?= htmlspecialchars($adr['adresse']) ?></p>
                    <p><strong>Code postal :</strong> <?= htmlspecialchars($adr['code_postal']) ?></p>
                    <p><strong>Ville :</strong> <?= htmlspecialchars($adr['ville']) ?></p>
                    <p><strong>Pays :</strong> <?= htmlspecialchars($adr['pays']) ?></p>
                    <?php if ($adr['num_tel']): ?>
                        <p><strong>Téléphone :</strong> <?= htmlspecialchars($adr['num_tel']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="adresse-actions">
                    <button onclick="ouvrirModalModif(<?= $adr['id_adresse'] ?>)" class="btn btn-outline">
                        Modifier
                    </button>

                    <?php if (!$adr['est_defaut']): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="definir_defaut">
                        <input type="hidden" name="id_adresse" value="<?= $adr['id_adresse'] ?>">
                        <button type="submit" class="btn btn-secondary">Définir par défaut</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette adresse ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_adresse" value="<?= $adr['id_adresse'] ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>

            <!-- Modal de modification pour cette adresse -->
            <div id="modal-<?= $adr['id_adresse'] ?>" class="modal">
                <div class="modal-content">
                    <h3>Modifier <?= htmlspecialchars($adr['libelle']) ?></h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="modifier">
                        <input type="hidden" name="id_adresse" value="<?= $adr['id_adresse'] ?>">
                        
                        <div class="form-group">
                            <label>Adresse complète *</label>
                            <input type="text" name="adresse" required value="<?= htmlspecialchars($adr['adresse']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Code postal *</label>
                            <input type="text" name="code_postal" required value="<?= htmlspecialchars($adr['code_postal']) ?>" pattern="[0-9]{5}">
                        </div>

                        <div class="form-group">
                            <label>Ville *</label>
                            <input type="text" name="ville" required value="<?= htmlspecialchars($adr['ville']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Pays *</label>
                            <input type="text" name="pays" required value="<?= htmlspecialchars($adr['pays']) ?>">
                        </div>

                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="num_tel" value="<?= htmlspecialchars($adr['num_tel'] ?? '') ?>">
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <button type="button" onclick="fermerModal(<?= $adr['id_adresse'] ?>)" class="btn btn-secondary">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div>
            <a href="consulterProfilClient.php" class="btn btn-profil">← Retour au profil</a>
        </div>

        <div>
            <a href="compte.php" class="btn btn-compte">← Retour au compte</a>
        </div>
    </main>

    <footer class="footer mobile">
        <?php include 'footer.php'?>
    </footer>

    <script>
        function ouvrirModalModif(id) {
            document.getElementById('modal-' + id).style.display = 'block';
        }

        function fermerModal(id) {
            document.getElementById('modal-' + id).style.display = 'none';
        }

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
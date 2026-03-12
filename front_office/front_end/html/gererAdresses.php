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
            max-width: 860px;
            margin: 0 auto;
            padding: 150px 2rem 4rem 2rem;
        }

        h2 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        /* ── MESSAGES ── */
        .messages { margin-bottom: 1.5rem; }

        .message-succes {
            background: #edfff5;
            color: #1a7a45;
            padding: 1rem 1.2rem;
            border-radius: 12px;
            border-left: 4px solid #2ed573;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .message-erreur {
            background: #fff0f1;
            color: #b02030;
            padding: 1rem 1.2rem;
            border-radius: 12px;
            border-left: 4px solid #ff4757;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        /* ── COMPTEUR ── */
        .limite-info {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff0fc;
            color: #d946ba;
            font-size: 0.88rem;
            font-weight: 700;
            padding: 0.6rem 1.1rem;
            border-radius: 999px;
            border: 1.5px solid #ffd6f7;
            margin-bottom: 2rem;
        }

        /* ── FORMULAIRE AJOUT ── */
        .form-ajout {
            background: #ffffff;
            border: 1.5px solid #ede8f5;
            border-radius: 14px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 2px 8px rgba(180, 80, 200, 0.07);
        }

        .form-ajout h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #1a1a2e;
            letter-spacing: -0.01em;
        }

        .form-group { margin-bottom: 1.1rem; }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4a4a6a;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid #ede8f5;
            border-radius: 10px;
            font-size: 0.97rem;
            color: #1a1a2e;
            background: #fafafa;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff6ce2;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 108, 226, 0.12);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* ── CARDS ── */
        .adresse-card {
            background: #ffffff;
            border: 1.5px solid #ede8f5;
            border-radius: 14px;
            padding: 1.8rem;
            margin-bottom: 1.2rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(180, 80, 200, 0.07);
            transition: box-shadow 0.25s, border-color 0.25s;
        }

        .adresse-card:hover {
            box-shadow: 0 6px 24px rgba(180, 80, 200, 0.12);
        }

        .adresse-card.defaut {
            border-color: #ff6ce2;
            box-shadow: 0 0 0 3px rgba(255, 108, 226, 0.1);
        }

        .badge-defaut {
            position: absolute;
            top: 1.4rem;
            right: 1.4rem;
            background: #ff6ce2;
            color: white;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .adresse-header { margin-bottom: 1rem; }

        .adresse-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #1a1a2e;
        }

        .adresse-infos p {
            margin: 0.35rem 0;
            color: #4a4a6a;
            font-size: 0.93rem;
            line-height: 1.6;
        }

        .adresse-infos strong {
            font-weight: 600;
            color: #1a1a2e;
            min-width: 110px;
            display: inline-block;
        }

        .adresse-actions {
            display: flex;
            gap: 0.6rem;
            margin-top: 1.3rem;
            padding-top: 1.2rem;
            border-top: 1px solid #ede8f5;
            flex-wrap: wrap;
        }

        /* BOUTONS  */
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: transform 0.18s, background 0.18s;
            white-space: nowrap;
        }

        .btn-outline {
            background: #fff0fc;
            border: 1.5px solid #ffd6f7;
            color: #d946ba;
        }
        .btn-outline:hover {
            background: #ffd6f7;
            border-color: #ff6ce2;
            box-shadow: 0 4px 12px rgba(255, 108, 226, 0.2);
        }

        .btn-danger {
            background: #fff0f1;
            border: 1.5px solid #ffc0c5;
            color: #ff4757;
        }
        .btn-danger:hover {
            background: #ff4757;
            border-color: #ff4757;
            color: white;
        }

        /* ── BOUTONS NAVIGATION (en haut) ── */
        .navigation-buttons {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn-profil,
        .btn-compte {
            background: #ffffff;
            border: 1.5px solid #ede8f5;
            color: #4a4a6a;
            padding: 0.6rem 1.2rem;
            border-radius: 9px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 700;
            font-size: 0.88rem;
            transition: all 0.2s;
        }
        .btn-profil:hover,
        .btn-compte:hover {
            border-color: #ff6ce2;
            color: #d946ba;
            background: #fff0fc;
            box-shadow: 0 4px 12px rgba(255, 108, 226, 0.15);
        }

        /* ── MODAL ── */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            inset: 0;
            background: rgba(26, 26, 46, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal[style*="block"] { display: flex !important; }

        .modal-content {
            background: #ffffff;
            border-radius: 18px;
            padding: 2.2rem;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 16px 48px rgba(180, 80, 200, 0.18);
            border: 1.5px solid #ede8f5;
            animation: modalIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalIn {
            from { transform: scale(0.9) translateY(20px); opacity: 0; }
            to   { transform: scale(1) translateY(0); opacity: 1; }
        }

        .modal-content h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a1a2e;
        }

        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .adresses-container { padding: 150px 2rem 2rem 2rem; }
        }

        @media (max-width: 768px) {
            .adresses-container { padding: 120px 1rem 2rem 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .adresse-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .badge-defaut { position: static; display: inline-block; margin-bottom: 1rem; }
            .adresse-card { padding: 1.4rem; }
            .modal-content { max-width: 92%; padding: 1.6rem; margin: 0 1rem; }
            .navigation-buttons { flex-direction: column; }
            .btn-profil, .btn-compte { justify-content: center; }
        }

        @media (max-width: 428px) {
            .adresses-container { padding: 0 1.2rem 2rem 1.2rem; }
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

        <!-- Boutons navigation -->
        <div class="navigation-buttons">
            <a href="consulterProfilClient.php" class="btn-profil">← Retour au profil</a>
            <a href="compte.php" class="btn-compte">← Retour au compte</a>
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
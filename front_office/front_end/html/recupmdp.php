<?php
session_start();
include 'config.php';

$erreur = "";
$succes = "";
$etape = isset($_GET['etape']) ? $_GET['etape'] : 'email';

// Questions de sécurité adaptées aux clients
$questions_securite = [
    "Quel est votre nom de famille ?",
    "Quel est votre prénom ?", 
    "Quel est votre numéro de téléphone ?",
    "Quelle est votre date de naissance ? (format JJ/MM/AAAA)"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if ($etape === 'email') {
            $email = trim($_POST['email']);
            
            // Vérifier si l'email existe et récupérer les infos du client
            $stmt = $pdo->prepare("
                SELECT i.id_num, i.mdp, cc.nom, cc.prenom, cc.num_tel, cc.date_naissance
                FROM public.identifiants i 
                JOIN public.compte_client cc ON i.id_num = cc.id_num 
                WHERE i.login = ?
            ");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();
            
            if ($utilisateur) {
                $_SESSION['email_recuperation'] = $email;
                $_SESSION['ancien_mdp_hash'] = $utilisateur['mdp'];
                
                // Stocker toutes les réponses possibles pour les clients
                $_SESSION['reponses_correctes'] = [
                    strtolower(trim($utilisateur['nom'])),
                    strtolower(trim($utilisateur['prenom'])),
                    strtolower(trim($utilisateur['num_tel'])),
                    date('d/m/Y', strtotime($utilisateur['date_naissance']))
                ];
                
                echo "<script>
                    window.location.href = 'recupmdp.php?etape=question';
                </script>";
                exit();
            } else {
                $erreur = "Aucun compte client avec cet email.";
            }
            
        } elseif ($etape === 'question') {
            $reponse_saisie = strtolower(trim($_POST['reponse_secrete']));
            $question_choisie = $_POST['question_secrete'];
            
            // Trouver l'index de la question choisie
            $index_question = array_search($question_choisie, $questions_securite);
            
            if ($index_question !== false && isset($_SESSION['reponses_correctes'][$index_question])) {
                if ($reponse_saisie === $_SESSION['reponses_correctes'][$index_question]) {
                    echo "<script>
                        window.location.href = 'recupmdp.php?etape=nouveau_mdp';
                    </script>";
                    exit();
                } else {
                    $erreur = "Réponse incorrecte. Veuillez réessayer.";
                }
            } else {
                $erreur = "Question invalide.";
            }
            
        } elseif ($etape === 'nouveau_mdp') {
            $nouveau_mdp = trim($_POST['nouveau_mdp']);
            $confirmation_mdp = trim($_POST['confirmation_mdp']);
            
            // Vérifier la longueur du mot de passe
            if (strlen($nouveau_mdp) < 12) {
                $erreur = "Le mot de passe doit contenir au moins 12 caractères.";
            } 
            // Vérifier que les mots de passe correspondent
            elseif ($nouveau_mdp !== $confirmation_mdp) {
                $erreur = "Les mots de passe ne correspondent pas.";
            } 
            // Vérifier que ce n'est pas l'ancien mot de passe
            elseif (isset($_SESSION['ancien_mdp_hash']) && $nouveau_mdp === $_SESSION['ancien_mdp_hash']) {
                $erreur = "Vous ne pouvez pas utiliser votre ancien mot de passe. Veuillez en choisir un nouveau.";
            } 
            else {
                $email = $_SESSION['email_recuperation'];
                
                // Mettre à jour le mot de passe en clair dans la base de données
                $stmt = $pdo->prepare("UPDATE public.identifiants SET mdp = ? WHERE login = ?");
                $stmt->execute([$nouveau_mdp, $email]);
                
                // Nettoyer les variables de session
                unset($_SESSION['email_recuperation']);
                unset($_SESSION['ancien_mdp_hash']);
                unset($_SESSION['reponses_correctes']);
                
                $succes = "Mot de passe modifié avec succès !";
                $etape = 'termine';
            }
        }
    } catch (PDOException $e) {
        $erreur = "Erreur: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Alizon</title>
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body__connexion">
   
    
    <div class="recuperation-container">
        <div class="header__recuperation">
            <h2>Mot de passe oublié</h2>
        </div>
        
        <?php if (!empty($succes)): ?>
            <div class="succes-message">
                <?= htmlentities($succes) ?>
            </div>
            <a href="connecter.php" class="btn__link">Se connecter</a>
        <?php else: ?>
        
        <form class="form__recuperation" method="POST">
            <?php if (!empty($erreur)): ?>
                <div class="error-message"><?= htmlentities($erreur) ?></div>
            <?php endif; ?>
            
            <?php if ($etape === 'email'): ?>
                <div class="input-group">
                    <label for="email" class="input-label">Votre email</label>
                    <input type="email" id="email" name="email" placeholder="email@exemple.com" required class="input__connexion" value="<?= htmlentities($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn__connexion">Continuer</button>
                
            <?php elseif ($etape === 'question'): ?>
                <div class="input-group">
                    <label for="question_secrete" class="input-label">Choisissez une question de sécurité</label>
                    <select name="question_secrete" id="question_secrete" required class="input__connexion">
                        <option value="">-- Sélectionnez une question --</option>
                        <?php foreach($questions_securite as $question): ?>
                            <option value="<?= htmlentities($question) ?>"><?= htmlentities($question) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="reponse_secrete" class="input-label">Votre réponse</label>
                    <input type="text" id="reponse_secrete" name="reponse_secrete" placeholder="Votre réponse" required class="input__connexion">
                    <small>Répondez avec les informations que vous avez utilisées lors de la création de votre compte</small>
                </div>
                <button type="submit" class="btn__connexion">Vérifier</button>
                
            <?php elseif ($etape === 'nouveau_mdp'): ?>
                <div class="input-group">
                    <label for="nouveau_mdp" class="input-label">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mdp" name="nouveau_mdp" placeholder="12 caractères minimum" required class="input__connexion" minlength="12">
                    <small>Minimum 12 caractères. Ne peut pas être votre ancien mot de passe.</small>
                </div>
                <div class="input-group">
                    <label for="confirmation_mdp" class="input-label">Confirmation</label>
                    <input type="password" id="confirmation_mdp" name="confirmation_mdp" placeholder="Retapez le mot de passe" required class="input__connexion" minlength="12">
                </div>
                <button type="submit" class="btn__connexion">Changer le mot de passe</button>
            <?php endif; ?>
            
            <div class="recuperation-links">
                <a href="seconnecter.php">Retour connexion</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
    use OTPHP\TOTP;
    date_default_timezone_set('Europe/Paris');
    session_start();
    include 'config.php';
    
    require_once '../../../vendor/autoload.php';

    

    

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $erreur_mdp = "";
    $erreur_ident = "";
    $erreur_a2f = ""; 
    $mdp = "";
    $email = "";

    $age_verifie = isset($_COOKIE['age_verifie']) && $_COOKIE['age_verifie'] === '1';
    $attente_a2f = false;
    $delai_attente = 5;

    // Gestion de la vérification d'âge
    if (isset($_POST['verif_age'])) {
        if ($_POST['verif_age'] === 'oui') {
            setcookie('age_verifie', '1', time() + 86400, '/');

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $erreur_age = "Vous devez avoir 18 ans ou plus pour vous connecter.";
        }
    }

    // Gestion de la vérification du code A2F
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['code_a2f'])) {
        $time = time();
        $code_saisi = trim($_POST['code_a2f']);
        // --- AJOUT ICI : On récupère le secret de la session ---
        $secret = $_SESSION['temp_secret'] ?? null;

        if (isset($_SESSION['dernier_envoi'])) {
            $temps_ecoule = $time - $_SESSION['dernier_envoi'];
            if ($temps_ecoule < $delai_attente) {
                $temps_restant = $delai_attente - $temps_ecoule;
                $message = "Trop vite ! Veuillez patienter encore $temps_restant secondes.";
                $erreur_a2f = "Code de vérification incorrect.";
                $attente_a2f = true; 
                $_SESSION["message_erreur"] = $erreur_a2f;
            } else {
                $_SESSION['dernier_envoi'] = $time;
                if ($secret) {
                    $otp = TOTP::createFromSecret($secret);
                    if ($otp->verify($code_saisi)) {
                        // La vérification A2F a réussi, on connecte l'utilisateur
                        // On récupère les infos temporaires stockées en session lors de la première étape
                        if(isset($_SESSION['temp_user'])) {
                            $user = $_SESSION['temp_user'];
                            
                            // Récup panier
                            $panier_sql = $pdo->prepare("SELECT id_panier FROM public.panier WHERE id_num = ?");
                            $panier_sql->execute([$user['id_num']]); 
                            $panier = $panier_sql->fetch();

                            // Connexion définitive
                            $_SESSION['id'] = $user['id_num'];
                            $_SESSION['login'] = $user['login'];
                            $_SESSION['id_client'] = $user['id_client'];
                            $_SESSION['id_panier'] = $panier['id_panier'];
                            $_SESSION["message_success"] = "Connexion avec succès !";
                            // Nettoyage
                            unset($_SESSION['temp_user']);
                            unset($_SESSION['temp_secret']);
                            
                            echo "<script>window.location.href = '/index.php';</script>";
                            exit();
                        }
                    } else {
                        // Code incorrect, on réaffiche la popup A2F avec une erreur
                        $erreur_a2f = "Code de vérification incorrect.";
                        $attente_a2f = true; 
                        $_SESSION["message_erreur"] = $erreur_a2f;
                    }
                }
            }
        } else {
            $_SESSION['dernier_envoi'] = $time;
            if ($secret) {
                $otp = TOTP::createFromSecret($secret);
                if ($otp->verify($code_saisi)) {
                    // La vérification A2F a réussi, on connecte l'utilisateur
                    // On récupère les infos temporaires stockées en session lors de la première étape
                    if(isset($_SESSION['temp_user'])) {
                        $user = $_SESSION['temp_user'];
                        
                        // Récup panier
                        $panier_sql = $pdo->prepare("SELECT id_panier FROM public.panier WHERE id_num = ?");
                        $panier_sql->execute([$user['id_num']]); 
                        $panier = $panier_sql->fetch();

                        // Connexion définitive
                        $_SESSION['id'] = $user['id_num'];
                        $_SESSION['login'] = $user['login'];
                        $_SESSION['id_client'] = $user['id_client'];
                        $_SESSION['id_panier'] = $panier['id_panier'];
                        $_SESSION["message_success"] = "Connexion avec succès !";
                        // Nettoyage
                        unset($_SESSION['temp_user']);
                        unset($_SESSION['temp_secret']);
                        
                        echo "<script>window.location.href = '/index.php';</script>";
                        exit();
                    }
                } else {
                    $erreur_a2f = "Code de vérification incorrect.";
                    $attente_a2f = true; 
                    $_SESSION["message_erreur"] = $erreur_a2f;
                }
            }
        }


    }
    // Connexion normale (Etape 1 : Vérif Login/MDP) uniquement si l'âge est vérifié et qu'on ne traite pas l'A2F
    elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['motdepasse']) && $age_verifie) {
        $mdp = trim($_POST['motdepasse']);
        $email = trim($_POST['adresse_mail']);
        $user_sql = $pdo->prepare("
            SELECT i.id_num, i.login, i.mdp, cv.id_client 
            FROM public.identifiants i 
            JOIN public.compte_client cv ON i.id_num = cv.id_num 
            WHERE i.login = ?
        ");
        $user_sql->execute([$email]);
        $user = $user_sql->fetch();
        
        // 1) LOGIN INCORRECT
        if (!$user) {
            $erreur_ident = "Identifiant incorrect";
            $_SESSION["message_erreur"] = $erreur_ident;
        }
        // 2) MOT DE PASSE INCORRECT
        elseif ($mdp !== $user['mdp']) {
            $erreur_mdp = "Mot de passe incorrect";
            $_SESSION["message_erreur"] = $erreur_mdp;
        }
        // 3) OK → DECLENCHER A2F (Au lieu de connecter direct)
        else {
            // On stocke temporairement les infos de l'utilisateur en session
            // pour finaliser la connexion après la vérification A2F
            $_SESSION['temp_user'] = $user;

            $stmtsecret = $pdo->prepare("SELECT codea2f FROM compte_client WHERE adresse_mail = :adresse_mail");
            $stmtsecret->execute([
                'adresse_mail' => $email
            ]);
            $secret = $stmtsecret->fetchColumn();

            if($secret && strcmp($secret, "") != 0){
                $_SESSION['temp_secret'] = $secret;
                $attente_a2f = true; // On active l'affichage de la popup A2F
            }
            else{

                // Récup panier
                $panier_sql = $pdo->prepare("SELECT id_panier FROM public.panier WHERE id_num = ?");
                $panier_sql->execute([$user['id_num']]); 
                $panier = $panier_sql->fetch();

                // Connexion définitive
                $_SESSION['id'] = $user['id_num'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['id_client'] = $user['id_client'];
                $_SESSION['id_panier'] = $panier['id_panier'];
                $_SESSION["message_success"] = "Connexion avec succès !";
                // Nettoyage
                unset($_SESSION['temp_user']);

                echo "<script>window.location.href = '/index.php';</script>";
                exit();
            }
            
        }
    } 
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se connecter</title>
    <meta name="description" content="Ceci est le profil du compte de notre market place !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
</head>

<body class="body__connexion">

    <?php if (!$age_verifie): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <h2>Vérification d'âge</h2>
            <form method="post" action="">
                <p><strong>Avez-vous plus de 18 ans ?</strong></p>
                <p>Certains de nos produits sont réservés aux personnes majeures...<br>
                    Veuillez donc accepter les conditions d'utilisations ci-dessous.</p>
                <input type="checkbox" required> <label>Accepter les conditions d'utilisations. *</label>
                <br><br>
                <div class="popup-buttons">
                    <button type="submit" name="verif_age" value="oui" class="btn-popup btn-valider" style="width: auto;">Oui</button>
                    <button type="submit" name="verif_age" value="non" class="btn-popup btn-non">Non</button>
                </div>
            </form>
            <?php if (isset($erreur_age)): ?>
                <div class="erreur-msg"><?= htmlspecialchars($erreur_age) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($attente_a2f): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <h2>Double Authentification</h2>
            <form action="" class="form__connexion" method="post" enctype="multipart/form-data">
                <p>Veuillez entrer le code de vérification à 6 chiffres pour sécuriser votre connexion.</p>
                <input type="text" name="code_a2f" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code">
                
                <div class="popup-buttons">
                    <button type="submit" class="btn-popup btn-valider">Vérifier</button>
                </div>
                <div style="margin-top: 15px;">
                     <a href="seconnecter.php" style="color: #666; text-decoration: none; font-size: 14px;">Annuler et retourner à la connexion</a>
                </div>
            </form>

            <?php if (!empty($erreur_a2f)): ?>
                <div class="erreur-msg"><?= htmlspecialchars($erreur_a2f) ?></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="erreur-msg"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>


    <?php if ($attente_a2f): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <h2>Double Authentification</h2>
            
            <form action="" class="form__connexion" method="post" enctype="multipart/form-data">
                <p>Veuillez entrer le code de vérification à 6 chiffres pour sécuriser votre connexion.</p>
                
                <input type="text" name="code_a2f" placeholder="000000" maxlength="6" required autofocus autocomplete="one-time-code">
                
                <div class="popup-buttons">
                    <button type="submit" class="btn-popup btn-valider" >
                        Vérifier
                    </button>
                </div>
                <div style="margin-top: 15px;">
                     <a href="seconnecter.php" style="color: #666; text-decoration: none; font-size: 14px;">Annuler et retourner à la connexion</a>
                </div>
            </form>

            <?php if (!empty($erreur_a2f)): ?>
                <div class="erreur-msg">
                    <?= htmlspecialchars($erreur_a2f) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="erreur-msg">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>


    <div class="container__connexion">
        <div class="header__connexion">
            <h2>Rebonjour</h2>
        </div>
        <form action="" class="form__connexion" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="email" class="input-label">E-mail</label>
                <input class="input__connexion" type="email" name="adresse_mail" placeholder="Votre adresse email" value="<?= htmlspecialchars($email) ?>" required />
                <?php
                    if (!empty($erreur_ident)) {
                        echo "<span style='color:red'>$erreur_ident</span><br />";
                    }
                ?>
            </div>
            <div class="input-group">
                <label for="motdepasse" class="input-label">Mot de passe</label>
                <input class="input__connexion" type="password" name="motdepasse" placeholder="Votre mot de passe" required />
                <?php
                    if (!empty($erreur_mdp)) {
                        echo "<span style='color:red'>$erreur_mdp</span><br />";
                    }
                ?>
            </div>
            <a href="recupmdp.php" class="forgot-password">Mot de passe oublié ?</a>
            <button type="submit" class="btn__connexion">Se connecter</button>
            <div class="separator"><p>ou</p></div>
            <label><a href="createcompte.php" class="btn__creer-compte">Créer compte</a></label>
            <a href="../../../back_office/connecter.php" class="forgot-password">Côté Vendeur</a>
        </form>
    </div>
    <div id="toast-global" class="toast"></div>
    <script src="../assets/js/toast.js"></script>
    <?php if (isset($_SESSION['message_erreur'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                afficherToast("<?php echo addslashes($_SESSION['message_erreur']); ?>", "erreur");
            });
        </script>
        <?php 
            unset($_SESSION['message_erreur']); 
        ?>
    <?php endif; ?>
</body>
</html>
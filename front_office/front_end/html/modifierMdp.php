<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: consulterProfilClient.php");
    exit;
}

$user = $_SESSION['user'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['mot_de_passe']);
    $confirmerPassword = trim($_POST['confirmer_mot_de_passe']);

    if (empty($newPassword) || empty($confirmerPassword)) {
        $erreur = "Les champs ne peuvent pas être vides.";
    } elseif ($newPassword !== $confirmerPassword) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $_SESSION['user']['mdp'] = $newPassword;

        // Et aussi dans la base
        include 'config.php';
        $stmt = $pdo->prepare("UPDATE identifiants SET mdp = ? WHERE id_num = ?");
        $stmt->execute([$newPassword, $_SESSION['id_num']]);

        header("Location: consulterProfilClient.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier mon mot de passe</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
      <h2>Modifier mon mot de passe :</h2>
      <div class="options">
          <form method="POST">
              <label for="mot_de_passe">Nouveau mot de passe :</label>
              <input type="password" name="mot_de_passe" id="mot_de_passe" class="option" required><br><br>

              <label for="confirmer_mot_de_passe">Confirmer mot de passe :</label>
              <input type="password" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" class="option" required><br><br>

              <?php if ($erreur){ ?>
                  <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
              <?php } ?>

              <button type="submit" class="payer-btn">Enregistrer</button>
          </form>
      </div>
      <a href="ConsulterProfilClient.php" style="display:block; margin-top:1rem;">← Retour au profil</a>
    </section>
  </main>
</body>
</html>
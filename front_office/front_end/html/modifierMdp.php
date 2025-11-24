<?php
include 'session.php';
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
        $stmt->execute([$newPassword, $_SESSION['id']]);
        echo "<script>
            window.location.href = 'consulterProfilClient.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Mot de passe - Compte CLient</title>
    <meta name="description" content="Page ou tu peux modifier ton mot de passe du compte client !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
      <a href="consulterProfilClient.php" class="retour-profil">← Retour au profil</a>
      <h2>Modifier mon mot de passe :</h2>
      <div class="options">
          <form method="POST">
              <label for="mot_de_passe">Nouveau mot de passe :</label>
              <input type="password" name="mot_de_passe" id="mot_de_passe" class="input-modify" required><br><br>

              <label for="confirmer_mot_de_passe">Confirmer mot de passe :</label>
              <input type="password" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" class="input-modify" required><br><br>

              <?php if ($erreur){ ?>
                  <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
              <?php } ?>

              <button type="submit" class="payer-btn">Enregistrer</button>
          </form>
      </div>
    </section>
  </main>
</body>
</html>
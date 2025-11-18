<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: consulterProfilClient.php");
    exit;
}

$user = $_SESSION['user'];
$numActuel = $user['telephone'];
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Newtelephone = preg_replace('/\D/', '', $_POST['telephone']); // on garde que les chiffres
    
    if(empty($Newtelephone)){
      $erreur = "Le numéro de téléphone ne peut pas être vide.";
    }
    else if (strlen($Newtelephone) !== 10) {
        $erreur = "Le numéro doit contenir 10 chiffres.";
    } else {
        $_SESSION['user']['telephone'] = $Newtelephone;

        // Et aussi dans la BASE !
        include 'config.php';
        $stmt = $pdo->prepare("UPDATE compte_client SET num_tel = ? WHERE id_client = ?");
        $stmt->execute([$Newtelephone, $_SESSION['id_client']]);
        header("Location: consulterProfilClient.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier mon numéro de téléphone</title>
  <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
      <h2>Modifier mon numéro de téléphone :</h2>
        <div class="options">
          <form method="post">
              <label>Nouvel numéro :</label>
              <input type="tel" name="telephone" class="option" value="<?= htmlspecialchars($numActuel) ?>">
              
              <?php if ($erreur){ ?>
                  <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
              <?php } ?>
              
              <button type="submit" class="payer-btn">Enregistrer</button>
          </form>
        </div>
      <a href="consulterProfilClient.php" style="display:block; margin-top:1rem;">← Retour au profil</a>
    </section>
  </main>
</body>
</html>
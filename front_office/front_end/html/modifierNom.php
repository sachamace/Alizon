<?php 
include 'session.php';

$user = $_SESSION['user'];
$nomActuel = $user['nom'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newNom = trim($_POST['nom']);

    if (empty($newNom)) {
        $erreur = "Le nom ne peut pas être vide.";
    } else {
        // Mise à jour dans la SESSION
        $_SESSION['user']['nom'] = $newNom;

        // Et aussi dans la BASE !
        include 'config.php';
        $stmt = $pdo->prepare("UPDATE compte_client SET nom = ? WHERE id_client = ?");
        $stmt->execute([$newNom, $_SESSION['id']]);

        header("Location: consulterProfilClient.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier mon nom</title>
  <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
      <a href="consulterProfilClient.php" class="retour-profil">← Retour au profil</a>
      <h2>Modifier mon nom</h2>
        <div class="options">
          <form method="POST">
              <label for="nom">Nom :</label>
              <input type="text" id="nom" name="nom" class="input-modify" value="<?= htmlspecialchars($nomActuel) ?>">

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
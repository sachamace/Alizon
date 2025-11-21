<?php 
include 'session.php';
$user = $_SESSION['user'];
$prenomActuel = $user['prenom'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPrenom = trim($_POST['prenom']);

    if (empty($newPrenom)) {
        $erreur = "Le nom ne peut pas être vide.";
    } else {
        // Mise à jour dans la SESSION
        $_SESSION['user']['prenom'] = $newPrenom;

        // Et aussi dans la base
        include 'config.php';
        $stmt = $pdo->prepare("UPDATE compte_client SET prenom = ? WHERE id_client = ?");
        $stmt->execute([$newPrenom, $_SESSION['id']]);

        header("Location: consulterProfilClient.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon prénom - Compte CLient</title>
    <meta name="description" content="Page ou tu peux modifer ton prénom coté compte client !">
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
      <h2>Modifier mon prénom :</h2>
        <div class="options">
          <form method="POST">
              <label for="nom">Prénom :</label>
              <input type="text" id="prenom" name="prenom" class="input-modify" value="<?= htmlspecialchars($prenomActuel) ?>">

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
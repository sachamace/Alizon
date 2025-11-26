<?php 
include 'session.php';
include 'config.php';
$user = $_SESSION['user'];
$nomActuel = $user['nom'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newNom = trim($_POST['nom']);

    if (empty($newNom)) {
        $erreur = "Le nom ne peut pas être vide.";
    } else {
        // Mise à jour dans la SESSION
        $id_client_connecte = $_SESSION['id_client'];

        // Et aussi dans la BASE !
        
        $stmt = $pdo->prepare("UPDATE compte_client SET nom = ? WHERE id_client = ?");
        $stmt->execute([$newNom, $id_client_connecte]);

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
    <title>Modifier mon nom - Compte CLient</title>
    <meta name="description" content="Page ou tu peux modifer ton nom coté compte client !">
    <meta name="keywords" content="MarketPlace, Shopping,Ventes,Breton,Produit" lang="fr">
    <link rel="stylesheet" href="../assets/csss/style.css">
    <!--<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="" crossorigin="anonymous">-->
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
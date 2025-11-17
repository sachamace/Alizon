<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: consulterProfilClient.php");
    exit;
}

$user = $_SESSION['user'];
$adresseActuelle = $user['adresse'];
$codePostalActuel = $user['code_postal'];
$villeActuelle = $user['ville'];
$paysActuel = $user['pays'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newAdresse = trim($_POST['adresse']);
    $newCodePostal = trim($_POST['code_postal']);
    $newVille = trim($_POST['ville']);
    $newPays = trim($_POST['pays']);
    
    if (empty($newAdresse) || empty($newCodePostal) || empty($newVille) || empty($newPays)) {
        $erreur = "Tous les champs doivent être remplis.";
    } elseif (!preg_match('/^\d{5}$/', $newCodePostal)) {
        $erreur = "Le code postal doit contenir 5 chiffres.";
    } else {
        $_SESSION['user']['adresse'] = $newAdresse;
        $_SESSION['user']['code_postal'] = $newCodePostal;
        $_SESSION['user']['ville'] = $newVille;
        $_SESSION['user']['pays'] = $newPays;

        include 'config.php';
        $stmt = $pdo->prepare("UPDATE adresse SET adresse = ? WHERE id_client = ?");
        $stmt->execute([$newAdresse, $_SESSION['id_client']]);

        $stmt = $pdo->prepare("UPDATE adresse SET code_postal = ? WHERE id_client = ?");
        $stmt->execute([$newCodePostal, $_SESSION['id_client']]);

        $stmt = $pdo->prepare("UPDATE adresse SET ville = ? WHERE id_client = ?");
        $stmt->execute([$newVille, $_SESSION['id_client']]);

        $stmt = $pdo->prepare("UPDATE adresse SET pays = ? WHERE id_client = ?");
        $stmt->execute([$newPays, $_SESSION['id_client']]);

        header("Location: consulterProfilClient.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier mon e-mail</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
        <h2>Modifier mon adresse de livraison et facturation :</h2>
        <div class="options">
            <form method="POST">
                <label for="adresse">Adresse :</label>
                <input type="text" name="adresse" id="adresse" class="option" value="<?= htmlspecialchars($adresseActuelle) ?>" required><br><br>

                <label for="code_postal">Code Postal :</label>
                <input type="text" name="code_postal" id="code_postal" class="option" value="<?= htmlspecialchars($codePostalActuel) ?>" maxlength="5" required><br><br>

                <label for="ville">Ville :</label>
                <input type="text" name="ville" id="ville" class="option" value="<?= htmlspecialchars($villeActuelle) ?>" required><br><br>

                <label for="pays">Pays :</label>
                <input type="text" name="pays" id="pays" class="option" value="<?= htmlspecialchars($paysActuel) ?>" required><br><br>

                <?php if ($erreur) {?>
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
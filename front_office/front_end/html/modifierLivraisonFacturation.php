<?php
include 'session.php';

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
        $stmt->execute([$newAdresse, $_SESSION['id']]);

        $stmt = $pdo->prepare("UPDATE adresse SET code_postal = ? WHERE id_client = ?");
        $stmt->execute([$newCodePostal, $_SESSION['id']]);

        $stmt = $pdo->prepare("UPDATE adresse SET ville = ? WHERE id_client = ?");
        $stmt->execute([$newVille, $_SESSION['id']]);

        $stmt = $pdo->prepare("UPDATE adresse SET pays = ? WHERE id_client = ?");
        $stmt->execute([$newPays, $_SESSION['id']]);
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
    <title>Modifier livraison Facturation - Compte CLient</title>
    <meta name="description" content="Page ou tu peux modifier livraison et facturation du compte client !">
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
        <h2>Modifier mon adresse de livraison / facturation :</h2>
        <div class="options">
            <form method="POST">
                <label for="adresse">Adresse :</label>
                <input type="text" name="adresse" id="adresse" class="input-modify" value="<?= htmlspecialchars($adresseActuelle) ?>" required><br><br>

                <label for="code_postal">Code Postal :</label>
                <input type="text" name="code_postal" id="code_postal" class="input-modify" value="<?= htmlspecialchars($codePostalActuel) ?>" maxlength="5" required><br><br>

                <label for="ville">Ville :</label>
                <input type="text" name="ville" id="ville" class="input-modify" value="<?= htmlspecialchars($villeActuelle) ?>" required><br><br>

                <label for="pays">Pays :</label>
                <input type="text" name="pays" id="pays" class="input-modify" value="<?= htmlspecialchars($paysActuel) ?>" required><br><br>

                <?php if ($erreur) {?>
                    <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
                <?php } ?>

                <button type="submit" class="payer-btn">Enregistrer</button>
            </form>
        </div>
      
    </section>
  </main>
</body>
</html>
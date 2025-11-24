<?php
include 'session.php';

$user = $_SESSION['user'];
$dateActuel = $user['date_naissance'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDate = trim($_POST['date_naissance']);

    if (empty($newDate)) {
        $erreur = "La date de naissance ne peut pas être vide.";
    } else {
        $dateSoumise = strtotime($newDate);
        $dateAujourdHui = strtotime(date('Y-m-d'));

        if ($dateSoumise > $dateAujourdHui) {
            $erreur = "La date de naissance ne peut pas être dans le futur.";
        } else {
            $_SESSION['user']['date_naissance'] = $newDate;
            include 'config.php';
            $stmt = $pdo->prepare("UPDATE compte_client SET date_naissance = ? WHERE id_client = ?");
            $stmt->execute([$newDate, $_SESSION['id']]);
            echo "<script>
                window.location.href = 'consulterProfilClient.php';
            </script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifer Date de Naissance - Compte Client</title>
    <meta name="description" content="Page pour modifier la date de naissance du compte client !">
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
        <h2>Modifier ma date de naissance : </h2>
        <div class="options">
          <form method="post">
            <label>Nouvel date :</label>
            <input type="date" name="date_naissance" class="input-modify" value="<?= htmlspecialchars($dateActuel) ?>" required>

            <?php if (!empty($erreur)){ ?>
            <p style="color:red;"><?= htmlspecialchars($erreur) ?></p>
            <?php } ?>

            <button type="submit" class="payer-btn">Enregistrer</button>
          </form>
        </div>
    </section>
  </main>
</body>
</html>
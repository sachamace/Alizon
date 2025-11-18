<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: consulterProfilClient.php");
    exit;
}

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
            $stmt->execute([$newDate, $_SESSION['id_client']]);
            
            header("Location: consulterProfilClient.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier ma date de naissance</title>
  <link rel="stylesheet" href="../assets/csss/style.css">
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
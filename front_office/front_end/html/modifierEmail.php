<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: consulterProfilClient.php");
    exit;
}

include 'config.php';

$user = $_SESSION['user'];
$emailActuel = $user['email'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = trim($_POST['email']);

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse e-mail invalide.";
    } else {

        // 🔹 Mise à jour de l'email dans compte_client
        $stmt = $pdo->prepare("UPDATE compte_client SET adresse_mail = ? WHERE id_client = ?");
        $stmt->execute([$newEmail, $_SESSION['id_client']]);

        // 🔹 Mise à jour du login dans identifiants
        $stmt = $pdo->prepare("UPDATE identifiants SET login = ? WHERE id_num = ?");
        $stmt->execute([$newEmail, $user['id_num']]);

        // 🔹 Recharger les infos depuis la BDD pour mettre la session à jour
        $stmt = $pdo->prepare("
            SELECT 
                cc.nom,
                cc.prenom,
                cc.date_naissance,
                cc.adresse_mail AS email,
                cc.num_tel AS telephone,
                id.mdp,
                id.login,
                id.id_num,
                a.adresse,
                a.code_postal,
                a.ville,
                a.pays
            FROM compte_client cc
            LEFT JOIN identifiants id ON cc.id_num = id.id_num
            LEFT JOIN adresse a ON cc.id_client = a.id_client
            WHERE cc.id_client = ?
        ");
        $stmt->execute([$_SESSION['id_client']]);

        $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

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
  <link rel="stylesheet" href="../assets/csss/style.css">
</head>
<body class="body_profilClient">
  <main class="main_profilClient">
    <section class="bloc donneePerso">
        <h2>Modifier mon adresse e-mail</h2>
        <div class="options">
          <form method="post">
              <label>Nouvel e-mail :</label>
              <input type="email" name="email" class="option" value="<?= htmlspecialchars($emailActuel) ?>" required>

              <?php if (!empty($erreur)){ ?>
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
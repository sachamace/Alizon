<?php
include 'session.php';
include 'config.php';

$user = $_SESSION['user'];
$emailActuel = $user['email'];
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail = trim($_POST['email']);

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse e-mail invalide.";
    } else {
        // ✅ CORRECTION : Utiliser id_client au lieu de id
        $id_client_connecte = $_SESSION['id_client'];

        // 🔹 Mise à jour de l'email dans compte_client
        $stmt = $pdo->prepare("UPDATE compte_client SET adresse_mail = ? WHERE id_client = ?");
        $stmt->execute([$newEmail, $id_client_connecte]);

        // 🔹 Récupérer l'id_num pour mettre à jour identifiants
        $stmt = $pdo->prepare("SELECT id_num FROM compte_client WHERE id_client = ?");
        $stmt->execute([$id_client_connecte]);
        $id_num = $stmt->fetchColumn();

        // 🔹 Mise à jour du login dans identifiants (seulement si id_num existe)
        if ($id_num) {
            $stmt = $pdo->prepare("UPDATE identifiants SET login = ? WHERE id_num = ?");
            $stmt->execute([$newEmail, $id_num]);
        }

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
        $stmt->execute([$id_client_connecte]);

        $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

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
    <title>Modifier Email - Compte CLient</title>
    <meta name="description" content="Page ou tu peux modifier l'email du compte client !">
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
        <h2>Modifier mon adresse e-mail</h2>
        <div class="options">
          <form method="post">
              <label>Nouvel e-mail :</label>
              <input type="email" name="email" class="input-modify" value="<?= htmlspecialchars($emailActuel) ?>" required>

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
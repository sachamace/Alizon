<?php
session_start();
include 'config.php';

// Vérifier si l'utilisateur est connecté, sinon rediriger vers la page de connexion
if (!isset($_SESSION['est_connecte']) || $_SESSION['est_connecte'] !== true) {
    header("Location: connecter.php");
    exit();
}

if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = '';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Alizon</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">

</head>

<body>
    <div class="dashboard">
        <?php include 'sidebar.php' ?>

        <main>
            <?php include 'topbar.php' ?>

            <?php
            if ($page !== '') {
                $file = "content/$page.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<p>Page introuvable</p>";
                }
            }
            ?>
        </main>
    </div>
</body>

</html>
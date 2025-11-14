
<?php
    include 'config.php';

    $stmt = $pdo->query("SELECT version();");
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil d'un vendeur</title>
    <link rel="stylesheet" href="front_end/assets/css/style.css">

</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php' ?>

        <main>
            <?php include 'header.php' ?>
            <div>
                <h2>Informations Générales</h2>
                
            </div>
        </main>
    </div>
</body>

</html>
<?php
// test.php - page index pour tester le côté session
session_start();
include 'session.php';

// Fonctions utilitaires
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Test Session - index</title>
</head>
<body>
    <h1>Test des sessions PHP</h1>

    <div class="box">
        <strong>Données courantes</strong>
        <p>Nom (session["id"]) : <em><?php echo $_SESSION['id'] ?></em></p>
    </div>

    <div class="box">
        <strong>Dump complet de $_SESSION</strong>
        <p>Nom (session["login"]) : <pre><?php echo $_SESSION['login'] ?></pre>
    </div>

    <p>Utilisez cette page pour tester la persistance des données entre requêtes, la regénération d'ID et la destruction de session.</p>
</body>
</html>
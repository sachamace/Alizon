<?php
include 'config.php';

$id_vendeur = $_SESSION['vendeur_id'];

$date_debut = $_GET['date_debut'] ?? date('Y-01-01');
$date_fin   = $_GET['date_fin']   ?? date('Y-m-d');
$id_produit = $_GET['id_produit'] ?? 'tous';
$vue        = $_GET['vue']        ?? 'produit';

$stmt = $pdo->prepare("SELECT id_produit, nom_produit FROM produit WHERE id_vendeur = ? ORDER BY nom_produit");
$stmt->execute([$id_vendeur]);
$liste_produits = $stmt->fetchAll();


if ($vue === 'categorie') {
    $params = [$id_vendeur, $date_debut, $date_fin];
    $stmt = $pdo->prepare("
        SELECT p.categorie AS nom_produit,
               SUM(lc.quantite) AS volume_total,
               ROUND(SUM(lc.prix_unitaire_ttc * lc.quantite)::numeric, 2) AS montant_total
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        JOIN commande c ON lc.id_commande = c.id_commande
        WHERE p.id_vendeur = ?
          AND c.date_commande::date BETWEEN ? AND ?
        GROUP BY p.categorie
        ORDER BY montant_total DESC
    ");
} else {
    $params = [$id_vendeur, $date_debut, $date_fin];
    $filtre = "";
    if ($id_produit !== 'tous') {
        $filtre = "AND p.id_produit = ?";
        $params[] = $id_produit;
    }
    $stmt = $pdo->prepare("
        SELECT p.nom_produit,
               SUM(lc.quantite) AS volume_total,
               ROUND(SUM(lc.prix_unitaire_ttc * lc.quantite)::numeric, 2) AS montant_total
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        JOIN commande c ON lc.id_commande = c.id_commande
        WHERE p.id_vendeur = ?
          AND c.date_commande::date BETWEEN ? AND ?
        GROUP BY p.id_produit, p.nom_produit
        ORDER BY montant_total DESC
    ");
}

$stmt->execute($params);
$stats = $stmt->fetchAll();

$total_volume  = array_sum(array_column($stats, 'volume_total'));
$total_montant = array_sum(array_column($stats, 'montant_total'));

$labels_bar   = json_encode(array_column($stats, 'nom_produit'));
$data_volume  = json_encode(array_column($stats, 'volume_total'));
$data_montant = json_encode(array_column($stats, 'montant_total'));
?>

<div class="stats-page">
    <h2>Statistiques de ventes</h2>
    <p class="stats-subtitle">Performances par produit et par periode</p>

    <form method="GET" action="" class="stats-filtres">
        <input type="hidden" name="page" value="statistiques">
        <div class="filtre-group">
            <label>Date debut</label>
            <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
        </div>
        <div class="filtre-group">
            <label>Date fin</label>
            <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
        </div>
        <div class="filtre-group">
            <label>Vue</label>
            <select name="vue" onchange="this.form.submit()">
                <option value="produit"   <?= $vue === 'produit'   ? 'selected' : '' ?>>Par produit</option>
                <option value="categorie" <?= $vue === 'categorie' ? 'selected' : '' ?>>Par categorie</option>
            </select>
        </div>
        <?php if ($vue === 'produit'): ?>
        <div class="filtre-group">
            <label>Produit</label>
            <select name="id_produit">
                <option value="tous" <?= $id_produit === 'tous' ? 'selected' : '' ?>>Tous</option>
                <?php foreach ($liste_produits as $p): ?>
                    <option value="<?= $p['id_produit'] ?>" <?= $id_produit == $p['id_produit'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nom_produit']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit">Appliquer</button>
    </form>

    <div class="stats-kpi">
        <div class="kpi-card kpi-volume">
            <div class="kpi-label">Unites vendues</div>
            <div class="kpi-value"><?= number_format($total_volume, 0, ',', ' ') ?></div>
            <div class="kpi-sub">sur la periode</div>
        </div>
        <div class="kpi-card kpi-montant">
            <div class="kpi-label">Chiffre d affaires TTC</div>
            <div class="kpi-value"><?= number_format($total_montant, 2, ',', ' ') ?> euros</div>
            <div class="kpi-sub">sur la periode</div>
        </div>
        <div class="kpi-card kpi-produits">
            <div class="kpi-label"><?= $vue === 'categorie' ? 'Categories' : 'Produits' ?> vendus</div>
            <div class="kpi-value"><?= count($stats) ?></div>
            <div class="kpi-sub">sur la periode</div>
        </div>
    </div>

    <?php if (empty($stats)): ?>
        <p class="stats-no-data">Aucune vente sur cette periode.</p>
    <?php else: ?>

    <div class="stats-charts">
        <div class="chart-card">
            <h3>Volume <?= $vue === 'categorie' ? 'par categorie' : 'par produit' ?></h3>
            <canvas id="chartVolume"></canvas>
        </div>
        <div class="chart-card">
            <h3>CA TTC <?= $vue === 'categorie' ? 'par categorie' : 'par produit' ?></h3>
            <canvas id="chartMontant"></canvas>
        </div>
    </div>

    <div class="stats-table-wrap">
        <h3>Detail</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th><?= $vue === 'categorie' ? 'Categorie' : 'Produit' ?></th>
                    <th>Unites</th>
                    <th>CA TTC</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nom_produit']) ?></td>
                    <td><?= $s['volume_total'] ?></td>
                    <td><?= number_format($s['montant_total'], 2, ',', ' ') ?> euro</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labelsBar   = <?= $labels_bar ?>;
const dataVolume  = <?= $data_volume ?>;
const dataMontant = <?= $data_montant ?>;

const couleurs = ['#6c63ff','#22c55e','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#a855f7','#f97316','#84cc16'];

if (labelsBar.length > 0) {
    new Chart(document.getElementById('chartVolume'), {
        type: 'bar',
        data: {
            labels: labelsBar,
            datasets: [{ label: 'Unites', data: dataVolume, backgroundColor: labelsBar.map((_, i) => couleurs[i % couleurs.length]) }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartMontant'), {
        type: 'doughnut',
        data: { labels: labelsBar, datasets: [{ data: dataMontant, backgroundColor: labelsBar.map((_, i) => couleurs[i % couleurs.length]) }] }
    });
}
</script>
<?php
// statistiques.php - Page de statistiques de ventes du vendeur connecte
// Affiche : KPI, graphes (barres, donut, ligne, comparaison), tableau de detail
include 'config.php';

// Recuperation de l identifiant du vendeur connecte depuis la session
$id_vendeur = $_SESSION['vendeur_id'];

// Parametres de filtrage recuperes depuis l URL (avec valeurs par defaut)
$date_debut = $_GET['date_debut'] ?? date('Y-01-01'); // par defaut : 1er janvier de l annee en cours
$date_fin   = $_GET['date_fin']   ?? date('Y-m-d');   // par defaut : aujourd hui
$id_produit = $_GET['id_produit'] ?? 'tous';           // filtre produit (optionnel)
$vue        = $_GET['vue']        ?? 'produit';        // produit ou categorie
$cmp_a      = $_GET['cmp_a']      ?? '';               // element A pour la comparaison
$cmp_b      = $_GET['cmp_b']      ?? '';               // element B pour la comparaison

// Liste des produits du vendeur pour le select du formulaire
$stmt = $pdo->prepare("SELECT id_produit, nom_produit FROM produit WHERE id_vendeur = ? ORDER BY nom_produit");
$stmt->execute([$id_vendeur]);
$liste_produits = $stmt->fetchAll();

// Requete principale : stats agregees par produit ou par categorie selon la vue
if ($vue === 'categorie') {
    // Vue categorie : on groupe par categorie, pas de filtre individuel
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
    // Vue produit : filtre optionnel sur un produit specifique
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
          $filtre
        GROUP BY p.id_produit, p.nom_produit
        ORDER BY montant_total DESC
    ");
}

$stmt->execute($params);
$stats = $stmt->fetchAll();

// Graphe ligne : quantite vendue jour par jour
// Visible uniquement si un produit est selectionne (vue produit) ou en vue categorie
$ligne_data = [];
if ($vue === 'produit' && $id_produit !== 'tous') {
    // Un seul produit selectionne : on trace ses ventes jour par jour
    $s = $pdo->prepare("
        SELECT c.date_commande::date AS jour, SUM(lc.quantite) AS qte
        FROM ligne_commande lc
        JOIN commande c ON lc.id_commande = c.id_commande
        WHERE lc.id_produit = ?
          AND c.date_commande::date BETWEEN ? AND ?
        GROUP BY jour ORDER BY jour ASC
    ");
    $s->execute([$id_produit, $date_debut, $date_fin]);
    foreach ($s->fetchAll() as $r) $ligne_data[$r['jour']] = $r['qte'];
} elseif ($vue === 'categorie') {
    // Vue categorie : on trace toutes les ventes du vendeur jour par jour
    $s = $pdo->prepare("
        SELECT c.date_commande::date AS jour, SUM(lc.quantite) AS qte
        FROM ligne_commande lc
        JOIN produit p ON lc.id_produit = p.id_produit
        JOIN commande c ON lc.id_commande = c.id_commande
        WHERE p.id_vendeur = ?
          AND c.date_commande::date BETWEEN ? AND ?
        GROUP BY jour ORDER BY jour ASC
    ");
    $s->execute([$id_vendeur, $date_debut, $date_fin]);
    foreach ($s->fetchAll() as $r) $ligne_data[$r['jour']] = $r['qte'];
}
$ligne_labels = json_encode(array_keys($ligne_data));
$ligne_qtes   = json_encode(array_values($ligne_data));

// Comparaison entre 2 produits ou 2 categories
// Ne s execute que si les deux elements sont selectionnes
$cmp_data = [];
if ($cmp_a !== '' && $cmp_b !== '') {
    if ($vue === 'categorie') {
        $s = $pdo->prepare("
            SELECT p.categorie AS label,
                   SUM(lc.quantite) AS volume_total,
                   ROUND(SUM(lc.prix_unitaire_ttc * lc.quantite)::numeric, 2) AS montant_total
            FROM ligne_commande lc
            JOIN produit p ON lc.id_produit = p.id_produit
            JOIN commande c ON lc.id_commande = c.id_commande
            WHERE p.id_vendeur = ?
              AND c.date_commande::date BETWEEN ? AND ?
              AND p.categorie IN (?, ?)
            GROUP BY p.categorie
        ");
        $s->execute([$id_vendeur, $date_debut, $date_fin, $cmp_a, $cmp_b]);
    } else {
        $s = $pdo->prepare("
            SELECT p.nom_produit AS label,
                   SUM(lc.quantite) AS volume_total,
                   ROUND(SUM(lc.prix_unitaire_ttc * lc.quantite)::numeric, 2) AS montant_total
            FROM ligne_commande lc
            JOIN produit p ON lc.id_produit = p.id_produit
            JOIN commande c ON lc.id_commande = c.id_commande
            WHERE p.id_vendeur = ?
              AND c.date_commande::date BETWEEN ? AND ?
              AND p.id_produit IN (?, ?)
            GROUP BY p.id_produit, p.nom_produit
        ");
        $s->execute([$id_vendeur, $date_debut, $date_fin, $cmp_a, $cmp_b]);
    }
    foreach ($s->fetchAll() as $r) {
        $cmp_data[$r['label']] = $r;
    }
}

// Totaux pour les KPI
$total_volume  = array_sum(array_column($stats, 'volume_total'));
$total_montant = array_sum(array_column($stats, 'montant_total'));

// Serialisation des donnees en JSON pour Chart.js
$labels_bar   = json_encode(array_column($stats, 'nom_produit'));
$cmp_labels   = json_encode(array_keys($cmp_data));
$cmp_volumes  = json_encode(array_values(array_column($cmp_data, 'volume_total')));
$cmp_montants = json_encode(array_values(array_column($cmp_data, 'montant_total')));
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
            <label>Type</label>
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

    <?php if (!empty($ligne_data)): ?>
    <div class="stats-charts" style="grid-template-columns: 1fr; margin-bottom: 1.2rem;">
        <div class="chart-card">
            <h3>Ventes par jour</h3>
            <canvas id="chartLigne"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <div class="chart-card" style="margin-bottom: 1.5rem;">
        <h3>Comparaison</h3>
        <form method="GET" action="" class="stats-filtres" style="margin-bottom: 1.2rem; padding: 0; background: none;">
            <input type="hidden" name="page" value="statistiques">
            <input type="hidden" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>">
            <input type="hidden" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>">
            <input type="hidden" name="vue" value="<?= htmlspecialchars($vue) ?>">
            <input type="hidden" name="id_produit" value="<?= htmlspecialchars($id_produit) ?>">
            <div class="filtre-group">
                <label>Element A</label>
                <select name="cmp_a">
                    <option value="">--</option>
                    <?php if ($vue === 'categorie'): ?>
                        <?php foreach (array_unique(array_column($stats, 'nom_produit')) as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $cmp_a === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($liste_produits as $p): ?>
                            <option value="<?= $p['id_produit'] ?>" <?= $cmp_a == $p['id_produit'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nom_produit']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="filtre-group">
                <label>Element B</label>
                <select name="cmp_b">
                    <option value="">--</option>
                    <?php if ($vue === 'categorie'): ?>
                        <?php foreach (array_unique(array_column($stats, 'nom_produit')) as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $cmp_b === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($liste_produits as $p): ?>
                            <option value="<?= $p['id_produit'] ?>" <?= $cmp_b == $p['id_produit'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nom_produit']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit">Comparer</button>
        </form>
        <?php if (!empty($cmp_data)): ?>
        <canvas id="chartComparaison"></canvas>
        <?php else: ?>
        <p style="color: #999; font-size: 0.9rem;">Selectionnez deux elements pour afficher la comparaison.</p>
        <?php endif; ?>
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
// Donnees injectees depuis PHP
const labelsBar   = <?= $labels_bar ?>;   // noms des produits ou categories
const dataVolume  = <?= $data_volume ?>;  // quantites vendues par produit
const dataMontant = <?= $data_montant ?>; // CA TTC par produit

// Palette de couleurs partagee par tous les graphes
// Le % couleurs.length permet de boucler si on a plus de produits que de couleurs
const couleurs = ['#6c63ff','#22c55e','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#a855f7','#f97316','#84cc16'];

if (labelsBar.length > 0) {
    // Graphe barres : volume par produit/categorie
    new Chart(document.getElementById('chartVolume'), {
        type: 'bar',
        data: {
            labels: labelsBar,
            datasets: [{ label: 'Unites', data: dataVolume, backgroundColor: labelsBar.map((_, i) => couleurs[i % couleurs.length]), maxBarThickness: 60 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Graphe donut : repartition du CA TTC par produit/categorie
    new Chart(document.getElementById('chartMontant'), {
        type: 'doughnut',
        data: { labels: labelsBar, datasets: [{ data: dataMontant, backgroundColor: labelsBar.map((_, i) => couleurs[i % couleurs.length]) }] }
    });
}

// Graphe ligne : ventes jour par jour
// Visible seulement si un produit est selectionne ou en vue categorie
const ligneLabels = <?= $ligne_labels ?? '[]' ?>;
const ligneQtes   = <?= $ligne_qtes ?? '[]' ?>;

// Conversion des dates ISO (2026-03-04) en labels lisibles (Mer 4/3)
const jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
const ligneJours = ligneLabels.map(d => {
    const date = new Date(d);
    return jours[date.getDay()] + ' ' + date.getDate() + '/' + (date.getMonth() + 1);
});

if (ligneLabels.length > 0) {
    new Chart(document.getElementById('chartLigne'), {
        type: 'line',
        data: {
            labels: ligneJours,
            datasets: [{
                label: 'Unites vendues',
                data: ligneQtes,
                borderColor: couleurs[0],
                backgroundColor: couleurs[0] + '22', // '22' en hex = ~13% opacite pour le fill
                fill: true,
                tension: 0.3,  // legere courbe sur la ligne
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
        }
    });
}

// Graphe comparaison : barres groupees cote a cote pour 2 produits/categories
const cmpLabels   = <?= $cmp_labels ?? '[]' ?>;   // noms des 2 elements compares
const cmpVolumes  = <?= $cmp_volumes ?? '[]' ?>;  // volumes des 2 elements
const cmpMontants = <?= $cmp_montants ?? '[]' ?>; // CA des 2 elements

if (cmpLabels.length === 2) {
    new Chart(document.getElementById('chartComparaison'), {
        type: 'bar',
        data: {
            labels: ['Unites vendues', 'CA TTC'],
            datasets: [
                { label: cmpLabels[0], data: [cmpVolumes[0], cmpMontants[0]], backgroundColor: couleurs[0], maxBarThickness: 80 },
                { label: cmpLabels[1], data: [cmpVolumes[1], cmpMontants[1]], backgroundColor: couleurs[1], maxBarThickness: 80 }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });
}
</script>
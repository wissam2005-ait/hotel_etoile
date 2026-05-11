<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); exit;
}

$user_id = $_SESSION['user_id'];

// Annulation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $res_id = (int)$_GET['cancel'];
    $stmt = $conn->prepare("SELECT statut FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $res_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res && $res['statut'] === 'en_attente') {
        $stmt = $conn->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Réservation annulée avec succès.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Impossible d\'annuler cette réservation.'];
    }
    header('Location: my_reservations.php'); exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$reservations = $conn->query("
    SELECT r.*, c.numero AS chambre_num, c.type AS chambre_type,
           c.image AS chambre_image, c.prix AS prix_nuit
    FROM reservations r
    JOIN chambres c ON r.chambre_id = c.id
    WHERE r.user_id = $user_id
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$stats = ['total'=>0,'en_attente'=>0,'confirmee'=>0,'terminee'=>0,'annulee'=>0,'depense'=>0];
foreach ($reservations as $r) {
    $stats['total']++;
    $stats[$r['statut']]++;
    if (in_array($r['statut'], ['confirmee','terminee'])) $stats['depense'] += $r['prix_total'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Réservations — Hôtel Etoile</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="reservation_client.css">
        <link rel="stylesheet" href="theme.css">  

</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a href="index.php" class="logo">
        <div class="logo-gem">✦</div>
        <span class="logo-text">Hôtel Etoile</span>
    </a>
    <div class="nav-links">
        <a href="booking.php" class="nav-link primary"><i class="bi bi-calendar-plus"></i> Réserver</a>
        <a href="notifications.php" class="nav-link"><i class="bi bi-bell"></i></a>
        <a href="profile.php" class="nav-link"><i class="bi bi-person"></i> <?= htmlspecialchars($_SESSION['prenom']) ?></a>
        <a href="logout.php" class="nav-link"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="hero-tag"><i class="bi bi-clock-history"></i> Historique</div>
    <h1>Mes <em>Réservations</em></h1>
    <p class="hero-sub">Retrouvez l'ensemble de vos séjours à l'Hôtel Etoile</p>
    <p class="hero-user">Connecté en tant que <strong><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></strong></p>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-item">
        <div class="stat-num"><?= $stats['total'] ?></div>
        <div class="stat-lbl">Total</div>
    </div>
    <div class="stat-item">
        <div class="stat-num" style="color:#b7791f"><?= $stats['en_attente'] ?></div>
        <div class="stat-lbl">En attente</div>
    </div>
    <div class="stat-item">
        <div class="stat-num" style="color:#2e7d32"><?= $stats['confirmee'] ?></div>
        <div class="stat-lbl">Confirmées</div>
    </div>
    <div class="stat-item">
        <div class="stat-num" style="color:#888"><?= $stats['terminee'] ?></div>
        <div class="stat-lbl">Terminées</div>
    </div>
    <div class="stat-item">
        <div class="stat-num" style="color:#c0392b"><?= $stats['annulee'] ?></div>
        <div class="stat-lbl">Annulées</div>
    </div>
    <div class="stat-item">
        <div class="stat-num" style="font-size:1.1rem"><?= number_format($stats['depense'],0,',',' ') ?> DA</div>
        <div class="stat-lbl">Total dépensé</div>
    </div>
</div>

<!-- Flash -->
<?php if ($flash): ?>
<div class="flash flash-<?= $flash['type'] ?>">
    <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filters-bar">
    <button class="flt-btn active" data-filter="all">Toutes (<?= $stats['total'] ?>)</button>
    <button class="flt-btn" data-filter="en_attente">⏳ En attente (<?= $stats['en_attente'] ?>)</button>
    <button class="flt-btn" data-filter="confirmee">✅ Confirmées (<?= $stats['confirmee'] ?>)</button>
    <button class="flt-btn" data-filter="terminee">🏁 Terminées (<?= $stats['terminee'] ?>)</button>
    <button class="flt-btn" data-filter="annulee">❌ Annulées (<?= $stats['annulee'] ?>)</button>
</div>

<!-- Table -->
<div class="table-wrap">
<?php if (empty($reservations)): ?>
    <div class="empty">
        <i class="bi bi-calendar-x"></i>
        <p>Vous n'avez aucune réservation pour le moment.</p>
        <a href="booking.php" class="btn-book"><i class="bi bi-calendar-check"></i> Faire une réservation</a>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Chambre</th>
                <th>Dates</th>
                <th>Personnes</th>
                <th>Statut</th>
                <th>Montant</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="reservBody">
        <?php
        $badges = [
            'en_attente' => ['class'=>'badge-pending','icon'=>'bi-hourglass-split','label'=>'En attente'],
            'confirmee'  => ['class'=>'badge-confirm','icon'=>'bi-check-circle-fill','label'=>'Confirmée'],
            'annulee'    => ['class'=>'badge-cancel', 'icon'=>'bi-x-circle-fill',    'label'=>'Annulée'],
            'terminee'   => ['class'=>'badge-done',   'icon'=>'bi-flag-fill',         'label'=>'Terminée'],
        ];
        foreach ($reservations as $r):
            $nights = max(1, round((strtotime($r['date_fin']) - strtotime($r['date_debut'])) / 86400));
            $b = $badges[$r['statut']];
        ?>
        <tr data-status="<?= $r['statut'] ?>">
            <td style="color:var(--muted);font-size:0.78rem">#<?= $r['id'] ?></td>

            <td>
                <div class="room-cell">
                    <div class="room-thumb">
                        <?php if (!empty($r['chambre_image']) && file_exists("uploads/chambres/".$r['chambre_image'])): ?>
                        <img src="uploads/chambres/<?= htmlspecialchars($r['chambre_image']) ?>" alt="">
                        <?php else: ?>🚪<?php endif; ?>
                    </div>
                    <div>
                        <div class="room-name">N°<?= htmlspecialchars($r['chambre_num']) ?></div>
                        <div class="room-type-tag"><?= htmlspecialchars($r['chambre_type']) ?></div>
                    </div>
                </div>
            </td>

            <td class="dates-cell">
                <div class="date-line"><i class="bi bi-box-arrow-in-right"></i> <?= date('d/m/Y', strtotime($r['date_debut'])) ?></div>
                <div class="date-line"><i class="bi bi-box-arrow-right"></i> <?= date('d/m/Y', strtotime($r['date_fin'])) ?></div>
                <span class="nights-pill"><?= $nights ?> nuit<?= $nights>1?'s':'' ?></span>
            </td>

            <td style="text-align:center">
                <i class="bi bi-people" style="color:var(--gold)"></i>
                <?= $r['nb_personnes'] ?>
            </td>

            <td>
                <span class="badge <?= $b['class'] ?>">
                    <i class="bi <?= $b['icon'] ?>"></i>
                    <?= $b['label'] ?>
                </span>
            </td>

            <td>
                <div class="price-val"><?= number_format($r['prix_total'],0,',',' ') ?> DA</div>
                <div class="price-sub"><?= number_format($r['prix_nuit'],0,',',' ') ?> DA × <?= $nights ?> nuit<?= $nights>1?'s':'' ?></div>
            </td>

            <td>
                <?php if ($r['statut'] === 'en_attente'): ?>
                <a href="my_reservations.php?cancel=<?= $r['id'] ?>"
                   class="btn-annuler"
                   onclick="return confirm('Annuler cette réservation ?')">
                    <i class="bi bi-x-lg"></i> Annuler
                </a>
                <?php else: ?>
                <span style="color:var(--muted);font-size:0.75rem">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<script>
document.querySelectorAll('.flt-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.flt-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const f = this.dataset.filter;
        document.querySelectorAll('#reservBody tr').forEach(row => {
            row.style.display = (f === 'all' || row.dataset.status === f) ? '' : 'none';
        });
    });
});
</script>
<script src="theme.js"></script>
</body>
</html>
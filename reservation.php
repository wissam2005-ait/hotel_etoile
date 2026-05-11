<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$notifs_count = compterNotificationsNonLues($conn, $_SESSION['user_id']);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$filter = $_GET['filter'] ?? 'tous';
$where = '';
if ($filter === 'attente')   $where = "WHERE r.statut = 'en_attente'";
if ($filter === 'confirmee') $where = "WHERE r.statut = 'confirmee'";
if ($filter === 'annulee')   $where = "WHERE r.statut = 'annulee'";
if ($filter === 'terminee')  $where = "WHERE r.statut = 'terminee'";

$reservations = $conn->query("
    SELECT r.*, u.nom, u.prenom, u.email,
           c.numero AS chambre_num, c.type AS chambre_type, c.image AS chambre_image
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    $where
    ORDER BY FIELD(r.statut,'en_attente','confirmee','annulee','terminee'), r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$counts = [
    'tous'      => $conn->query("SELECT COUNT(*) FROM reservations")->fetch_row()[0],
    'attente'   => $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetch_row()[0],
    'confirmee' => $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmee'")->fetch_row()[0],
    'annulee'   => $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='annulee'")->fetch_row()[0],
    'terminee'  => $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='terminee'")->fetch_row()[0],
];

$statut_labels = ['en_attente'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée','terminee'=>'Terminée'];
$statut_class  = ['en_attente'=>'badge-pending','confirmee'=>'badge-confirm','annulee'=>'badge-cancel','terminee'=>'badge-done'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations — Hôtel Etoile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .hamburger { display:none; flex-direction:column; gap:5px; cursor:pointer; padding:6px; background:none; border:none; }
        .hamburger span { display:block; width:24px; height:2px; background:var(--text-dark); border-radius:2px; transition:all 0.3s; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:199; }
        .sidebar-overlay.open { display:block; }
        @media (max-width:900px) {
            .hamburger { display:flex; }
            .sidebar { display:flex !important; transform:translateX(-100%); transition:transform 0.3s ease; z-index:300; }
            .sidebar.open { transform:translateX(0); }
            .admin-layout { margin-left:0 !important; }
        }
        @media (max-width:640px) {
            .admin-main { padding:12px !important; }
            .res-card { flex-direction:column; gap:10px; }
            .res-thumb { width:100%; height:120px; border-radius:10px; }
            .res-right { flex-direction:row; justify-content:space-between; align-items:center; width:100%; }
            .res-meta { flex-direction:column; gap:4px; }
            .filter-group { gap:4px; }
            .filter-btn { padding:5px 8px; font-size:11px; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">✦</div>
        <span>Hôtel Etoile</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"   class="sidebar-link">⊞ Dashboard</a>
        <a href="rooms.php"       class="sidebar-link">🚪 Chambres</a>
        <a href="reservation.php" class="sidebar-link active">📅 Réservations</a>
        <a href="clients.php"     class="sidebar-link">👥 Clients</a>
        <a href="notifications.php" class="sidebar-link">
            🔔 Notifications
            <?php if ($notifs_count > 0): ?><span class="sidebar-badge"><?= $notifs_count ?></span><?php endif; ?>
        </a>
        <hr class="sidebar-sep">
    </nav>
    <a href="logout.php" class="sidebar-logout">⬡ Déconnexion</a>
</div>

<div class="admin-layout">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:8px;">
            <button class="hamburger" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <h5>📅 Réservations</h5>
        </div>
        <div class="topbar-right">
            <a href="notifications.php" class="notif-btn">
                🔔<?php if ($notifs_count > 0): ?><span class="notif-dot"><?= $notifs_count ?></span><?php endif; ?>
            </a>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['prenom'],0,1)) ?></div>
        </div>
    </div>

    <div class="admin-main">
        <?php if ($flash): ?>
        <div class="flash flash-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger') ?>">
            <?= $flash['msg'] ?>
        </div>
        <?php endif; ?>

        <div class="filter-group" style="margin-bottom:20px">
            <a href="?filter=tous"      class="filter-btn <?= $filter==='tous'     ?'active':'' ?>">Tous (<?= $counts['tous'] ?>)</a>
            <a href="?filter=attente"   class="filter-btn <?= $filter==='attente'  ?'active':'' ?>">⏳ En attente (<?= $counts['attente'] ?>)</a>
            <a href="?filter=confirmee" class="filter-btn <?= $filter==='confirmee'?'active':'' ?>">✅ Confirmées (<?= $counts['confirmee'] ?>)</a>
            <a href="?filter=annulee"   class="filter-btn <?= $filter==='annulee'  ?'active':'' ?>">❌ Annulées (<?= $counts['annulee'] ?>)</a>
            <a href="?filter=terminee"  class="filter-btn <?= $filter==='terminee' ?'active':'' ?>">🏁 Terminées (<?= $counts['terminee'] ?>)</a>
        </div>

        <?php if (empty($reservations)): ?>
        <div class="empty-state" style="background:#fff;border:1px solid var(--gold-border);border-radius:var(--radius-lg)">
            <div class="icon">📋</div>
            <p>Aucune réservation dans cette catégorie.</p>
        </div>
        <?php else: ?>
        <?php foreach ($reservations as $r): ?>
        <div class="res-card <?= $r['statut'] ?>">
            <div class="res-thumb">
                <?php if (!empty($r['chambre_image']) && file_exists("uploads/chambres/".$r['chambre_image'])): ?>
                <img src="uploads/chambres/<?= htmlspecialchars($r['chambre_image']) ?>">
                <?php else: ?>🚪<?php endif; ?>
            </div>
            <div class="res-info">
                <div class="res-room">Chambre N°<?= htmlspecialchars($r['chambre_num']) ?> — <?= htmlspecialchars($r['chambre_type']) ?></div>
                <div class="res-meta">
                    <span>👤 <?= htmlspecialchars($r['prenom'].' '.$r['nom']) ?></span>
                    <span>✉ <?= htmlspecialchars($r['email']) ?></span>
                    <span>📅 <?= $r['date_debut'] ?> → <?= $r['date_fin'] ?></span>
                    <span>👥 <?= $r['nb_personnes'] ?> pers.</span>
                    <span style="color:var(--text-light)">Créée le <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                </div>
                <?php if ($r['message']): ?>
                <div style="font-size:12px;color:var(--text-mid);font-style:italic;margin-bottom:8px">"<?= htmlspecialchars($r['message']) ?>"</div>
                <?php endif; ?>
                <?php if ($r['statut'] === 'en_attente'): ?>
                <div class="res-actions">
                    <form method="POST" action="confirmer_reservation.php" style="display:inline">
                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="action" value="confirmer">
                        <button type="submit" class="action-btn action-confirm">✓ Confirmer</button>
                    </form>
                    <form method="POST" action="confirmer_reservation.php" style="display:inline">
                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="action" value="annuler">
                        <input type="hidden" name="raison" value="">
                        <button type="submit" class="action-btn action-cancel" onclick="return confirm('Annuler cette réservation ?')">✕ Annuler</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="res-right">
                <span class="badge <?= $statut_class[$r['statut']] ?>"><?= $statut_labels[$r['statut']] ?></span>
                <div class="res-amount">
                    <?= number_format($r['prix_total'], 0, ',', ' ') ?> DA
                    <small>Réservation #<?= $r['id'] ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('open');
}
</script>
<script src="theme.js"></script>
</body>
</html>
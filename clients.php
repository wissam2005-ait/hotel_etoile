<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id AND role='client'");
    header('Location: clients.php?deleted=1'); exit;
}

$search = trim($_GET['search'] ?? '');
$where  = "WHERE role='client'";
if ($search) $where .= " AND (nom LIKE '%".addslashes($search)."%' OR prenom LIKE '%".addslashes($search)."%' OR email LIKE '%".addslashes($search)."%')";

$clients      = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM reservations WHERE user_id=u.id) AS nb_resa, (SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE user_id=u.id AND statut IN ('confirmee','terminee')) AS total_depense FROM users u $where ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$notifs_count = compterNotificationsNonLues($conn, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients — Hôtel Etoile</title>
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
            .topbar-date { display:none; }
            .admin-main { padding:12px !important; }
            .he-table { min-width:550px; }
            .search-input { max-width:100%; }
            .table-card { overflow-x:auto; }
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
        <a href="reservation.php" class="sidebar-link">📅 Réservations</a>
        <a href="clients.php"     class="sidebar-link active">👥 Clients</a>
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
            <h5>👥 Gestion des Clients</h5>
        </div>
        <div class="topbar-right">
            <span class="badge badge-type"><?= count($clients) ?> client(s)</span>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['prenom'],0,1)) ?></div>
        </div>
    </div>

    <div class="admin-main">
        <?php if (isset($_GET['deleted'])): ?>
        <div class="flash flash-warning">✓ Client supprimé avec succès.</div>
        <?php endif; ?>

        <div class="table-card" style="margin-bottom:16px;padding:12px 20px">
            <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="text" name="search" class="search-input" placeholder="Rechercher par nom, prénom ou email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-outline-gold btn-sm">🔍 Rechercher</button>
                <?php if ($search): ?>
                <a href="clients.php" class="btn btn-outline-dark btn-sm">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <div style="overflow-x:auto">
                <table class="he-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Réservations</th>
                            <th>Dépenses totales</th>
                            <th>Membre depuis</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $c): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="client-avatar"><?= strtoupper(substr($c['prenom'],0,1)) ?></div>
                                    <div>
                                        <div style="font-weight:600"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?></div>
                                        <div style="font-size:11px;color:var(--text-light)">ID #<?= $c['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--text-mid)"><?= htmlspecialchars($c['email']) ?></td>
                            <td><span class="badge badge-type"><?= $c['nb_resa'] ?> résa.</span></td>
                            <td style="color:var(--gold);font-weight:600"><?= number_format($c['total_depense'], 0, ',', ' ') ?> DA</td>
                            <td style="color:var(--text-mid);font-size:13px"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="clients.php?delete=<?= $c['id'] ?>" class="icon-btn danger"
                                   onclick="return confirm('Supprimer ce client et toutes ses réservations ?')" title="Supprimer">🗑</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:32px">Aucun client trouvé.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
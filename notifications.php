<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notifications WHERE id=$id AND user_id=$user_id");
    header('Location: notifications.php'); exit;
}
if (isset($_GET['clear'])) {
    $conn->query("DELETE FROM notifications WHERE user_id=$user_id");
    header('Location: notifications.php'); exit;
}

$conn->query("UPDATE notifications SET lu=1 WHERE user_id=$user_id AND lu=0");
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$back   = $role === 'admin' ? 'dashboard.php' : 'booking.php';

$notif_icons   = ['success'=>'✅','danger'=>'❌','info'=>'📢','warning'=>'⚠️'];
$notifs_count_admin = ($role === 'admin') ? compterNotificationsNonLues($conn, $user_id) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — Hôtel Etoile

    </title>
    <link rel="stylesheet" href="style.css">
            <link rel="stylesheet" href="theme.css">  

</head>
<body>

<?php if ($role === 'admin'): ?>
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">✦</div>
        <span>Hôtel Etoile</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"   class="sidebar-link">⊞ Dashboard</a>
        <a href="rooms.php"       class="sidebar-link">🚪 Chambres</a>
        <a href="reservation.php" class="sidebar-link">📅 Réservations</a>
        <a href="clients.php"     class="sidebar-link">👥 Clients</a>
        <a href="notifications.php" class="sidebar-link active">🔔 Notifications</a>
        <hr class="sidebar-sep">
    </nav>
    <a href="logout.php" class="sidebar-logout">⬡ Déconnexion</a>
</div>
<div class="admin-layout">
    <div class="topbar">
        <h5>🔔 Notifications</h5>
        <?php if (!empty($notifs)): ?>
        <a href="notifications.php?clear=1" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer toutes les notifications ?')">🗑 Tout effacer</a>
        <?php endif; ?>
    </div>
    <div class="admin-main">

<?php else: ?>
<div class="client-nav">
    <a href="<?= $back ?>" class="back-btn">← Retour</a>
    <div class="brand"><span>✦</span> Mes Notifications</div>
    <div class="client-nav-right">
        <?php if (!empty($notifs)): ?>
        <a href="notifications.php?clear=1" class="btn btn-danger btn-sm" onclick="return confirm('Effacer toutes les notifications ?')">🗑 Tout effacer</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-outline-dark btn-sm">Déconnexion</a>
    </div>
</div>
<div style="max-width:720px;margin:0 auto;padding:28px 24px">
<?php endif; ?>

    <?php if (empty($notifs)): ?>
    <div class="empty-state" style="background:#fff;border:1px solid var(--gold-border);border-radius:var(--radius-lg);margin-top:8px">
        <div class="icon">🔕</div>
        <p style="font-size:16px;color:var(--text-mid)">Aucune notification</p>
        <p style="font-size:13px;color:var(--text-light)">Vous serez notifié ici de toute activité.</p>
        <a href="<?= $back ?>" class="btn btn-outline-gold btn-sm" style="margin-top:14px">← Retour</a>
    </div>
    <?php else: ?>
    <?php foreach ($notifs as $n): ?>
    <div class="notif-card <?= $n['type'] ?>">
        <div class="notif-icon"><?= $notif_icons[$n['type']] ?? 'ℹ️' ?></div>
        <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                <h6 style="font-family:'Jost',sans-serif;font-weight:600;font-size:14px;margin-bottom:4px"><?= htmlspecialchars($n['titre']) ?></h6>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                    <span style="font-size:11px;color:var(--text-light);white-space:nowrap"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                    <a href="notifications.php?delete=<?= $n['id'] ?>" style="color:var(--text-light);font-size:16px;line-height:1" title="Supprimer">✕</a>
                </div>
            </div>
            <p style="font-size:13px;color:var(--text-mid);margin:0"><?= $n['message'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

<?php if ($role === 'admin'): ?>
    </div>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<script src="theme.js"></script>
</body>
</html>

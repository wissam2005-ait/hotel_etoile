<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$stats = [];
$stats['chambres_total']          = $conn->query("SELECT COUNT(*) FROM chambres")->fetch_row()[0];
$stats['chambres_dispo']          = $conn->query("SELECT COUNT(*) FROM chambres WHERE statut='disponible'")->fetch_row()[0];
$stats['chambres_occupees']       = $conn->query("SELECT COUNT(*) FROM chambres WHERE statut='occupee'")->fetch_row()[0];
$stats['reservations_attente']    = $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetch_row()[0];
$stats['reservations_confirmees'] = $conn->query("SELECT COUNT(*) FROM reservations WHERE statut='confirmee'")->fetch_row()[0];
$stats['clients']                 = $conn->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetch_row()[0];
$stats['revenus']                 = $conn->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut IN ('confirmee','terminee')")->fetch_row()[0];

$reservations = $conn->query("
    SELECT r.*, u.nom, u.prenom, u.email,
           c.numero AS chambre_num, c.type AS chambre_type, c.image AS chambre_image
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN chambres c ON r.chambre_id = c.id
    ORDER BY FIELD(r.statut,'en_attente','confirmee','annulee','terminee'), r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$notifs_count = compterNotificationsNonLues($conn, $_SESSION['user_id']);

$statut_labels = ['en_attente'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée','terminee'=>'Terminée'];
$statut_class  = ['en_attente'=>'badge-pending','confirmee'=>'badge-confirm','annulee'=>'badge-cancel','terminee'=>'badge-done'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Hôtel Etoile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        /* ── Hamburger ── */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 6px;
            background: none;
            border: none;
        }
        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--text-dark);
            border-radius: 2px;
            transition: all 0.3s;
        }

        /* ── Overlay mobile ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 199;
        }
        .sidebar-overlay.open {
            display: block;
        }

        /* ── Res card responsive ── */
        @media (max-width: 900px) {
            .hamburger { display: flex; }
            .sidebar {
                display: flex !important;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 300;
            }
            .sidebar.open {
                transform: translateX(0);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }
            .stat-card {
                padding: 12px 10px;
            }
            .stat-val { font-size: 20px; }
            .stat-val.sm { font-size: 14px; }
            .stat-icon { font-size: 18px; width: 36px; height: 36px; }
            .stat-label { font-size: 10px; }

            .res-card {
                flex-direction: column;
                gap: 10px;
            }
            .res-thumb {
                width: 100%;
                height: 120px;
                border-radius: 10px;
            }
            .res-right {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            .res-meta {
                flex-direction: column;
                gap: 4px;
            }
            .res-actions {
                flex-wrap: wrap;
            }
            .topbar-date { display: none; }
            .admin-main { padding: 12px; }
        }
    </style>
</head>
<body>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ── -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">✦</div>
        <span>Hôtel Etoile</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"     class="sidebar-link active">⊞ Dashboard</a>
        <a href="rooms.php"         class="sidebar-link">🚪 Chambres</a>
        <a href="reservation.php"   class="sidebar-link">📅 Réservations</a>
        <a href="clients.php"       class="sidebar-link">👥 Clients</a>
        <a href="notifications.php" class="sidebar-link">
            🔔 Notifications
            <?php if ($notifs_count > 0): ?>
            <span class="sidebar-badge"><?= $notifs_count ?></span>
            <?php endif; ?>
        </a>
        <hr class="sidebar-sep">
    </nav>
    <a href="logout.php" class="sidebar-logout">⬡ Déconnexion</a>
</div>

<!-- ── MAIN ── -->
<div class="admin-layout">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <div>
                <h5>Tableau de bord</h5>
                <div style="font-size:12px;color:var(--text-mid);margin-top:2px">
                    Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                </div>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><?= date('d/m/Y H:i') ?></span>
            <a href="notifications.php" class="notif-btn">
                🔔
                <?php if ($notifs_count > 0): ?>
                <span class="notif-dot"><?= $notifs_count ?></span>
                <?php endif; ?>
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

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['chambres_total'] ?></div><div class="stat-label">Chambres totales</div></div>
                <div class="stat-icon">🚪</div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['chambres_dispo'] ?></div><div class="stat-label">Disponibles</div></div>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['chambres_occupees'] ?></div><div class="stat-label">Occupées</div></div>
                <div class="stat-icon">🔴</div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['reservations_attente'] ?></div><div class="stat-label">En attente</div></div>
                <div class="stat-icon">⏳</div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['reservations_confirmees'] ?></div><div class="stat-label">Confirmées</div></div>
                <div class="stat-icon">📅</div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $stats['clients'] ?></div><div class="stat-label">Clients</div></div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-card" style="grid-column:span 2">
                <div>
                    <div class="stat-val sm"><?= number_format($stats['revenus'], 0, ',', ' ') ?> DA</div>
                    <div class="stat-label">Revenus totaux (confirmées + terminées)</div>
                </div>
                <div class="stat-icon">💰</div>
            </div>
        </div>

        <!-- Réservations -->
        <div class="table-card-header" style="background:#fff;border:1px solid var(--gold-border);border-radius:var(--radius-lg) var(--radius-lg) 0 0;padding:16px 20px;margin-top:8px">
            <h6>📋 Toutes les Réservations</h6>
            <span class="badge badge-type"><?= count($reservations) ?> réservation(s)</span>
        </div>

        <?php if (empty($reservations)): ?>
        <div class="empty-state" style="background:#fff;border:1px solid var(--gold-border);border-top:none;border-radius:0 0 var(--radius-lg) var(--radius-lg);padding:48px">
            <div class="icon">📋</div>
            <p>Aucune réservation pour le moment.</p>
        </div>
        <?php else: ?>
        <div style="background:#fff;border:1px solid var(--gold-border);border-top:none;border-radius:0 0 var(--radius-lg) var(--radius-lg);padding:16px 20px">
            <?php foreach ($reservations as $r): ?>
            <div class="res-card <?= $r['statut'] ?>">
                <div class="res-thumb">
                    <?php if (!empty($r['chambre_image']) && file_exists("uploads/chambres/".$r['chambre_image'])): ?>
                    <img src="uploads/chambres/<?= htmlspecialchars($r['chambre_image']) ?>">
                    <?php else: ?>
                    🚪
                    <?php endif; ?>
                </div>
                <div class="res-info">
                    <div class="res-room">Chambre N°<?= htmlspecialchars($r['chambre_num']) ?></div>
                    <div class="res-type"><?= htmlspecialchars($r['chambre_type']) ?></div>
                    <div class="res-meta">
                        <span>👤 <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></span>
                        <span>✉ <?= htmlspecialchars($r['email']) ?></span>
                        <span>📅 <?= $r['date_debut'] ?> → <?= $r['date_fin'] ?></span>
                        <span>👥 <?= $r['nb_personnes'] ?> pers.</span>
                    </div>
                    <?php if ($r['message']): ?>
                    <div style="font-size:12px;color:var(--text-mid);font-style:italic;margin-bottom:8px">
                        "<?= htmlspecialchars($r['message']) ?>"
                    </div>
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
                            <button type="submit" class="action-btn action-cancel"
                                    onclick="return confirm('Annuler cette réservation ?')">✕ Annuler</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="res-right">
                    <span class="badge <?= $statut_class[$r['statut']] ?>"><?= $statut_labels[$r['statut']] ?></span>
                    <div class="res-amount">
                        <?= number_format($r['prix_total'], 0, ',', ' ') ?> DA
                        <small>Réservé le <?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
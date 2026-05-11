<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $numero      = trim($_POST['numero']);
    $type        = trim($_POST['type']);
    $prix        = (float)$_POST['prix'];
    $capacite    = (int)$_POST['capacite'];
    $description = htmlspecialchars(trim($_POST['description']));
    $statut      = $_POST['statut'];
    $image_name  = '';

    if (!empty($_FILES['image']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            $error = "Format d'image non supporté.";
        } else {
            $image_name = uniqid('ch_') . '.' . $ext;
            $dest = "uploads/chambres/" . $image_name;
            if (!is_dir("uploads/chambres")) mkdir("uploads/chambres", 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $error = "Erreur upload."; $image_name = '';
            }
        }
    }
    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO chambres (numero, type, prix, capacite, description, image, statut) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssdisss", $numero, $type, $prix, $capacite, $description, $image_name, $statut);
        $stmt->execute(); $stmt->close();
        $success = "Chambre ajoutée avec succès !";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id          = (int)$_POST['id'];
    $numero      = trim($_POST['numero']);
    $type        = trim($_POST['type']);
    $prix        = (float)$_POST['prix'];
    $capacite    = (int)$_POST['capacite'];
    $description = htmlspecialchars(trim($_POST['description']));
    $statut      = $_POST['statut'];
    $image_sql = '';
    if (!empty($_FILES['image']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $image_name = uniqid('ch_') . '.' . $ext;
            $dest = "uploads/chambres/" . $image_name;
            if (!is_dir("uploads/chambres")) mkdir("uploads/chambres", 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_sql = ", image='$image_name'";
            }
        }
    }
    $conn->query("UPDATE chambres SET numero='".addslashes($numero)."', type='".addslashes($type)."', prix=$prix, capacite=$capacite, description='".addslashes($description)."', statut='$statut'$image_sql WHERE id=$id");
    $success = "Chambre mise à jour.";
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $ch = $conn->query("SELECT image, statut FROM chambres WHERE id=$id")->fetch_assoc();
    if ($ch && $ch['statut'] === 'occupee') {
        $error = "Impossible de supprimer une chambre occupée.";
    } else {
        if ($ch && $ch['image'] && file_exists("uploads/chambres/" . $ch['image'])) unlink("uploads/chambres/" . $ch['image']);
        $conn->query("DELETE FROM chambres WHERE id=$id");
        $success = "Chambre supprimée.";
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $ch = $conn->query("SELECT statut FROM chambres WHERE id=$id")->fetch_assoc();
    $new = $ch['statut'] === 'disponible' ? 'maintenance' : 'disponible';
    $conn->query("UPDATE chambres SET statut='$new' WHERE id=$id");
    header('Location: rooms.php'); exit;
}

if (isset($_GET['liberer'])) {
    $conn->query("UPDATE chambres SET statut='disponible' WHERE id IN (SELECT chambre_id FROM reservations WHERE statut='confirmee' AND date_fin < CURDATE())");
    $conn->query("UPDATE reservations SET statut='terminee' WHERE statut='confirmee' AND date_fin < CURDATE()");
    $success = "Chambres expirées libérées avec succès.";
}

$chambres     = $conn->query("SELECT * FROM chambres ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$notifs_count = compterNotificationsNonLues($conn, $_SESSION['user_id']);
$statut_class = ['disponible'=>'badge-dispo','occupee'=>'badge-occupied','maintenance'=>'badge-maint'];
$statut_label = ['disponible'=>'✓ Disponible','occupee'=>'● Occupée','maintenance'=>'⚠ Maintenance'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chambres — Hôtel Etoile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        .preview-img { width:100%; height:150px; object-fit:cover; border-radius:10px; display:none; margin-bottom:8px; }
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
            .rooms-form-grid { display:block !important; }
            .form-card { margin-top:20px; }
            .table-card { overflow-x:auto; }
            .he-table { min-width:550px; }
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
        <a href="rooms.php"       class="sidebar-link active">🚪 Chambres</a>
        <a href="reservation.php" class="sidebar-link">📅 Réservations</a>
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
            <h5>🚪 Gestion des Chambres</h5>
        </div>
        <div class="topbar-right">
            <span class="badge badge-type"><?= count($chambres) ?> chambre(s)</span>
            <a href="notifications.php" class="notif-btn">
                🔔<?php if ($notifs_count > 0): ?><span class="notif-dot"><?= $notifs_count ?></span><?php endif; ?>
            </a>
            <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['prenom'],0,1)) ?></div>
        </div>
    </div>

    <div class="admin-main">
        <a href="rooms.php?liberer=1" class="btn btn-outline-gold btn-sm" style="margin-bottom:16px;display:inline-flex;"
           onclick="return confirm('Libérer toutes les chambres dont la période est terminée ?')">
            🔓 Libérer les chambres expirées
        </a>

        <?php if ($success): ?>
        <div class="flash flash-success">✓ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="flash flash-danger">⚠ <?= $error ?></div>
        <?php endif; ?>

        <div class="rooms-form-grid" style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
            <div class="table-card">
                <div class="table-card-header">
                    <h6>Liste des chambres</h6>
                </div>
                <div style="overflow-x:auto">
                    <table class="he-table">
                        <thead>
                            <tr>
                                <th>Photo</th><th>N°</th><th>Type</th>
                                <th>Prix/nuit</th><th>Capacité</th><th>Statut</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chambres as $c): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($c['image']) && file_exists("uploads/chambres/".$c['image'])): ?>
                                    <img src="uploads/chambres/<?= htmlspecialchars($c['image']) ?>" class="table-thumb">
                                    <?php else: ?>
                                    <div class="table-thumb-ph">🚪</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($c['numero']) ?></strong></td>
                                <td><?= htmlspecialchars($c['type']) ?></td>
                                <td style="color:var(--gold);font-weight:600"><?= number_format($c['prix'],0,',',' ') ?> DA</td>
                                <td><?= $c['capacite'] ?> pers.</td>
                                <td><span class="badge <?= $statut_class[$c['statut']] ?>"><?= $statut_label[$c['statut']] ?></span></td>
                                <td>
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <a href="rooms.php?toggle=<?= $c['id'] ?>" class="icon-btn" title="Changer statut">⇄</a>
                                        <button class="icon-btn" onclick="openEdit(<?= htmlspecialchars(json_encode($c)) ?>)" title="Modifier">✏</button>
                                        <a href="rooms.php?delete=<?= $c['id'] ?>" class="icon-btn danger" onclick="return confirm('Supprimer cette chambre ?')" title="Supprimer">🗑</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($chambres)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--text-light);padding:32px">Aucune chambre</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-card">
                <h6>➕ Ajouter une chambre</h6>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="ajouter">
                    <div class="form-group">
                        <label class="form-label">Numéro de chambre</label>
                        <input type="text" name="numero" class="form-control no-icon" placeholder="ex: 101" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control">
                            <option value="Simple">Simple</option>
                            <option value="Double">Double</option>
                            <option value="Suite">Suite</option>
                            <option value="Prestige">Prestige</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Prix / nuit (DA)</label>
                            <input type="number" name="prix" class="form-control no-icon" placeholder="2500" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Capacité</label>
                            <input type="number" name="capacite" class="form-control no-icon" placeholder="1" min="1" max="10" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:14px">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Description de la chambre..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-control">
                            <option value="disponible">Disponible</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo</label>
                        <img class="preview-img" id="preview-add">
                        <input type="file" name="image" class="form-control no-icon" accept="image/*" onchange="previewImg(this, 'preview-add')">
                    </div>
                    <button type="submit" class="btn btn-gold btn-w100">➕ Ajouter la chambre</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div class="modal-header">
            <h5>✏ Modifier la chambre</h5>
            <button class="modal-close" onclick="closeEdit()">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Numéro</label>
                        <input type="text" name="numero" id="edit-numero" class="form-control no-icon" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Type</label>
                        <select name="type" id="edit-type" class="form-control">
                            <option value="Simple">Simple</option>
                            <option value="Double">Double</option>
                            <option value="Suite">Suite</option>
                            <option value="Prestige">Prestige</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Prix (DA)</label>
                        <input type="number" name="prix" id="edit-prix" class="form-control no-icon" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Capacité</label>
                        <input type="number" name="capacite" id="edit-capacite" class="form-control no-icon" min="1" max="10">
                    </div>
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit-description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" id="edit-statut" class="form-control">
                        <option value="disponible">Disponible</option>
                        <option value="occupee">Occupée</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nouvelle photo</label>
                    <img class="preview-img" id="preview-edit">
                    <input type="file" name="image" class="form-control no-icon" accept="image/*" onchange="previewImg(this, 'preview-edit')">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark btn-sm" onclick="closeEdit()">Annuler</button>
                <button type="submit" class="btn btn-gold btn-sm">💾 Enregistrer</button>
            </div>
        </form>
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
function previewImg(input, previewId) {
    const img = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
        r.readAsDataURL(input.files[0]);
    }
}
function openEdit(c) {
    document.getElementById('edit-id').value          = c.id;
    document.getElementById('edit-numero').value      = c.numero;
    document.getElementById('edit-type').value        = c.type;
    document.getElementById('edit-prix').value        = c.prix;
    document.getElementById('edit-capacite').value    = c.capacite;
    document.getElementById('edit-description').value = c.description || '';
    document.getElementById('edit-statut').value      = c.statut;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
<script src="theme.js"></script>
</body>
</html>
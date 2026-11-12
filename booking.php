<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php'); exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

$chambres = $conn->query("SELECT * FROM chambres WHERE statut='disponible' ORDER BY prix ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chambre_id = (int)$_POST['chambre_id'];
    $date_debut = $_POST['date_debut'];
    $date_fin   = $_POST['date_fin'];
    $nb_pers    = (int)($_POST['nb_personnes'] ?? 1);
    $message    = htmlspecialchars(trim($_POST['message'] ?? ''));

    if (!$chambre_id) {
        $error = "Veuillez sélectionner une chambre.";
    } elseif ($date_debut >= $date_fin) {
        $error = "La date de fin doit être après la date de début.";
    } elseif ($date_debut < date('Y-m-d')) {
        $error = "La date de début ne peut pas être dans le passé.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM reservations WHERE chambre_id=? AND statut IN ('en_attente','confirmee') AND NOT (date_fin <= ? OR date_debut >= ?)");
        $stmt->bind_param("iss", $chambre_id, $date_debut, $date_fin);
        $stmt->execute();
        $overlap = $stmt->get_result()->num_rows;
        $stmt->close();

        if ($overlap > 0) {
            $error = "Cette chambre est déjà réservée pour ces dates.";
        } else {
            $ch = $conn->query("SELECT prix, numero FROM chambres WHERE id=$chambre_id")->fetch_assoc();
            $days = (strtotime($date_fin) - strtotime($date_debut)) / 86400;
            $prix_total = $days * $ch['prix'];

            $stmt = $conn->prepare("INSERT INTO reservations (user_id, chambre_id, date_debut, date_fin, nb_personnes, prix_total, message) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("iissids", $user_id, $chambre_id, $date_debut, $date_fin, $nb_pers, $prix_total, $message);
            $stmt->execute();
            $stmt->close();

            $admins = $conn->query("SELECT id FROM users WHERE role='admin'")->fetch_all(MYSQLI_ASSOC);
            foreach ($admins as $admin) {
                envoyerNotification($conn, $admin['id'],
                    "📅 Nouvelle Réservation",
                    "Nouvelle demande pour la chambre {$ch['numero']} du $date_debut au $date_fin par " . htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']),
                    'info'
                );
            }
            $success = "Votre réservation a été envoyée ! Vous serez notifié dès qu'elle sera traitée.";
        }
    }
}

$notifs = compterNotificationsNonLues($conn, $user_id);

$mes_reservations = $conn->query("
    SELECT r.*, c.numero AS chambre_num, c.type AS chambre_type, c.image AS chambre_image
    FROM reservations r JOIN chambres c ON r.chambre_id = c.id
    WHERE r.user_id = $user_id ORDER BY r.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$sl = ['en_attente'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée','terminee'=>'Terminée'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver — Hôtel Etoile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="booking.css">
        <link rel="stylesheet" href="theme.css">

</head>
<body>

<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="bi bi-stars"></i></div>
            <span class="logo-text">Hôtel Etoile
            </span>
        </a>
        <div class="nav-buttons">
            <a href="notifications.php" class="btn-notif">
                <i class="bi bi-bell"></i>
                <?php if ($notifs > 0): ?>
                <span class="notif-badge"><?= $notifs ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php" class="btn-profile">
                <i class="bi bi-person"></i>
                <?= htmlspecialchars($_SESSION['prenom']) ?>
            </a>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
            <a href="index.php" class="btn-back">← Accueil</a>
        </div>
    </div>
</nav>

<div class="hero-booking">
    <h1>Réserver <span>votre séjour</span></h1>
    <p>Choisissez la chambre parfaite pour un moment d'exception à l'Hôtel Etoile</p>
</div>

<div class="booking-container">

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= $success ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start;">

        <div style="flex:2;min-width:300px;">
            <div class="section-title">
                <span class="step-number">1</span>
                Choisissez votre chambre
            </div>

            <?php if (empty($chambres)): ?>
            <div class="empty-preview" style="padding:60px;">
                <i class="bi bi-door-closed"></i>
                <p>Aucune chambre disponible actuellement.</p>
            </div>
            <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($chambres as $c): ?>
                <div class="room-card" id="card-<?= $c['id'] ?>"
                     onclick="selectRoom(<?= $c['id'] ?>, '<?= addslashes($c['numero']) ?>', '<?= addslashes($c['type']) ?>', <?= $c['prix'] ?>, <?= $c['capacite'] ?>)">

                    <div class="room-image">
                        <?php if (media_url($c['image'])): ?>
                        <img src="<?= htmlspecialchars(media_url($c['image'])) ?>" alt="Chambre <?= htmlspecialchars($c['numero']) ?>">
                        <?php else: ?>
                        <div class="room-image-placeholder"><i class="bi bi-door-open"></i></div>
                        <?php endif; ?>
                    </div>

                    <div class="room-info">
                        <div class="room-header">
                            <div class="room-name">Chambre N°<?= htmlspecialchars($c['numero']) ?></div>
                            <span class="room-type"><?= htmlspecialchars($c['type']) ?></span>
                        </div>
                        <div class="room-capacity">
                            <i class="bi bi-people"></i>
                            Capacité : <?= $c['capacite'] ?> personne(s)
                        </div>
                        <?php if (!empty($c['description'])): ?>
                        <div class="room-description">
                            <?= htmlspecialchars(substr($c['description'], 0, 100)) ?>...
                        </div>
                        <?php endif; ?>
                        <div class="room-footer">
                            <div class="room-price">
                                <span class="price-amount"><?= number_format($c['prix'], 0, ',', ' ') ?></span>
                                <span class="price-unit"> DA/nuit</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($mes_reservations)): ?>
            <div class="reservations-section">
                <div class="section-title">
                    <i class="bi bi-clock-history" style="color:var(--gold);font-size:1.1rem;"></i>
                    Mes dernières réservations
                </div>
                <div class="reservations-list">
                    <?php foreach ($mes_reservations as $r): ?>
                    <div class="reservation-item">
                        <div class="reservation-image">
                            <?php if (media_url($r['chambre_image'])): ?>
                            <img src="<?= htmlspecialchars(media_url($r['chambre_image'])) ?>" alt="">
                            <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--sand),var(--gold));display:flex;align-items:center;justify-content:center;color:white;">
                                <i class="bi bi-door-open"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="reservation-details">
                            <h4>Chambre N°<?= htmlspecialchars($r['chambre_num']) ?> — <?= htmlspecialchars($r['chambre_type']) ?></h4>
                            <div class="reservation-dates">
                                <i class="bi bi-calendar3"></i>
                                <?= date('d/m/Y', strtotime($r['date_debut'])) ?>
                                <i class="bi bi-arrow-right"></i>
                                <?= date('d/m/Y', strtotime($r['date_fin'])) ?>
                                <span class="reservation-price"><?= number_format($r['prix_total'], 0, ',', ' ') ?> DA</span>
                            </div>
                        </div>
                        <span class="status-badge status-<?= $r['statut'] ?>">
                            <?= $sl[$r['statut']] ?? $r['statut'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div style="flex:1;min-width:300px;">
            <div class="section-title">
                <span class="step-number">2</span>
                Vos informations
            </div>

            <div class="booking-panel">

                <div id="preview" class="empty-preview">
                    <i class="bi bi-hand-index"></i>
                    <p>Cliquez sur une chambre pour la sélectionner</p>
                </div>

                <form method="POST" id="booking-form">
                    <input type="hidden" name="chambre_id" id="chambre_id_input" value="">

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-check"></i> Date d'arrivée
                        </label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control"
                               min="<?= date('Y-m-d') ?>" onchange="calcTotal()" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-x"></i> Date de départ
                        </label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" onchange="calcTotal()" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-people"></i> Nombre de personnes
                        </label>
                        <select name="nb_personnes" class="form-select" onchange="calcTotal()">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> personne<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-chat-text"></i> Message (optionnel)
                        </label>
                        <textarea name="message" class="form-control"
                                  placeholder="Demandes spéciales, informations complémentaires..."></textarea>
                    </div>

                    <div id="price-box" class="price-calculator" style="display:none;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                            <span style="font-size:.82rem;color:var(--muted);" id="calc-detail">—</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:.85rem;font-weight:600;">Total estimé</span>
                            <span class="total" id="calc-total">— DA</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="submit-btn" disabled>
                        <i class="bi bi-calendar-check"></i>
                        Envoyer ma réservation
                    </button>
                </form>

                <p style="font-size:.72rem;color:var(--muted);text-align:center;margin-top:14px;">
                    <i class="bi bi-info-circle"></i>
                    La réservation doit être confirmée par l'équipe avant votre arrivée.
                </p>
            </div>
        </div>

    </div>
</div>

<script>
let selectedRoomId    = null;
let selectedRoomPrice = 0;
let selectedRoomCap   = 0;

function selectRoom(id, numero, type, prix, capacite) {
    selectedRoomId    = id;
    selectedRoomPrice = prix;
    selectedRoomCap   = capacite;

    document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
    const el = document.getElementById('card-' + id);
    if (el) el.classList.add('selected');

    document.getElementById('chambre_id_input').value = id;

    const img    = el ? el.querySelector('.room-image img') : null;
    const imgSrc = img ? img.src : '';
    const preview = document.getElementById('preview');
    preview.className = 'selected-preview';
    preview.innerHTML = `
        ${imgSrc ? `<div class="selected-preview-image"><img src="${imgSrc}" alt=""></div>` : ''}
        <h4>Chambre N°${numero}</h4>
        <p style="color:var(--gold);font-size:1.1rem;font-weight:700;margin:4px 0">
            ${prix.toLocaleString('fr')} DA
            <small style="font-size:.7rem;color:#999;">/nuit</small>
        </p>
        <small style="color:var(--muted);">👥 ${capacite} personne(s) max</small>
    `;

    calcTotal();
    checkSubmit();
}

function calcTotal() {
    const d1 = document.getElementById('date_debut').value;
    const d2 = document.getElementById('date_fin').value;
    if (!d1 || !d2 || !selectedRoomId) return;

    const nuits = Math.round((new Date(d2) - new Date(d1)) / 86400000);
    if (nuits <= 0) { document.getElementById('price-box').style.display = 'none'; return; }

    const total = nuits * selectedRoomPrice;
    document.getElementById('calc-detail').textContent = `${nuits} nuit(s) × ${selectedRoomPrice.toLocaleString('fr')} DA`;
    document.getElementById('calc-total').textContent  = total.toLocaleString('fr') + ' DA';
    document.getElementById('price-box').style.display = 'block';
    checkSubmit();
}

function checkSubmit() {
    const d1  = document.getElementById('date_debut').value;
    const d2  = document.getElementById('date_fin').value;
    const btn = document.getElementById('submit-btn');
    btn.disabled = !(selectedRoomId && d1 && d2 && d1 < d2);
}

document.getElementById('date_debut').addEventListener('change', function () {
    if (this.value) {
        const next = new Date(new Date(this.value).getTime() + 86400000).toISOString().split('T')[0];
        document.getElementById('date_fin').min = next;
    }
    checkSubmit();
});
document.getElementById('date_fin').addEventListener('change', checkSubmit);

window.addEventListener('load', function () {
    const preselectId = <?= isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0 ?>;
    const firstCard = preselectId > 0
        ? document.getElementById('card-' + preselectId)
        : document.querySelector('.room-card');

    if (firstCard) {
        const onclickAttr = firstCard.getAttribute('onclick');
        const match = onclickAttr.match(/selectRoom\((\d+),\s*'([^']*)',\s*'([^']*)',\s*([\d.]+),\s*(\d+)/);
        if (match) {
            selectRoom(parseInt(match[1]), match[2], match[3], parseFloat(match[4]), parseInt(match[5]));
        }
    }
});
</script>
    <script src="theme.js"></script>
</body>
</html>
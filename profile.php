<?php
session_start();
require_once 'database.php';
require_once 'notif_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Rediriger les admins vers leur propre page de profil
if ($role === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error   = '';
$user    = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $nom    = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email  = trim($_POST['email']);

        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé par un autre compte.";
        } else {
            $upd = $conn->prepare("UPDATE users SET nom=?, prenom=?, email=? WHERE id=?");
            $upd->bind_param("sssi", $nom, $prenom, $email, $user_id);
            $upd->execute(); $upd->close();
            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['email'] = $email;
            $user    = $conn->query("SELECT * FROM users WHERE id=$user_id")->fetch_assoc();
            $success = "Profil mis à jour avec succès !";
        }
        $stmt->close();
    }

    if ($_POST['action'] === 'password') {
        $old  = $_POST['old_password'];
        $new  = $_POST['new_password'];
        $conf = $_POST['confirm_password'];

        if (!password_verify($old, $user['password'])) {
            $error = "Ancien mot de passe incorrect.";
        } elseif (strlen($new) < 6) {
            $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } elseif ($new !== $conf) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hash' WHERE id=$user_id");
            $success = "Mot de passe modifié avec succès !";
        }
    }
}

$notifs_count = compterNotificationsNonLues($conn, $user_id);

// Statistiques client
$stats_client['total']     = $conn->query("SELECT COUNT(*) FROM reservations WHERE user_id=$user_id")->fetch_row()[0];
$stats_client['confirmee'] = $conn->query("SELECT COUNT(*) FROM reservations WHERE user_id=$user_id AND statut='confirmee'")->fetch_row()[0];
$stats_client['depenses']  = $conn->query("SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE user_id=$user_id AND statut IN ('confirmee','terminee')")->fetch_row()[0];

$mes_resa = $conn->query("
    SELECT r.*, c.numero AS chambre_num, c.type AS chambre_type
    FROM reservations r JOIN chambres c ON r.chambre_id=c.id
    WHERE r.user_id=$user_id ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$sl = ['en_attente'=>'En attente','confirmee'=>'Confirmée','annulee'=>'Annulée','terminee'=>'Terminée'];
$sc = ['en_attente'=>'badge-pending','confirmee'=>'badge-confirm','annulee'=>'badge-cancel','terminee'=>'badge-done'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil — Hôtel Etoile</title>
    <link rel="stylesheet" href="style.css">
            <link rel="stylesheet" href="theme.css">  

    
</head>
<body>

<div class="client-nav">
    <a href="booking.php" class="back-btn">← Retour</a>
    <div class="brand"><span>✦</span> Hôtel Etoile</div>
    <div class="client-nav-right">
        <a href="notifications.php" class="notif-btn">
            🔔<?php if ($notifs_count > 0): ?><span class="notif-dot"><?= $notifs_count ?></span><?php endif; ?>
        </a>
        <a href="logout.php" style="background: none; border: 1px solid #ddd; padding: 8px 16px; border-radius: 30px; text-decoration: none; color: #333; font-size: 13px;">Déconnexion</a>
    </div>
</div>

<div class="client-container">
    <?php if ($success): ?>
        <div class="flash flash-success" style="margin-bottom: 20px;">✓ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash flash-danger" style="margin-bottom: 20px;">⚠ <?= $error ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Colonne gauche - Infos client -->
        <div>
            <div class="profile-card">
                <div class="avatar-lg"><?= strtoupper(substr($user['prenom'],0,1)) ?></div>
                <h5 style="font-family:'Playfair Display',serif;font-size: 20px;font-weight: 400;margin: 0 0 4px;">
                    <?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?>
                </h5>
                <p style="font-size: 13px; color: #777; margin: 4px 0;"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge badge-type" style="margin: 8px 0;">👤 Client</span>
                <p style="font-size: 12px; color: #aaa; margin-top: 12px;">Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            </div>

            <!-- Cartes statistiques -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                <div class="stat-mini">
                    <h5><?= $stats_client['total'] ?></h5>
                    <small>Réservations</small>
                </div>
                <div class="stat-mini">
                    <h5><?= $stats_client['confirmee'] ?></h5>
                    <small>Confirmées</small>
                </div>
            </div>
            <div class="stat-mini" style="margin-top: 12px;">
                <h5><?= number_format($stats_client['depenses'], 0, ',', ' ') ?> DA</h5>
                <small>Total dépensé</small>
            </div>
        </div>

        <!-- Colonne droite - Formulaires -->
        <div>
            <!-- Formulaire modification infos -->
            <div class="form-card">
                <h6>✏ Modifier mes informations</h6>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" class="form-control no-icon"
                                   value="<?= htmlspecialchars($user['nom']) ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" class="form-control no-icon"
                                   value="<?= htmlspecialchars($user['prenom']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 12px;">
                        <label class="form-label">Email</label>
                        <div class="input-wrap">
                            <span class="input-icon">✉</span>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm">💾 Enregistrer</button>
                </form>
            </div>

            <!-- Formulaire changement mot de passe -->
            <div class="form-card">
                <h6>🔒 Changer le mot de passe</h6>
                <form method="POST">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label class="form-label">Ancien mot de passe</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="old_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nouveau mot de passe</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 caractères" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <div class="input-wrap">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark btn-sm">🔑 Changer le mot de passe</button>
                </form>
            </div>

           
<script src="theme.js"></script>
</body>
</html>
<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['prenom']  = $user['prenom'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];
        header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'index.php'));
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Hôtel Etoile
        
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="login.css">
        <link rel="stylesheet" href="theme.css">  

</head>
<body>

<!-- ── Left Panel ───────────────────────────────── -->
<div class="panel-left">
    <a href="index.php" class="panel-brand">
        <div class="brand-orb"><i class="bi bi-stars"></i></div>
        Hôtel Etoile
    </a>

    <div class="panel-hero">
        <div class="panel-eyebrow">Bienvenue</div>
        <h2>Votre compte,<br><em>vos avantages</em></h2>
        <p>Gérez vos réservations, accédez à des tarifs exclusifs et profitez d'une expérience personnalisée.</p>
    </div>

    <div class="panel-features">
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-lightning-charge"></i></div>
            <div>
                <div class="feat-title">Réservation instantanée</div>
                <div class="feat-desc">Réservez en quelques clics, confirmation en temps réel</div>
            </div>
        </div>
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-tag"></i></div>
            <div>
                <div class="feat-title">Offres exclusives membres</div>
                <div class="feat-desc">Accédez à des tarifs préférentiels et promotions</div>
            </div>
        </div>
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="feat-title">Historique & suivi</div>
                <div class="feat-desc">Consultez toutes vos réservations passées et actuelles</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Right Panel ──────────────────────────────── -->
<div class="panel-right">
    <div class="login-form-wrap">

        <div class="form-header">
            <h3>Se <em>connecter</em></h3>
            <p>Pas encore de compte ? <a href="register.php">S'inscrire →</a></p>
        </div>

        <?php if ($error): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Adresse email</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control"
                           placeholder="votre@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-gold btn-w100">
                <i class="bi bi-box-arrow-in-right"></i>
                Se connecter
            </button>
        </form>

        <div style="margin-top:20px; text-align:center;">
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>

       

    </div>
</div>
<script src="theme.js"></script>

</body>
</html>
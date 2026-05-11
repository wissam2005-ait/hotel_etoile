<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard.php' : 'index.php'));    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom']);
    $prenom   = trim($_POST['prenom']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $hash  = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO users (nom, prenom, email, password) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("ssss", $nom, $prenom, $email, $hash);
            $stmt2->execute();
            $stmt2->close();
            $success = "Compte créé avec succès !";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Hôtel Etoile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="register.css">
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
        <div class="panel-eyebrow">Rejoignez-nous</div>
        <h2>Créez votre compte,<br><em>vivez l'exception</em></h2>
        <p>Inscrivez-vous pour bénéficier de tarifs exclusifs, gérer vos réservations et vivre une expérience personnalisée.</p>
    </div>

    <div class="panel-features">
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-gift"></i></div>
            <div>
                <div class="feat-title">Offre de bienvenue</div>
                <div class="feat-desc">-10% sur votre première réservation</div>
            </div>
        </div>
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-star"></i></div>
            <div>
                <div class="feat-title">Programme fidélité</div>
                <div class="feat-desc">Accédez à des avantages exclusifs</div>
            </div>
        </div>
        <div class="panel-feat">
            <div class="feat-icon"><i class="bi bi-headset"></i></div>
            <div>
                <div class="feat-title">Support prioritaire</div>
                <div class="feat-desc">Service client dédié 24h/24</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Right Panel ──────────────────────────────── -->
<div class="panel-right">
    <div class="register-form-wrap">

        <div class="form-header">
            <h3>Créer un <em>compte</em></h3>
            <p>Déjà inscrit ? <a href="login.php">Se connecter →</a></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($success) ?> — <a href="login.php">Se connecter</a>
        </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">

            <!-- Nom / Prénom -->
            <div class="name-row">
                <div class="form-group">
                    <label class="form-label">Nom <span class="required">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" name="nom" class="form-control"
                               placeholder="aitelhadi"
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Prénom <span class="required">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" name="prenom" class="form-control"
                               placeholder="wissam"
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label">Adresse email <span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control"
                           placeholder="votre@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Mot de passe -->
            <div class="form-group">
                <label class="form-label">Mot de passe <span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Min. 6 caractères" required>
                </div>
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar-fill" id="strengthFill"></div>
                    </div>
                    <span id="strengthText" style="color:#999;"></span>
                </div>
            </div>

            <!-- Confirmer mot de passe -->
            <div class="form-group">
                <label class="form-label">Confirmer le mot de passe <span class="required">*</span></label>
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="confirm" id="confirmPassword"
                           class="form-control" placeholder="Répétez le mot de passe" required>
                </div>
                <div class="match-hint" id="passwordMatch"></div>
            </div>

            <button type="submit" class="btn-gold">
                <i class="bi bi-person-plus"></i>
                Créer mon compte
            </button>
        </form>

        <div class="auth-footer" style="margin-top:24px;">
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour à l'accueil
            </a>
        </div>

    </div>
</div>

<script>
const passwordInput  = document.getElementById('password');
const confirmInput   = document.getElementById('confirmPassword');
const strengthFill   = document.getElementById('strengthFill');
const strengthText   = document.getElementById('strengthText');
const passwordMatch  = document.getElementById('passwordMatch');

function checkStrength(password) {
    let s = 0;
    if (password.length >= 6)              s++;
    if (password.length >= 10)             s++;
    if (/[A-Z]/.test(password))            s++;
    if (/[0-9]/.test(password))            s++;
    if (/[^A-Za-z0-9]/.test(password))    s++;

    if (!password.length) {
        strengthFill.className = 'strength-bar-fill';
        strengthFill.style.width = '0%';
        strengthText.textContent = '';
        return;
    }
    if (s <= 2) {
        strengthFill.className = 'strength-bar-fill strength-weak';
        strengthText.textContent = 'Mot de passe faible';
        strengthText.style.color = '#e74c3c';
    } else if (s <= 4) {
        strengthFill.className = 'strength-bar-fill strength-medium';
        strengthText.textContent = 'Mot de passe moyen';
        strengthText.style.color = '#f39c12';
    } else {
        strengthFill.className = 'strength-bar-fill strength-strong';
        strengthText.textContent = 'Mot de passe fort';
        strengthText.style.color = '#27ae60';
    }
}

function checkMatch() {
    const p = passwordInput.value;
    const c = confirmInput.value;
    if (!c.length) { passwordMatch.innerHTML = ''; return; }
    if (p === c) {
        passwordMatch.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#27ae60;margin-right:4px;"></i>Les mots de passe correspondent';
        passwordMatch.style.color = '#27ae60';
    } else {
        passwordMatch.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#e74c3c;margin-right:4px;"></i>Les mots de passe ne correspondent pas';
        passwordMatch.style.color = '#e74c3c';
    }
}

passwordInput.addEventListener('input', function () {
    checkStrength(this.value);
    checkMatch();
});
confirmInput.addEventListener('input', checkMatch);
</script>
<script src="theme.js"></script>
</body>
</html>
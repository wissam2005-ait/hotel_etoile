<?php
// Connexion à la base de données.
// Réglages, par ordre de priorité :
//   1. fichier config.local.php (hébergement mutualisé : ByetHost, InfinityFree…), jamais envoyé sur GitHub
//   2. variables d'environnement MYSQLHOST, MYSQLUSER… (Docker, Railway, Render)
//   3. valeurs par défaut d'un serveur local (XAMPP / WAMP)
$cfg = is_file(__DIR__ . '/config.local.php') ? (require __DIR__ . '/config.local.php') : [];

$dbHost = $cfg['host']     ?? (getenv('MYSQLHOST') ?: 'localhost');
$dbUser = $cfg['user']     ?? (getenv('MYSQLUSER') ?: 'root');
$dbPass = $cfg['password'] ?? (getenv('MYSQLPASSWORD') ?: '');
$dbName = $cfg['database'] ?? (getenv('MYSQLDATABASE') ?: 'wissam');
$dbPort = (int) ($cfg['port'] ?? (getenv('MYSQLPORT') ?: 3306));
$DEMO_MODE = (bool) ($cfg['demo'] ?? getenv('DEMO_MODE'));

// PostgreSQL (Vercel + Neon) si une adresse de base Postgres est fournie, sinon MySQL.
$pgUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');

if ($pgUrl && empty($cfg)) {
    require_once __DIR__ . '/pgcompat.php';
    $conn = new PgCompatConn($pgUrl);
    $seedFile = __DIR__ . '/deploy/seed.pg.sql';
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $seedFile = __DIR__ . '/deploy/seed.sql';
}
if ($conn->connect_error) {
    http_response_code(500);
    die("Connexion à la base de données impossible.");
}
$conn->set_charset("utf8mb4");

// Base vide : création des tables et des chambres de démonstration.
$check = $conn->query("SHOW TABLES LIKE 'users'");
if ($check && $check->num_rows === 0 && is_file($seedFile)) {
    if ($conn->multi_query(file_get_contents($seedFile))) {
        while ($conn->more_results() && $conn->next_result()) { /* vider les résultats */ }
    }
}

// Serverless : sessions enregistrées dans la base.
if ($conn instanceof PgCompatConn && session_status() === PHP_SESSION_NONE) {
    session_set_save_handler(new PgSessionHandler($conn->pdo), true);
}

// Mode démo : comptes de test toujours disponibles.
if ($DEMO_MODE) {
    $demo = [
        ['Démo', 'Client', 'client@demo.fr', '$2y$10$R.lC0EdsAZ0DjoNqCzuxQeYrN3uni9G9FAkmU3A9nq50DsZIDCK0y', 'client'],
        ['Démo', 'Admin',  'admin@demo.fr',  '$2y$10$hg85bEhXXBOwoGsAggL4.utvezI0HK7TXaDIntOruUbin7tBvIPW2', 'admin'],
    ];
    $ins = $conn->prepare("INSERT IGNORE INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)");
    if ($ins) {
        foreach ($demo as $d) { $ins->bind_param("sssss", ...$d); $ins->execute(); }
        $ins->close();
    }
}

require_once __DIR__ . '/media.php';

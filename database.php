<?php
// Connexion à la base : réglages lus dans les variables d'environnement (hébergement),
// avec des valeurs par défaut pour un serveur local (XAMPP / WAMP).
$dbHost = getenv('MYSQLHOST') ?: 'localhost';
$dbUser = getenv('MYSQLUSER') ?: 'root';
$dbPass = getenv('MYSQLPASSWORD') ?: '';
$dbName = getenv('MYSQLDATABASE') ?: 'wissam';
$dbPort = (int) (getenv('MYSQLPORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($conn->connect_error) {
    http_response_code(500);
    die("Connexion à la base de données impossible.");
}
$conn->set_charset("utf8mb4");

// Première mise en ligne : création des tables et des données de démonstration.
$check = $conn->query("SHOW TABLES LIKE 'users'");
if ($check && $check->num_rows === 0 && is_file(__DIR__ . '/deploy/seed.sql')) {
    if ($conn->multi_query(file_get_contents(__DIR__ . '/deploy/seed.sql'))) {
        while ($conn->more_results() && $conn->next_result()) { /* vider les résultats */ }
    }
}
?>

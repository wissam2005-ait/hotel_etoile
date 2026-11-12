<?php
// Sert une photo de chambre enregistrée dans la base de données.
require_once 'database.php';
$name = $_GET['f'] ?? '';
if (!($conn instanceof PgCompatConn) || !preg_match('/^[\w.-]{1,255}$/', $name)) { http_response_code(404); exit; }
$st = $conn->pdo->prepare('SELECT mime, data FROM images WHERE name = ?');
$st->execute([$name]);
$img = $st->fetch();
if (!$img) { http_response_code(404); exit; }
header('Content-Type: ' . $img['mime']);
header('Cache-Control: public, max-age=31536000, immutable');
echo base64_decode($img['data']);

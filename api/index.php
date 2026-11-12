<?php
// Point d'entrée Vercel : chaque page .php du site passe par ici.
$root = dirname(__DIR__);
chdir($root);
set_include_path($root . PATH_SEPARATOR . get_include_path());

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$page = basename($path);
if ($page === '' || $path === '/') $page = 'index.php';

if (!preg_match('/^[a-z_]+\.php$/', $page) || in_array($page, ['database.php', 'pgcompat.php', 'media.php', 'notif_helper.php', 'config.local.php'], true)
    || !is_file($root . '/' . $page)) {
    http_response_code(404);
    echo '<!doctype html><meta charset="utf-8"><title>Page introuvable</title><p style="font-family:sans-serif;padding:40px">Page introuvable. <a href="/">Retour à l’accueil</a></p>';
    exit;
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/' . $page;
$_SERVER['SCRIPT_FILENAME'] = $root . '/' . $page;
require_once $root . '/database.php';   // connexion + sessions avant session_start() des pages
require $root . '/' . $page;

# Hôtel Etoile — réservation de chambres en ligne

Application web complète (PHP, MySQL, JavaScript, HTML, CSS) : recherche de chambres, réservation en ligne,
espace client (réservations, notifications, profil) et espace administrateur (tableau de bord, chambres,
réservations à confirmer ou annuler, clients).

## Démo en ligne
Comptes de démonstration (affichés sur la page de connexion) :
- Client : `client@demo.fr` / `Client2026!`
- Administrateur : `admin@demo.fr` / `Admin2026!`

## Lancer en local (XAMPP / WAMP)
1. Importer `wissam.sql` dans phpMyAdmin (base `wissam`).
2. Copier le dossier dans `htdocs` et ouvrir `http://localhost/hotel_etoile`.

## Hébergement mutualisé (ByetHost, InfinityFree…)
Envoyer les fichiers dans `htdocs`, puis copier `config.local.example.php` en `config.local.php`
et y mettre les identifiants MySQL de l'hébergeur. Le fichier `.htaccess` bloque l'accès aux fichiers sensibles.

## Hébergement Vercel (gratuit)
Le projet est prêt pour Vercel (`vercel.json`, runtime `vercel-php`) avec une base **PostgreSQL Neon** :
`pgcompat.php` traduit automatiquement les appels mysqli et le SQL MySQL vers PostgreSQL, les sessions et
les photos des chambres sont enregistrées dans la base (`deploy/seed.pg.sql` crée les tables au premier lancement).
Variables : `DATABASE_URL` (fournie par Neon) et `DEMO_MODE=1`.

## Hébergement Docker (Railway, Render)
`Dockerfile` (PHP 8.2 + Apache). La connexion à la base se règle par les variables
`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
Au premier lancement, les tables et les données de démonstration (`deploy/seed.sql`) sont créées automatiquement.
`DEMO_MODE=1` affiche les comptes de démonstration sur la page de connexion.

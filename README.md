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

## Hébergement
`Dockerfile` (PHP 8.2 + Apache). La connexion à la base se règle par les variables
`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
Au premier lancement, les tables et les données de démonstration (`deploy/seed.sql`) sont créées automatiquement.
`DEMO_MODE=1` affiche les comptes de démonstration sur la page de connexion.

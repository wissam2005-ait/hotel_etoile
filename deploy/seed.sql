-- Base de démonstration (mise en ligne) : chambres et comptes de test.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client') DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`), UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chambres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'Simple',
  `prix` decimal(10,2) NOT NULL,
  `capacite` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `statut` enum('disponible','occupee','maintenance') DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `chambre_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_personnes` int(11) DEFAULT 1,
  `prix_total` decimal(10,2) DEFAULT NULL,
  `statut` enum('en_attente','confirmee','annulee','terminee') DEFAULT 'en_attente',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`), KEY `user_id` (`user_id`), KEY `chambre_id` (`chambre_id`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`chambre_id`) REFERENCES `chambres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `lu` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`), KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comptes de démonstration : client@demo.fr / Client2026!  et  admin@demo.fr / Admin2026!
INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `password`, `role`) VALUES
(1, 'Démo', 'Client', 'client@demo.fr', '$2y$10$R.lC0EdsAZ0DjoNqCzuxQeYrN3uni9G9FAkmU3A9nq50DsZIDCK0y', 'client'),
(2, 'Démo', 'Admin', 'admin@demo.fr', '$2y$10$hg85bEhXXBOwoGsAggL4.utvezI0HK7TXaDIntOruUbin7tBvIPW2', 'admin');

INSERT INTO `chambres` (`numero`, `type`, `prix`, `capacite`, `description`, `statut`) VALUES
('101', 'Simple', 2500.00, 1, 'Chambre simple calme, idéale pour un voyage d’affaires.', 'disponible'),
('102', 'Double', 3500.00, 2, 'Chambre double lumineuse avec salle de bain privée.', 'disponible'),
('201', 'Double', 3800.00, 2, 'Chambre double supérieure au 2ème étage avec balcon et vue dégagée.', 'disponible'),
('202', 'Suite', 8200.00, 3, 'Suite prestige avec kitchenette, jacuzzi et espace lounge.', 'disponible'),
('301', 'Familiale', 6000.00, 4, 'Chambre familiale spacieuse avec deux grands lits.', 'disponible'),
('302', 'Suite', 9500.00, 4, 'Suite panoramique avec terrasse privée.', 'maintenance');

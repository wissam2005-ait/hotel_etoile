-- Base PostgreSQL (Vercel + Neon) : tables, chambres et comptes de démonstration.
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  nom varchar(100) NOT NULL,
  prenom varchar(100) NOT NULL,
  email varchar(150) NOT NULL UNIQUE,
  password varchar(255) NOT NULL,
  role varchar(10) NOT NULL DEFAULT 'client' CHECK (role IN ('admin','client')),
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS chambres (
  id SERIAL PRIMARY KEY,
  numero varchar(20) NOT NULL,
  type varchar(50) NOT NULL DEFAULT 'Simple',
  prix numeric(10,2) NOT NULL,
  capacite integer NOT NULL DEFAULT 1,
  description text,
  image varchar(255),
  statut varchar(15) DEFAULT 'disponible' CHECK (statut IN ('disponible','occupee','maintenance')),
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS reservations (
  id SERIAL PRIMARY KEY,
  user_id integer NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  chambre_id integer NOT NULL REFERENCES chambres(id) ON DELETE CASCADE,
  date_debut date NOT NULL,
  date_fin date NOT NULL,
  nb_personnes integer DEFAULT 1,
  prix_total numeric(10,2),
  statut varchar(15) DEFAULT 'en_attente' CHECK (statut IN ('en_attente','confirmee','annulee','terminee')),
  message text,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS reservations_user ON reservations(user_id);
CREATE INDEX IF NOT EXISTS reservations_chambre ON reservations(chambre_id);
CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  user_id integer NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  titre varchar(200) NOT NULL,
  message text NOT NULL,
  type varchar(10) DEFAULT 'info' CHECK (type IN ('info','success','warning','danger')),
  lu smallint DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS notifications_user ON notifications(user_id);
CREATE TABLE IF NOT EXISTS php_sessions (
  id varchar(128) PRIMARY KEY,
  data text NOT NULL DEFAULT '',
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS images (
  name varchar(255) PRIMARY KEY,
  mime varchar(50) NOT NULL,
  data text NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO chambres (numero, type, prix, capacite, description, statut) VALUES
('101', 'Simple', 2500.00, 1, 'Chambre simple calme, idéale pour un voyage d’affaires.', 'disponible'),
('102', 'Double', 3500.00, 2, 'Chambre double lumineuse avec salle de bain privée.', 'disponible'),
('201', 'Double', 3800.00, 2, 'Chambre double supérieure au 2ème étage avec balcon et vue dégagée.', 'disponible'),
('202', 'Suite', 8200.00, 3, 'Suite prestige avec kitchenette, jacuzzi et espace lounge.', 'disponible'),
('301', 'Familiale', 6000.00, 4, 'Chambre familiale spacieuse avec deux grands lits.', 'disponible'),
('302', 'Suite', 9500.00, 4, 'Suite panoramique avec terrasse privée.', 'maintenance');

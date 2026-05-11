<?php
session_start();
require_once 'database.php';

$chambres = $conn->query("SELECT * FROM chambres WHERE statut = 'disponible' ORDER BY prix ASC LIMIT 6")->fetch_all(MYSQLI_ASSOC);


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hôtel Étoile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="theme.css">
    <style>
        /* ── Hamburger ── */
        .hamburger-btn {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 6px;
        }
        .hamburger-btn span {
            display: block;
            width: 26px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .hamburger-btn.scrolled span { background: #333; }

        /* Mobile menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(20,12,4,0.97);
            z-index: 999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 28px;
        }
        .mobile-menu.open { display: flex; }
        .mobile-menu a {
            color: #fff;
            font-size: 22px;
            font-family: 'Cormorant Garamond', serif;
            text-decoration: none;
            letter-spacing: 0.05em;
        }
        .mobile-menu a:hover { color: #c9a96e; }
        .mobile-menu-close {
            position: absolute;
            top: 20px; right: 20px;
            background: none;
            border: none;
            color: #fff;
            font-size: 32px;
            cursor: pointer;
        }
        .mobile-menu-btns {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .nav-buttons { display: none; }
            .hamburger-btn { display: flex; }
        }

        @media (max-width: 768px) {
            /* Navbar */
            .navbar-container { padding: 0 16px; }

            /* Hero */
            .hero-content h1 { font-size: clamp(28px, 8vw, 48px); }
            .hero-subtitle { font-size: 15px; }
            .hero-buttons { flex-direction: column; align-items: center; gap: 10px; }
            .hero-buttons a { width: 100%; max-width: 280px; text-align: center; justify-content: center; }

            /* Stats bar */
            .stats-bar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0;
            }
            .stat-item { padding: 16px 10px; }
            .stat-num { font-size: 22px; }

            /* Section */
            .section { padding: 50px 16px; }
            .section-title { font-size: clamp(24px, 6vw, 36px); }

            /* Rooms grid */
            .rooms-grid {
                grid-template-columns: 1fr !important;
                gap: 20px;
                padding: 0 16px;
            }

            /* About */
            .about-container {
                flex-direction: column !important;
                gap: 28px;
            }
            .about-image { width: 100% !important; }
            .about-image img { height: 240px; object-fit: cover; border-radius: 12px; }
            .about-content { padding: 0 16px; }
            .about-content h3 { font-size: 24px; }
            .about-stats { grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
            .about-stat-number { font-size: 22px; }

            /* Services */
            .services-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 14px;
                padding: 0 16px;
            }
            .service-card { padding: 20px 14px; }

            /* Footer */
            .footer-container {
                grid-template-columns: 1fr 1fr !important;
                gap: 28px;
                padding: 40px 16px;
            }
        }

        @media (max-width: 480px) {
            /* Stats */
            .stats-bar { grid-template-columns: 1fr 1fr; }

            /* Services en colonne */
            .services-grid { grid-template-columns: 1fr !important; }

            /* Footer en colonne */
            .footer-container { grid-template-columns: 1fr !important; }

            /* About stats */
            .about-stats { grid-template-columns: 1fr; gap: 8px; }

            /* Hero badge */
            .hero-badge { font-size: 12px; padding: 5px 14px; }

            /* Room card */
            .room-price { font-size: 20px; }
        }
    </style>
</head>
<body>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <button class="mobile-menu-close" onclick="closeMobileMenu()">✕</button>
    <a href="#accueil" onclick="closeMobileMenu()">Accueil</a>
    <a href="#chambres" onclick="closeMobileMenu()">Chambres</a>
    <a href="#services" onclick="closeMobileMenu()">Services</a>
    <a href="#about" onclick="closeMobileMenu()">L'hôtel</a>
    <a href="#contact" onclick="closeMobileMenu()">Contact</a>
    <div class="mobile-menu-btns">
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="dashboard.php" onclick="closeMobileMenu()" style="color:#c9a96e;font-size:15px;">Admin</a>
            <?php endif; ?>
            <a href="reservation_client.php" onclick="closeMobileMenu()" style="color:#c9a96e;font-size:15px;">Mes réservations</a>
            <a href="logout.php" onclick="closeMobileMenu()" style="color:#aaa;font-size:14px;">Déconnexion</a>
        <?php else: ?>
            <a href="login.php" onclick="closeMobileMenu()" style="border:1px solid #c9a96e;padding:10px 28px;border-radius:30px;color:#c9a96e;font-size:15px;">Connexion</a>
            <a href="register.php" onclick="closeMobileMenu()" style="background:#c9a96e;padding:10px 28px;border-radius:30px;color:#fff;font-size:15px;">S'inscrire</a>
        <?php endif; ?>
    </div>
</div>

<!-- Navigation -->
<nav class="navbar" id="navbar">
    <div class="navbar-container">
        <a href="index.php" class="logo">
            <div class="logo-icon">🏨</div>
            <span class="logo-text">Hôtel Étoile</span>
        </a>
        <ul class="nav-links">
            <li><a href="#accueil">Accueil</a></li>
            <li><a href="#chambres">Chambres</a></li>
            <li><a href="#services">Services</a></li>
            <li><a href="#about">L'hôtel</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <div class="nav-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="user-welcome">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?></div>
                    <span><?= htmlspecialchars($_SESSION['prenom']) ?></span>
                </div>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="dashboard.php" class="btn-nav btn-nav-login">Admin</a>
                <?php endif; ?>
                <a href="reservation_client.php" class="btn-nav btn-nav-login">Mes réservations</a>
                <a href="logout.php" class="btn-nav btn-nav-login">Déconnexion</a>
            <?php else: ?>
                <a href="login.php" class="btn-nav btn-nav-login">Connexion</a>
                <a href="register.php" class="btn-nav btn-nav-register">S'inscrire</a>
            <?php endif; ?>
        </div>
        <!-- Hamburger -->
        <button class="hamburger-btn" id="hamburgerBtn" onclick="openMobileMenu()">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero" id="accueil">
    <div class="hero-bg"></div>
    <div class="hero-pattern"></div>
    <div class="hero-content">
        <div class="hero-badge">
            <i class="bi bi-star-fill"></i>
            Hôtel 5 étoiles
            <i class="bi bi-star-fill"></i>
        </div>
        <h1>L'élégance</h1>
        <p class="hero-subtitle">Un art de vivre unique au cœur de la ville lumière</p>
        <div class="hero-buttons">
            <a href="<?= isset($_SESSION['user_id']) ? 'booking.php' : 'register.php' ?>" class="btn-primary">
                <i class="bi bi-calendar-check"></i>
                Réserver maintenant
            </a>
            <a href="#chambres" class="btn-secondary">
                <i class="bi bi-arrow-down"></i>
                Découvrir nos offres
            </a>
        </div>
    </div>
    <div class="scroll-indicator">
        <i class="bi bi-chevron-down" style="font-size: 2rem; color: white;"></i>
    </div>
</section>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-num">150+</div>
        <div class="stat-label">Chambres</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">5★</div>
        <div class="stat-label">Classement</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">24/7</div>
        <div class="stat-label">Service</div>
    </div>
    <div class="stat-item">
        <div class="stat-num">5000+</div>
        <div class="stat-label">Clients satisfaits</div>
    </div>
</div>

<!-- Chambres Section -->
<section class="section" id="chambres">
    <div class="section-header">
        <div class="section-subtitle">Nos hébergements</div>
        <h2 class="section-title">Chambres <span>& Suites</span></h2>
        <div class="section-divider"></div>
        <p style="margin-top:20px;color:#666;">Des espaces uniques pensés pour votre confort et votre bien-être</p>
    </div>

    <?php if (empty($chambres)): ?>
        <div style="text-align:center;padding:60px;">
            <i class="bi bi-door-closed" style="font-size:4rem;color:#c9a96e;"></i>
            <p style="margin-top:20px;">Aucune chambre disponible pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="rooms-grid">
            <?php
            $defaultImages = [
                'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600',
                'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600',
                'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600',
                'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600',
                'https://images.unsplash.com/photo-1617098474311-81e136b5c7fc?w=600',
                'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600'
            ];
            $i = 0;
            foreach ($chambres as $c):
                $typeMap = ['simple'=>'Chambre Simple','double'=>'Chambre Double','deluxe'=>'Chambre Deluxe','suite'=>'Suite'];
                $label = $typeMap[$c['type']] ?? ucfirst($c['type']);
                $roomImage = (!empty($c['image']) && file_exists("uploads/chambres/".$c['image']))
                    ? "uploads/chambres/".$c['image']
                    : $defaultImages[$i % count($defaultImages)];
                $i++;
            ?>
            <div class="room-card">
                <div class="room-image">
                    <img src="<?= htmlspecialchars($roomImage) ?>" alt="Chambre <?= htmlspecialchars($c['numero']) ?>">
                    <div class="room-badge"><?= $label ?></div>
                </div>
                <div class="room-content">
                    <div class="room-type">À partir de</div>
                    <h3 class="room-name">Chambre <?= htmlspecialchars($c['numero']) ?></h3>
                    <div class="room-features">
                        <div class="room-feature"><i class="bi bi-wifi"></i> WiFi gratuit</div>
                        <div class="room-feature"><i class="bi bi-people"></i> <?= $c['capacite'] ?> pers.</div>
                        <div class="room-feature"><i class="bi bi-cup-hot"></i> Petit-déj.</div>
                    </div>
                    <div class="room-price"><?= number_format($c['prix'], 0) ?> <span>DA / nuit</span></div>
                    <a href="<?= isset($_SESSION['user_id']) ? 'booking.php?room_id='.$c['id'] : 'register.php' ?>" class="btn-room">
                        Réserver <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- About Section -->
<section class="about-section" id="about">
    <div class="about-container">
        <div class="about-image">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800" alt="Hôtel Étoile">
        </div>
        <div class="about-content">
            <h3>L'excellence algérienne <br>depuis 1998</h3>
            <p>Depuis plus de 25 ans, l'Hôtel Étoile incarne l'élégance et le raffinement à l'algérienne. Niché au cœur de Bejaia, notre établissement allie l'authenticité de notre patrimoine et le confort moderne pour offrir une expérience unique à chaque visiteur.</p>
            <p>Chaque détail a été pensé pour votre bien-être : literie d'exception, service personnalisé, et une gastronomie qui célèbre les saveurs de notre terroir.</p>
            <p>Notre équipe dévouée est à votre disposition 24h/24 pour faire de votre séjour un moment inoubliable.</p>
            <div class="about-stats">
                <div class="about-stat">
                    <div class="about-stat-number">25+</div>
                    <div class="about-stat-label">Années d'excellence</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-number">150+</div>
                    <div class="about-stat-label">Chambres & Suites</div>
                </div>
                <div class="about-stat">
                    <div class="about-stat-number">5000+</div>
                    <div class="about-stat-label">Clients satisfaits</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section" id="services">
    <div class="section-header">
        <div class="section-subtitle">Nos services</div>
        <h2 class="section-title">Un service <span>d'exception</span></h2>
        <div class="section-divider"></div>
    </div>
    <div class="services-grid">
        <div class="service-card">
            <div class="service-icon"><i class="bi bi-wifi"></i></div>
            <h3>WiFi Haut Débit</h3>
            <p>Connexion ultra-rapide gratuite dans tout l'établissement</p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="bi bi-cup-hot"></i></div>
            <h3>Petit-déjeuner</h3>
            <p>Buffet continental avec produits frais et locaux</p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="bi bi-water"></i></div>
            <h3>Spa & Bien-être</h3>
            <p>Centre de bien-être avec sauna, hammam et soins</p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="bi bi-car-front"></i></div>
            <h3>Parking Privé</h3>
            <p>Parking sécurisé gratuit pour tous nos clients</p>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="contact">
    <div class="footer-container">
        <div class="footer-col">
            <h4>Hôtel Étoile</h4>
            <p>Un palace d'exception au cœur de la ville, alliant luxe, confort et tradition.</p>
            <div class="footer-social">
                <a href="https://web.facebook.com/wissam.aitelhadi"><i class="bi bi-facebook"></i></a>
                <a href="https://www.instagram.com/wissamaitelhadi/"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-twitter"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p><i class="bi bi-geo-alt"></i> Bejaia, Algérie</p>
            <p><i class="bi bi-telephone"></i> +213 5 62 26 15 63</p>
            <p><i class="bi bi-envelope"></i> contact@hoteletoile.com</p>
        </div>
        <div class="footer-col">
            <h4>Horaires</h4>
            <p>Réception : 24h/24, 7j/7</p>
        </div>
        <div class="footer-col">
            <h4>Mon compte</h4>
            <?php if(isset($_SESSION['user_id'])): ?>
                <p><a href="profile.php">Mon profil</a></p>
                <p><a href="reservation_client.php">Mes réservations</a></p>
                <p><a href="booking.php">Nouvelle réservation</a></p>
            <?php else: ?>
                <p><a href="login.php">Se connecter</a></p>
                <p><a href="register.php">S'inscrire</a></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Hôtel Étoile - Tous droits réservés</p>
    </div>
</footer>

<script>
// Navbar scroll
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    const btn = document.getElementById('hamburgerBtn');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
        btn.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
        btn.classList.remove('scrolled');
    }
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});

// Scroll indicator
document.querySelector('.scroll-indicator')?.addEventListener('click', () => {
    document.querySelector('#chambres')?.scrollIntoView({ behavior: 'smooth' });
});

// Mobile menu
function openMobileMenu() {
    document.getElementById('mobileMenu').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeMobileMenu() {
    document.getElementById('mobileMenu').classList.remove('open');
    document.body.style.overflow = '';
}
</script>
<script src="theme.js"></script>
</body>
</html>
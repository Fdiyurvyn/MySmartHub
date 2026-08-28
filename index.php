<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MySmartHub - One Place For Your Digital Life. Kelola todo, calendar, dan keuangan dalam satu platform.">
    <title>MySmartHub | One Place For Your Digital Life</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="icon" type="image/png" href="assets/img/myis.png">
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Logo -->
            <div class="navbar-logo">
                <a href="index.php">MySmartHub</a>
            </div>
            
            <!-- Menu Tengah -->
            <div class="navbar-menu">
                <ul class="nav-links">
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#about">Tentang</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>
            
            <!-- CTA Button -->
            <div class="navbar-cta">
                <a href="register.php" class="btn btn-primary">Daftar Gratis</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- HERO SECTION -->
        <section class="hero">
            <!-- Animated canvas particles -->
            <canvas id="hero-canvas" class="hero-canvas"></canvas>
            <!-- Floating orbs -->
            <div class="hero-orb hero-orb-1"></div>
            <div class="hero-orb hero-orb-2"></div>
            <div class="hero-orb hero-orb-3"></div>
            <!-- Aurora glow -->
            <div class="hero-glow"></div>
            <div class="hero-container">
                <!-- Badge -->
                <div class="badge">
                    ✨ Dirancang untuk produktivitas maksimal
                </div>
                
                <!-- Judul dengan typewriter -->
                <h1 class="hero-title">
                    <span class="hero-title-static">One Place For Your<br></span><span id="hero-typewriter" class="hero-typewriter"></span><span class="typewriter-cursor">|</span>
                </h1>
                
                <!-- Deskripsi -->
                <p class="hero-description">
                    Kelola todo list, calendar, dan keuangan Anda dalam satu platform yang sederhana dan elegan.
                </p>
                
                <!-- CTA Buttons -->
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary btn-large">Mulai Gratis</a>
                    <a href="#features" class="btn btn-secondary btn-large">Lihat Fitur</a>
                </div>
            </div>
        </section>

        <!-- FEATURES SECTION -->
        <section id="features" class="features">
            <div class="features-container">
                <h2 class="section-title reveal">Fitur Utama</h2>
                
                <div class="features-grid">
                    <!-- Feature 1: Todo -->
                    <article class="feature-card reveal-stagger">
                        <div class="feature-icon">📝</div>
                        <h3 class="feature-title">Todo List</h3>
                        <p class="feature-description">
                            Buat dan kelola daftar tugas harian Anda dengan antarmuka yang intuitif dan responsif.
                        </p>
                    </article>
                    
                    <!-- Feature 2: Calendar -->
                    <article class="feature-card reveal-stagger">
                        <div class="feature-icon">📅</div>
                        <h3 class="feature-title">Calendar</h3>
                        <p class="feature-description">
                            Visualisasi jadwal Anda dengan calendar yang powerful dan mudah digunakan.
                        </p>
                    </article>
                    
                    <!-- Feature 3: Finance -->
                    <article class="feature-card reveal-stagger">
                        <div class="feature-icon">💰</div>
                        <h3 class="feature-title">Finance Tracker</h3>
                        <p class="feature-description">
                            Pantau pengeluaran dan pendapatan Anda untuk kontrol keuangan yang lebih baik.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ABOUT SECTION -->
        <section id="about" class="about">
            <div class="about-container reveal">
                <h2 class="section-title">Tentang MySmartHub</h2>
                <p class="about-description">
                    MySmartHub adalah platform all-in-one yang dirancang untuk membantu Anda mengelola berbagai aspek kehidupan digital Anda. Dengan fitur-fitur seperti todo list, calendar, dan finance tracker, Anda dapat meningkatkan produktivitas dan efisiensi dalam satu tempat.
                </p>
            </div>
        </section>

        <!-- CTA SECTION (Optional) -->
        <section class="cta-section">
            <div class="cta-container reveal">
                <h2>Siap meningkatkan produktivitas Anda?</h2>
                <p>Bergabunglah dengan ribuan pengguna yang sudah merasakan manfaatnya.</p>
                <a href="register.php" class="btn btn-primary btn-large">Daftar Gratis Sekarang</a>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copyright">
                &copy; 2026 MySmartHub. Semua hak dilindungi.
            </p>
            <div class="footer-links">
                <a href="#privacy">Privacy</a>
                <span class="divider">•</span>
                <a href="#terms">Terms</a>
                <span class="divider">•</span>
                <a href="#contact">Kontak</a>
            </div>
        </div>
    </footer>
    <script src="assets/js/app.js"></script>
    <script>
    // ===== TYPEWRITER EFFECT =====
    (function() {
        const words = ['Digital Life', 'Produktivitas', 'Keuangan', 'Jadwal', 'Semua Hal'];
        const el = document.getElementById('hero-typewriter');
        const cursor = document.querySelector('.typewriter-cursor');
        if (!el) return;
        let wordIdx = 0, charIdx = 0, deleting = false;
        function type() {
            const current = words[wordIdx];
            if (!deleting) {
                el.textContent = current.slice(0, charIdx + 1);
                charIdx++;
                if (charIdx === current.length) {
                    deleting = true;
                    setTimeout(type, 2000);
                    return;
                }
                setTimeout(type, 90);
            } else {
                el.textContent = current.slice(0, charIdx - 1);
                charIdx--;
                if (charIdx === 0) {
                    deleting = false;
                    wordIdx = (wordIdx + 1) % words.length;
                    setTimeout(type, 400);
                    return;
                }
                setTimeout(type, 45);
            }
        }
        // Start after hero animation settles
        setTimeout(type, 900);
    })();

    // ===== CANVAS PARTICLES =====
    (function() {
        const canvas = document.getElementById('hero-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        let particles = [];
        let animFrame;

        function resize() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        function rand(min, max) { return Math.random() * (max - min) + min; }

        function createParticle() {
            return {
                x: rand(0, canvas.width),
                y: rand(0, canvas.height),
                r: rand(1, 2.5),
                dx: rand(-0.25, 0.25),
                dy: rand(-0.4, -0.1),
                alpha: rand(0.1, 0.5)
            };
        }

        for (let i = 0; i < 60; i++) particles.push(createParticle());

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(99, 102, 241, ${p.alpha})`;
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.y < -5) {
                    p.y = canvas.height + 5;
                    p.x = rand(0, canvas.width);
                }
                if (p.x < -5) p.x = canvas.width + 5;
                if (p.x > canvas.width + 5) p.x = -5;
            });
            animFrame = requestAnimationFrame(draw);
        }
        draw();
    })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MySmartHub - One Place For Your Digital Life. Kelola todo, calendar, dan keuangan dalam satu platform.">
    <title>MySmartHub | One Place For Your Digital Life</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    
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
            <div class="hero-glow"></div>
            <div class="hero-container">
                <!-- Badge -->
                <div class="badge">
                    ✨ Dirancang untuk produktivitas maksimal
                </div>
                
                <!-- Judul -->
                <h1 class="hero-title">One Place For Your<br>Digital Life</h1>
                
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
</body>
</html>

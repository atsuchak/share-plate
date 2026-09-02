<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Share Food, Share Hope.</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-dash">-</span> SharePlate
            </div>
            
            <ul class="nav-links">
                <li><a href="marketplace.php" class="active">Explore</a></li>
                <li><a href="#">How it Works</a></li>
                <li><a href="#">Our Impact</a></li>
            </ul>

            <div class="nav-actions">
                <button class="dark-mode-toggle" id="darkModeToggle">
                    <i class="fa-regular fa-moon"></i>
                </button>
                <a href="auth/login.php" class="login-btn">Log In</a>
                <a href="auth/signup.php" class="btn-primary sign-up-btn">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="badge">END HUNGER &bull; ZERO FOOD WASTE</div>
            <h1>Share Food,<br><span class="highlight-green">Share Hope.</span></h1>
            <p>Connecting surplus with hunger. We turn potential waste into thousands of daily meals for those who need them most.</p>
            <div class="hero-buttons">
                <a href="#" class="btn-primary">Donate Food</a>
                <a href="marketplace.php" class="btn-secondary">Find Food</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-container">
            <div class="stat-item primary-stat">
                <div class="stat-icon">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <div class="stat-text">
                    <span class="stat-label">GLOBAL COMMUNITY IMPACT</span>
                    <span class="stat-value">12,450 <span class="stat-unit">Meals Donated</span></span>
                </div>
            </div>
            <div class="stat-item right-align">
                <div class="stat-text">
                    <span class="stat-label">ACTIVE PARTNERS</span>
                    <span class="stat-value">842+</span>
                </div>
            </div>
            <div class="stat-item right-align">
                <div class="stat-text">
                    <span class="stat-label">LIVES REACHED</span>
                    <span class="stat-value">124</span>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="how-it-works">
        <div class="section-header">
            <h2>How it Works</h2>
            <p>A seamless process specifically designed for speed, safety, and maximum social impact.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card card-green">
                <div class="step-number">01</div>
                <h3>Register Surplus</h3>
                <p>Restaurants and groceries list their food availability on our intuitive, self-service hub.</p>
            </div>
            <div class="step-card card-blue">
                <div class="step-number">02</div>
                <h3>Rapid Distribution</h3>
                <p>Our network of volunteers picks up and delivers the food to our partner shelters in minutes.</p>
            </div>
            <div class="step-card card-yellow">
                <div class="step-number">03</div>
                <h3>Direct Support</h3>
                <p>Food is immediately shared to those in the city with families to serve nutritious, high-quality meals.</p>
            </div>
        </div>
    </section>

    <!-- More than a meal Section -->
    <section class="more-than-meal">
        <div class="mtm-container">
            <div class="mtm-image-wrapper">
                <img src="assets/img/just-a-meal.png" alt="Volunteers preparing food">
                <div class="floating-card">
                    <h4>98%</h4>
                    <p>Reduction in food waste from the top 100 restaurant partners.</p>
                </div>
            </div>
            <div class="mtm-content">
                <h2>More than just a meal.</h2>
                <p>SharePlate connects local businesses with excess high-quality food to local shelters, via optimized logistics efficiency. It is all about rebuilding dignity through community care.</p>
                <ul class="features-list">
                    <li><i class="fa-solid fa-circle-check"></i> Nutritional health for the local center</li>
                    <li><i class="fa-solid fa-circle-check"></i> Real-time impact dashboards</li>
                    <li><i class="fa-solid fa-circle-check"></i> 24/7 Community Network</li>
                </ul>
                <a href="#" class="link-with-arrow">Read Our 2023 Impact Report <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2>Ready to feed a<br>neighborhood?</h2>
            <div class="cta-buttons">
                <a href="marketplace.php" class="btn-primary">Visit Marketplace</a>
                <a href="#" class="btn-secondary-dark">Become a Partner</a>
            </div>
            <p class="cta-footer-text">NO DONATION IS TOO SMALL. EVERY PLATE COUNTS.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo">
                        <span class="logo-dash">-</span> SharePlate
                    </div>
                    <p>Connecting surplus food from generous donors to those who need it most. Together, we can end hunger and reduce food waste.</p>
                    <div class="social-links">
                        <a href="#"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h4>Platform</h4>
                    <ul>
                        <li><a href="#">Explore</a></li>
                        <li><a href="#">How it Works</a></li>
                        <li><a href="#">Our Impact</a></li>
                        <li><a href="#">Partners</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Volunteer FAQ</a></li>
                        <li><a href="#">Food Safety</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-links">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Guidelines</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 SharePlate. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Made with <i class="fa-solid fa-heart" style="color: var(--primary-green);"></i> for the community</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>

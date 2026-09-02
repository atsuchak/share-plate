<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Login</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="auth-page login-page-bg">

    <!-- Navigation -->
    <nav class="navbar auth-navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <span class="logo-dash">-</span> SharePlate
            </a>
            
            <ul class="nav-links">
                <li><a href="#" class="active-badge">Explore</a></li>
                <li><a href="#">How it Works</a></li>
                <li><a href="#">Our Impact</a></li>
            </ul>

            <div class="nav-actions">
                <a href="#" class="btn-primary">Donate Now</a>
                <button class="profile-btn">
                    <i class="fa-solid fa-user"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-container">
        <div class="auth-bg-gradient"></div>
        
        <div class="auth-card login-card">
            <div class="auth-header">
                <div class="auth-icon icon-light-green">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Join the movement to end food waste.</p>
            </div>
            
            <form class="auth-form" action="process_login.php" method="POST">
                <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<div style="color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="form-group login-form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper login-input">
                        <i class="fa-regular fa-envelope left-icon"></i>
                        <input type="email" id="email" name="email" placeholder="name@example.com" required>
                    </div>
                </div>
                
                <div class="form-group login-form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper login-input">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" id="password" name="password" placeholder="********" required>

                        <i class="fa-regular fa-eye right-icon"></i>
                    </div>
                </div>
                
                <div class="forgot-password-container">
                    <a href="forgot_password.php" class="forgot-password">FORGOT PASSWORD?</a>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn uppercase-btn">
                    SIGN IN <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>OR CONTINUE WITH</span>
            </div>
            
            <div class="social-login">
                <button class="social-btn">
                    <i class="fa-brands fa-google" style="color: #DB4437;"></i> Google
                </button>
                <button class="social-btn">
                    <i class="fa-brands fa-facebook" style="color: #1877F2;"></i> Facebook
                </button>
            </div>
            
            <p class="auth-footer-text">
                New here? <a href="signup.php">Create an account</a>
            </p>
        </div>
        
        <!-- Floating Impact Widget -->
        <div class="floating-impact-widget">
            <div class="widget-avatar" style="display: flex; align-items: center; justify-content: center; background: var(--light-green); color: var(--primary-green); font-size: 1.2rem;">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="widget-text">
                <span class="widget-label">LATEST IMPACT</span>
                <span class="widget-value">24 Meals Shared</span>
            </div>
        </div>
    </main>

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

    <script src="../assets/js/script.js"></script>
</body>
</html>

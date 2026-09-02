<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Create Account</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="auth-page">

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
        
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <h2>Join the Community</h2>
                <p>Experience the dignity of giving and receiving with SharePlate.</p>
            </div>
            
            <div class="auth-tabs">
                <button type="button" class="auth-tab">I want to Donate</button>
                <button type="button" class="auth-tab active">I need Food</button>
            </div>
            
            <form class="auth-form" action="process_signup.php" method="POST">
                <input type="hidden" name="role_id" id="role_id" value="2">
                
                <div class="form-group">
                    <label for="fullname">FULL NAME</label>
                    <div class="input-wrapper">
                        <input type="text" id="fullname" name="fullname" placeholder="John Doe" required>
                        <i class="fa-regular fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="hello@example.com" required>
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <i class="fa-regular fa-eye"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">CONFIRM PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="********" required>
                        <i class="fa-regular fa-eye"></i>
                    </div>
                </div>
                
                <div class="form-checkbox">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms & Privacy</a></label>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn">
                    Create Account <i class="fa-solid fa-arrow-right"></i>
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
                Already have an account? <a href="login.php">Log in here</a>
            </p>
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

    <script src="../assets/js/script.js?v=2"></script>
</body>
</html>

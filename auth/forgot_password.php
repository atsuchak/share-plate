<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Forgot Password</title>
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
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-container">
        <div class="auth-bg-gradient"></div>
        
        <div class="auth-card login-card">
            <div class="auth-header">
                <div class="auth-icon icon-light-green" style="background-color: #fee2e2; color: #ef4444;">
                    <i class="fa-solid fa-unlock-keyhole"></i>
                </div>
                <h2>Forgot Password?</h2>
                <p>Enter the email address associated with your account, and we'll send you a recovery code.</p>
            </div>
            
            <?php
            session_start();
            if (isset($_SESSION['error_message'])) {
                echo '<div style="color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <form class="auth-form" action="process_forgot_password.php" method="POST">
                <div class="form-group login-form-group">
                    <label for="email">Account Email Address</label>
                    <div class="input-wrapper login-input">
                        <i class="fa-regular fa-envelope left-icon"></i>
                        <input type="email" id="email" name="email" placeholder="name@example.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn uppercase-btn" style="margin-top: 30px;">
                    Send Recovery Code <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>OR</span>
            </div>
            
            <p class="auth-footer-text">
                Remembered your password? <a href="login.php">Log in here</a>
            </p>
        </div>
    </main>

    <script src="../assets/js/script.js?v=3"></script>
</body>
</html>

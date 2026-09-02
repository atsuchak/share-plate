<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Verify Email</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .verify-input {
            text-align: center;
            letter-spacing: 5px;
            font-size: 1.5rem;
            font-weight: 700;
        }
    </style>
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
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-container">
        <div class="auth-bg-gradient"></div>
        
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon" style="background-color: #dbeafe; color: #3b82f6;">
                    <i class="fa-regular fa-envelope-open"></i>
                </div>
                <h2>Verify Your Email</h2>
                <p>We've sent a 6-digit verification code to your email address. Please enter it below to complete your registration.</p>
            </div>
            
            <?php
            session_start();
            if (isset($_SESSION['error_message'])) {
                echo '<div style="color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <form class="auth-form" action="verify_code.php" method="POST">
                <div class="form-group">
                    <label for="code">VERIFICATION CODE</label>
                    <div class="input-wrapper">
                        <input type="text" id="code" name="code" class="verify-input" placeholder="------" maxlength="6" required>
                        <i class="fa-solid fa-key"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn">
                    Verify & Create Account <i class="fa-solid fa-check"></i>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>DIDN'T RECEIVE IT?</span>
            </div>
            
            <p class="auth-footer-text">
                <a href="signup.php">Go back to Sign Up</a>
            </p>
        </div>
    </main>

    <script src="../assets/js/script.js?v=3"></script>
</body>
</html>

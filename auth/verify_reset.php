<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Verify Recovery Code</title>
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
<body class="auth-page login-page-bg">

    <!-- Navigation -->
    <nav class="navbar auth-navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <span class="logo-dash">-</span> SharePlate
            </a>
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-container">
        <div class="auth-bg-gradient"></div>
        
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon" style="background-color: #fee2e2; color: #ef4444;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2>Enter Recovery Code</h2>
                <p>We've sent a 6-digit recovery code to your email. Please enter it below.</p>
            </div>
            
            <?php
            session_start();
            if (!isset($_SESSION['reset_email'])) {
                header("Location: forgot_password.php");
                exit();
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div style="color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <form class="auth-form" action="process_verify_reset.php" method="POST">
                <div class="form-group">
                    <label for="code">6-DIGIT RECOVERY CODE</label>
                    <div class="input-wrapper">
                        <input type="text" id="code" name="code" class="verify-input" placeholder="------" maxlength="6" required>
                        <i class="fa-solid fa-key"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn">
                    Verify Code <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>DIDN'T RECEIVE IT?</span>
            </div>
            
            <p class="auth-footer-text">
                <a href="forgot_password.php">Try another email</a>
            </p>
        </div>
    </main>

    <script src="../assets/js/script.js?v=3"></script>
</body>
</html>

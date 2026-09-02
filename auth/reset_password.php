<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharePlate - Set New Password</title>
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
        </div>
    </nav>

    <!-- Main Auth Section -->
    <main class="auth-container">
        <div class="auth-bg-gradient"></div>
        
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon icon-light-green">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2>Create New Password</h2>
                <p>Your identity has been verified. Please enter your new password below.</p>
            </div>
            
            <?php
            session_start();
            if (!isset($_SESSION['reset_authorized']) || $_SESSION['reset_authorized'] !== true) {
                header("Location: forgot_password.php");
                exit();
            }
            if (isset($_SESSION['error_message'])) {
                echo '<div style="color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500;">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            
            <form class="auth-form" action="process_reset.php" method="POST">
                <div class="form-group login-form-group">
                    <label for="password">NEW PASSWORD</label>
                    <div class="input-wrapper login-input">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <i class="fa-regular fa-eye right-icon fa-eye-btn"></i>
                    </div>
                </div>

                <div class="form-group login-form-group">
                    <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                    <div class="input-wrapper login-input">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="********" required>
                        <i class="fa-regular fa-eye right-icon fa-eye-btn"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary btn-block shadow-btn">
                    Update Password <i class="fa-solid fa-check"></i>
                </button>
            </form>
        </div>
    </main>

    <script src="../assets/js/script.js?v=4"></script>
</body>
</html>

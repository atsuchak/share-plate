<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 3) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Admin Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search reports...">
            </div>
            </div>
            
            <div class="header-actions">
                <a href="#" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?></span>
                        <span class="user-id">#ADMIN</span>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="dashboard-col-main" style="width: 100%;">
                
                <div style="margin-bottom: 25px;">
                    <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">System Reports</h2>
                    <p style="color: #64748b; margin: 0;">View flagged content, system health warnings, and user feedback.</p>
                </div>

                <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 50px; text-align: center;">
                    <i class="fa-solid fa-clipboard-check" style="font-size: 4rem; color: var(--primary-green); margin-bottom: 20px; opacity: 0.8;"></i>
                    <h3 style="color: var(--secondary-dark); margin-bottom: 10px; font-size: 1.5rem;">All Clear!</h3>
                    <p style="color: #64748b; max-width: 400px; margin: 0 auto;">There are no active reports or flagged issues at this time. The system is running smoothly.</p>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>

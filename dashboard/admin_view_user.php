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

if (!isset($_GET['id'])) {
    header("Location: admin_users.php");
    exit();
}

$target_user_id = (int)$_GET['id'];
$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: admin_users.php");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

// Fetch some metrics
$listings_count = 0;
$claims_count = 0;

if ($user['role_id'] == 1) {
    $res = $conn->query("SELECT COUNT(*) as total FROM food_listings WHERE donor_id = $target_user_id");
    if ($row = $res->fetch_assoc()) $listings_count = $row['total'];
} else {
    $res = $conn->query("SELECT COUNT(*) as total FROM food_claims WHERE receiver_id = $target_user_id");
    if ($row = $res->fetch_assoc()) $claims_count = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Admin Portal</title>
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
                    <input type="text" placeholder="Search users...">
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
            <div class="dashboard-col-main" style="width: 100%; max-width: 800px; margin: 0 auto;">
                
                <div style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <a href="admin_users.php" style="color: #64748b; text-decoration: none; font-size: 1.2rem;"><i class="fa-solid fa-arrow-left"></i></a>
                    <div>
                        <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">User Profile</h2>
                        <p style="color: #64748b; margin: 0;">Detailed view of user #<?php echo $user['id']; ?></p>
                    </div>
                </div>

                <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; margin-bottom: 30px;">
                    <div style="padding: 40px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 25px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #f8fafc; color: var(--primary-green); font-size: 2.5rem; display: flex; align-items: center; justify-content: center; border: 2px solid #e2e8f0;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0 0 5px 0; font-size: 1.8rem; color: #1e293b;">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                                <?php if($user['status'] == 'Suspended'): ?>
                                    <span style="background: #fee2e2; color: #ef4444; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; vertical-align: middle; margin-left: 10px;">Suspended</span>
                                <?php else: ?>
                                    <span style="background: #ecfdf5; color: #10b981; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; vertical-align: middle; margin-left: 10px;">Active</span>
                                <?php endif; ?>
                            </h3>
                            <p style="margin: 0; color: #64748b; font-size: 1rem;">
                                <?php echo ($user['role_id'] == 1) ? 'Donor Account' : 'Receiver Account'; ?> 
                                &middot; Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div style="padding: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                        <div>
                            <h4 style="margin: 0 0 15px 0; color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Contact Information</h4>
                            <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Phone:</strong> <?php echo !empty($user['phone_number']) ? htmlspecialchars($user['phone_number']) : '<em>Not provided</em>'; ?></p>
                            <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Organization:</strong> <?php echo !empty($user['organization_name']) ? htmlspecialchars($user['organization_name']) : '<em>Not provided</em>'; ?></p>
                        </div>
                        
                        <div>
                            <h4 style="margin: 0 0 15px 0; color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Activity Metrics</h4>
                            <?php if ($user['role_id'] == 1): ?>
                                <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Total Listings Created:</strong> <?php echo $listings_count; ?></p>
                            <?php else: ?>
                                <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Total Food Claims:</strong> <?php echo $claims_count; ?></p>
                            <?php endif; ?>
                            <p style="margin: 0 0 10px 0; color: #1e293b;"><strong>Email Notifications:</strong> <?php echo $user['email_notifications'] ? 'Enabled' : 'Disabled'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 40px;">
                    <h3 style="margin: 0 0 20px 0; color: #1e293b;">Danger Zone</h3>
                    <div style="display: flex; gap: 20px;">
                        <?php if($user['status'] == 'Active'): ?>
                            <form action="admin_user_actions.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to suspend this user? They will not be able to log in.');">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="suspend">
                                <button type="submit" style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; padding: 12px 24px; border-radius: 50px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-ban"></i> Suspend Account
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="admin_user_actions.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" style="background: #ecfdf5; color: #10b981; border: 1px solid #6ee7b7; padding: 12px 24px; border-radius: 50px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-check-circle"></i> Activate Account
                                </button>
                            </form>
                        <?php endif; ?>

                        <form action="admin_user_actions.php" method="POST" style="margin: 0;" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete this user? All their listings and claims will be removed. This cannot be undone.');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" style="background: #fef2f2; color: #ef4444; border: 1px solid #fca5a5; padding: 12px 24px; border-radius: 50px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-trash"></i> Delete Account
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>

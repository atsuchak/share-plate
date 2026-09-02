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
$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Portal</title>
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
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search users...">
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
                    <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">User Management</h2>
                    <p style="color: #64748b; margin: 0;">View and manage all registered users on the platform.</p>
                </div>

                <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 30px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Name</th>
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Email</th>
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Role</th>
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Status</th>
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Joined Date</th>
                                <th style="padding: 15px 10px; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">No users found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($users as $user): ?>
                                <tr style="border-bottom: 1px solid #f8fafc;">
                                    <td style="padding: 15px 10px; font-weight: 500; color: #1e293b;">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                        <?php if(!empty($user['organization_name'])): ?>
                                            <div style="font-size: 0.8rem; color: #64748b; font-weight: 400;"><?php echo htmlspecialchars($user['organization_name']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px 10px; color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td style="padding: 15px 10px;">
                                        <?php if($user['role_id'] == 1): ?>
                                            <span style="background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Donor</span>
                                        <?php else: ?>
                                            <span style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Receiver</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px 10px;">
                                        <?php if($user['status'] == 'Suspended'): ?>
                                            <span style="background: #fee2e2; color: #ef4444; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Suspended</span>
                                        <?php else: ?>
                                            <span style="background: #f1f5f9; color: #64748b; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px 10px; color: #64748b; font-size: 0.9rem;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td style="padding: 15px 10px;">
                                        <a href="admin_view_user.php?id=<?php echo $user['id']; ?>" style="background: white; border: 1px solid #cbd5e1; color: var(--secondary-dark); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-block;">View Profile</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>

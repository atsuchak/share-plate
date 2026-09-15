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

$logs = [];
$sql = "SELECT l.*, 
        COALESCE(u.full_name, a.full_name) as name, 
        COALESCE(u.email, a.email) as email 
        FROM login_logs l 
        LEFT JOIN users u ON l.user_id = u.id AND l.role_id != 3 
        LEFT JOIN admin a ON l.user_id = a.id AND l.role_id = 3 
        ORDER BY l.login_time DESC LIMIT 100";
        
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .audit-table th, .audit-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .audit-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .audit-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .user-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-icon {
            width: 40px;
            height: 40px;
            background: #e2e8f0;
            color: #475569;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search logs...">
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
                
                <div style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">Audit Logs</h2>
                        <p style="color: #64748b; margin: 0;">Monitor recent login activities across the platform.</p>
                    </div>
                </div>

                <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No login logs found.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log): 
                                        $roleName = 'Admin';
                                        $iconColor = '#8b5cf6';
                                        $bgColor = '#ede9fe';
                                        if($log['role_id'] == 1) { 
                                            $roleName = 'Donor'; 
                                            $iconColor = '#10b981';
                                            $bgColor = '#ecfdf5';
                                        }
                                        elseif($log['role_id'] == 2) { 
                                            $roleName = 'Receiver'; 
                                            $iconColor = '#3b82f6';
                                            $bgColor = '#eff6ff';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-icon" style="color: <?php echo $iconColor; ?>; background: <?php echo $bgColor; ?>;">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?></div>
                                                    <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span style="font-weight: 600; color: #475569; font-size: 0.9rem;"><?php echo $roleName; ?></span></td>
                                        <td><span style="color: #64748b; font-size: 0.9rem; font-family: monospace;"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                                        <td><span style="color: #64748b; font-size: 0.9rem;"><i class="fa-regular fa-clock" style="margin-right: 5px;"></i><?php echo date('M d, Y h:i A', strtotime($log['login_time'])); ?></span></td>
                                        <td>
                                            <?php if($log['status'] == 'Success'): ?>
                                                <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700;">Success</span>
                                            <?php else: ?>
                                                <span style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700;">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>

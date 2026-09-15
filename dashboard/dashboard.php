<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

// Helper function to calculate freshness color
function getFreshnessColor($createdAt, $expiryTime, $category) {
    $now = time();
    $expiry = strtotime($expiryTime);
    
    $hoursRemaining = ($expiry - $now) / 3600;
    
    if ($hoursRemaining <= 24) return 'red';
    elseif ($hoursRemaining <= 48) return 'yellow';
    else return 'green';
}

// Unread Notifications for Popup
$unread_count = 0;
$popup_notifications = [];
$uid = $_SESSION['user_id'];
$res_notif = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE user_id = $uid AND is_read = 0");
if ($res_notif && $row = $res_notif->fetch_assoc()) {
    $unread_count = $row['unread'];
}

$res_notif_list = $conn->query("SELECT * FROM notifications WHERE user_id = $uid ORDER BY created_at DESC LIMIT 4");
if ($res_notif_list && $res_notif_list->num_rows > 0) {
    while($row = $res_notif_list->fetch_assoc()) {
        $popup_notifications[] = $row;
    }
}

if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        date_default_timezone_set('Asia/Dhaka');
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second');
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

$role_id = $_SESSION['role_id'] ?? 1;

$recent_donations = [];
$recent_claims = [];
$total_meals_claimed = 0;
$total_money_saved = 0;

if ($role_id == 1) {
    $donor_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM food_listings WHERE donor_id = $donor_id ORDER BY created_at DESC LIMIT 3");
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $recent_donations[] = $row;
        }
    }
    
    // Total Donated (quantity + claims_count represents original amount)
    $total_donated = 0;
    $res = $conn->query("SELECT SUM(quantity + claims_count) as total FROM food_listings WHERE donor_id = $donor_id");
    if ($res && $row = $res->fetch_assoc()) {
        $total_donated = $row['total'] ? $row['total'] : 0;
    }
    
    // People Helped
    $people_helped = 0;
    $res = $conn->query("SELECT COUNT(DISTINCT receiver_id) as total FROM food_claims WHERE food_id IN (SELECT id FROM food_listings WHERE donor_id = $donor_id)");
    if ($res && $row = $res->fetch_assoc()) {
        $people_helped = $row['total'] ? $row['total'] : 0;
    }
    
    // Active Pickups (Pending Claims only)
    $active_pickups = [];
    $res = $conn->query("
        SELECT fc.*, fl.title, u.full_name as receiver_name 
        FROM food_claims fc 
        JOIN food_listings fl ON fc.food_id = fl.id 
        JOIN users u ON fc.receiver_id = u.id 
        WHERE fl.donor_id = $donor_id AND fc.status = 'Pending' 
        ORDER BY fc.created_at DESC LIMIT 3
    ");
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $active_pickups[] = $row;
        }
    }
} elseif ($role_id == 2) {
    $receiver_id = $_SESSION['user_id'];
    $result = $conn->query("
        SELECT fl.*, fc.status as claim_status 
        FROM food_listings fl 
        JOIN food_claims fc ON fl.id = fc.food_id 
        WHERE fc.receiver_id = $receiver_id 
        ORDER BY fc.created_at DESC LIMIT 3
    ");
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $recent_claims[] = $row;
        }
    }
    
    $res = $conn->query("SELECT COUNT(*) as total_qty FROM food_claims WHERE receiver_id = $receiver_id AND status = 'Approved'");
    if ($res && $row = $res->fetch_assoc()) {
        $total_meals_claimed = $row['total_qty'] ? $row['total_qty'] : 0;
    }
    $total_money_saved = $total_meals_claimed * 5; // $5 estimated per meal

    // Fetch Nearby Donations (Grouped by Donor)
    $nearby_donations = [];
    $nearby_res = $conn->query("
        SELECT u.organization_name, u.full_name, COUNT(f.id) as listing_count 
        FROM food_listings f 
        JOIN users u ON f.donor_id = u.id 
        WHERE f.status = 'Available' 
        GROUP BY f.donor_id 
        ORDER BY listing_count DESC 
        LIMIT 4
    ");
    if ($nearby_res && $nearby_res->num_rows > 0) {
        while($row = $nearby_res->fetch_assoc()) {
            $nearby_donations[] = $row;
        }
    }
} elseif ($role_id == 3) {
    // ADMIN DASHBOARD LOGIC
    $total_users = 0;
    $total_donors = 0;
    $total_receivers = 0;
    $total_listings = 0;
    $total_claims = 0;
    
    $res = $conn->query("SELECT COUNT(*) as total FROM users");
    if ($res && $row = $res->fetch_assoc()) $total_users = $row['total'];

    $res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 1");
    if ($res && $row = $res->fetch_assoc()) $total_donors = $row['total'];

    $res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
    if ($res && $row = $res->fetch_assoc()) $total_receivers = $row['total'];

    $res = $conn->query("SELECT COUNT(*) as total FROM food_listings");
    if ($res && $row = $res->fetch_assoc()) $total_listings = $row['total'];
    
    $res = $conn->query("SELECT COUNT(*) as total FROM food_claims");
    if ($res && $row = $res->fetch_assoc()) $total_claims = $row['total'];
    
    // Fetch Recent Users for the Moderation/Review Queue
    $recent_users = [];
    $res = $conn->query("SELECT id, full_name, email, role_id, status, created_at FROM users ORDER BY created_at DESC LIMIT 4");
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }
    
    // Fetch Chart Data for User Signups (Last 15 days)
    $chart_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                  FROM users 
                  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY) 
                  GROUP BY DATE(created_at) ORDER BY date ASC";
    $stmt = $conn->prepare($chart_sql);
    $stmt->execute();
    $chart_result = $stmt->get_result();

    $chart_labels_raw = [];
    $signups_data_map = [];
    while($row = $chart_result->fetch_assoc()) {
        $date_str = date('M d', strtotime($row['date']));
        $chart_labels_raw[] = $date_str;
        $signups_data_map[$date_str] = $row['count'];
    }
    $stmt->close();
    
    $signups_data = [];
    foreach ($chart_labels_raw as $lbl) {
        $signups_data[] = $signups_data_map[$lbl] ?? 0;
    }
    
    $admin_chart_labels_js = json_encode($chart_labels_raw);
    $admin_signups_data_js = json_encode($signups_data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role_id == 1 ? 'Restaurant' : 'Receiver'; ?> Dashboard - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <?php if ($role_id == 3): ?>
    <!-- Chart.js for Admin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
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
                    <input type="text" placeholder="Search donations, partners...">
                </div>
            </div>
            
            <div class="header-actions">
                <div class="notification-wrapper" style="position: relative;">
                    <button class="action-btn notification-btn" id="notificationBtn">
                        <i class="fa-regular fa-bell"></i>
                        <?php if($unread_count > 0): ?>
                        <span class="badge-dot"></span>
                        <?php endif; ?>
                    </button>
                    <!-- Notifications Widget (Popup) -->
                    <div class="widget notifications-widget popup-hidden" id="notificationPopup">
                        <div class="widget-header">
                            <h3>Notifications</h3>
                            <?php if($unread_count > 0): ?>
                            <span class="badge-new"><?php echo $unread_count; ?> NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($popup_notifications)): ?>
                                <p style="text-align:center; color:#94a3b8; padding: 20px 0; font-size: 0.9rem;">No new notifications</p>
                            <?php else: ?>
                                <?php foreach($popup_notifications as $notif): 
                                    $link = str_replace('dashboard/', '', $notif['link']);
                                    $iconClass = 'fa-bell text-blue';
                                    $bgClass = 'bg-blue-light';
                                    if (stripos($notif['title'], 'New Claim') !== false || stripos($notif['title'], 'Request') !== false) {
                                        $iconClass = 'fa-hand-holding-heart text-green';
                                        $bgClass = 'bg-green-light';
                                    } elseif (stripos($notif['title'], 'Accepted') !== false) {
                                        $iconClass = 'fa-check-circle text-green';
                                        $bgClass = 'bg-green-light';
                                    } elseif (stripos($notif['title'], 'Alert') !== false || stripos($notif['title'], 'Expiring') !== false) {
                                        $iconClass = 'fa-triangle-exclamation text-yellow';
                                        $bgClass = 'bg-yellow-light';
                                    }
                                ?>
                                <a href="<?php echo htmlspecialchars($link); ?>" class="notification-item" style="text-decoration:none; color:inherit; display:flex;">
                                    <div class="notif-icon <?php echo $bgClass; ?>">
                                        <i class="fa-solid <?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div class="notif-content">
                                        <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($notif['message'], 0, 60)) . (strlen($notif['message']) > 60 ? '...' : ''); ?></p>
                                        <span class="notif-time"><?php echo strtoupper(time_elapsed_string($notif['created_at'])); ?></span>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="notifications.php" class="view-all-center">View all activity</a>
                    </div>
                </div>

                <a href="profile.php" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                        <span class="user-id">
                            <?php 
                                $rid = $_SESSION['role_id'] ?? 1;
                                if ($rid == 1) echo 'Food Provider';
                                elseif ($rid == 2) echo 'Community Member';
                                elseif ($rid == 3) echo 'Administrator';
                            ?>
                        </span>
                    </div>
                    <?php 
                        $prefix = isset($root_prefix) ? $root_prefix : (basename($_SERVER['PHP_SELF']) == 'marketplace.php' ? '' : '../');
                        $avatarUrl = !empty($_SESSION['profile_image']) ? $prefix . $_SESSION['profile_image'] : '';
                    ?>
                    <div class="user-avatar" style="<?php echo $avatarUrl ? 'background-image: url(\'' . htmlspecialchars($avatarUrl) . '\'); background-size: cover; background-position: center;' : ''; ?>">
                        <?php if(!$avatarUrl): ?>
                            <i class="fa-solid fa-user"></i>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if ($role_id == 3): ?>
            <!-- Admin Dashboard -->
            <div class="dashboard-col-main" style="width: 100%;">
                <div class="stats-row admin-stats-row">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL USERS</span>
                            <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
                            <span class="stat-trend trend-neutral"><i class="fa-solid fa-users"></i> Platform Members</span>
                        </div>
                        <div class="stat-icon icon-blue">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL DONORS</span>
                            <h3 class="stat-value"><?php echo number_format($total_donors); ?></h3>
                            <span class="stat-trend trend-neutral"><i class="fa-solid fa-hand-holding-heart"></i> Food Providers</span>
                        </div>
                        <div class="stat-icon icon-green">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL LISTINGS</span>
                            <h3 class="stat-value"><?php echo number_format($total_listings); ?></h3>
                            <span class="stat-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> System Growth</span>
                        </div>
                        <div class="stat-icon icon-yellow">
                            <i class="fa-solid fa-store"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL CLAIMS</span>
                            <h3 class="stat-value"><?php echo number_format($total_claims); ?></h3>
                            <span class="stat-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> Successful Matchups</span>
                        </div>
                        <div class="stat-icon icon-green">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="admin-grid-layout">
                    
                    <!-- Left: Signups Chart -->
                    <div class="chart-container" style="background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; color: var(--secondary-dark);">New Signups (Last 15 Days)</h3>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="adminSignupsChart"></canvas>
                        </div>
                    </div>

                    <!-- Right: Moderation/Recent Users List -->
                    <div style="background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; color: var(--secondary-dark);">Recent Registrations</h3>
                            <a href="admin_users.php" style="color: var(--primary-green); font-size: 0.9rem; text-decoration: none; font-weight: 600;">View All</a>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php foreach($recent_users as $u): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border: 1px solid #f1f5f9; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #f8fafc; color: var(--primary-green); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; font-size: 0.9rem; color: #1e293b;"><?php echo htmlspecialchars($u['full_name']); ?></h4>
                                        <p style="margin: 2px 0 0 0; font-size: 0.75rem; color: #64748b;"><?php echo ($u['role_id']==1)?'Donor':'Receiver'; ?> &middot; <?php echo date('M d', strtotime($u['created_at'])); ?></p>
                                    </div>
                                </div>
                                <a href="admin_view_user.php?id=<?php echo $u['id']; ?>" style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-decoration: none;">Review</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <div class="impact-card" style="margin-top: 30px; background: linear-gradient(120deg, #1e293b 0%, #0f172a 100%);">
                    <div class="impact-content">
                        <span class="impact-subtitle" style="color: #94a3b8;"><span class="line" style="background: #94a3b8;"></span> SYSTEM OVERVIEW</span>
                        <h2 style="color: white;">Welcome to the SharePlate Admin Portal.</h2>
                        <p style="color: #cbd5e1;">Monitor system health, manage users, and review platform analytics from this dashboard.</p>
                        <div class="impact-actions">
                            <a href="admin_users.php" class="btn-primary" style="background: var(--primary-green); color: white;">Manage Users</a>
                            <a href="admin_reports.php" class="btn-secondary" style="background: rgba(255,255,255,0.1); color: white;">View Reports</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($role_id == 1): ?>
            <!-- Left Column (DONOR) -->
            <div class="dashboard-col-main">
                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL DONATED (PORTIONS)</span>
                            <h3 class="stat-value"><?php echo number_format($total_donated); ?></h3>
                            <span class="stat-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> Lifetime Impact</span>
                        </div>
                        <div class="stat-icon icon-green">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">PEOPLE HELPED</span>
                            <h3 class="stat-value"><?php echo number_format($people_helped); ?></h3>
                            <span class="stat-trend trend-neutral"><i class="fa-solid fa-users"></i> Unique Receivers</span>
                        </div>
                        <div class="stat-icon icon-blue">
                            <i class="fa-solid fa-hand-holding-hand"></i>
                        </div>
                    </div>
                </div>

                <!-- Impact Hero Card -->
                <div class="impact-card">
                    <div class="impact-content">
                        <span class="impact-subtitle"><span class="line"></span> MAKE AN IMPACT</span>
                        <h2>Share the surplus,<br>nourish the soul.</h2>
                        <p>Your extra meals can be a lifeline for someone nearby. Our smart routing connects your donation to those in need within minutes.</p>
                        <div class="impact-actions">
                            <button class="btn-primary" onclick="window.location.href='post_food.php'">Post a Meal <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity-section">
                    <div class="section-header-dash">
                        <h3>My Recent Donations</h3>
                        <a href="active_listings.php" class="view-all">View All <i class="fa-solid fa-angle-right"></i></a>
                    </div>
                    <div class="activity-list">
                        <?php if(empty($recent_donations)): ?>
                            <p style="text-align: center; color: #9ca3af; padding: 20px;">No recent donations found. Post a meal to get started!</p>
                        <?php else: ?>
                            <?php foreach($recent_donations as $donation): 
                                $color = getFreshnessColor($donation['created_at'], $donation['expiry_time'], $donation['category']);
                                $statusClass = 'status-' . $color;
                                $borderClass = 'border-' . $color;
                                $isExpired = (strtotime($donation['expiry_time']) < time());
                                $displayStatus = $isExpired ? 'EXPIRED' : (($color === 'red') ? 'URGENT' : (($color === 'yellow') ? 'EXPIRING SOON' : ($donation['status'] === 'Available' ? 'VERY FRESH' : strtoupper($donation['status']))));
                                
                                $imgSrc = $donation['image_path'] ? htmlspecialchars($donation['image_path']) : '../assets/img/just-a-meal.png';
                            ?>
                            <div class="activity-item <?php echo $borderClass; ?>">
                                <div class="activity-img">
                                    <img src="<?php echo $imgSrc; ?>" alt="Food">
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo htmlspecialchars($donation['title']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($donation['details'], 0, 60)) . '...'; ?></p>
                                    <div class="activity-meta">
                                        <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($donation['category']); ?></span>
                                        <span><i class="fa-regular fa-clock"></i> Exp: <?php echo date('M d, g:i A', strtotime($donation['expiry_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="activity-status <?php echo $statusClass; ?>" title="Freshness Level">
                                    <?php echo htmlspecialchars($displayStatus); ?>
                                </div>
                                <a href="food_details.php?id=<?php echo $donation['id']; ?>" class="btn-view-details" title="View Details">
                                    View Details
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column (DONOR) -->
            <div class="dashboard-col-side">
                <!-- Active Pickups Widget -->
                <div class="widget active-pickups-widget">
                    <div class="widget-header">
                        <h3>Active Pickups</h3>
                        <span class="badge-new"><?php echo count($active_pickups); ?> PENDING</span>
                    </div>
                    <div class="notification-list">
                        <?php if (empty($active_pickups)): ?>
                            <p style="color: #9ca3af; font-size: 0.9rem; text-align: center; padding: 10px 0;">No active pickups at the moment.</p>
                        <?php else: ?>
                            <?php foreach ($active_pickups as $pickup): ?>
                            <a href="incoming_requests.php" class="notification-item" style="text-decoration: none; color: inherit; display: flex; cursor: pointer;">
                                <div class="notif-icon <?php echo $pickup['status'] === 'Approved' ? 'bg-green-light' : 'bg-yellow-light'; ?>">
                                    <i class="fa-solid <?php echo $pickup['status'] === 'Approved' ? 'fa-check text-green' : 'fa-clock text-yellow'; ?>"></i>
                                </div>
                                <div class="notif-content" style="flex: 1; min-width: 0;">
                                    <h4 style="display: flex; justify-content: space-between; align-items: center; margin: 0 0 5px 0;">
                                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;"><?php echo htmlspecialchars($pickup['receiver_name']); ?></span>
                                        <span style="font-size: 0.7rem; color: #94a3b8; font-weight: normal; white-space: nowrap;"><?php echo date('M d', strtotime($pickup['created_at'])); ?></span>
                                    </h4>
                                    <p style="margin: 0 0 5px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Requested: <strong><?php echo htmlspecialchars($pickup['title']); ?></strong></p>
                                    <span style="font-size: 0.75rem; color: <?php echo $pickup['status'] === 'Approved' ? '#16a34a' : '#d97706'; ?>; font-weight: 600;"><?php echo strtoupper($pickup['status']); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Impact Summary Widget -->
                <div class="widget nearby-needs-widget">
                    <div class="widget-header">
                        <h3>Your Environmental Impact</h3>
                    </div>
                    <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); padding: 20px; border-radius: 16px; text-align: center; margin-top: 10px;">
                        <i class="fa-solid fa-leaf" style="font-size: 2.5rem; color: #166534; margin-bottom: 10px;"></i>
                        <h2 style="color: #166534; font-size: 2rem; margin: 0;"><?php echo number_format($total_donated * 0.5, 1); ?> kg</h2>
                        <p style="color: #15803d; font-weight: 600; font-size: 0.9rem; margin-top: 5px;">CO2 Emissions Prevented</p>
                    </div>
                    <p style="font-size: 0.85rem; color: #64748b; text-align: center; margin-top: 15px; line-height: 1.5;">By donating your surplus food, you are directly reducing greenhouse gases that would have been produced if the food went to a landfill.</p>
                </div>

                <!-- Invite Partners Widget -->
                <div class="widget invite-widget">
                    <h3>Invite Partners</h3>
                    <p>Know a restaurant or shelter that could benefit? Grow the network and earn Impact Points.</p>
                    <button class="btn-white-full" onclick="navigator.clipboard.writeText(window.location.origin + '/SharePlate/auth/signup.php?ref=<?php echo $_SESSION['user_id']; ?>'); alert('Invite link copied to clipboard!');">
                        <i class="fa-solid fa-share-nodes"></i> Copy Invite Link
                    </button>
                </div>
            </div>

            <?php elseif ($role_id == 2): ?>
            
            <!-- Left Column (RECEIVER) -->
            <div class="dashboard-col-main">
                <!-- Stats -->
                <div class="stats-row" style="gap: 20px;">
                    <div class="stat-card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: none; padding: 20px; border-radius: 16px;">
                        <div class="stat-info" style="width: 100%; text-align: center; gap: 0;">
                            <span class="stat-title" style="color: #92400e; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 5px; display: block;">MEALS CLAIMED</span>
                            <h3 class="stat-value" style="color: #92400e; font-size: 3.2rem; margin: 0; line-height: 1;"><?php echo number_format($total_meals_claimed); ?></h3>
                            <span class="stat-trend" style="color: #b45309; margin-top: 10px; font-size: 0.85rem;"><i class="fa-solid fa-chart-pie"></i> Total to date</span>
                        </div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: none; padding: 20px; border-radius: 16px;">
                        <div class="stat-info" style="width: 100%; text-align: center; gap: 0;">
                            <span class="stat-title" style="color: #166534; font-weight: 700; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 5px; display: block;">MONEY SAVED</span>
                            <h3 class="stat-value" style="color: #166534; font-size: 3.2rem; margin: 0; line-height: 1;">$<?php echo number_format($total_money_saved); ?></h3>
                            <span class="stat-trend" style="color: #15803d; margin-top: 10px; font-size: 0.85rem;"><i class="fa-solid fa-piggy-bank"></i> Est. value</span>
                        </div>
                    </div>
                </div>

                <!-- Impact Hero Card (RECEIVER) -->
                <div class="impact-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #162c66 100%);">
                    <div class="impact-content">
                        <span class="impact-subtitle"><span class="line"></span> FIND SURPLUS</span>
                        <h2>Quality food,<br>ready for pickup.</h2>
                        <p>Browse fresh, high-quality surplus meals and ingredients from local restaurants and grocers. Claim what you need instantly.</p>
                        <div class="impact-actions">
                            <button class="btn-primary" onclick="window.location.href='../marketplace.php'">Browse Foods <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Recent Claims (RECEIVER) -->
                <div class="recent-activity-section">
                    <div class="section-header-dash">
                        <h3>My Recent Claims</h3>
                        <a href="../marketplace.php" class="view-all">Find More <i class="fa-solid fa-angle-right"></i></a>
                    </div>
                    <div class="activity-list">
                        <?php if(empty($recent_claims)): ?>
                            <p style="text-align: center; color: #9ca3af; padding: 20px;">You haven't claimed any food yet. Browse the marketplace to find available food.</p>
                        <?php else: ?>
                            <?php foreach($recent_claims as $claim): 
                                $imgSrc = $claim['image_path'] ? htmlspecialchars($claim['image_path']) : '../assets/img/just-a-meal.png';
                            ?>
                            <div class="activity-item border-green">
                                <div class="activity-img">
                                    <img src="<?php echo $imgSrc; ?>" alt="Food">
                                </div>
                                <div class="activity-details">
                                    <h4><?php echo htmlspecialchars($claim['title']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($claim['details'], 0, 60)) . '...'; ?></p>
                                    <div class="activity-meta">
                                        <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($claim['category']); ?></span>
                                        <span><i class="fa-regular fa-clock"></i> Claimed: <?php echo date('M d, Y', strtotime($claim['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="activity-status status-green" title="Status">
                                    CLAIMED
                                </div>
                                <a href="init_conversation.php?food_id=<?php echo $claim['id']; ?>" class="btn-view-details" title="Message Donor" style="background-color: var(--primary-green); color: white; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-message"></i> Message
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column (RECEIVER) -->
            <div class="dashboard-col-side">
                <!-- Nearby Available Food Widget -->
                <div class="widget nearby-needs-widget">
                    <div class="widget-header" style="margin-bottom: 15px;">
                        <h3>Nearby Donations</h3>
                    </div>
                    <div class="needs-list">
                        <?php if(empty($nearby_donations)): ?>
                            <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 10px 0;">No donations available right now.</p>
                        <?php else: ?>
                            <?php foreach($nearby_donations as $donor): 
                                $donorName = $donor['organization_name'] ? $donor['organization_name'] : $donor['full_name'];
                            ?>
                            <div class="need-item" style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                                <span class="need-area" style="font-weight: 500; color: var(--secondary-dark);"><?php echo htmlspecialchars($donorName); ?></span>
                                <span class="need-count" style="color: var(--primary-green); font-weight: 600; background: #dcfce7; padding: 2px 8px; border-radius: 20px; font-size: 0.8rem;"><?php echo $donor['listing_count']; ?> <?php echo $donor['listing_count'] == 1 ? 'Listing' : 'Listings'; ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Saved Searches Widget -->
                <div class="widget invite-widget" style="background: var(--white); border: 1px solid var(--border-color);">
                    <h3 style="color: var(--secondary-dark);">Donation Alerts</h3>
                    <p style="color: var(--text-muted);">Get notified instantly when local businesses post new food.</p>
                    <a href="settings.php" class="btn-secondary" style="width: 100%; display: block; text-align: center;">
                        <i class="fa-solid fa-bell"></i> Manage Alerts
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <?php if ($role_id == 3 && !empty($admin_chart_labels_js)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctxAdmin = document.getElementById('adminSignupsChart').getContext('2d');
        const labelsAdmin = <?php echo $admin_chart_labels_js; ?>;
        const dataAdmin = <?php echo $admin_signups_data_js; ?>;
        
        let gradientAdmin = ctxAdmin.createLinearGradient(0, 0, 0, 300);
        gradientAdmin.addColorStop(0, 'rgba(59, 130, 246, 0.4)');   
        gradientAdmin.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctxAdmin, {
            type: 'line',
            data: {
                labels: labelsAdmin,
                datasets: [{
                    label: 'New Users',
                    data: dataAdmin,
                    borderColor: '#3b82f6',
                    backgroundColor: gradientAdmin,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 12 }, color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { 
                            font: { family: 'Inter', size: 12 }, 
                            color: '#94a3b8',
                            stepSize: 1
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
    <script>
        // Simple dark mode toggle for dashboard
        const dashToggle = document.getElementById('darkModeToggleDash');
        if (dashToggle) {
            dashToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme');
                const icon = dashToggle.querySelector('i');
                if (document.body.classList.contains('dark-theme')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        }

        // Notifications Popup Toggle
        const notifBtn = document.getElementById('notificationBtn');
        const notifPopup = document.getElementById('notificationPopup');
        if(notifBtn && notifPopup) {
            notifBtn.addEventListener('click', () => {
                notifPopup.classList.toggle('popup-show');
            });
            document.addEventListener('click', (e) => {
                if (!notifBtn.contains(e.target) && !notifPopup.contains(e.target)) {
                    notifPopup.classList.remove('popup-show');
                }
            });
        }
    </script>
</body>
</html>

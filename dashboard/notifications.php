<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

date_default_timezone_set('Asia/Dhaka');

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to calculate time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
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

$user_id = $_SESSION['user_id'];

// Mark all as read when page is visited
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");

$result = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");
$notifications = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .notifications-page-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .notifications-page-list .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            transition: background 0.3s ease;
            border: 1px solid var(--border-light);
        }
        .notifications-page-list .notification-item:hover {
            background: #f8fafc;
        }
        .notifications-page-list .notif-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .notifications-page-list .notif-content h4 {
            margin: 0 0 5px 0;
            color: var(--secondary-dark);
            font-size: 1.1rem;
        }
        .notifications-page-list .notif-content p {
            margin: 0 0 8px 0;
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .notifications-page-list .notif-time {
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .notifications-page-list {
                padding: 10px;
            }
            .notifications-page-list .notification-item {
                padding: 10px;
            }
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
                    <input type="text" placeholder="Search notifications...">
                </div>
            </div>
            <div class="header-actions">

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
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </a>
            </div>
        </header>

        <div class="post-food-content">
            <div class="post-food-header">
                <div class="header-text">
                    <span class="breadcrumb">DASHBOARD <span class="divider">/</span> <span class="active">NOTIFICATIONS</span></span>
                    <h2>All Notifications</h2>
                    <p>Stay updated on requests, milestones, and community activity.</p>
                </div>
            </div>

            <div class="notifications-page-list">
                <?php if (empty($notifications)): ?>
                    <div style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No notifications yet</h3>
                        <p>You're all caught up!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($notifications as $notif): 
                        // Fix link paths if they incorrectly include 'dashboard/' since we are already in dashboard folder
                        $link = str_replace('dashboard/', '', $notif['link']);
                        
                        // Decide icon and color based on title keywords
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
                    <a href="<?php echo htmlspecialchars($link); ?>" class="notification-item" style="text-decoration: none; color: inherit; display: flex;">
                        <div class="notif-icon <?php echo $bgClass; ?>">
                            <i class="fa-solid <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notif-content" style="flex: 1;">
                            <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                            <p><?php echo htmlspecialchars($notif['message']); ?></p>
                            <span class="notif-time"><?php echo strtoupper(time_elapsed_string($notif['created_at'])); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        const dashToggle = document.getElementById('darkModeToggleDash');
        if (dashToggle) {
            dashToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme');
            });
        }
    </script>
</body>
</html>

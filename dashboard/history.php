<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

function getFreshnessColor($createdAt, $expiryTime, $category) {
    $now = time();
    $expiry = strtotime($expiryTime);
    
    $hoursRemaining = ($expiry - $now) / 3600;
    
    if ($hoursRemaining <= 24) return 'red';
    elseif ($hoursRemaining <= 48) return 'yellow';
    else return 'green';
}

$role_id = $_SESSION['role_id'] ?? 1;
$user_id = $_SESSION['user_id'];
$listings = [];

if ($role_id == 1) {
    // Fetch ALL listings for History for Donor
    $result = $conn->query("SELECT * FROM food_listings WHERE donor_id = $user_id ORDER BY created_at DESC");
} else {
    // Fetch ALL claims for Receiver
    $result = $conn->query("
        SELECT fl.*, fc.status as claim_status, fc.created_at as claimed_at 
        FROM food_listings fl 
        JOIN food_claims fc ON fl.id = fc.food_id 
        WHERE fc.receiver_id = $user_id 
        ORDER BY fc.created_at DESC
    ");
}

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }
}

// Fetch complaints made by this user
$complaints = [];
$comp_result = $conn->query("
    SELECT c.*, u.full_name as accused_name, f.title as food_title 
    FROM complaints c 
    JOIN users u ON c.accused_id = u.id 
    LEFT JOIN food_listings f ON c.food_id = f.id 
    WHERE c.complainant_id = $user_id 
    ORDER BY c.created_at DESC
");
if ($comp_result && $comp_result->num_rows > 0) {
    while($row = $comp_result->fetch_assoc()) {
        $complaints[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        @media (max-width: 768px) {
            .post-food-content {
                margin: 0 5px 15px !important;
                padding: 15px 10px !important;
            }
            .btn-history {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            .activity-item .btn-view-details {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
            }
        }
        @media (max-width: 480px) {
            .post-food-content {
                margin: 0 0 15px !important;
                padding: 15px 10px !important;
                border-radius: 12px !important;
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
                    <input type="text" placeholder="Search your history...">
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

        <div class="post-food-content">
            <div class="post-food-header" style="align-items: center; flex-wrap: wrap; gap: 15px;">
                <div class="header-text" style="flex: 1; min-width: 250px;">
                    <span class="breadcrumb" id="top">INVENTORY <span class="divider">/</span> <span class="active">HISTORY</span></span>
                    <h2 style="margin: 0; margin-bottom: 5px;"><?php echo $role_id == 1 ? 'Donation' : 'Claim'; ?> History</h2>
                    <p style="margin: 0;">A complete record of all food items you've <?php echo $role_id == 1 ? 'posted' : 'claimed'; ?> on SharePlate.</p>
                </div>
                
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="background: #f1f5f9; padding: 4px; border-radius: 50px; display: inline-flex;">
                        <a href="#top" style="padding: 6px 16px; background: white; border-radius: 50px; text-decoration: none; color: var(--primary-green); font-weight: 600; font-size: 0.85rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;">History</a>
                        <a href="#complaints-section" style="padding: 6px 16px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.85rem; transition: all 0.2s;">Complaints</a>
                    </div>
                    <?php if ($role_id == 1): ?>
                    <a href="active_listings.php" class="btn-history" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-utensils"></i> Active Listings</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="activity-list">
                <?php if(empty($listings)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No History Yet</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">You haven't <?php echo $role_id == 1 ? 'posted' : 'claimed'; ?> any food yet.</p>
                        <a href="<?php echo $role_id == 1 ? 'post_food.php' : '../marketplace.php'; ?>" class="btn-primary"><?php echo $role_id == 1 ? 'Post Your First Meal' : 'Browse Marketplace'; ?></a>
                    </div>
                <?php else: ?>
                    <?php foreach($listings as $item): 
                        $isAvailable = ($item['status'] === 'Available');
                        $isExpired = (strtotime($item['expiry_time']) < time());
                        
                        if ($isAvailable && !$isExpired) {
                            $color = getFreshnessColor($item['created_at'], $item['expiry_time'], $item['category']);
                            $statusClass = 'status-' . $color;
                            $borderClass = 'border-' . $color;
                            $displayStatus = ($color === 'red') ? 'URGENT' : (($color === 'yellow') ? 'EXPIRING SOON' : 'VERY FRESH');
                        } else {
                            $statusClass = 'status-expired';
                            $borderClass = '';
                            $displayStatus = ($isAvailable && $isExpired) ? 'EXPIRED' : strtoupper($item['status']);
                            $isAvailable = false;
                        }
                        
                        $imgSrc = $item['image_path'] ? htmlspecialchars($item['image_path']) : '../assets/img/just-a-meal.png';
                    ?>
                    <div class="activity-item <?php echo $borderClass; ?>" style="<?php echo !$isAvailable ? 'opacity: 0.6;' : ''; ?>">
                        <div class="activity-img">
                            <img src="<?php echo $imgSrc; ?>" alt="Food" style="<?php echo !$isAvailable ? 'filter: grayscale(100%);' : ''; ?>">
                        </div>
                        <div class="activity-details">
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($item['details'], 0, 80)) . '...'; ?></p>
                            <div class="activity-meta" style="flex-wrap: wrap;">
                                <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($item['category']); ?></span>
                                <span><i class="fa-solid fa-box"></i> Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                <span><i class="fa-regular fa-calendar"></i> Posted: <?php echo date('M d, Y', strtotime($item['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="activity-status <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($displayStatus); ?>
                        </div>
                        <?php if($item['donor_id'] != $_SESSION['user_id']): ?>
                        <a href="init_conversation.php?food_id=<?php echo $item['id']; ?>" class="btn-view-details" title="Message Donor" style="background-color: var(--primary-green); color: white; border: none; gap: 6px;">
                            <i class="fa-solid fa-message"></i> Message
                        </a>
                        <?php endif; ?>
                        <a href="food_details.php?id=<?php echo $item['id']; ?>" class="btn-view-details" title="View Details">
                            View Details
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Complaints Section -->
            <div id="complaints-section" class="post-food-header" style="margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 30px; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div class="header-text" style="flex: 1; min-width: 250px;">
                    <h2 style="margin: 0; margin-bottom: 5px;">My Recent Complaints</h2>
                    <p style="margin: 0;">Status of issues you have reported.</p>
                </div>
                <div style="background: #f1f5f9; padding: 4px; border-radius: 50px; display: inline-flex;">
                    <a href="#top" style="padding: 6px 16px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.85rem; transition: all 0.2s;">History</a>
                    <a href="#complaints-section" style="padding: 6px 16px; background: white; border-radius: 50px; text-decoration: none; color: var(--primary-green); font-weight: 600; font-size: 0.85rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s;">Complaints</a>
                </div>
            </div>
            <div class="activity-list">
                <?php if(empty($complaints)): ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <i class="fa-regular fa-face-smile" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No Complaints</h3>
                        <p style="color: var(--text-muted); margin-bottom: 0;">You haven't reported any issues.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($complaints as $comp): ?>
                    <div class="activity-item" style="border-left: 4px solid <?php echo $comp['status'] == 'Pending' ? '#f59e0b' : ($comp['status'] == 'Resolved' ? '#10b981' : '#3b82f6'); ?>">
                        <div class="activity-details" style="padding-left: 15px;">
                            <h4 style="color: #ef4444;">Report against: <?php echo htmlspecialchars($comp['accused_name']); ?></h4>
                            <p style="font-weight: 500; color: #475569;">Food: <?php echo htmlspecialchars($comp['food_title']); ?></p>
                            <p style="font-size: 0.9rem; color: #64748b; margin-top: 8px;">"<?php echo htmlspecialchars($comp['complaint_text']); ?>"</p>
                            <div class="activity-meta" style="margin-top: 10px;">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($comp['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="activity-status" style="background-color: <?php echo $comp['status'] == 'Pending' ? '#fef3c7' : ($comp['status'] == 'Resolved' ? '#dcfce7' : '#dbeafe'); ?>; color: <?php echo $comp['status'] == 'Pending' ? '#d97706' : ($comp['status'] == 'Resolved' ? '#166534' : '#1d4ed8'); ?>; font-weight: 700; padding: 6px 12px; border-radius: 50px; font-size: 0.8rem;">
                            <?php echo strtoupper($comp['status']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        </div>
    </main>
    
    <!-- Scroll to top button -->
    <a href="#top" id="scrollTopBtn" style="position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; background-color: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; transition: transform 0.2s; font-size: 1.2rem;">
        <i class="fa-solid fa-arrow-up"></i>
    </a>

    <script src="../assets/js/script.js"></script>
    <script>
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
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>

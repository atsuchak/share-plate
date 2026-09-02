<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

function getFreshnessColor($createdAt, $expiryTime, $category) {
    $now = time();
    $expiry = strtotime($expiryTime);
    $created = strtotime($createdAt);
    
    if ($expiry - $now <= 24 * 3600) {
        return 'red';
    }
    
    $hoursSinceCreation = ($now - $created) / 3600;
    
    if ($category === 'Other' || $category === 'Fresh Produce' || $category === 'Dairy & Eggs') {
        if ($hoursSinceCreation <= 24) return 'green';
        elseif ($hoursSinceCreation <= 48) return 'yellow';
        else return 'red';
    } else {
        if ($hoursSinceCreation <= 12) return 'green';
        elseif ($hoursSinceCreation <= 24) return 'yellow';
        else return 'red';
    }
}

$donor_id = $_SESSION['user_id'];
// Fetch ONLY Available listings that are not expired
$result = $conn->query("SELECT * FROM food_listings WHERE donor_id = $donor_id AND status = 'Available' AND expiry_time >= NOW() ORDER BY created_at DESC");
$listings = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Listings - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search your listings...">
            </div>
            <div class="header-actions">

                <a href="profile.php" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                        <span class="user-id">#<?php echo htmlspecialchars(substr(strtoupper(md5($_SESSION['user_id'] ?? 'E895')), 0, 4)); ?></span>
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
                    <span class="breadcrumb">INVENTORY <span class="divider">/</span> <span class="active">ACTIVE LISTINGS</span></span>
                    <h2>Active Listings</h2>
                    <p>These are your foods currently available for pickup.</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="incoming_requests.php" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; background: #f8fafc; color: var(--secondary-dark); padding: 10px 20px; border-radius: 50px; font-weight: 600; text-decoration: none;"><i class="fa-solid fa-bell" style="color: #f59e0b;"></i> Incoming Requests</a>
                    <a href="post_food.php" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-circle-plus"></i> Post Food</a>
                </div>
            </div>

            <div class="activity-list">
                <?php if(empty($listings)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fa-solid fa-box-open" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No Active Listings</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">You don't have any food currently available for donation.</p>
                        <a href="post_food.php" class="btn-primary">Post Food Now</a>
                    </div>
                <?php else: ?>
                    <?php foreach($listings as $item): 
                        $color = getFreshnessColor($item['created_at'], $item['expiry_time'], $item['category']);
                        $statusClass = 'status-' . $color;
                        $borderClass = 'border-' . $color;
                        $isExpired = (strtotime($item['expiry_time']) < time());
                        $displayStatus = $isExpired ? 'EXPIRED' : (($color === 'red') ? 'EXPIRING SOON' : strtoupper($item['status']));
                        
                        $imgSrc = $item['image_path'] ? htmlspecialchars($item['image_path']) : '../assets/img/just-a-meal.png';
                    ?>
                    <div class="activity-item-wrapper <?php echo $borderClass; ?>" style="background: #ffffff; border-radius: 16px; margin-bottom: 15px; border-left-width: 4px; border-left-style: solid; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; overflow: hidden;">
                        <div class="activity-item" style="margin-bottom: 0; border: none; box-shadow: none; border-radius: 0; background: transparent; padding: 20px;">
                        <div class="activity-img">
                            <img src="<?php echo $imgSrc; ?>" alt="Food">
                        </div>
                        <div class="activity-details">
                            <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($item['details'], 0, 80)) . '...'; ?></p>
                            <div class="activity-meta" style="flex-wrap: wrap;">
                                <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($item['category']); ?></span>
                                <span><i class="fa-solid fa-box"></i> Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($item['pickup_location']); ?></span>
                                <span><i class="fa-regular fa-clock"></i> Exp: <?php echo date('M d, g:i A', strtotime($item['expiry_time'])); ?></span>
                            </div>
                        </div>
                            <div class="activity-status <?php echo $statusClass; ?>" title="Freshness Level">
                                <?php echo htmlspecialchars($displayStatus); ?>
                            </div>
                        <?php if($item['donor_id'] != $_SESSION['user_id']): ?>
                        <a href="init_conversation.php?food_id=<?php echo $item['id']; ?>" class="btn-view-details" title="Message Donor" style="background-color: var(--primary-green); color: white; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-message"></i> Message
                        </a>
                        <?php endif; ?>
                        <a href="food_details.php?id=<?php echo $item['id']; ?>" class="btn-view-details" title="View Details">
                            View Details
                        </a>
                        </div>
                    </div>
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
    </script>
</body>
</html>

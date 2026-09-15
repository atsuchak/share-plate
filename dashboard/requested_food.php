<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 2) {
    header("Location: dashboard.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

$receiver_id = $_SESSION['user_id'];
// Fetch all requests by this receiver
$query = "SELECT fc.*, fl.title as food_title, fl.image_path, fl.pickup_location, u.full_name as donor_name, fl.id as listing_id
          FROM food_claims fc 
          JOIN food_listings fl ON fc.food_id = fl.id 
          JOIN users u ON fl.donor_id = u.id 
          WHERE fc.receiver_id = $receiver_id 
          ORDER BY fc.created_at DESC";
$result = $conn->query($query);

$claims = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $claims[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requested Food - SharePlate</title>
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
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search requests...">
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
                    <span class="breadcrumb"><a href="dashboard.php" style="color:inherit; text-decoration:none;">OVERVIEW</a> <span class="divider">/</span> <span class="active">REQUESTED FOOD</span></span>
                    <h2>Requested Food</h2>
                    <p>Track the status of the food donations you have requested to claim.</p>
                </div>
                <a href="../marketplace.php" class="btn-history" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-store"></i> Browse Marketplace</a>
            </div>

            <div class="activity-list">
                <?php if(empty($claims)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fa-solid fa-box-open" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No Requested Food</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">You haven't requested any food yet. Check out the marketplace to see what's available!</p>
                        <a href="../marketplace.php" class="btn-primary">Go to Marketplace</a>
                    </div>
                <?php else: ?>
                    <?php foreach($claims as $claim): 
                        $imgSrc = $claim['image_path'] ? htmlspecialchars($claim['image_path']) : '../assets/img/just-a-meal.png';
                        if (strpos($imgSrc, '../') === 0) {
                            // If we are in dashboard, paths starting with ../ should be correct assuming uploads are in assets/uploads/
                            // But wait, image paths in DB are often relative to root, e.g. ../assets/uploads/...
                            // Actually, let's just output as is, since incoming_requests.php does the same
                        }
                        
                        $statusClass = 'status-green'; // Default
                        $statusText = 'PENDING';
                        $statusIcon = 'fa-clock';
                        if ($claim['status'] === 'Approved') {
                            $statusClass = 'status-green'; // Assuming we have a green status
                            $statusText = 'APPROVED';
                            $statusIcon = 'fa-check-circle';
                        } elseif ($claim['status'] === 'Rejected') {
                            $statusClass = 'status-red';
                            $statusText = 'REJECTED';
                            $statusIcon = 'fa-times-circle';
                        } else {
                            $statusClass = 'status-yellow';
                            $statusText = 'PENDING';
                            $statusIcon = 'fa-clock';
                        }
                    ?>
                    <div class="activity-item-wrapper req-card" style="border-radius: 16px; margin-bottom: 20px; <?php echo $claim['status'] === 'Approved' ? 'border-left: 4px solid var(--primary-green);' : ($claim['status'] === 'Rejected' ? 'border-left: 4px solid #ef4444;' : 'border-left: 4px solid #eab308;'); ?> box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden;">
                        <div class="req-card-inner">
                            <div class="req-card-img">
                                <img src="<?php echo $imgSrc; ?>" alt="Food">
                            </div>
                            <div class="req-card-content">
                                <div class="req-card-header">
                                    <div>
                                        <h3 style="color: var(--secondary-dark); margin-top: 0; margin-bottom: 5px; font-size: 1.1rem;">Requested <span style="color: var(--primary-green);"><?php echo htmlspecialchars($claim['food_title']); ?></span></h3>
                                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 12px;">Donor: <strong><?php echo htmlspecialchars($claim['donor_name']); ?></strong></p>
                                    </div>
                                    <div class="activity-status <?php echo $statusClass; ?>" style="margin: 0;"><i class="fa-regular <?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?></div>
                                </div>
                                
                                <div class="req-details-box">
                                    <p style="margin: 0 0 8px 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-location-dot" style="width: 20px;"></i> Pickup Location:</strong> <?php echo htmlspecialchars($claim['pickup_location']); ?></p>
                                    <?php if(!empty($claim['message'])): ?>
                                        <p style="margin: 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-message" style="width: 20px;"></i> Your Message:</strong> <i>"<?php echo htmlspecialchars($claim['message']); ?>"</i></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="req-card-footer">
                                    <span style="font-size: 0.8rem; color: #94a3b8; margin-right: auto;"><i class="fa-regular fa-calendar"></i> Requested on <?php echo date('M d, Y \a\t g:i A', strtotime($claim['created_at'])); ?></span>
                                    <a href="food_details.php?id=<?php echo $claim['listing_id']; ?>" class="btn-view-details req-btn-gray">
                                        <i class="fa-solid fa-eye"></i> View Listing
                                    </a>
                                </div>
                            </div>
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
            });
        }
    </script>
</body>
</html>

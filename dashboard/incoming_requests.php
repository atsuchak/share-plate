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

$donor_id = $_SESSION['user_id'];
// Fetch ONLY Pending incoming requests
$query = "SELECT fc.*, fl.title as food_title, fl.image_path, u.full_name as receiver_name 
          FROM food_claims fc 
          JOIN food_listings fl ON fc.food_id = fl.id 
          JOIN users u ON fc.receiver_id = u.id 
          WHERE fl.donor_id = $donor_id AND fc.status = 'Pending' 
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
    <title>Incoming Requests - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=3">
    <style>
        @media (max-width: 768px) {
            .req-card-inner {
                flex-direction: column !important;
                gap: 15px !important;
            }
            .req-card-img {
                width: 100% !important;
                height: 180px !important;
            }
            .req-card-header {
                position: relative;
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .req-status {
                position: static !important;
                display: inline-block;
            }
            .req-card-header h3 {
                font-size: 1.1rem !important;
            }
            .req-card-actions {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px !important;
            }
            .req-date {
                margin-right: 0 !important;
                margin-bottom: 5px;
                text-align: center;
            }
            .req-btn-gray, .req-accept-btn {
                width: 100%;
                justify-content: center;
            }
            .req-form {
                width: 100%;
                margin: 0;
            }
            .btn-view-details {
                margin-left: 0 !important;
            }
            .btn-history {
                width: 100%;
                justify-content: center;
            }
        }
        @media (max-width: 480px) {
            .req-card-img {
                height: 150px !important;
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
                    <span class="breadcrumb"><a href="dashboard.php" style="color:inherit; text-decoration:none;">INVENTORY</a> <span class="divider">/</span> <span class="active">INCOMING REQUESTS</span></span>
                    <h2>Incoming Requests</h2>
                    <p>Review and accept food requests from receivers.</p>
                </div>
                <a href="active_listings.php" class="btn-history" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-arrow-left"></i> Back to Listings</a>
            </div>

            <div class="activity-list">
                <?php if(empty($claims)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fa-regular fa-envelope-open" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 5px;">No Pending Requests</h3>
                        <p style="color: var(--text-muted); margin-bottom: 20px;">You don't have any incoming requests to review right now.</p>
                        <a href="active_listings.php" class="btn-primary">View Active Listings</a>
                    </div>
                <?php else: ?>
                    <?php foreach($claims as $claim): 
                        $imgSrc = $claim['image_path'] ? htmlspecialchars($claim['image_path']) : '../assets/img/just-a-meal.png';
                    ?>
                    <div class="activity-item-wrapper req-card" style="border-radius: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-green); box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden;">
                        <div class="req-card-inner" style="padding: 20px; display: flex; gap: 20px; align-items: flex-start;">
                            <div class="req-card-img" style="width: 100px; height: 100px; border-radius: 12px; overflow: hidden; flex-shrink: 0;">
                                <img src="<?php echo $imgSrc; ?>" alt="Food" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="req-card-content" style="flex: 1; width: 100%;">
                                <div class="req-card-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h3 style="color: var(--secondary-dark); margin-top: 0; margin-bottom: 5px; font-size: 1.1rem;">Request from <span style="color: var(--primary-green);"><?php echo htmlspecialchars($claim['receiver_name']); ?></span></h3>
                                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 12px;">For: <strong><?php echo htmlspecialchars($claim['food_title']); ?></strong></p>
                                    </div>
                                    <div class="activity-status status-green req-status" style="margin: 0;"><i class="fa-regular fa-clock"></i> PENDING</div>
                                </div>
                                
                                
                                <?php if(!empty($claim['contact_number']) || !empty($claim['address']) || !empty($claim['reason']) || !empty($claim['message'])): ?>
                                <div class="req-info" style="margin-top: 15px; margin-bottom: 15px;">
                                    <?php if(!empty($claim['contact_number'])): ?>
                                        <p style="margin: 0 0 8px 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-phone" style="width: 20px;"></i> Contact:</strong> <?php echo htmlspecialchars($claim['contact_number']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($claim['address'])): ?>
                                        <p style="margin: 0 0 8px 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-location-dot" style="width: 20px;"></i> Address:</strong> <?php echo htmlspecialchars($claim['address']); ?></p>
                                    <?php endif; ?>

                                    <?php if(!empty($claim['reason'])): ?>
                                        <p style="margin: 0 0 8px 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-circle-info" style="width: 20px;"></i> Reason:</strong> <?php echo htmlspecialchars($claim['reason']); ?></p>
                                    <?php endif; ?>

                                    <?php if(!empty($claim['message'])): ?>
                                        <p style="margin: 0; font-size: 0.95rem; color: #475569;"><strong><i class="fa-solid fa-message" style="width: 20px;"></i> Message:</strong> <i>"<?php echo htmlspecialchars($claim['message']); ?>"</i></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="req-card-actions" style="display: flex; justify-content: flex-end; gap: 10px; align-items: center;">
                                    <span class="req-date" style="font-size: 0.8rem; color: #94a3b8; margin-right: auto;"><i class="fa-regular fa-calendar"></i> Requested on <?php echo date('M d, g:i A', strtotime($claim['created_at'])); ?></span>
                                    <a href="init_conversation.php?food_id=<?php echo $claim['food_id']; ?>&receiver_id=<?php echo $claim['receiver_id']; ?>" class="btn-view-details req-btn-gray" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                        <i class="fa-solid fa-message"></i> Message
                                    </a>
                                    <form action="accept_request.php" method="POST" style="margin: 0;" class="req-form">
                                        <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                        <button type="submit" class="req-accept-btn" style="background: var(--primary-green); color: white; border: none; padding: 8px 20px; border-radius: 50px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(34,197,94,0.3); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                            <i class="fa-solid fa-check"></i> Accept Request
                                        </button>
                                    </form>
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

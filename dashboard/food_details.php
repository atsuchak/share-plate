<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$food_id = (int)$_GET['id'];

// Fetch the food listing joined with the users table to get donor name
$sql = "SELECT f.*, u.full_name as donor_name 
        FROM food_listings f 
        JOIN users u ON f.donor_id = u.id 
        WHERE f.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $food_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Food not found
    header("Location: dashboard.php");
    exit();
}

$food = $result->fetch_assoc();
$stmt->close();

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

$freshnessColor = getFreshnessColor($food['created_at'], $food['expiry_time'], $food['category']);
$statusClass = 'status-' . $freshnessColor;
$isExpired = (strtotime($food['expiry_time']) < time());
if ($food['status'] === 'Available') {
    $displayStatus = $isExpired ? 'EXPIRED' : (($freshnessColor === 'red') ? 'EXPIRING SOON' : 'AVAILABLE');
} else {
    $displayStatus = strtoupper($food['status']);
}

if($food['status'] === 'Completed' || $isExpired) {
    $statusClass = 'status-expired';
}

$imgSrc = $food['image_path'] ? htmlspecialchars($food['image_path']) : '../assets/img/just-a-meal.png';

// Setup Javascript countdown variables
$expiryTimeJS = date('Y-m-d\TH:i:s', strtotime($food['expiry_time']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($food['title']); ?> - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        /* Specific styles for food details layout that don't need to pollute dashboard.css */
        .food-details-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
            margin-top: 20px;
        }
        @media (max-width: 1024px) {
            .food-details-grid { grid-template-columns: 1fr; }
        }
        .hero-img-container {
            width: 100%;
            height: 350px;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            position: relative;
        }
        .hero-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .details-title-tag {
            color: var(--primary-green);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: inline-block;
        }
        .details-title {
            font-size: 3rem;
            line-height: 1.1;
            color: var(--secondary-dark);
            margin-bottom: 20px;
        }
        .prepared-by-box {
            background-color: #f0fdf4;
            border-radius: 16px;
            padding: 15px 25px;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            border: 1px solid #dcfce7;
        }
        .prepared-icon {
            width: 40px;
            height: 40px;
            background-color: #bbf7d0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            font-size: 1.2rem;
        }
        .prepared-text span {
            display: block;
            font-size: 0.75rem;
            color: #166534;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .prepared-text strong {
            font-size: 1.1rem;
            color: var(--secondary-dark);
        }
        .details-desc {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .tag-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        .tag-pill {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: var(--bg-light);
            color: var(--secondary-dark);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .pickup-card {
            background-color: #f8fafc;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .pickup-info {
            padding: 20px;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .pickup-icon {
            width: 45px;
            height: 45px;
            background-color: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1.2rem;
        }
        
        /* Right Side Panel */
        .action-panel {
            background: linear-gradient(145deg, #f0fdf4 0%, #ffffff 100%);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            border: 1px solid #f0fdf4;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: sticky;
            top: 40px;
        }
        .portions-number {
            font-family: 'Playfair Display', serif;
            font-size: 5rem;
            color: var(--primary-green);
            line-height: 1;
            margin-bottom: 5px;
        }
        .portions-text {
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 30px;
        }
        .countdown-box {
            background-color: #f8fafc;
            width: 100%;
            padding: 25px 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        .countdown-title {
            color: #ef4444;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .timer-display {
            font-size: 2.2rem;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: var(--secondary-dark);
            letter-spacing: 0;
        }
        .btn-claim {
            width: 100%;
            padding: 20px;
            border-radius: 50px;
            background-color: #86efac;
            color: #166534;
            font-size: 1.3rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(134, 239, 172, 0.4);
            margin-bottom: 15px;
            text-decoration: none;
        }
        .btn-claim:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(134, 239, 172, 0.6);
            background-color: #4ade80;
        }
        .btn-claim.disabled {
            background-color: #e2e8f0;
            color: #94a3b8;
            box-shadow: none;
            cursor: not-allowed;
            pointer-events: none;
        }
        .no-cost-text {
            font-style: italic;
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 40px;
        }
        .stats-row-small {
            display: flex;
            justify-content: space-between;
            width: 100%;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .stat-small {
            flex: 1;
        }
        .stat-small i {
            color: var(--primary-green);
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        .stat-small strong {
            display: block;
            color: var(--secondary-dark);
            font-size: 1rem;
        }
        .stat-small span {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
        
        body.dark-theme .food-details-grid .details-title { color: #f8fafc; }
        body.dark-theme .food-details-grid .details-desc { color: #cbd5e1; }
        body.dark-theme .food-details-grid .prepared-by-box { background-color: #064e3b; border-color: #065f46; }
        body.dark-theme .food-details-grid .prepared-text strong { color: #f8fafc; }
        body.dark-theme .food-details-grid .tag-pill { background-color: #334155; color: #f1f5f9; }
        body.dark-theme .food-details-grid .pickup-card, body.dark-theme .food-details-grid .pickup-info { background-color: #1e293b; border-color: #334155; }
        body.dark-theme .food-details-grid .pickup-icon { background-color: #334155; }
        body.dark-theme .food-details-grid .action-panel { background: #1e293b; border-color: #334155; }
        body.dark-theme .food-details-grid .countdown-box { background-color: #0f172a; border-color: #334155; }
        body.dark-theme .food-details-grid .stats-row-small { border-color: #334155; }
        body.dark-theme .food-details-grid .stat-small strong { color: #f1f5f9; }
    </style>
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search...">
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

        <div class="post-food-content" style="background: transparent; border: none; box-shadow: none; padding-top: 10px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
                <a href="javascript:history.back()" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; color: #475569; text-decoration: none; transition: background 0.2s; font-size: 1.1rem;">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <div class="header-text" style="margin: 0;">
                    <span class="breadcrumb"><a href="dashboard.php" style="color:inherit; text-decoration:none;">OVERVIEW</a> <span class="divider">/</span> <span class="active">FOOD DETAILS</span></span>
                </div>
            </div>

            <div class="food-details-grid">
                <!-- Left Column -->
                <div class="details-left">
                    <div class="hero-img-container">
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($food['title']); ?>">
                        <div style="position: absolute; bottom: 20px; right: 20px; background: rgba(255,255,255,0.9); padding: 8px 16px; border-radius: 50px; font-weight: 700; color: var(--secondary-dark); backdrop-filter: blur(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <i class="fa-solid fa-camera"></i> Actual Photo
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 10px; align-items: center;">
                        <span class="details-title-tag" style="margin: 0;"><i class="fa-solid fa-leaf"></i> <?php echo htmlspecialchars($food['category']); ?></span>
                        <div class="activity-status <?php echo $statusClass; ?>" style="margin: 0;">
                            <?php echo htmlspecialchars($displayStatus); ?>
                        </div>
                    </div>
                    <h1 class="details-title"><?php echo htmlspecialchars($food['title']); ?></h1>
                    
                    <div class="prepared-by-box" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; width: 100%; box-sizing: border-box;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="prepared-icon">
                                <i class="fa-solid fa-kitchen-set"></i>
                            </div>
                            <div class="prepared-text">
                                <span>Prepared By</span>
                                <strong style="display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($food['donor_name']); ?></strong>
                                <div style="font-size: 0.8rem; color: #166534; display: flex; align-items: center; gap: 6px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($food['pickup_location']); ?>">
                                    <i class="fa-solid fa-location-dot" style="opacity: 0.7;"></i> <?php echo htmlspecialchars($food['pickup_location']); ?>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 20px; padding-left: 20px; border-left: 1px solid #dcfce7; flex-wrap: wrap;">
                            <span style="font-size: 0.95rem; color: #166534; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                                <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($food['contact_info']); ?>
                            </span>
                            <span style="font-size: 0.95rem; color: #166534; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                                <i class="fa-regular fa-calendar-check"></i> Posted <?php echo date('M d, Y', strtotime($food['created_at'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <p class="details-desc">
                        <?php echo nl2br(htmlspecialchars($food['details'])); ?>
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 35px;">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column;">
                            <i class="fa-solid fa-temperature-arrow-down" style="color: #3b82f6; font-size: 1.5rem; margin-bottom: 12px;"></i>
                            <h5 style="color: var(--secondary-dark); margin-bottom: 5px; font-size: 1.05rem;">Storage Guidelines</h5>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 0; line-height: 1.5;">Keep refrigerated below 4°C if not consuming immediately to maintain freshness.</p>
                        </div>
                        <div style="background: #fdf4ff; padding: 20px; border-radius: 16px; border: 1px solid #f9a8d4; display: flex; flex-direction: column;">
                            <i class="fa-solid fa-shield-heart" style="color: #ec4899; font-size: 1.5rem; margin-bottom: 12px;"></i>
                            <h5 style="color: var(--secondary-dark); margin-bottom: 5px; font-size: 1.05rem;">Quality Assured</h5>
                            <p style="color: #64748b; font-size: 0.9rem; margin: 0; line-height: 1.5;">Prepared in a clean environment following standard food safety guidelines.</p>
                        </div>
                    </div>
                    

                </div>

                <!-- Right Column -->
                <div class="details-right">
                    <div class="action-panel">
                        <div class="portions-number"><?php echo htmlspecialchars($food['quantity']); ?></div>
                        <div class="portions-text">Portions Available</div>
                        
                        <div class="countdown-box">
                            <div class="countdown-title"><i class="fa-regular fa-clock"></i> Expiring In</div>
                            <div class="timer-display" id="timerDisplay">00:00:00</div>
                        </div>
                        
                        <?php if($food['donor_id'] != $_SESSION['user_id']): ?>
                            <a href="init_conversation.php?food_id=<?php echo $food['id']; ?>" class="btn-claim" style="background-color: var(--primary-green); color: white; margin-bottom: 15px;">
                                <i class="fa-solid fa-message"></i> Message Donor
                            </a>
                        <?php endif; ?>
                        
                        <?php if($food['donor_id'] != $_SESSION['user_id']): ?>
                            <?php if($food['status'] === 'Available'): ?>
                                <button class="btn-claim" id="openClaimModalBtn">Claim This Food <i class="fa-solid fa-arrow-right"></i></button>
                                <span class="no-cost-text">No-cost donation</span>
                            <?php else: ?>
                                <button class="btn-claim disabled"><?php echo strtoupper($food['status']); ?></button>
                                <span class="no-cost-text">This food is no longer available</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn-claim disabled" style="background-color: #f1f5f9; color: #64748b; box-shadow: none;">Your Listing</button>
                            <span class="no-cost-text">You cannot claim your own food</span>
                        <?php endif; ?>
                        
                        <div class="stats-row-small">
                            <div class="stat-small">
                                <i class="fa-solid fa-users"></i>
                                <strong><?php echo (int)$food['claims_count']; ?> Claims</strong>
                                <span>TOTAL</span>
                            </div>
                            <div class="stat-small">
                                <i class="fa-solid fa-leaf"></i>
                                <strong><?php echo number_format($food['quantity'] * 0.5, 1); ?>kg CO2</strong>
                                <span>EST. SAVED</span>
                            </div>
                        </div>
                        
                        <div class="pickup-card" style="margin-top: 25px; width: 100%; text-align: left; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;">
                            <div class="pickup-info" style="padding: 15px; gap: 15px; display: flex; align-items: center; border-radius: 16px;">
                                <div class="pickup-icon" style="width: 45px; height: 45px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #64748b;"><i class="fa-solid fa-location-dot"></i></div>
                                <div style="flex: 1;">
                                    <strong style="display:block; color: var(--secondary-dark); margin-bottom: 4px; font-size: 0.95rem;">Pickup Location</strong>
                                    <span style="font-size: 0.85rem; color: #64748b; line-height: 1.4; display: block;"><?php echo htmlspecialchars($food['pickup_location']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Claim Modal -->
    <div id="claimModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 1.5rem; color: #1e293b;">Claim Food Request</h3>
                <button id="closeClaimModalBtn" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
            </div>
            <form action="process_claim.php" method="POST">
                <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                
                <div style="margin-bottom: 15px;">
                    <label for="contact_number" style="display: block; font-weight: 600; color: #475569; margin-bottom: 8px;">Contact Number *</label>
                    <input type="text" name="contact_number" id="contact_number" required style="width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box;" placeholder="Your phone number">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="address" style="display: block; font-weight: 600; color: #475569; margin-bottom: 8px;">Address</label>
                    <textarea name="address" id="address" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box; resize: vertical;" placeholder="Your full address"></textarea>
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="reason" style="display: block; font-weight: 600; color: #475569; margin-bottom: 8px;">Reason</label>
                    <textarea name="reason" id="reason" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box; resize: vertical;" placeholder="Why do you need this?"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="claim_message" style="display: block; font-weight: 600; color: #475569; margin-bottom: 8px;">Message to Donor</label>
                    <textarea name="claim_message" id="claim_message" rows="2" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box; resize: vertical;" placeholder="Hi! I can pick this up today at..."></textarea>
                    <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px; margin-bottom: 0;">Provide any pickup details or ask questions to the donor.</p>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 15px;">
                    <button type="button" id="cancelClaimBtn" style="padding: 10px 20px; border-radius: 50px; border: 1px solid #e2e8f0; background: #ffffff; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 24px; border-radius: 50px; border: none; background: var(--primary-green); color: white; font-weight: 600; cursor: pointer;">Submit Claim Request</button>
                </div>
            </form>
        </div>
    </div>

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

        // Countdown Timer Logic
        const expiryDate = new Date("<?php echo $expiryTimeJS; ?>").getTime();
        const timerDisplay = document.getElementById("timerDisplay");
        const status = "<?php echo $food['status']; ?>";

        function updateTimer() {
            if (status !== 'Available') {
                timerDisplay.innerHTML = "00:00:00";
                return;
            }

            const now = new Date().getTime();
            const distance = expiryDate - now;

            if (distance < 0) {
                timerDisplay.innerHTML = "EXPIRED";
                timerDisplay.style.color = "#ef4444";
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)) + Math.floor(distance / (1000 * 60 * 60 * 24)) * 24;
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerDisplay.innerHTML = 
                (hours < 10 ? "0" + hours : hours) + ":" + 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);
        }

        if(timerDisplay) {
            updateTimer();
            setInterval(updateTimer, 1000);
        }
        
        // Modal Logic
        const claimModal = document.getElementById('claimModal');
        const openClaimModalBtn = document.getElementById('openClaimModalBtn');
        const closeClaimModalBtn = document.getElementById('closeClaimModalBtn');
        const cancelClaimBtn = document.getElementById('cancelClaimBtn');
        
        if (openClaimModalBtn && claimModal) {
            openClaimModalBtn.addEventListener('click', () => {
                claimModal.style.display = 'flex';
            });
            
            closeClaimModalBtn.addEventListener('click', () => {
                claimModal.style.display = 'none';
            });
            
            cancelClaimBtn.addEventListener('click', () => {
                claimModal.style.display = 'none';
            });
            
            // Close on outside click
            claimModal.addEventListener('click', (e) => {
                if (e.target === claimModal) {
                    claimModal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

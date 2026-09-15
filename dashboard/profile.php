<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

$user_id = $_SESSION['user_id'];
// Compute stats
$is_admin = (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3);

if ($is_admin) {
    $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $user['role_id'] = 3;
    $role = 'Administrator';
    
    $stats_donations = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $stats_active = $conn->query("SELECT COUNT(*) FROM food_listings")->fetch_row()[0];
    $recent_donations = [];
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = ($user['role_id'] == 1) ? 'Donor' : 'Receiver';

    if ($user['role_id'] == 1) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_donations FROM food_listings WHERE donor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_donations = $stmt->get_result()->fetch_assoc()['total_donations'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as total_active FROM food_listings WHERE donor_id = ? AND status = 'Available'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_active = $stmt->get_result()->fetch_assoc()['total_active'];
        $stmt->close();

        $recent_result = $conn->query("SELECT * FROM food_listings WHERE donor_id = $user_id ORDER BY created_at DESC LIMIT 3");
        $recent_donations = [];
        if ($recent_result && $recent_result->num_rows > 0) {
            while($row = $recent_result->fetch_assoc()) {
                $recent_donations[] = $row;
            }
        }
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_claims FROM food_claims WHERE receiver_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_donations = $stmt->get_result()->fetch_assoc()['total_claims'];
        $stmt->close();

        $stmt = $conn->prepare("SELECT COUNT(*) as approved_claims FROM food_claims WHERE receiver_id = ? AND status = 'Approved'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_active = $stmt->get_result()->fetch_assoc()['approved_claims'];
        $stmt->close();

        $recent_result = $conn->query("SELECT fl.*, fc.created_at as claimed_at FROM food_claims fc JOIN food_listings fl ON fc.food_id = fl.id WHERE fc.receiver_id = $user_id ORDER BY fc.created_at DESC LIMIT 3");
        $recent_donations = [];
        if ($recent_result && $recent_result->num_rows > 0) {
            while($row = $recent_result->fetch_assoc()) {
                $row['created_at'] = $row['claimed_at'];
                $recent_donations[] = $row;
            }
        }
    }
}

if ($user && isset($user['full_name'])) {
    $_SESSION['full_name'] = $user['full_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            font-family: 'Inter', sans-serif;
        }

        /* Cover & Avatar Header */
        .cover-photo {
            height: 250px;
            border-radius: 24px;
            background: linear-gradient(120deg, #10b981, #047857, #064e3b);
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(4, 120, 87, 0.2);
        }
        .cover-photo::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 20% 150%, rgba(255,255,255,0.15) 0%, transparent 50%),
                              radial-gradient(circle at 80% -50%, rgba(255,255,255,0.1) 0%, transparent 50%);
        }
        
        .profile-header-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            position: absolute;
            bottom: -60px;
            left: 50px;
            right: 50px;
            display: flex;
            align-items: flex-end;
            gap: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }
        .profile-avatar-xl {
            width: 140px;
            height: 140px;
            border-radius: 20px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--primary-green);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 4px solid #ffffff;
            margin-top: -80px;
            flex-shrink: 0;
            position: relative;
        }
        .avatar-overlay-btn {
            position: absolute;
            bottom: -5px;
            right: -5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            transition: transform 0.2s, background 0.2s;
            z-index: 10;
        }
        .avatar-overlay-btn:hover {
            transform: scale(1.1);
        }
        .avatar-overlay-btn.remove-mode {
            background: #ef4444;
        }
        .d-none { display: none !important; }
        .profile-title-area {
            flex: 1;
            padding-bottom: 10px;
        }
        .profile-title-area h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            color: var(--secondary-dark);
            margin: 0 0 5px 0;
            font-weight: 700;
        }
        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #166534;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .profile-header-actions {
            padding-bottom: 10px;
        }
        
        /* Main Content Grid */
        .profile-content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 40px;
        }

        /* Left Column */
        .info-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .info-panel h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            margin: 0 0 25px 0;
            color: var(--secondary-dark);
        }
        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
        }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .detail-text span {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .detail-text strong {
            display: block;
            color: var(--secondary-dark);
            font-weight: 500;
            font-size: 1rem;
        }

        /* Right Column */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card-premium {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .stat-card-premium:hover {
            transform: translateY(-5px);
        }
        .stat-card-premium::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 80px; height: 80px;
            background: #f8fafc;
            border-radius: 0 0 0 100%;
            z-index: 0;
        }
        .stat-card-premium i {
            position: absolute;
            top: 20px; right: 20px;
            color: #cbd5e1;
            font-size: 1.5rem;
            z-index: 1;
        }
        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-green);
            margin: 0 0 5px 0;
            position: relative;
            z-index: 1;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* Timeline */
        .timeline-panel {
            background: #ffffff;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }
        .timeline-panel h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.3rem;
            margin: 0 0 25px 0;
            color: var(--secondary-dark);
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0; left: 6px;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-dot {
            position: absolute;
            left: -31px;
            top: 4px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary-green);
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 2px #e2e8f0;
        }
        .timeline-content {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
        }
        .timeline-content h4 {
            margin: 0 0 5px 0;
            font-size: 1.05rem;
            color: var(--secondary-dark);
        }
        .timeline-content p {
            margin: 0;
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .btn-edit {
            background: var(--secondary-dark);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-edit:hover { opacity: 0.9; }

        /* Dark Mode */
        body.dark-theme .profile-header-card { background: #1e293b; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        body.dark-theme .profile-avatar-xl { background: #0f172a; border-color: #1e293b; color: #10b981; }
        body.dark-theme .profile-title-area h1 { color: #f8fafc; }
        body.dark-theme .profile-badge { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        body.dark-theme .info-panel, body.dark-theme .stat-card-premium, body.dark-theme .timeline-panel { background: #1e293b; border-color: #334155; }
        body.dark-theme .info-panel h3, body.dark-theme .timeline-panel h3 { color: #f8fafc; }
        body.dark-theme .detail-icon { background: #0f172a; color: #94a3b8; }
        body.dark-theme .detail-text strong { color: #f8fafc; }
        body.dark-theme .stat-card-premium::before { background: #0f172a; }
        body.dark-theme .stat-card-premium i { color: #334155; }
        body.dark-theme .stat-label { color: #94a3b8; }
        body.dark-theme .timeline::before { background: #334155; }
        body.dark-theme .timeline-dot { border-color: #1e293b; box-shadow: 0 0 0 2px #334155; }
        body.dark-theme .timeline-content { background: #0f172a; border-color: #334155; }
        body.dark-theme .timeline-content h4 { color: #f8fafc; }

        /* Responsive */
        .hero-cover-container {
            position: relative;
            margin-bottom: 80px;
        }

        @media (max-width: 1024px) {
            .profile-content-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .post-food-content {
                margin: 0 !important;
                padding: 10px !important;
            }
            .profile-header-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
                bottom: -130px;
                left: 15px;
                right: 15px;
                padding: 20px 15px;
                gap: 15px;
            }
            .profile-avatar-xl {
                margin-top: -60px;
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }
            .cover-photo {
                height: 180px;
                border-radius: 20px;
            }
            .hero-cover-container {
                margin-bottom: 150px;
            }
            .stats-overview {
                grid-template-columns: 1fr;
            }
            .profile-content-grid {
                gap: 15px;
            }
            .info-panel, .timeline-panel, .stat-card-premium {
                padding: 15px;
            }
        }
        @media (max-width: 480px) {
            .post-food-content {
                padding: 5px !important;
            }
            .profile-header-card {
                bottom: -140px;
                left: 10px;
                right: 10px;
            }
            .hero-cover-container {
                margin-bottom: 160px;
            }
            .stat-value {
                font-size: 2rem;
            }
            .profile-title-area h1 {
                font-size: 1.8rem;
            }
            .btn-edit {
                width: 100%;
            }
        }
    </style>
</head>
<body class="dashboard-body">

    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search your dashboard...">
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

        <div class="post-food-content" style="background: transparent; border: none; box-shadow: none; padding-top: 10px;">
            <div class="profile-wrapper">
                
                <!-- Hero Cover -->
                <div class="hero-cover-container">
                    <div class="cover-photo">
                    </div>
                    <div class="profile-header-card">
                        <?php 
                        $hasImage = !empty($user['profile_image']);
                        $avatarStyle = $hasImage ? "background-image: url('../" . htmlspecialchars($user['profile_image']) . "'); background-size: cover; background-position: center;" : "";
                        ?>
                        <div class="profile-avatar-xl" id="profileAvatar" style="<?php echo $avatarStyle; ?>">
                            <i class="fa-solid fa-user" id="defaultAvatarIcon" style="<?php echo $hasImage ? 'display:none;' : ''; ?>"></i>
                            
                            <label for="img-upload" class="avatar-overlay-btn <?php echo $hasImage ? 'd-none' : ''; ?>" id="btnUploadIcon" title="Upload Image">
                                <i class="fa-solid fa-camera"></i>
                            </label>
                            
                            <div class="avatar-overlay-btn remove-mode <?php echo !$hasImage ? 'd-none' : ''; ?>" id="btnRemoveIcon" title="Remove Image" onclick="removeProfileImage()">
                                <i class="fa-solid fa-trash"></i>
                            </div>
                        </div>
                        <input type="file" id="img-upload" style="display: none;" accept="image/*" onchange="uploadProfileImage(this)">
                        
                        <div class="profile-title-area">
                            <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                            <div class="profile-badge">
                                <i class="fa-solid fa-check-circle"></i> Verified <?php echo htmlspecialchars($role); ?>
                            </div>
                        </div>
                        <div class="profile-header-actions" id="toast-container" style="color:var(--primary-green); font-weight: 500;">
                        </div>
                    </div>
                </div>

                <!-- Main Layout -->
                <div class="profile-content-grid">
                    
                    <!-- Left: Details -->
                    <div class="info-panel">
                        <h3>About</h3>
                        
                        <div class="detail-item">
                            <div class="detail-icon"><i class="fa-regular fa-envelope"></i></div>
                            <div class="detail-text">
                                <span>Email Address</span>
                                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon"><i class="fa-regular fa-calendar"></i></div>
                            <div class="detail-text">
                                <span>Member Since</span>
                                <strong><?php echo date('F Y', strtotime($user['created_at'])); ?></strong>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="detail-text">
                                <span>Location Area</span>
                                <strong>Seattle, WA</strong>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Impact & Timeline -->
                    <div>
                        <!-- Stats Row -->
                        <div class="stats-overview">
                            <div class="stat-card-premium">
                                <i class="fa-solid fa-hand-holding-heart"></i>
                                <div class="stat-value"><?php echo $stats_donations; ?></div>
                                <div class="stat-label"><?php echo $is_admin ? 'Total Users' : ($user['role_id'] == 1 ? 'Total Contributions' : 'Total Claims'); ?></div>
                            </div>
                            <div class="stat-card-premium">
                                <i class="fa-solid fa-utensils"></i>
                                <div class="stat-value"><?php echo $stats_active; ?></div>
                                <div class="stat-label"><?php echo $is_admin ? 'Total Listings' : ($user['role_id'] == 1 ? 'Active Listings' : 'Approved Claims'); ?></div>
                            </div>
                            <?php if(!$is_admin): ?>
                            <div class="stat-card-premium">
                                <i class="fa-solid fa-seedling"></i>
                                <div class="stat-value"><?php echo number_format($stats_donations * 2.5, 1); ?>kg</div>
                                <div class="stat-label">CO2 Saved</div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Timeline -->
                        <div class="timeline-panel">
                            <h3>Recent Activity</h3>
                            <?php if (empty($recent_donations)): ?>
                                <p style="color: #94a3b8; font-size: 0.95rem;"><?php echo $is_admin ? 'No recent activity.' : 'No recent activity yet. Make your first contribution!'; ?></p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach($recent_donations as $donation): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <h4><?php echo $user['role_id'] == 1 ? 'Posted:' : 'Claimed:'; ?> <?php echo htmlspecialchars($donation['title']); ?></h4>
                                            <p><?php echo date('F j, Y, g:i a', strtotime($donation['created_at'])); ?> &bull; <?php echo htmlspecialchars($donation['category']); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

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

        function showToastMsg(msg, isError = false) {
            const container = document.getElementById('toast-container');
            container.style.color = isError ? '#ef4444' : 'var(--primary-green)';
            container.innerText = msg;
            setTimeout(() => { container.innerText = ''; }, 3000);
        }

        async function uploadProfileImage(input) {
            if (!input.files || !input.files[0]) return;
            let formData = new FormData();
            formData.append('profile_image', input.files[0]);

            try {
                let res = await fetch('upload_profile_image.php', {
                    method: 'POST',
                    body: formData
                });
                let data = await res.json();
                if (data.success) {
                    document.getElementById('profileAvatar').style.backgroundImage = `url('../${data.path}')`;
                    document.getElementById('profileAvatar').style.backgroundSize = 'cover';
                    document.getElementById('profileAvatar').style.backgroundPosition = 'center';
                    document.getElementById('defaultAvatarIcon').style.display = 'none';
                    document.getElementById('btnUploadIcon').classList.add('d-none');
                    document.getElementById('btnRemoveIcon').classList.remove('d-none');
                    showToastMsg('Profile image updated successfully.');
                } else {
                    showToastMsg(data.error || 'Upload failed.', true);
                }
            } catch (e) {
                showToastMsg('Network error.', true);
            }
            input.value = ''; // reset
        }

        async function removeProfileImage() {
            if(!confirm("Are you sure you want to remove your profile picture?")) return;
            try {
                let res = await fetch('remove_profile_image.php', { method: 'POST' });
                let data = await res.json();
                if (data.success) {
                    document.getElementById('profileAvatar').style.backgroundImage = 'none';
                    document.getElementById('defaultAvatarIcon').style.display = 'block';
                    document.getElementById('btnUploadIcon').classList.remove('d-none');
                    document.getElementById('btnRemoveIcon').classList.add('d-none');
                    showToastMsg('Profile image removed.');
                } else {
                    showToastMsg(data.error || 'Remove failed.', true);
                }
            } catch (e) {
                showToastMsg('Network error.', true);
            }
        }
    </script>
</body>
</html>

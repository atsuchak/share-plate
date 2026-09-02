<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$conn = new mysqli('localhost', 'root', '', 'share_plate');

// Set timezone to Dhaka
date_default_timezone_set('Asia/Dhaka');

// Fetch food listings
$query = "SELECT * FROM food_listings WHERE status = 'Available' AND expiry_time >= NOW() ORDER BY created_at DESC";
$result = $conn->query($query);
$listings = [];
while ($row = $result->fetch_assoc()) {
    $listings[] = $row;
}

// Function to calculate time remaining
function getTimeRemaining($expiry_time) {
    $expiry = new DateTime($expiry_time);
    $now = new DateTime();
    
    if ($now > $expiry) {
        return ['text' => 'Expired', 'class' => 'tag-expired', 'countdown' => 'Expired'];
    }
    
    $diff = $now->diff($expiry);
    $hours = ($diff->days * 24) + $diff->h;
    
    $countdown = '';
    if ($diff->days > 0) {
        $countdown .= $diff->days . 'd ';
    }
    if ($diff->h > 0 || $diff->days > 0) {
        $countdown .= $diff->h . 'h ';
    }
    $countdown .= $diff->i . 'm';
    
    if ($hours < 2) {
        return ['text' => 'URGENT', 'class' => 'tag-urgent', 'countdown' => $countdown];
    } elseif ($hours < 6) {
        return ['text' => 'EXPIRING SOON', 'class' => 'tag-soon', 'countdown' => $countdown];
    } else {
        return ['text' => 'VERY FRESH', 'class' => 'tag-fresh', 'countdown' => $countdown];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if ($is_logged_in): ?>
        <link rel="stylesheet" href="assets/css/dashboard.css">
    <?php endif; ?>
    <style>
        html { overflow-y: scroll; }
        .market-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: <?php echo $is_logged_in ? '20px' : '120px 20px 80px 20px'; ?>;
            font-family: 'Inter', sans-serif;
        }
        
        /* Pill Filters */
        .category-filters {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
            scrollbar-width: none; /* Firefox */
        }
        .category-filters::-webkit-scrollbar { display: none; } /* Chrome */
        
        .filter-pill {
            padding: 8px 20px;
            border-radius: 50px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        .filter-pill:hover { background: #e2e8f0; }
        .filter-pill.active {
            background: var(--primary-green);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }
        
        /* Grid */
        .market-grid {
            display: grid;
            grid-template-columns: <?php echo $is_logged_in ? 'repeat(3, 1fr)' : 'repeat(4, 1fr)'; ?>;
            gap: 25px;
        }
        
        @media (max-width: 992px) {
            .market-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .market-grid { grid-template-columns: 1fr; }
        }
        
        /* Cards */
        .food-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }
        
        .card-img-wrapper {
            position: relative;
            height: 200px;
            width: 100%;
        }
        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .status-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            backdrop-filter: blur(4px);
            color: #ffffff;
        }
        .tag-fresh { background: rgba(16, 185, 129, 0.85); }
        .tag-soon { background: rgba(234, 179, 8, 0.85); }
        .tag-urgent { background: rgba(239, 68, 68, 0.85); }
        .tag-expired { background: rgba(100, 116, 139, 0.85); }
        
        .card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.25rem;
            color: var(--secondary-dark);
            margin: 0 0 10px 0;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title i {
            color: var(--primary-green);
            font-size: 1rem;
        }
        .card-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-meta i { width: 14px; text-align: center; }
        .card-expiry {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-expiry.urgent { color: #ef4444; }
        .card-expiry.soon { color: #eab308; }
        .card-expiry.fresh { color: var(--primary-green); }
        
        .btn-request {
            width: 100%;
            padding: 12px;
            background: #334155;
            color: #ffffff;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: auto;
            text-decoration: none;
            text-align: center;
        }
        .btn-request:hover { background: #1e293b; }
        
        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }
        .btn-msg {
            flex: 1;
            padding: 10px;
            text-align: center;
            border-radius: 50px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-msg:hover { background: #e2e8f0; }
        
        .btn-view {
            flex: 1;
            padding: 10px;
            margin-top: 0;
            font-size: 0.9rem;
        }
        
        /* Dark Mode overrides for public view */
        body.dark-theme .market-wrapper { background: #0f172a; }
        body.dark-theme .food-card { background: #1e293b; border-color: #334155; }
        body.dark-theme .card-title { color: #f8fafc; }
        body.dark-theme .card-meta { color: #94a3b8; }
        body.dark-theme .filter-pill { background: #1e293b; color: #cbd5e1; border: 1px solid #334155; }
        body.dark-theme .filter-pill:hover { background: #334155; }
        body.dark-theme .filter-pill.active { background: var(--primary-green); color: #fff; border-color: var(--primary-green); }
        body.dark-theme .btn-msg { background: #1e293b; color: #cbd5e1; border-color: #334155; }
        body.dark-theme .btn-msg:hover { background: #334155; }
        
        .public-main { min-height: 100vh; background: #f8fafc; }
        body.dark-theme .public-main { background: #0f172a; }
    </style>
</head>
<body class="<?php echo $is_logged_in ? 'dashboard-body' : ''; ?>">

    <?php if ($is_logged_in): ?>
        <?php include 'dashboard/sidebar.php'; ?>
        
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search donations, partners...">
                </div>
                <div class="header-actions">
                    <a href="dashboard/profile.php" class="user-profile" style="text-decoration: none; color: inherit;">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                            <span class="user-id">#<?php echo substr(strtoupper(md5($_SESSION['user_id'] ?? 'E895')), 0, 4); ?></span>
                        </div>
                        <div class="user-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </a>
                </div>
            </header>
            
            <div class="market-wrapper">
                <?php include 'marketplace_content.php'; ?>
            </div>
        </main>
        
    <?php else: ?>
        
        <!-- Public Navbar -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="logo">
                    <span class="logo-dash">-</span> SharePlate
                </div>
                <ul class="nav-links">
                    <li><a href="marketplace.php" class="active">Explore</a></li>
                    <li><a href="index.php">How it Works</a></li>
                    <li><a href="index.php">Our Impact</a></li>
                </ul>
                <div class="nav-actions">
                    <button class="dark-mode-toggle" id="darkModeToggle">
                        <i class="fa-regular fa-moon"></i>
                    </button>
                    <a href="auth/login.php" class="login-btn">Log In</a>
                    <a href="auth/signup.php" class="btn-primary sign-up-btn">Sign Up</a>
                </div>
            </div>
        </nav>
        
        <main class="public-main">
            <div class="market-wrapper">
                <?php include 'marketplace_content.php'; ?>
            </div>
        </main>
        
        <!-- Public Footer -->
        <footer class="site-footer">
            <div class="footer-container">
                <div class="footer-bottom" style="border-top: none; padding-top: 20px;">
                    <p>&copy; 2024 SharePlate. All rights reserved.</p>
                </div>
            </div>
        </footer>
        
    <?php endif; ?>

    <script src="assets/js/script.js"></script>
    <?php if ($is_logged_in): ?>
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
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');
            const cards = document.querySelectorAll('.food-card');

            function filterCards() {
                const selectedCat = categoryFilter.value;
                const selectedStatus = statusFilter.value;
                
                let visibleCount = 0;

                cards.forEach(card => {
                    const cardCat = card.getAttribute('data-category');
                    const cardStatus = card.getAttribute('data-status');
                    
                    let catMatch = (selectedCat === 'All' || cardCat === selectedCat);
                    
                    // By default, hide Expired unless specifically asked for
                    let statusMatch = false;
                    if (selectedStatus === 'All') {
                        statusMatch = (cardStatus !== 'Expired');
                    } else {
                        statusMatch = (cardStatus === selectedStatus);
                    }
                    
                    if (catMatch && statusMatch) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Handle empty state here if visibleCount === 0
                const loadMoreBtn = document.getElementById('loadMoreContainer');
                if (loadMoreBtn) {
                    if (visibleCount > 6) {
                        loadMoreBtn.style.display = 'block';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            }

            if (categoryFilter && statusFilter) {
                categoryFilter.addEventListener('change', filterCards);
                statusFilter.addEventListener('change', filterCards);
                
                // Initial filter run
                filterCards();
            }
        });
    </script>
</body>
</html>

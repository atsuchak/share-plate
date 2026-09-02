<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How it Works - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .hiw-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
            font-family: 'Inter', sans-serif;
        }
        
        .hiw-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 50px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
        }

        .uml-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
            position: relative;
        }

        /* Start/End Nodes */
        .node-start {
            width: 24px;
            height: 24px;
            background: #f97316;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 0 0 2px #f97316;
            margin-bottom: 5px;
            z-index: 2;
        }
        
        .node-end {
            width: 24px;
            height: 24px;
            background: #fff;
            border-radius: 50%;
            border: 2px solid #f97316;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 5px;
        }
        .node-end::after {
            content: '';
            width: 12px;
            height: 12px;
            background: #f97316;
            border-radius: 50%;
        }

        /* Arrows */
        .arrow-down {
            width: 2px;
            height: 30px;
            background: #f97316;
            position: relative;
            margin: 0 auto;
        }
        .arrow-down::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: -4px;
            border-width: 6px 5px 0 5px;
            border-style: solid;
            border-color: #f97316 transparent transparent transparent;
        }
        .arrow-down.dotted {
            background: transparent;
            border-left: 2px dotted #f97316;
        }

        /* Sync Bars */
        .sync-bar {
            width: 320px;
            height: 6px;
            background: #f97316;
            margin: 5px 0;
            border-radius: 3px;
        }

        /* Parallel Tracks */
        .parallel-tracks {
            display: flex;
            justify-content: center;
            gap: 40px;
            width: 100%;
        }
        .track {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 240px;
        }

        /* Nodes */
        .node {
            width: 100%;
            padding: 15px 10px;
            background: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            color: #1e293b;
            margin: 5px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            min-height: 60px;
            z-index: 2;
        }
        .node.action {
            border: 3px solid #0ea5e9;
            border-radius: 20px;
        }
        .node.state {
            border: 2px solid #94a3b8;
            border-radius: 4px;
            background: #f8fafc;
            color: #334155;
            min-height: 50px;
            padding: 10px;
        }
        .node.decision {
            width: 40px;
            height: 40px;
            background: #ffffff;
            border: 2px solid #f97316;
            transform: rotate(45deg);
            margin: 15px 0;
            padding: 0;
            min-height: 0;
            border-radius: 4px;
        }

        /* Side Loop CSS for Decision Diamond */
        .decision-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        
        .side-path-line {
            position: absolute;
            top: 35px; /* Center of diamond */
            right: 50%;
            margin-right: -220px; /* Width of side path */
            width: 200px;
            height: 2px;
            background: #f97316;
            z-index: 1;
        }
        .side-path-line::after {
            content: '';
            position: absolute;
            right: -2px;
            top: -4px;
            border-width: 5px 0 5px 6px;
            border-style: solid;
            border-color: transparent transparent transparent #f97316;
        }
        
        .side-track {
            position: absolute;
            top: 35px;
            left: 50%;
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transform: translateX(-50%);
        }
        .side-arrow-down {
            width: 2px;
            height: 60px;
            background: #f97316;
            position: relative;
        }
        .side-arrow-down::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: -4px;
            border-width: 6px 5px 0 5px;
            border-style: solid;
            border-color: #f97316 transparent transparent transparent;
        }
        .side-arrow-left {
            width: 200px;
            height: 2px;
            border-top: 2px dotted #f97316;
            position: relative;
            margin-right: 130px; /* Push it left */
        }
        .side-arrow-left::before {
            content: '';
            position: absolute;
            left: -2px;
            top: -6px;
            border-width: 5px 6px 5px 0;
            border-style: solid;
            border-color: transparent #f97316 transparent transparent;
        }

        @media (max-width: 768px) {
            .parallel-tracks {
                flex-direction: column;
                align-items: center;
                gap: 0;
            }
            .sync-bar { width: 100%; max-width: 240px; }
            .side-path-line, .side-track { display: none; /* Simplify on mobile */ }
        }

        /* Dark Mode */
        body.dark-theme .hiw-card { background: #1e293b; border-color: #334155; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        body.dark-theme .node.action { background: #0f172a; color: #f8fafc; border-color: #0284c7; }
        body.dark-theme .node.state { background: #1e293b; color: #cbd5e1; border-color: #475569; }
        body.dark-theme .node.decision { background: #0f172a; }
        body.dark-theme .node-end { background: #0f172a; }
    </style>
</head>
<body class="dashboard-body">

    <?php include 'sidebar.php'; ?>

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
                        <span class="user-id">#<?php echo substr(strtoupper(md5($_SESSION['user_id'] ?? 'E895')), 0, 4); ?></span>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </a>
            </div>
        </header>

        <div class="post-food-content" style="background: transparent; border: none; box-shadow: none; padding-top: 10px;">
            <div class="hiw-wrapper">
                <div class="hiw-card">
                    <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; color: var(--secondary-dark); margin-bottom: 10px;">How SharePlate Works</h1>
                    <p style="color: #64748b; margin-bottom: 30px;">Follow the activity flow to successfully share food.</p>

                    <!-- Custom UML Activity Diagram -->
                    <div class="uml-container">
                        <div class="node-start"></div>
                        
                        <div class="arrow-down"></div>
                        <div class="sync-bar"></div>
                        
                        <div class="parallel-tracks">
                            <!-- Track 1: Donor -->
                            <div class="track">
                                <div class="arrow-down"></div>
                                <div class="node action">Post Food Listing</div>
                                <div class="arrow-down dotted"></div>
                                <div class="node state">Listing Active</div>
                                <div class="arrow-down dotted"></div>
                                <div class="node action">Wait for Claim</div>
                                <div class="arrow-down"></div>
                            </div>
                            
                            <!-- Track 2: Receiver -->
                            <div class="track">
                                <div class="arrow-down"></div>
                                <div class="node action">Browse Dashboard</div>
                                <div class="arrow-down dotted"></div>
                                <div class="node state">Find Food</div>
                                <div class="arrow-down dotted"></div>
                                <div class="node action">Click Claim</div>
                                <div class="arrow-down"></div>
                            </div>
                        </div>
                        
                        <div class="sync-bar"></div>
                        <div class="arrow-down"></div>
                        
                        <!-- Middle Section -->
                        <div class="node action" style="width: 280px;">Coordinate Pickup via Messages</div>
                        <div class="arrow-down"></div>
                        
                        <div class="decision-container">
                            <div class="node decision"></div>
                            
                            <!-- Side Loop Desktop Only -->
                            <div class="side-path-line"></div>
                            <div class="side-track">
                                <div class="node decision"></div>
                                <div class="arrow-down"></div>
                                <div class="node action" style="width: 140px; padding: 10px;">Reschedule</div>
                                <div class="arrow-down dotted" style="height: 25px;"></div>
                                <div class="side-arrow-left"></div>
                            </div>
                            
                            <div class="arrow-down dotted"></div>
                            <div class="node state" style="width: 240px;">Pickup Scheduled</div>
                            
                            <div class="arrow-down dotted" style="height: 60px;"></div>
                            <div class="node action" style="width: 280px; position: relative; z-index: 2;">Confirm Handover</div>
                            
                            <div class="arrow-down dotted"></div>
                            <div class="node state" style="width: 240px;">Transaction Logged</div>
                            
                            <div class="arrow-down dotted"></div>
                            <div class="node action" style="width: 280px;">Track CO2 Impact</div>
                        </div>
                        
                        <div class="arrow-down dotted"></div>
                        <div class="node-end"></div>
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
    </script>
</body>
</html>

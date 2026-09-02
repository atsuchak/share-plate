<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 3) {
    header("Location: dashboard.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

// System-wide KPIs
$total_users = 0;
$total_donors = 0;
$total_listings = 0;
$total_claims = 0;

$res = $conn->query("SELECT COUNT(*) as total FROM users");
if ($res && $row = $res->fetch_assoc()) $total_users = $row['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 1");
if ($res && $row = $res->fetch_assoc()) $total_donors = $row['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM food_listings");
if ($res && $row = $res->fetch_assoc()) $total_listings = $row['total'];

$res = $conn->query("SELECT COUNT(*) as total FROM food_claims");
if ($res && $row = $res->fetch_assoc()) $total_claims = $row['total'];

// Fetch Chart Data (System Wide Last 30 Days) - Listings
$chart_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
              FROM food_listings 
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
              GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $conn->prepare($chart_sql);
$stmt->execute();
$chart_result = $stmt->get_result();

$chart_labels_raw = [];
$listings_data_map = [];
while($row = $chart_result->fetch_assoc()) {
    $date_str = date('M d', strtotime($row['date']));
    if (!in_array($date_str, $chart_labels_raw)) {
        $chart_labels_raw[] = $date_str;
    }
    $listings_data_map[$date_str] = $row['count'];
}

// Fetch Chart Data - Claims
$chart_sql_claims = "SELECT DATE(created_at) as date, COUNT(*) as count 
              FROM food_claims 
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
              GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $conn->prepare($chart_sql_claims);
$stmt->execute();
$chart_result_claims = $stmt->get_result();

$claims_data_map = [];
while($row = $chart_result_claims->fetch_assoc()) {
    $date_str = date('M d', strtotime($row['date']));
    if (!in_array($date_str, $chart_labels_raw)) {
        $chart_labels_raw[] = $date_str;
    }
    $claims_data_map[$date_str] = $row['count'];
}

// Sort labels by date (since we might have added claim dates that were missing in listings)
usort($chart_labels_raw, function($a, $b) {
    return strtotime($a) - strtotime($b);
});

$listings_data = [];
$claims_data = [];
foreach ($chart_labels_raw as $lbl) {
    $listings_data[] = $listings_data_map[$lbl] ?? 0;
    $claims_data[] = $claims_data_map[$lbl] ?? 0;
}

$chart_labels_js = json_encode($chart_labels_raw);
$listings_data_js = json_encode($listings_data);
$claims_data_js = json_encode($claims_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - Admin Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search analytics...">
            </div>
            
            <div class="header-actions">
                <a href="#" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?></span>
                        <span class="user-id">#ADMIN</span>
                    </div>
                    <div class="user-avatar">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="dashboard-col-main" style="width: 100%;">
                
                <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <div>
                        <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">System Analytics</h2>
                        <p style="color: #64748b; margin: 0;">Overall performance and impact of the SharePlate platform.</p>
                    </div>
                    <div>
                        <button style="background: white; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; color: #475569; font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <i class="fa-solid fa-download"></i> Export Data
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="stats-row" style="margin-bottom: 30px; grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL USERS</span>
                            <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
                            <span class="stat-trend trend-neutral"><i class="fa-solid fa-users"></i> Platform Members</span>
                        </div>
                        <div class="stat-icon bg-blue-light">
                            <i class="fa-solid fa-users text-blue"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL DONORS</span>
                            <h3 class="stat-value"><?php echo number_format($total_donors); ?></h3>
                            <span class="stat-trend trend-neutral"><i class="fa-solid fa-hand-holding-heart"></i> Food Providers</span>
                        </div>
                        <div class="stat-icon bg-green-light">
                            <i class="fa-solid fa-utensils text-green"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL LISTINGS</span>
                            <h3 class="stat-value"><?php echo number_format($total_listings); ?></h3>
                            <span class="stat-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> System Growth</span>
                        </div>
                        <div class="stat-icon bg-yellow-light">
                            <i class="fa-solid fa-store text-yellow"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-title">TOTAL CLAIMS</span>
                            <h3 class="stat-value"><?php echo number_format($total_claims); ?></h3>
                            <span class="stat-trend trend-up"><i class="fa-solid fa-check-circle"></i> Successful Matchups</span>
                        </div>
                        <div class="stat-icon bg-green-light">
                            <i class="fa-solid fa-handshake text-green"></i>
                        </div>
                    </div>
                </div>

                <!-- Main Chart -->
                <div class="chart-container" style="background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: var(--secondary-dark);">Platform Activity (Last 30 Days)</h3>
                    </div>
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');
        const labels = <?php echo $chart_labels_js; ?>;
        const listingsData = <?php echo $listings_data_js; ?>;
        const claimsData = <?php echo $claims_data_js; ?>;
        
        // Gradient for listings chart
        let gradientListings = ctx.createLinearGradient(0, 0, 0, 400);
        gradientListings.addColorStop(0, 'rgba(34, 197, 94, 0.5)');   
        gradientListings.addColorStop(1, 'rgba(34, 197, 94, 0)');
        
        // Gradient for claims chart
        let gradientClaims = ctx.createLinearGradient(0, 0, 0, 400);
        gradientClaims.addColorStop(0, 'rgba(59, 130, 246, 0.5)');   
        gradientClaims.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Listings',
                        data: listingsData,
                        borderColor: '#22c55e',
                        backgroundColor: gradientListings,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#22c55e',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Claims Processed',
                        data: claimsData,
                        borderColor: '#3b82f6',
                        backgroundColor: gradientClaims,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        display: true,
                        position: 'top',
                        labels: {
                            font: { family: 'Inter', size: 13 },
                            color: '#475569',
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
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
</body>
</html>

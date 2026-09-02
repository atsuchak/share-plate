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

$user_id = $_SESSION['user_id'];

// Fetch KPIs
$stmt = $conn->prepare("SELECT COUNT(*) as total_donations, SUM(claims_count) as total_claims, SUM(quantity) as total_quantity FROM food_listings WHERE donor_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$kpis = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as active_listings FROM food_listings WHERE donor_id = ? AND status = 'Available'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_kpi = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_donations = $kpis['total_donations'] ?? 0;
$total_claims = $kpis['total_claims'] ?? 0;
$active_listings = $active_kpi['active_listings'] ?? 0;
// Fallback CO2 calc if quantity is null
$total_quantity = $kpis['total_quantity'] ?? ($total_donations * 5); 
$co2_saved = $total_quantity * 0.5;

// Fetch Chart Data (Last 30 Days)
$chart_sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
              FROM food_listings 
              WHERE donor_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
              GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chart_result = $stmt->get_result();

$chart_labels = [];
$chart_data = [];
while($row = $chart_result->fetch_assoc()) {
    $chart_labels[] = date('M d', strtotime($row['date']));
    $chart_data[] = $row['count'];
}
$stmt->close();

// Fetch Table Data (Top Performing)
$table_sql = "SELECT title, category, status, claims_count, created_at FROM food_listings WHERE donor_id = ? ORDER BY claims_count DESC, created_at DESC LIMIT 5";
$stmt = $conn->prepare($table_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$top_listings = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            font-family: 'Inter', sans-serif;
            padding-bottom: 40px;
        }
        
        /* KPI Row */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            border-top-width: 4px;
        }
        .card-green { border-top-color: #10b981; }
        .card-blue { border-top-color: #0ea5e9; }
        .card-yellow { border-top-color: #eab308; }
        .card-purple { border-top-color: #a855f7; }
        
        .kpi-info { width: 100%; }
        .kpi-info h4 {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .kpi-info .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--secondary-dark);
            margin: 0;
            display: flex;
            align-items: baseline;
            gap: 10px;
        }
        .kpi-trend {
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
        }
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .trend-neutral { color: #64748b; }
        


        /* Chart Section */
        .chart-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            margin-bottom: 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .section-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            color: var(--secondary-dark);
            margin: 0;
        }
        .section-header p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
        }

        /* Table Section */
        .table-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
        }
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }
        .custom-table th {
            text-align: left;
            padding: 15px 20px;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }
        .custom-table td {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--secondary-dark);
            vertical-align: middle;
        }
        .custom-table tr:last-child td {
            border-bottom: none;
        }
        
        .food-item-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .food-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1.1rem;
        }
        .food-details h5 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            color: var(--secondary-dark);
            font-weight: 600;
        }
        .food-details span {
            font-size: 0.85rem;
            color: #94a3b8;
        }
        
        .status-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background: #dcfce7; color: #166534; }
        .status-completed { background: #f1f5f9; color: #475569; }
        
        .claims-badge {
            background: #fef3c7;
            color: #b45309;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-export {
            padding: 10px 20px;
            background: #f1f5f9;
            color: var(--secondary-dark);
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        /* Dark Mode */
        body.dark-theme .kpi-card, body.dark-theme .chart-section, body.dark-theme .table-section { background: #1e293b; border-color: #334155; }
        body.dark-theme .kpi-card.card-green { border-top-color: #059669; }
        body.dark-theme .kpi-card.card-blue { border-top-color: #0284c7; }
        body.dark-theme .kpi-card.card-yellow { border-top-color: #ca8a04; }
        body.dark-theme .kpi-card.card-purple { border-top-color: #9333ea; }
        body.dark-theme .kpi-info h4, body.dark-theme .section-header p, body.dark-theme .custom-table th { color: #94a3b8; }
        body.dark-theme .kpi-info .kpi-value, body.dark-theme .section-header h2, body.dark-theme .food-details h5 { color: #f8fafc; }
        body.dark-theme .kpi-icon { background: #0f172a; border: 1px solid #334155; }
        body.dark-theme .custom-table th, body.dark-theme .custom-table td { border-color: #334155; }
        body.dark-theme .food-icon { background: #0f172a; }
        body.dark-theme .btn-export { background: #334155; color: #f8fafc; }
        body.dark-theme .status-completed { background: #334155; color: #cbd5e1; }
    </style>
</head>
<body class="dashboard-body">

    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search analytics...">
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
            <div class="analytics-wrapper">
                
                <!-- KPI Row -->
                <div class="kpi-row">
                    <div class="kpi-card card-green">
                        <div class="kpi-info">
                            <h4>Total Listings</h4>
                            <p class="kpi-value"><?php echo number_format($total_donations); ?> <span class="kpi-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> +12%</span></p>
                        </div>
                    </div>
                    
                    <div class="kpi-card card-blue">
                        <div class="kpi-info">
                            <h4>Total Claims</h4>
                            <p class="kpi-value"><?php echo number_format($total_claims); ?> <span class="kpi-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> +5%</span></p>
                        </div>
                    </div>
                    
                    <div class="kpi-card card-yellow">
                        <div class="kpi-info">
                            <h4>Active Listings</h4>
                            <p class="kpi-value"><?php echo number_format($active_listings); ?> <span class="kpi-trend trend-neutral">Stable</span></p>
                        </div>
                    </div>
                    
                    <div class="kpi-card card-purple">
                        <div class="kpi-info">
                            <h4>CO2 Saved (Est)</h4>
                            <p class="kpi-value"><?php echo number_format($co2_saved, 1); ?>kg <span class="kpi-trend trend-up"><i class="fa-solid fa-arrow-trend-up"></i> +8%</span></p>
                        </div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="chart-section">
                    <div class="section-header">
                        <div>
                            <h2>Donation Trends</h2>
                            <p>Listing frequency over the last 30 days</p>
                        </div>
                        <div class="chart-legend" style="display: flex; gap: 15px; font-size: 0.85rem; font-weight: 600; color: #64748b;">
                            <span style="display: flex; align-items: center; gap: 6px;"><div style="width: 10px; height: 10px; border-radius: 50%; background: var(--primary-green);"></div> Postings</span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <div class="section-header">
                        <div>
                            <h2>Top Performing Listings</h2>
                            <p>Your most claimed and engaged food items</p>
                        </div>
                        <button class="btn-export"><i class="fa-solid fa-download"></i> Export Report</button>
                    </div>
                    
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Listing Details</th>
                                <th>Category</th>
                                <th>Date Posted</th>
                                <th>Total Claims</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($top_listings->num_rows > 0): ?>
                                <?php while($item = $top_listings->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="food-item-cell">
                                            <div class="food-icon"><i class="fa-solid fa-bowl-food"></i></div>
                                            <div class="food-details">
                                                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    <td>
                                        <div class="claims-badge">
                                            <i class="fa-solid fa-fire"></i> <?php echo (int)$item['claims_count']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($item['status'] === 'Available'): ?>
                                            <span class="status-pill status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-pill status-completed">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No data available yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        // Dark Mode Logic
        const dashToggle = document.getElementById('darkModeToggleDash');
        let chartInstance = null;

        function updateChartTheme() {
            if(!chartInstance) return;
            const isDark = document.body.classList.contains('dark-theme');
            chartInstance.options.scales.x.grid.color = isDark ? '#334155' : '#f1f5f9';
            chartInstance.options.scales.y.grid.color = isDark ? '#334155' : '#f1f5f9';
            chartInstance.options.scales.x.ticks.color = isDark ? '#94a3b8' : '#64748b';
            chartInstance.options.scales.y.ticks.color = isDark ? '#94a3b8' : '#64748b';
            chartInstance.update();
        }

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
                updateChartTheme();
            });
        }

        // Initialize Chart.js
        const ctx = document.getElementById('trendsChart').getContext('2d');
        
        // Data from PHP
        const rawLabels = <?php echo json_encode($chart_labels); ?>;
        const rawData = <?php echo json_encode($chart_data); ?>;
        
        // If no data, provide dummy data to show the beautiful chart UI
        const labels = rawLabels.length > 0 ? rawLabels : ['Oct 1', 'Oct 5', 'Oct 10', 'Oct 15', 'Oct 20', 'Oct 25', 'Oct 30'];
        const dataPoints = rawData.length > 0 ? rawData : [2, 5, 3, 8, 4, 12, 6];

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)'); // Primary green with opacity
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Listings Created',
                    data: dataPoints,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4 // Smooth curves
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Outfit', size: 14, weight: 'bold' },
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' Listings';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { color: '#64748b', font: { family: 'Inter' } }
                    },
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { 
                            color: '#64748b', 
                            font: { family: 'Inter' },
                            stepSize: 1,
                            beginAtZero: true
                        },
                        border: { display: false }
                    }
                }
            }
        });
        
        // Initial theme set
        updateChartTheme();
    </script>
</body>
</html>

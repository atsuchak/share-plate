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

if (isset($_POST['action']) && isset($_POST['complaint_id'])) {
    $cid = (int)$_POST['complaint_id'];
    $new_status = $_POST['status'] === 'Resolved' ? 'Resolved' : 'Reviewed';
    
    // Fetch complaint details
    $stmt = $conn->prepare("SELECT c.*, 
                            u_comp.email as comp_email, u_comp.email_notifications as comp_email_notif,
                            u_acc.email as acc_email, u_acc.email_notifications as acc_email_notif
                            FROM complaints c 
                            JOIN users u_comp ON c.complainant_id = u_comp.id 
                            JOIN users u_acc ON c.accused_id = u_acc.id 
                            WHERE c.id = ?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $cdata = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($cdata && $cdata['status'] !== $new_status) {
        $conn->query("UPDATE complaints SET status = '$new_status' WHERE id = $cid");
        
        if ($new_status === 'Reviewed') {
            $donor_id = $cdata['accused_id'];
            $comp_id = $cdata['complainant_id'];
            $food_id = $cdata['food_id'];
            $complaint_text = $cdata['complaint_text'];
            
            // Check if conversation exists (requester_id = 0 for System Admin)
            $conv_id = 0;
            $chk_stmt = $conn->prepare("SELECT id FROM conversations WHERE food_id = ? AND donor_id = ? AND requester_id = 0");
            $chk_stmt->bind_param("ii", $food_id, $donor_id);
            $chk_stmt->execute();
            $res = $chk_stmt->get_result();
            if ($res->num_rows > 0) {
                $conv_id = $res->fetch_assoc()['id'];
            } else {
                $ins_stmt = $conn->prepare("INSERT INTO conversations (food_id, donor_id, requester_id) VALUES (?, ?, 0)");
                $ins_stmt->bind_param("ii", $food_id, $donor_id);
                $ins_stmt->execute();
                $conv_id = $ins_stmt->insert_id;
                $ins_stmt->close();
            }
            $chk_stmt->close();
            
            // Insert automated message
            $msg_text = "Someone reported about your food: \"" . $complaint_text . "\". Please respond to this message within 48 hours, otherwise your account will be suspended.";
            $msg_stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, 0, ?)");
            $msg_stmt->bind_param("is", $conv_id, $msg_text);
            $msg_stmt->execute();
            $msg_stmt->close();
            
            // Update conversation time
            $conn->query("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = $conv_id");
            
            // Send In-App Notifications
            $donor_notif = "System Admin sent you a message regarding a complaint.";
            $donor_link = "dashboard/messages.php?conv_id=" . $conv_id;
            $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Complaint Alert', ?, ?)");
            $n_stmt->bind_param("iss", $donor_id, $donor_notif, $donor_link);
            $n_stmt->execute();
            $n_stmt->close();
            
            $comp_notif = "Your complaint is currently under review.";
            $comp_link = "dashboard/history.php#complaints-section";
            $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Complaint Status', ?, ?)");
            $n_stmt->bind_param("iss", $comp_id, $comp_notif, $comp_link);
            $n_stmt->execute();
            $n_stmt->close();
            
            // Send Emails via Resend
            require_once '../vendor/autoload.php';
            require_once '../config.php';
            $resend = Resend::client(RESEND_API_KEY);

            if ($cdata['acc_email_notif']) {
                try {
                    $resend->emails->send([
                        'from' => 'SharePlate Alerts <noreply@atsuchak.me>',
                        'to' => ['noreply@atsuchak.me'],
                        'bcc' => [$cdata['acc_email']],
                        'subject' => 'System Notice: Complaint Alert',
                        'html' => "<p>" . nl2br(htmlspecialchars($msg_text)) . "</p><p><a href='http://localhost/SharePlate/dashboard/messages.php?conv_id=$conv_id'>View Message</a></p>"
                    ]);
                } catch (\Exception $e) {}
            }
            if ($cdata['comp_email_notif']) {
                try {
                    $resend->emails->send([
                        'from' => 'SharePlate Alerts <noreply@atsuchak.me>',
                        'to' => ['noreply@atsuchak.me'],
                        'bcc' => [$cdata['comp_email']],
                        'subject' => 'Complaint Status Update',
                        'html' => "<p>$comp_notif</p><p><a href='http://localhost/SharePlate/dashboard/history.php'>View History</a></p>"
                    ]);
                } catch (\Exception $e) {}
            }
            
        } elseif ($new_status === 'Resolved') {
            $comp_id = $cdata['complainant_id'];
            
            $comp_notif = "Your complaint has been marked as resolved.";
            $comp_link = "dashboard/history.php#complaints-section";
            $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Complaint Resolved', ?, ?)");
            $n_stmt->bind_param("iss", $comp_id, $comp_notif, $comp_link);
            $n_stmt->execute();
            $n_stmt->close();
            
            if ($cdata['comp_email_notif']) {
                require_once '../vendor/autoload.php';
                require_once '../config.php';
                $resend = Resend::client(RESEND_API_KEY);
                
                try {
                    $resend->emails->send([
                        'from' => 'SharePlate Alerts <noreply@atsuchak.me>',
                        'to' => ['noreply@atsuchak.me'],
                        'bcc' => [$cdata['comp_email']],
                        'subject' => 'Complaint Resolved',
                        'html' => "<p>$comp_notif</p><p><a href='http://localhost/SharePlate/dashboard/history.php'>View History</a></p>"
                    ]);
                } catch (\Exception $e) {}
            }
        }
    }
    
    header("Location: admin_reports.php");
    exit();
}

$complaints = [];
$filter_clause = "";
if (isset($_GET['status_filter']) && in_array($_GET['status_filter'], ['Pending', 'Reviewed', 'Resolved'])) {
    $status = $conn->real_escape_string($_GET['status_filter']);
    $filter_clause = "WHERE c.status = '$status'";
}

$result = $conn->query("
    SELECT c.*, u1.full_name as complainant_name, u2.full_name as accused_name, f.title as food_title
    FROM complaints c
    JOIN users u1 ON c.complainant_id = u1.id
    JOIN users u2 ON c.accused_id = u2.id
    LEFT JOIN food_listings f ON c.food_id = f.id
    $filter_clause
    ORDER BY c.created_at DESC
");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .report-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
        }
        .report-status {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Reviewed { background: #dbeafe; color: #1d4ed8; }
        .status-Resolved { background: #dcfce7; color: #166534; }
        
        .report-body {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.5;
        }
        .report-meta {
            display: flex;
            gap: 20px;
            font-size: 0.85rem;
            color: #64748b;
        }
        .report-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-action {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn-resolve { background: #10b981; color: white; }
        .btn-review { background: #3b82f6; color: white; }
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
                <input type="text" placeholder="Search reports...">
            </div>
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
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div>
                        <h2 style="font-family: 'Playfair Display', serif; color: var(--secondary-dark); margin: 0 0 5px 0;">System Reports & Complaints</h2>
                        <p style="color: #64748b; margin: 0;">View flagged content, system health warnings, and user feedback.</p>
                    </div>
                    <div>
                        <form method="GET" action="admin_reports.php">
                            <select name="status_filter" onchange="this.form.submit()" style="padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; font-family: inherit; color: var(--secondary-dark); font-weight: 500; outline: none; background: white; cursor: pointer;">
                                <option value="All" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'All') ? 'selected' : ''; ?>>All Reports</option>
                                <option value="Pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Reviewed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="Resolved" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </form>
                    </div>
                </div>

                <?php if(empty($complaints)): ?>
                    <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 50px; text-align: center;">
                        <i class="fa-solid fa-clipboard-check" style="font-size: 4rem; color: var(--primary-green); margin-bottom: 20px; opacity: 0.8;"></i>
                        <h3 style="color: var(--secondary-dark); margin-bottom: 10px; font-size: 1.5rem;">All Clear!</h3>
                        <p style="color: #64748b; max-width: 400px; margin: 0 auto;">There are no active reports or flagged issues at this time. The system is running smoothly.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($complaints as $c): ?>
                        <div class="report-card">
                            <div class="report-header">
                                <div>
                                    <h4 style="margin: 0; color: #1e293b; font-size: 1.1rem;">Report #<?php echo $c['id']; ?></h4>
                                    <span style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('M d, Y h:i A', strtotime($c['created_at'])); ?></span>
                                </div>
                                <div class="report-status status-<?php echo $c['status']; ?>">
                                    <?php echo strtoupper($c['status']); ?>
                                </div>
                            </div>
                            
                            <div class="report-meta">
                                <span><strong>By:</strong> <?php echo htmlspecialchars($c['complainant_name']); ?></span>
                                <span><strong>Against:</strong> <span style="color: #ef4444;"><?php echo htmlspecialchars($c['accused_name']); ?></span></span>
                                <span><strong>Related Food:</strong> <?php echo $c['food_title'] ? htmlspecialchars($c['food_title']) : 'N/A'; ?></span>
                            </div>
                            
                            <div class="report-body">
                                "<?php echo nl2br(htmlspecialchars($c['complaint_text'])); ?>"
                            </div>
                            
                            <?php if($c['status'] !== 'Resolved'): ?>
                            <div class="report-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="status" value="Resolved">
                                    <button type="submit" class="btn-action btn-resolve"><i class="fa-solid fa-check"></i> Mark as Resolved</button>
                                </form>
                                <?php if($c['status'] === 'Pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="complaint_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="status" value="Reviewed">
                                    <button type="submit" class="btn-action btn-review"><i class="fa-solid fa-eye"></i> Mark as Reviewed</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$conn = new mysqli('localhost', 'root', '', 'share_plate');

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_profile'])) {
        $name = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone_number']);
        
        $table = (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) ? 'admin' : 'users';
        if ($table == 'admin') {
            $stmt = $conn->prepare("UPDATE admin SET full_name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
        } else {
            $org = $conn->real_escape_string($_POST['organization_name'] ?? '');
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, organization_name = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $phone, $org, $user_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $name; // Update session variable
            $success_msg = "Profile updated successfully!";
        } else {
            $error_msg = "Failed to update profile.";
        }
        $stmt->close();
    } elseif (isset($_POST['update_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if ($new !== $confirm) {
            $error_msg = "New passwords do not match!";
        } else {
            // Check current password
            $table = (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) ? 'admin' : 'users';
            $stmt = $conn->prepare("SELECT password_hash FROM $table WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (password_verify($current, $result['password_hash'])) {
                $new_hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE $table SET password_hash = ? WHERE id = ?");
                $stmt->bind_param("si", $new_hash, $user_id);
                if ($stmt->execute()) {
                    $success_msg = "Password updated successfully!";
                } else {
                    $error_msg = "Failed to update password.";
                }
                $stmt->close();
            } else {
                $error_msg = "Current password is incorrect!";
            }
        }
    } elseif (isset($_POST['update_preferences'])) {
        $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
        $in_app_notif = isset($_POST['in_app_notifications']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, in_app_notifications = ? WHERE id = ?");
        $stmt->bind_param("iii", $email_notif, $in_app_notif, $user_id);
        if ($stmt->execute()) {
            $success_msg = "Preferences updated successfully!";
        } else {
            $error_msg = "Failed to update preferences.";
        }
        $stmt->close();
    }
}

// Fetch current user data
$table = (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) ? 'admin' : 'users';
$stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .settings-wrapper {
            max-width: 900px;
            margin: 0 auto;
            font-family: 'Inter', sans-serif;
            padding-bottom: 40px;
        }
        .page-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            color: var(--secondary-dark);
            margin-bottom: 30px;
            font-weight: 700;
        }
        
        .settings-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            margin-bottom: 30px;
        }
        .settings-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            color: var(--secondary-dark);
            margin: 0 0 25px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .settings-card h3 i {
            color: var(--primary-green);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
            font-size: 0.95rem;
        }
        .form-control {
            width: 100%;
            padding: 12px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            color: var(--secondary-dark);
            transition: border-color 0.3s;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            background: #ffffff;
        }
        
        .btn-save {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Toggle Switch */
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        .setting-desc h4 {
            margin: 0 0 5px 0;
            font-size: 1.05rem;
            color: var(--secondary-dark);
        }
        .setting-desc p {
            margin: 0;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary-green);
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Dark Mode */
        body.dark-theme .page-title { color: #f8fafc; }
        body.dark-theme .settings-card { background: #1e293b; border-color: #334155; }
        body.dark-theme .settings-card h3 { color: #f8fafc; border-color: #334155; }
        body.dark-theme .form-group label { color: #94a3b8; }
        body.dark-theme .form-control { background: #0f172a; border-color: #334155; color: #f8fafc; }
        body.dark-theme .form-control:focus { border-color: var(--primary-green); }
        body.dark-theme .setting-desc h4 { color: #f8fafc; }
        body.dark-theme .alert-success { background: rgba(22, 101, 52, 0.3); color: #4ade80; border-color: #166534; }
        body.dark-theme .alert-error { background: rgba(153, 27, 27, 0.3); color: #f87171; border-color: #991b1b; }
        body.dark-theme .slider { background-color: #334155; }
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
                <input type="text" placeholder="Search settings...">
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

        <div class="post-food-content" style="background: transparent; border: none; box-shadow: none; padding-top: 10px;">
            <div class="settings-wrapper">
                
                <h1 class="page-title">Account Settings</h1>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- Profile Settings -->
                <div class="settings-card">
                    <h3><i class="fa-solid fa-user-pen"></i> Profile Information</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
                            </div>
                            <?php if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3): ?>
                            <div class="form-group">
                                <label>Organization Name</label>
                                <input type="text" name="organization_name" class="form-control" value="<?php echo htmlspecialchars($user['organization_name'] ?? ''); ?>" placeholder="Company or NGO Name (Optional)">
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="settings-card">
                    <h3><i class="fa-solid fa-shield-halved"></i> Security & Password</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn-save">Update Password</button>
                    </form>
                </div>

                <!-- Preferences Settings -->
                <div class="settings-card">
                    <h3><i class="fa-solid fa-sliders"></i> Preferences</h3>
                    
                    <div class="setting-row">
                        <div class="setting-desc">
                            <h4>Dark Mode</h4>
                            <p>Toggle the appearance of the dashboard</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="settingsDarkModeToggle">
                            <span class="slider"></span>
                        </label>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="setting-row" style="border-top: 1px solid #f1f5f9; margin-top: 10px; padding-top: 25px;">
                            <div class="setting-desc">
                                <h4>Email Notifications</h4>
                                <p>Receive updates when new food is posted</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_notifications" <?php echo isset($user['email_notifications']) && $user['email_notifications'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="setting-row" style="border-top: 1px solid #f1f5f9; margin-top: 10px; padding-top: 25px;">
                            <div class="setting-desc">
                                <h4>In-App Notifications</h4>
                                <p>Receive alerts directly in the dashboard</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="in_app_notifications" <?php echo isset($user['in_app_notifications']) && $user['in_app_notifications'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="submit" name="update_preferences" class="btn-save">Save Preferences</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        // Synchronize dark mode between header toggle and settings toggle
        const dashToggle = document.getElementById('darkModeToggleDash');
        const settingsToggle = document.getElementById('settingsDarkModeToggle');
        
        // Initialize settings toggle based on current body class
        if (document.body.classList.contains('dark-theme')) {
            if (settingsToggle) settingsToggle.checked = true;
        }

        // Handle settings toggle click
        if (settingsToggle) {
            settingsToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    document.body.classList.add('dark-theme');
                    localStorage.setItem('theme', 'dark');
                    if(dashToggle) dashToggle.querySelector('i').className = 'fa-solid fa-sun';
                } else {
                    document.body.classList.remove('dark-theme');
                    localStorage.setItem('theme', 'light');
                    if(dashToggle) dashToggle.querySelector('i').className = 'fa-regular fa-moon';
                }
            });
        }

        // Handle header toggle click (sync with settings toggle)
        if (dashToggle) {
            dashToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme');
                const isDark = document.body.classList.contains('dark-theme');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                
                const icon = dashToggle.querySelector('i');
                if (isDark) {
                    icon.className = 'fa-solid fa-sun';
                } else {
                    icon.className = 'fa-regular fa-moon';
                }
                
                if (settingsToggle) settingsToggle.checked = isDark;
            });
        }
    </script>
</body>
</html>

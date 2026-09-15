<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role_id, status, profile_image FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'Suspended') {
                $_SESSION['error_message'] = "Your account has been suspended by an administrator.";
                header("Location: login.php");
                exit();
            }
            
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['profile_image'] = $user['profile_image'] ?? null;
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip === '::1') $ip = '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $conn->query("INSERT INTO login_logs (user_id, role_id, ip_address, user_agent, status) VALUES ({$user['id']}, {$user['role_id']}, '$ip', '$ua', 'Success')");
            
            // Redirect to dashboard
            header("Location: ../dashboard/dashboard.php");
            exit();
        }
    }
    
    // If not found in users, check admin table
    $stmt = $conn->prepare("SELECT id, full_name, password_hash FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['role_id'] = 3; // Role 3 for admin
            $_SESSION['is_admin'] = true;
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($ip === '::1') $ip = '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $conn->query("INSERT INTO login_logs (user_id, role_id, ip_address, user_agent, status) VALUES ({$admin['id']}, 3, '$ip', '$ua', 'Success')");
            
            header("Location: ../dashboard/dashboard.php");
            exit();
        }
    }

    // If both fail, log failed attempt if user exists? 
    // Usually we don't know the ID if it fails without matching email. We can skip failed for now or just log email. 
    // We'll skip failed for now to keep it simple.
    
    $_SESSION['error_message'] = "Invalid email or password.";
    header("Location: login.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>

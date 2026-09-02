<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role_id, status FROM users WHERE email = ?");
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
            
            header("Location: ../dashboard/dashboard.php");
            exit();
        }
    }

    // If both fail
    $_SESSION['error_message'] = "Invalid email or password.";
    header("Location: login.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>

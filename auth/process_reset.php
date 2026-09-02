<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Ensure they are authorized
    if (!isset($_SESSION['reset_authorized']) || $_SESSION['reset_authorized'] !== true || !isset($_SESSION['reset_email'])) {
        header("Location: forgot_password.php");
        exit();
    }

    if ($password !== $confirmPassword) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: reset_password.php");
        exit();
    }

    $email = $_SESSION['reset_email'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update database
    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $table = (isset($_SESSION['reset_is_admin']) && $_SESSION['reset_is_admin']) ? 'admin' : 'users';
    $stmt = $conn->prepare("UPDATE $table SET password_hash = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        // Clear session data securely
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_authorized']);
        unset($_SESSION['reset_is_admin']);

        // Redirect with success
        echo "<script>alert('Password updated successfully! You can now log in.'); window.location.href='login.php';</script>";
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to update password. Try again later.";
        header("Location: reset_password.php");
        exit();
    }
} else {
    header("Location: reset_password.php");
    exit();
}
?>

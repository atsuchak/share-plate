<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredCode = str_replace(' ', '', trim($_POST['code']));

    if (!isset($_SESSION['verification_code']) || !isset($_SESSION['signup_data'])) {
        $_SESSION['error_message'] = "Session expired. Please sign up again.";
        header("Location: signup.php");
        exit();
    }

    if ($enteredCode == $_SESSION['verification_code']) {
        // Code is correct, insert into database
        $conn = new mysqli('localhost', 'root', '', 'share_plate');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $data = $_SESSION['signup_data'];
        
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $data['fullname'], $data['email'], $data['password'], $data['role_id']);
        
        if ($stmt->execute()) {
            // Success
            $stmt->close();
            $conn->close();
            
            // Clear session data
            unset($_SESSION['verification_code']);
            unset($_SESSION['signup_data']);
            
            // Log the user in or redirect to login page with success message
            echo "<script>alert('Account created successfully! You can now log in.'); window.location.href='login.php';</script>";
        } else {
            $_SESSION['error_message'] = "Error creating account. Please try again.";
            header("Location: verify.php");
        }
    } else {
        $_SESSION['error_message'] = "Invalid verification code.";
        header("Location: verify.php");
    }
} else {
    header("Location: verify.php");
    exit();
}
?>

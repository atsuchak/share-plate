<?php
session_start();

require '../vendor/autoload.php';
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isAdmin = false;
    if ($result->num_rows == 0) {
        $stmt->close();
        // Check admin table
        $stmt = $conn->prepare("SELECT id, full_name FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $_SESSION['error_message'] = "We couldn't find an account associated with that email.";
            header("Location: forgot_password.php");
            exit();
        }
        $isAdmin = true;
    }
    
    $user = $result->fetch_assoc();
    $fullName = $user['full_name'];
    $stmt->close();
    $conn->close();

    // Generate Recovery Code
    $recoveryCode = rand(100000, 999999);

    // Save to session
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_code'] = $recoveryCode;
    $_SESSION['reset_authorized'] = false; // Reset later
    $_SESSION['reset_is_admin'] = $isAdmin;

    // Send Email
    $resend = Resend::client(RESEND_API_KEY);

    try {
        $htmlBody = "
        <div style='font-family: Inter, sans-serif; background: #f0f4f8; padding: 40px; text-align: center;'>
            <div style='background: #fff; padding: 30px; border-radius: 10px; max-width: 500px; margin: auto;'>
                <h2 style='color: #ef4444;'>Password Recovery</h2>
                <p>Hi {$fullName},</p>
                <p>We received a request to reset the password for your SharePlate account.</p>
                <p>Your password recovery code is:</p>
                <h1 style='background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; letter-spacing: 5px; display: inline-block;'>{$recoveryCode}</h1>
                <p>Enter this code on the password recovery page to set a new password.</p>
                <p style='color: #6b7280; font-size: 0.9em;'>If you didn't request a password reset, you can safely ignore this email.</p>
            </div>
        </div>";

        $resend->emails->send([
            'from' => 'SharePlate Security <noreply@atsuchak.me>',
            'to' => [$email],
            'subject' => 'SharePlate - Password Recovery Code',
            'html' => $htmlBody,
        ]);
        
        header("Location: verify_reset.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Message could not be sent. Mailer Error: {$e->getMessage()}";
        header("Location: forgot_password.php");
        exit();
    }
} else {
    header("Location: forgot_password.php");
    exit();
}
?>

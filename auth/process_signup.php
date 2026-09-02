<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $roleId = $_POST['role_id'];

    if ($password !== $confirmPassword) {
        die("Passwords do not match. <a href='signup.php'>Go back</a>");
    }

    // Connect to DB to check if email exists
    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die("Email already exists. <a href='login.php'>Login instead</a>");
    }
    $stmt->close();
    $conn->close();

    // Generate Verification Code
    $verificationCode = rand(100000, 999999);

    // Save to session
    $_SESSION['signup_data'] = [
        'fullname' => $fullName,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role_id' => $roleId
    ];
    $_SESSION['verification_code'] = $verificationCode;

    // Send Email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL; 
        $mail->Password   = SMTP_PASSWORD; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(SMTP_EMAIL, 'SharePlate');
        $mail->addAddress($email, $fullName);

        $mail->isHTML(true);
        $mail->Subject = 'Verify your SharePlate Account';
        $mail->Body    = "
        <div style='font-family: Inter, sans-serif; background: #f0f4f8; padding: 40px; text-align: center;'>
            <div style='background: #fff; padding: 30px; border-radius: 10px; max-width: 500px; margin: auto;'>
                <h2 style='color: #0d7756;'>Welcome to SharePlate!</h2>
                <p>Hi {$fullName},</p>
                <p>Thank you for joining our community to share food and share hope.</p>
                <p>Your verification code is:</p>
                <h1 style='background: #d1fae5; color: #0d7756; padding: 15px; border-radius: 8px; font-letter-spacing: 5px; display: inline-block;'>{$verificationCode}</h1>
                <p>Please enter this code on the verification page to complete your account creation.</p>
                <p style='color: #6b7280; font-size: 0.9em;'>If you didn't request this, you can ignore this email.</p>
            </div>
        </div>";

        $mail->send();
        
        header("Location: verify.php");
        exit();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    header("Location: signup.php");
    exit();
}
?>

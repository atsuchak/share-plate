<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enteredCode = str_replace(' ', '', trim($_POST['code']));

    if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email'])) {
        $_SESSION['error_message'] = "Session expired. Please request a new code.";
        header("Location: forgot_password.php");
        exit();
    }

    if ($enteredCode == $_SESSION['reset_code']) {
        // Code is correct, set authorization flag and proceed to password reset form
        $_SESSION['reset_authorized'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid recovery code. Please try again.";
        header("Location: verify_reset.php");
        exit();
    }
} else {
    header("Location: verify_reset.php");
    exit();
}
?>

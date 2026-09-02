<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['claim_id'])) {
    header("Location: active_listings.php");
    exit();
}

$claim_id = (int)$_POST['claim_id'];
$donor_id = $_SESSION['user_id'];
$donor_name = $_SESSION['full_name'];

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if claim exists and food belongs to the donor
$stmt = $conn->prepare("
    SELECT c.food_id, c.receiver_id, f.title, u.email, u.full_name as receiver_name, u.email_notifications 
    FROM food_claims c
    JOIN food_listings f ON c.food_id = f.id
    JOIN users u ON c.receiver_id = u.id
    WHERE c.id = ? AND f.donor_id = ? AND c.status = 'Pending'
");
$stmt->bind_param("ii", $claim_id, $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header("Location: active_listings.php");
    exit();
}

$claim = $result->fetch_assoc();
$food_id = $claim['food_id'];
$stmt->close();

$conn->begin_transaction();
try {
    // 1. Accept this claim
    $stmt = $conn->prepare("UPDATE food_claims SET status = 'Approved' WHERE id = ?");
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $stmt->close();

    // We don't reject other claims here, since the quantity handles how many can be accepted.
    // Also we don't update food_listings here because the quantity was decremented at the time of claim.

    // 4. Create Notification for Receiver
    $notif_title = "Food Request Accepted";
    $notif_message = $donor_name . " has accepted your request for " . $claim['title'] . ". Please check your messages for pickup details.";
    $notif_link = "dashboard/messages.php";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $claim['receiver_id'], $notif_title, $notif_message, $notif_link);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // 5. Send Email if preferences enabled
    if ($claim['email_notifications']) {
        require '../vendor/autoload.php';
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'suchak9931@gmail.com'; 
            $mail->Password   = 'ttss tycv ryqn pppk'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('suchak9931@gmail.com', 'SharePlate');
            $mail->addAddress($claim['email'], $claim['receiver_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Your SharePlate Request was Accepted!';
            $mail->Body    = "
            <div style='font-family: Inter, sans-serif; background: #f0f4f8; padding: 40px; text-align: center;'>
                <div style='background: #fff; padding: 30px; border-radius: 10px; max-width: 500px; margin: auto;'>
                    <h2 style='color: #0d7756;'>Good news, " . htmlspecialchars($claim['receiver_name']) . "!</h2>
                    <p>Your request for <strong>" . htmlspecialchars($claim['title']) . "</strong> has been accepted by " . htmlspecialchars($donor_name) . ".</p>
                    <p>Please log in to your dashboard and message the donor to arrange pickup.</p>
                    <a href='http://localhost/SharePlate/dashboard/messages.php' style='display: inline-block; padding: 10px 20px; margin-top: 20px; background-color: #10b981; color: #fff; text-decoration: none; border-radius: 50px; font-weight: bold;'>View Messages</a>
                </div>
            </div>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    }

} catch (Exception $e) {
    $conn->rollback();
}

$conn->close();
header("Location: active_listings.php");
exit();
?>

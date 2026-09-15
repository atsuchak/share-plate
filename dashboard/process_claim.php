<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['food_id'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$food_id = (int)$_POST['food_id'];
$receiver_id = $_SESSION['user_id'];
$message = trim($_POST['claim_message'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$receiver_name = $_SESSION['full_name'];

// Verify food exists, is available, and user is not the donor
$stmt = $conn->prepare("SELECT donor_id, status, title FROM food_listings WHERE id = ?");
$stmt->bind_param("i", $food_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Food listing not found.";
    header("Location: dashboard.php");
    exit();
}

$food = $result->fetch_assoc();

if ($food['donor_id'] == $receiver_id) {
    $_SESSION['error_message'] = "You cannot claim your own food listing.";
    header("Location: food_details.php?id=" . $food_id);
    exit();
}

if ($food['status'] !== 'Available') {
    $_SESSION['error_message'] = "This food is no longer available.";
    header("Location: food_details.php?id=" . $food_id);
    exit();
}

// Check if user already has a pending claim for this food
$stmt = $conn->prepare("SELECT id FROM food_claims WHERE food_id = ? AND receiver_id = ? AND status = 'Pending'");
$stmt->bind_param("ii", $food_id, $receiver_id);
$stmt->execute();
$claim_check = $stmt->get_result();

if ($claim_check->num_rows > 0) {
    $_SESSION['error_message'] = "You have already submitted a claim for this food.";
    header("Location: food_details.php?id=" . $food_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert claim
    $stmt = $conn->prepare("INSERT INTO food_claims (food_id, receiver_id, message, contact_number, address, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("iissss", $food_id, $receiver_id, $message, $contact_number, $address, $reason);
    $stmt->execute();
    
    // Update claims_count
    $stmt = $conn->prepare("UPDATE food_listings SET claims_count = claims_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $food_id);
    $stmt->execute();
    
    // Create Notification for Donor
    $notif_title = "New Claim on " . $food['title'];
    $notif_message = $receiver_name . " has requested to claim this food.";
    if (!empty($message)) {
        $notif_message .= " They added a message: \"" . $message . "\"";
    }
    $notif_link = "dashboard/food_details.php?id=" . $food_id;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $food['donor_id'], $notif_title, $notif_message, $notif_link);
    $stmt->execute();
    
    $conn->commit();
    $_SESSION['success_message'] = "Your claim request has been sent to the donor!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Failed to submit claim. Please try again later.";
}

$stmt->close();
$conn->close();

header("Location: food_details.php?id=" . $food_id);
exit();
?>

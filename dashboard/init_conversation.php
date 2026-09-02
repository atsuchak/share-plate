<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['food_id']) || !is_numeric($_GET['food_id'])) {
    header("Location: dashboard.php");
    exit();
}

$food_id = (int)$_GET['food_id'];
$session_user_id = $_SESSION['user_id'];

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Get the actual donor_id of this food
$sql = "SELECT donor_id FROM food_listings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $food_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Food not found
    header("Location: dashboard.php");
    exit();
}

$food = $result->fetch_assoc();
$actual_donor_id = $food['donor_id'];
$stmt->close();

if ($session_user_id === $actual_donor_id) {
    // The current user IS the donor. They must specify a receiver to message
    if (!isset($_GET['receiver_id']) || !is_numeric($_GET['receiver_id'])) {
        header("Location: food_details.php?id=" . $food_id);
        exit();
    }
    $requester_id = (int)$_GET['receiver_id'];
    $donor_id = $actual_donor_id;
} else {
    // The current user is the requester (receiver)
    $requester_id = $session_user_id;
    $donor_id = $actual_donor_id;
}

// 2. Check if a conversation already exists
$sql = "SELECT id FROM conversations WHERE food_id = ? AND requester_id = ? AND donor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $food_id, $requester_id, $donor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Conversation exists, redirect to it
    $conv = $result->fetch_assoc();
    $conv_id = $conv['id'];
    header("Location: messages.php?conv_id=" . $conv_id);
    exit();
}

// 3. Prevent messaging yourself
if ($requester_id === $donor_id) {
    header("Location: food_details.php?id=" . $food_id);
    exit();
}

// 4. Create new conversation
$sql = "INSERT INTO conversations (food_id, donor_id, requester_id) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $food_id, $donor_id, $requester_id);
$stmt->execute();
$new_conv_id = $stmt->insert_id;
$stmt->close();

// Redirect to the new conversation
header("Location: messages.php?conv_id=" . $new_conv_id);
exit();
?>

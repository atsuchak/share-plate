<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli('localhost', 'root', '', 'share_plate');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $complainant_id = $_SESSION['user_id'];
    $food_id = (int)$_POST['food_id'];
    $accused_id = (int)$_POST['accused_id'];
    $complaint_text = trim($_POST['complaint_text']);

    if (empty($complaint_text) || empty($food_id) || empty($accused_id)) {
        header("Location: food_details.php?id=$food_id&error=Missing details");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO complaints (complainant_id, accused_id, food_id, complaint_text) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $complainant_id, $accused_id, $food_id, $complaint_text);
    
    if ($stmt->execute()) {
        header("Location: food_details.php?id=$food_id&success=Report submitted successfully. The system administrator will review it.");
    } else {
        header("Location: food_details.php?id=$food_id&error=Database error");
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: dashboard.php");
}
exit();
?>

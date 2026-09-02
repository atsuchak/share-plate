<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id <= 0) {
        header("Location: admin_users.php");
        exit();
    }

    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($action === 'suspend' || $action === 'activate') {
        $status = ($action === 'suspend') ? 'Suspended' : 'Active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $target_user_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin_view_user.php?id=" . $target_user_id);
        exit();
    } elseif ($action === 'delete') {
        // We need to cascade delete related records manually to ensure database consistency

        // 1. Delete claims where this user is the receiver
        $stmt = $conn->prepare("DELETE FROM food_claims WHERE receiver_id = ?");
        $stmt->bind_param("i", $target_user_id);
        $stmt->execute();
        $stmt->close();

        // 2. Delete claims on food listings created by this user
        $stmt = $conn->prepare("DELETE FROM food_claims WHERE food_id IN (SELECT id FROM food_listings WHERE donor_id = ?)");
        $stmt->bind_param("i", $target_user_id);
        $stmt->execute();
        $stmt->close();

        // 3. Delete food listings created by this user
        $stmt = $conn->prepare("DELETE FROM food_listings WHERE donor_id = ?");
        $stmt->bind_param("i", $target_user_id);
        $stmt->execute();
        $stmt->close();

        // 4. Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_users.php");
        exit();
    }
}

header("Location: admin_users.php");
exit();
?>

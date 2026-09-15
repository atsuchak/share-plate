<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost', 'root', '', 'share_plate');

$stmt = $conn->prepare("SELECT profile_image FROM " . (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3 ? 'admin' : 'users') . " WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$oldImg = $stmt->get_result()->fetch_assoc()['profile_image'];
$stmt->close();

if ($oldImg && file_exists('../' . $oldImg)) {
    unlink('../' . $oldImg);
}

$stmt = $conn->prepare("UPDATE " . (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3 ? 'admin' : 'users') . " SET profile_image = NULL WHERE id = ?");
$stmt->bind_param("i", $user_id);
if ($stmt->execute()) {
    $_SESSION['profile_image'] = null;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'DB update failed']);
}
$stmt->close();
$conn->close();
?>

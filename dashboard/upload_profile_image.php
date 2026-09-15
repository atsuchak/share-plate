<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_image'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user_id'];
$file = $_FILES['profile_image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
    exit();
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit();
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
$targetDir = '../assets/uploads/profiles/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . $filename;
$dbPath = 'assets/uploads/profiles/' . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    
    // Fetch old image to delete
    $stmt = $conn->prepare("SELECT profile_image FROM " . (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3 ? 'admin' : 'users') . " WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $oldImg = $stmt->get_result()->fetch_assoc()['profile_image'];
    $stmt->close();

    if ($oldImg && file_exists('../' . $oldImg)) {
        unlink('../' . $oldImg);
    }

    $stmt = $conn->prepare("UPDATE " . (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3 ? 'admin' : 'users') . " SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $dbPath, $user_id);
    if ($stmt->execute()) {
        $_SESSION['profile_image'] = $dbPath;
        echo json_encode(['success' => true, 'path' => $dbPath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB update failed']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
?>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $donor_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $details = trim($_POST['details']);
    $quantity = (int)$_POST['quantity'];
    $category = $_POST['category'];
    $pickup_location = trim($_POST['pickup_location']);
    $contact_info = trim($_POST['contact_info']);
    $expiry_time = $_POST['expiry_time'];
    if (strlen($expiry_time) == 10) {
        $expiry_time .= ' 23:59:59';
    }
    
    // Handle File Upload
    $image_path = '';
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_name = $_FILES['food_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_name = uniqid('food_', true) . '.' . $file_ext;
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            if (move_uploaded_file($_FILES['food_image']['tmp_name'], $upload_dir . $new_name)) {
                $image_path = $upload_dir . $new_name;
            }
        }
    }

    $conn = new mysqli('localhost', 'root', '', 'share_plate');
    if ($conn->connect_error) {
        $_SESSION['error_message'] = "Database Connection Failed.";
        header("Location: post_food.php");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO food_listings (donor_id, title, details, quantity, category, pickup_location, contact_info, expiry_time, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')");
    $stmt->bind_param("ississsss", $donor_id, $title, $details, $quantity, $category, $pickup_location, $contact_info, $expiry_time, $image_path);
    
    if ($stmt->execute()) {
        $new_food_id = $conn->insert_id;
        
        // Notification Logic
        $notif_title = "New Food Available!";
        $notif_message = "A new listing '$title' has been posted in '$category' at '$pickup_location'.";
        $notif_link = "marketplace.php";
        
        // Fetch all receivers
        $res = $conn->query("SELECT id, email, full_name, email_notifications, in_app_notifications FROM users WHERE role_id = 2");
        
        // Prepare emails
        require '../vendor/autoload.php';
        require '../config.php';
        $resend = Resend::client(RESEND_API_KEY);
        $bcc_emails = [];

        if ($res && $res->num_rows > 0) {
            while ($receiver = $res->fetch_assoc()) {
                // In-App Notification
                if ($receiver['in_app_notifications'] == 1) {
                    $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                    $n_stmt->bind_param("isss", $receiver['id'], $notif_title, $notif_message, $notif_link);
                    $n_stmt->execute();
                    $n_stmt->close();
                }
                
                // Email Notification via BCC
                if ($receiver['email_notifications'] == 1) {
                    $bcc_emails[] = $receiver['email'];
                }
            }
        }
        
        // Send email if there are recipients
        if (count($bcc_emails) > 0) {
            try {
                // Resend requires a 'to' address
                $resend->emails->send([
                    'from' => 'SharePlate Alerts <noreply@atsuchak.me>',
                    'to' => ['noreply@atsuchak.me'],
                    'bcc' => $bcc_emails,
                    'subject' => 'New Food Alert - SharePlate',
                    'html' => "<h2>New Food Available</h2><p>$notif_message</p><p><a href='http://localhost/SharePlate/marketplace.php'>View Marketplace</a></p>"
                ]);
            } catch (\Exception $e) {
                // Log or ignore email send failure
            }
        }

        $_SESSION['success_message'] = "Your food listing has been published successfully!";
        header("Location: post_food.php");
    } else {
        $_SESSION['error_message'] = "Failed to publish listing: " . $conn->error;
        header("Location: post_food.php");
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: post_food.php");
}
?>

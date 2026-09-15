<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'share_plate');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'], $_POST['conv_id'])) {
    $conv_id = (int)$_POST['conv_id'];
    $message_text = trim($_POST['message_text']);
    
    // Verify user is part of this conversation
    $verify_sql = "SELECT id FROM conversations WHERE id = ? AND (donor_id = ? OR requester_id = ?)";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("iii", $conv_id, $user_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0 && !empty($message_text)) {
        // Insert message
        $insert = "INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)";
        $istmt = $conn->prepare($insert);
        $istmt->bind_param("iis", $conv_id, $user_id, $message_text);
        $istmt->execute();
        
        // Update conversation timestamp
        $update = "UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $ustmt = $conn->prepare($update);
        $ustmt->bind_param("i", $conv_id);
        $ustmt->execute();
    }
    header("Location: messages.php?conv_id=" . $conv_id);
    exit();
}

// Fetch all conversations for this user
$sql = "SELECT c.*, f.title as food_title, f.image_path, 
        u_donor.full_name as donor_name, u_req.full_name as req_name 
        FROM conversations c
        JOIN food_listings f ON c.food_id = f.id
        JOIN users u_donor ON c.donor_id = u_donor.id
        JOIN users u_req ON c.requester_id = u_req.id
        WHERE c.donor_id = ? OR c.requester_id = ?
        ORDER BY c.updated_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$conversations_result = $stmt->get_result();
$conversations = [];
while($row = $conversations_result->fetch_assoc()) {
    $conversations[] = $row;
}

$is_chat_active = isset($_GET['conv_id']);
$active_conv_id = isset($_GET['conv_id']) ? (int)$_GET['conv_id'] : (count($conversations) > 0 ? $conversations[0]['id'] : null);
$active_conv = null;
$messages = [];

if ($active_conv_id) {
    // Find active conversation details
    foreach($conversations as $c) {
        if ($c['id'] == $active_conv_id) {
            $active_conv = $c;
            break;
        }
    }
    
    // Fetch messages for active conversation
    if ($active_conv) {
        $msg_sql = "SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC";
        $mstmt = $conn->prepare($msg_sql);
        $mstmt->bind_param("i", $active_conv_id);
        $mstmt->execute();
        $msg_result = $mstmt->get_result();
        while($mrow = $msg_result->fetch_assoc()) {
            $messages[] = $mrow;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - SharePlate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .messenger-container {
            display: flex;
            height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
        }
        .conv-list-panel {
            width: 320px;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .conv-list-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            color: var(--secondary-dark);
            font-size: 1.1rem;
        }
        .conv-list {
            flex: 1;
            overflow-y: auto;
        }
        .conv-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            gap: 15px;
        }
        .conv-item:hover { background: #f1f5f9; }
        .conv-item.active { background: #e0f2fe; border-left: 4px solid #0ea5e9; }
        
        .conv-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            background: #cbd5e1;
        }
        .conv-info { flex: 1; overflow: hidden; }
        .conv-name {
            display: block;
            font-weight: 600;
            color: var(--secondary-dark);
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conv-food {
            display: block;
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
        }
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8fafc;
        }
        .msg-bubble {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
        }
        .msg-received {
            background: #ffffff;
            color: #334155;
            align-self: flex-start;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }
        .msg-sent {
            background: var(--primary-green);
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .msg-time {
            font-size: 0.7rem;
            opacity: 0.7;
            display: block;
            margin-top: 5px;
            text-align: right;
        }
        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .chat-form {
            display: flex;
            gap: 15px;
        }
        .chat-input {
            flex: 1;
            padding: 15px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            outline: none;
            font-size: 0.95rem;
            transition: border 0.2s;
            font-family: inherit;
        }
        .chat-input:focus { border-color: var(--primary-green); }
        .btn-send {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-green);
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .btn-send:hover { transform: scale(1.05); }
        
        body.dark-theme .messenger-container { background: #1e293b; border-color: #334155; }
        body.dark-theme .conv-list-panel { background: #0f172a; border-color: #334155; }
        body.dark-theme .conv-list-header, body.dark-theme .conv-item { border-color: #334155; }
        body.dark-theme .conv-item:hover { background: #1e293b; }
        body.dark-theme .conv-item.active { background: #1e293b; border-left-color: var(--primary-green); }
        body.dark-theme .conv-name { color: #f8fafc; }
        body.dark-theme .chat-panel { background: #1e293b; }
        body.dark-theme .chat-header { border-color: #334155; }
        body.dark-theme .chat-messages { background: #0f172a; }
        body.dark-theme .msg-received { background: #1e293b; border-color: #334155; color: #f8fafc; }
        body.dark-theme .chat-input-area { background: #1e293b; border-color: #334155; }
        body.dark-theme .chat-input { background: #0f172a; border-color: #334155; color: #f8fafc; }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .conv-list-panel {
                width: 250px;
            }
        }
        @media (max-width: 768px) {
            .messenger-container {
                height: calc(100vh - 120px);
                flex-direction: row;
            }
            .messenger-container.mobile-list-active .conv-list-panel {
                width: 100%;
                border-right: none;
            }
            .messenger-container.mobile-list-active .chat-panel {
                display: none;
            }
            .messenger-container.mobile-chat-active .conv-list-panel {
                display: none;
            }
            .messenger-container.mobile-chat-active .chat-panel {
                width: 100%;
            }
            .mobile-back-btn {
                display: flex !important;
            }
        }
        @media (max-width: 480px) {
            .messenger-container {
                height: calc(100vh - 140px);
            }
            .conv-list-panel {
                flex: 0 0 40%;
            }
            .msg-bubble {
                max-width: 90%;
            }
            .chat-header {
                padding: 12px 15px;
            }
            .chat-input-area {
                padding: 12px 15px;
            }
            .btn-send {
                width: 45px;
                height: 45px;
            }
            .conv-item {
                padding: 10px 15px;
            }
            .conv-list-header {
                padding: 15px;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search...">
                </div>
            </div>
            <div class="header-actions">

                <a href="profile.php" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                        <span class="user-id">
                            <?php 
                                $rid = $_SESSION['role_id'] ?? 1;
                                if ($rid == 1) echo 'Food Provider';
                                elseif ($rid == 2) echo 'Community Member';
                                elseif ($rid == 3) echo 'Administrator';
                            ?>
                        </span>
                    </div>
                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                </a>
            </div>
        </header>

        <div class="post-food-content" style="background: transparent; border: none; box-shadow: none; padding-top: 10px;">
            <div class="messenger-container <?php echo $is_chat_active ? 'mobile-chat-active' : 'mobile-list-active'; ?>">
                <!-- Left Panel -->
                <div class="conv-list-panel">
                    <div class="conv-list-header">Inbox</div>
                    <div class="conv-list">
                        <?php if (empty($conversations)): ?>
                            <div style="padding: 20px; color: #94a3b8; text-align: center; font-size: 0.9rem;">No active conversations.</div>
                        <?php else: ?>
                            <?php foreach($conversations as $c): 
                                $other_person = ($c['donor_id'] == $user_id) ? $c['req_name'] : $c['donor_name'];
                                $img = $c['image_path'] ? htmlspecialchars($c['image_path']) : '../assets/img/just-a-meal.png';
                            ?>
                                <a href="messages.php?conv_id=<?php echo $c['id']; ?>" class="conv-item <?php echo ($active_conv_id == $c['id']) ? 'active' : ''; ?>">
                                    <img src="<?php echo $img; ?>" class="conv-avatar" alt="Food">
                                    <div class="conv-info">
                                        <span class="conv-name"><?php echo htmlspecialchars($other_person); ?></span>
                                        <span class="conv-food">Re: <?php echo htmlspecialchars($c['food_title']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Panel -->
                <div class="chat-panel">
                    <?php if ($active_conv): 
                        $other_person = ($active_conv['donor_id'] == $user_id) ? $active_conv['req_name'] : $active_conv['donor_name'];
                        $img = $active_conv['image_path'] ? htmlspecialchars($active_conv['image_path']) : '../assets/img/just-a-meal.png';
                    ?>
                        <div class="chat-header">
                            <a href="messages.php" class="mobile-back-btn" style="display: none; margin-right: 15px; font-size: 1.2rem; color: var(--secondary-dark); text-decoration: none;">
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <img src="<?php echo $img; ?>" class="conv-avatar" alt="Food">
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; color: var(--secondary-dark);"><?php echo htmlspecialchars($other_person); ?></h3>
                                <span style="font-size: 0.85rem; color: #64748b;">Regarding: <a href="food_details.php?id=<?php echo $active_conv['food_id']; ?>" style="color: var(--primary-green); text-decoration: none;"><?php echo htmlspecialchars($active_conv['food_title']); ?></a></span>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatBox">
                            <?php if (empty($messages)): ?>
                                <div style="text-align: center; color: #94a3b8; margin-top: 50px;">Start the conversation...</div>
                            <?php else: ?>
                                <?php foreach($messages as $m): 
                                    $is_me = ($m['sender_id'] == $user_id);
                                ?>
                                    <div class="msg-bubble <?php echo $is_me ? 'msg-sent' : 'msg-received'; ?>">
                                        <?php echo nl2br(htmlspecialchars($m['message_text'])); ?>
                                        <span class="msg-time"><?php echo date('h:i A', strtotime($m['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="chat-input-area">
                            <form action="messages.php" method="POST" class="chat-form">
                                <input type="hidden" name="conv_id" value="<?php echo $active_conv_id; ?>">
                                <input type="text" name="message_text" class="chat-input" placeholder="Type your message..." required autocomplete="off">
                                <button type="submit" class="btn-send"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8; flex-direction: column; gap: 15px;">
                            <i class="fa-regular fa-comments" style="font-size: 3rem; color: #cbd5e1;"></i>
                            Select a conversation to start messaging.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        const dashToggle = document.getElementById('darkModeToggleDash');
        if (dashToggle) {
            dashToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-theme');
                const icon = dashToggle.querySelector('i');
                if (document.body.classList.contains('dark-theme')) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        }
        
        // Auto-scroll chat to bottom
        const chatBox = document.getElementById('chatBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>

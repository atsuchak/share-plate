<?php
session_start();
// Include connection or ensure we check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 1) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Food - SharePlate</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="dashboard-main">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search your dashboard...">
            </div>
            </div>
            
            <div class="header-actions">
                <div class="notification-wrapper" style="position: relative;">
                    <button class="action-btn notification-btn" id="notificationBtn">
                        <i class="fa-regular fa-bell"></i>
                        <span class="badge-dot"></span>
                    </button>
                    <!-- Notifications Widget (Popup) -->
                    <div class="widget notifications-widget popup-hidden" id="notificationPopup">
                        <div class="widget-header">
                            <h3>Notifications</h3>
                            <span class="badge-new">4 NEW</span>
                        </div>
                        <div class="notification-list">
                            <div class="notification-item">
                                <div class="notif-icon bg-green-light">
                                    <i class="fa-solid fa-hand-holding-heart text-green"></i>
                                </div>
                                <div class="notif-content">
                                    <h4>New donation request nearby</h4>
                                    <p>"Community Kitchen" needs 20kg of fresh vegetables within 2 hours.</p>
                                    <span class="notif-time">2 MINS AGO</span>
                                </div>
                            </div>
                        </div>
                        <a href="notifications.php" class="view-all-center">View all activity</a>
                    </div>
                </div>

                <a href="profile.php" class="user-profile" style="text-decoration: none; color: inherit;">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Alex Rivera'); ?></span>
                        <span class="user-id">
                            <?php 
                                $rid = $_SESSION['role_id'] ?? 1;
                                if ($rid == 1) echo 'Food Provider';
                                elseif ($rid == 2) echo 'Community Member';
                                elseif ($rid == 3) echo 'Administrator';
                            ?>
                        </span>
                    </div>
                    <?php 
                        $prefix = isset($root_prefix) ? $root_prefix : (basename($_SERVER['PHP_SELF']) == 'marketplace.php' ? '' : '../');
                        $avatarUrl = !empty($_SESSION['profile_image']) ? $prefix . $_SESSION['profile_image'] : '';
                    ?>
                    <div class="user-avatar" style="<?php echo $avatarUrl ? 'background-image: url(\'' . htmlspecialchars($avatarUrl) . '\'); background-size: cover; background-position: center;' : ''; ?>">
                        <?php if(!$avatarUrl): ?>
                            <i class="fa-solid fa-user"></i>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </header>

        <div class="post-food-content">
            <div class="post-food-header">
                <div class="header-text">
                    <span class="breadcrumb">INVENTORY <span class="divider">/</span> <span class="active">NEW LISTING</span></span>
                    <h2>Share a Plate</h2>
                    <p>Turn your surplus into someone's sustenance. Fill in the details below to list your donation.</p>
                </div>
                <a href="history.php" class="btn-history" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
            </div>

            <form action="process_post_food.php" method="POST" enctype="multipart/form-data" class="post-food-form">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                    unset($_SESSION['error_message']);
                }
                ?>
                <!-- Image Dropzone -->
                <div class="form-group dropzone-wrapper" id="dropzoneWrapper">
                    <input type="file" name="food_image" id="food_image" class="dropzone-input" accept="image/*" required>
                    <div class="dropzone-content" id="dropzoneContent">
                        <i class="fa-solid fa-cloud-arrow-up cloud-icon"></i>
                        <h3>Drop your photos here</h3>
                        <p>High quality images help your donation find a home faster</p>
                    </div>
                    <img id="imagePreview" src="" alt="Image Preview" style="display: none; max-height: 200px; max-width: 100%; border-radius: 12px; margin: 0 auto; object-fit: cover; position: relative; z-index: 5;">
                </div>

                <div class="form-row-2">
                    <!-- Title Input -->
                    <div class="form-group">
                        <label for="title">WHAT ARE YOU DONATING?</label>
                        <input type="text" id="title" name="title" class="rounded-input" placeholder="e.g., 50 Servings of Fried Rice" required>
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label for="category">CATEGORY</label>
                        <div class="select-wrapper">
                            <select id="category" name="category" class="rounded-select" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Baked Goods">Baked Goods</option>
                                <option value="Fresh Produce">Fresh Produce</option>
                                <option value="Prepared Meals">Prepared Meals</option>
                                <option value="Dairy & Eggs">Dairy & Eggs</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row-2">
                    <!-- Pickup Location -->
                    <div class="form-group">
                        <label for="pickup_location">PICKUP LOCATION</label>
                        <input type="text" id="pickup_location" name="pickup_location" class="rounded-input" placeholder="e.g., Back alley door" required>
                    </div>

                    <!-- Contact Info -->
                    <div class="form-group">
                        <label for="contact_info">CONTACT PERSON</label>
                        <input type="text" id="contact_info" name="contact_info" class="rounded-input" placeholder="e.g., Sarah - (555) 123-4567" required>
                    </div>
                </div>

                <div class="form-row-2">
                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity">QUANTITY</label>
                        <div class="quantity-picker" style="width: 100%;">
                            <button type="button" class="qty-btn" id="qty-minus"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" class="qty-input" style="flex:1;" required>
                            <button type="button" class="qty-btn" id="qty-plus"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <!-- Expiry Time -->
                    <div class="form-group">
                        <label for="expiry_time">EXPIRY TIME</label>
                        <input type="date" id="expiry_time" name="expiry_time" class="rounded-input" required>
                    </div>
                </div>

                <!-- Details & Allergens -->
                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="details">DETAILS & ALLERGENS</label>
                    <textarea id="details" name="details" class="rounded-textarea" placeholder="List key ingredients, potential allergens (nuts, dairy, gluten), and packaging details..." rows="2" required></textarea>
                </div>

                <div class="form-actions-row">
                    <button type="button" class="btn-discard"><i class="fa-regular fa-trash-can"></i> Discard Draft</button>
                    <button type="submit" class="btn-primary-dark">Post Food Listing <i class="fa-solid fa-rocket"></i></button>
                </div>
            </form>

            <div class="did-you-know-card">
                <div class="dyk-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                <div class="dyk-content">
                    <h4>DID YOU KNOW?</h4>
                    <p>This listing could feed up to 4 people in your neighborhood today. Your small action creates a massive ripple of kindness.</p>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script>
        // Simple dark mode toggle for dashboard
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

        // Notifications Popup Toggle
        const notifBtn = document.getElementById('notificationBtn');
        const notifPopup = document.getElementById('notificationPopup');
        if(notifBtn && notifPopup) {
            notifBtn.addEventListener('click', () => {
                notifPopup.classList.toggle('popup-show');
            });
            document.addEventListener('click', (e) => {
                if (!notifBtn.contains(e.target) && !notifPopup.contains(e.target)) {
                    notifPopup.classList.remove('popup-show');
                }
            });
        }

        // Quantity Picker logic
        const qtyMinus = document.getElementById('qty-minus');
        const qtyPlus = document.getElementById('qty-plus');
        const qtyInput = document.getElementById('quantity');

        if(qtyMinus && qtyPlus && qtyInput) {
            qtyMinus.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value);
                if(currentVal > 1) {
                    qtyInput.value = currentVal - 1;
                }
            });
            qtyPlus.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value);
                qtyInput.value = currentVal + 1;
            });
        }

        // Image Preview logic
        const fileInput = document.getElementById('food_image');
        const dropzoneContent = document.getElementById('dropzoneContent');
        const imagePreview = document.getElementById('imagePreview');
        const dropzoneWrapper = document.getElementById('dropzoneWrapper');

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imagePreview.setAttribute('src', event.target.result);
                        imagePreview.style.display = 'block';
                        dropzoneContent.style.display = 'none';
                        dropzoneWrapper.style.padding = '10px';
                    }
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.style.display = 'none';
                    imagePreview.setAttribute('src', '');
                    dropzoneContent.style.display = 'block';
                    dropzoneWrapper.style.padding = '50px 20px';
                }
            });
        }
    </script>
</body>
</html>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 1;
$in_root = ($currentPage === 'marketplace.php' || $currentPage === 'index.php');
$dash_prefix = $in_root ? 'dashboard/' : '';
$root_prefix = $in_root ? '' : '../';
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-logo">
        <span class="logo-dash">-</span> SharePlate
    </div>
    
    <ul class="sidebar-nav">
        <li>
            <a href="<?php echo $dash_prefix; ?>dashboard.php" class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-border-all"></i>
                <span>Overview</span>
            </a>
        </li>
        <?php if ($role_id == 1): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?>active_listings.php" class="nav-item <?php echo ($currentPage == 'active_listings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-utensils"></i>
                <span>Active Listings</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 3): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?>admin_users.php" class="nav-item <?php echo ($currentPage == 'admin_users.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Users</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 1 || $role_id == 2): ?>
        <li>
            <a href="<?php echo $root_prefix; ?>marketplace.php" class="nav-item <?php echo ($currentPage == 'marketplace.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-store"></i>
                <span>Marketplace</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 1): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?>post_food.php" class="nav-item <?php echo ($currentPage == 'post_food.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Post Food</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 1 || $role_id == 2): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?>messages.php" class="nav-item <?php echo ($currentPage == 'messages.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-message"></i>
                <span>Messages</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $dash_prefix; ?>history.php" class="nav-item <?php echo ($currentPage == 'history.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>History</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $dash_prefix; ?>notifications.php" class="nav-item <?php echo ($currentPage == 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 1 || $role_id == 3): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?><?php echo $role_id == 3 ? 'admin_analytics.php' : 'analytics.php'; ?>" class="nav-item <?php echo ($currentPage == 'analytics.php' || $currentPage == 'admin_analytics.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Analytics</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($role_id == 3): ?>
        <li>
            <a href="<?php echo $dash_prefix; ?>admin_reports.php" class="nav-item <?php echo ($currentPage == 'admin_reports.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-contract"></i>
                <span>Reports</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-bottom">
        <a href="<?php echo $dash_prefix; ?>how_it_works.php" class="nav-item <?php echo ($currentPage == 'how_it_works.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-question"></i>
            <span>How it works</span>
        </a>
        <a href="<?php echo $dash_prefix; ?>settings.php" class="nav-item <?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>
        <a href="<?php echo $root_prefix; ?>auth/logout.php" class="nav-item logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Log Out</span>
        </a>
    </div>
</aside>

<?php
// Included by marketplace.php where $listings is available
?>
<div class="filters-container" style="display: flex; gap: 15px; margin-bottom: 30px; width: 100%; justify-content: flex-end;">
    <select id="categoryFilter" class="form-control" style="width: auto; padding: 10px 20px; border-radius: 8px;">
        <option value="All">All Categories</option>
        <option value="Baked Goods">Baked Goods</option>
        <option value="Fresh Produce">Fresh Produce</option>
        <option value="Prepared Meals">Prepared Meals</option>
        <option value="Dairy & Eggs">Dairy & Eggs</option>
        <option value="Other">Other</option>
    </select>
    
    <select id="statusFilter" class="form-control" style="width: auto; padding: 10px 20px; border-radius: 8px;">
        <option value="All">All Statuses</option>
        <option value="VERY FRESH">Very Fresh</option>
        <option value="EXPIRING SOON">Expiring Soon</option>
        <option value="URGENT">Urgent</option>
        <option value="Expired">Expired</option>
    </select>
</div>

<div class="market-grid" id="marketGrid">
    <?php if (empty($listings)): ?>
        <p style="text-align: center; color: #64748b; grid-column: 1 / -1; padding: 40px 0;">No active food listings available right now. Check back later!</p>
    <?php else: ?>
        <?php foreach ($listings as $item): ?>
            <?php 
                $statusInfo = getTimeRemaining($item['expiry_time']); 
                $img = !empty($item['image_path']) ? $item['image_path'] : 'assets/img/placeholder.jpg';
                if (strpos($img, '../') === 0) {
                    $img = substr($img, 3);
                }
            ?>
            <div class="food-card" data-category="<?php echo htmlspecialchars($item['category']); ?>" data-status="<?php echo htmlspecialchars($statusInfo['text']); ?>">
                <div class="card-img-wrapper">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <span class="status-tag <?php echo $statusInfo['class']; ?>"><?php echo $statusInfo['text']; ?></span>
                </div>
                <div class="card-content">
                    <h3 class="card-title" style="margin-bottom: 6px; font-size: 1.2rem;">
                        <?php echo htmlspecialchars($item['title']); ?>
                        <?php if ($item['category'] == 'Fresh Produce'): ?>
                            <i class="fa-solid fa-leaf" style="color: var(--primary-green); font-size: 1rem; margin-left: 4px;"></i>
                        <?php endif; ?>
                    </h3>
                    
                    <div style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-location-dot" style="opacity: 0.7;"></i> 
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 60%;"><?php echo htmlspecialchars($item['pickup_location']); ?></span>
                        <span style="opacity: 0.5;">•</span>
                        <span><?php echo date('M j', strtotime($item['created_at'])); ?></span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; padding: 12px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Quantity</div>
                            <div style="font-size: 0.95rem; font-weight: 600; color: #334155;">
                                <?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div style="border-left: 1px solid #f1f5f9; padding-left: 15px;">
                            <div style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Expires In</div>
                            <div class="<?php echo str_replace('tag-', '', $statusInfo['class']); ?>" style="font-size: 0.95rem; font-weight: 600;">
                                <?php echo $statusInfo['text'] == 'Expired' ? 'Expired' : $statusInfo['countdown']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <a href="<?php echo $is_logged_in ? 'dashboard/messages.php?user='.$item['donor_id'] : 'auth/login.php?redirect=marketplace'; ?>" class="btn-msg">Message</a>
                        <a href="<?php echo $is_logged_in ? 'dashboard/food_details.php?id='.$item['id'] : 'auth/login.php?redirect=marketplace'; ?>" class="btn-request btn-view">View</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="loadMoreContainer" style="text-align: center; margin-top: 50px; display: none;">
    <button style="padding: 12px 30px; border-radius: 50px; border: 1px solid #e2e8f0; background: #ffffff; color: #475569; font-weight: 600; cursor: pointer;">
        DISCOVER MORE ITEMS <i class="fa-solid fa-chevron-down" style="margin-left: 8px;"></i>
    </button>
</div>

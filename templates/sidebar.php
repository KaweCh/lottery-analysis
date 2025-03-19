<?php
/**
 * footer.php
 * 
 * Footer template for the Thai Lottery Analysis system
 */
?>

    </div><!-- End Main Container -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo getConfig('site_title'); ?> - 
                        ระบบวิเคราะห์และทำนายผลรางวัลสลากกินแบ่งรัฐบาล
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo getConfig('base_url'); ?>about.php" class="text-muted me-3">
                        <i class="fas fa-info-circle me-1"></i> เกี่ยวกับเรา
                    </a>
                    <a href="<?php echo getConfig('base_url'); ?>contact.php" class="text-muted me-3">
                        <i class="fas fa-envelope me-1"></i> ติดต่อ
                    </a>
                    <a href="<?php echo getConfig('base_url'); ?>privacy.php" class="text-muted">
                        <i class="fas fa-lock me-1"></i> นโยบายความเป็นส่วนตัว
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for some legacy components) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo getConfig('base_url'); ?>assets/js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
            <script src="<?php echo getConfig('base_url') . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inlineJs)): ?>
        <script>
            <?php echo $inlineJs; ?>
        </script>
    <?php endif; ?>
</body>
</html>

<?php
/**
 * sidebar.php
 * 
 * Sidebar template for the Thai Lottery Analysis admin dashboard
 */
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-chart-line me-2"></i> จัดการระบบ</h3>
    </div>
    
    <div class="sidebar-user">
        <div class="user-icon">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <p class="user-name"><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'ผู้ดูแลระบบ'; ?></p>
            <p class="user-role"><?php echo isset($_SESSION['user_role']) ? ($_SESSION['user_role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งาน') : ''; ?></p>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'dashboard') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/index.php">
                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
            </a>
        </li>
        
        <li class="menu-header">จัดการข้อมูล</li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'lottery_results') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/lottery_results.php">
                <i class="fas fa-ticket-alt"></i> ผลรางวัลสลาก
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'predictions') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/predictions.php">
                <i class="fas fa-crystal-ball"></i> การทำนาย
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'accuracy') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/accuracy.php">
                <i class="fas fa-bullseye"></i> ความแม่นยำ
            </a>
        </li>
        
        <li class="menu-header">การวิเคราะห์</li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'statistical_analysis') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/statistical_analysis.php">
                <i class="fas fa-chart-bar"></i> วิเคราะห์สถิติ
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'trend_analysis') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/trend_analysis.php">
                <i class="fas fa-chart-line"></i> วิเคราะห์แนวโน้ม
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'pattern_analysis') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/pattern_analysis.php">
                <i class="fas fa-shapes"></i> วิเคราะห์รูปแบบ
            </a>
        </li>
        
        <li class="menu-header">ระบบ</li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'users') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/users.php">
                <i class="fas fa-users"></i> ผู้ใช้งาน
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'settings') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/settings.php">
                <i class="fas fa-cog"></i> ตั้งค่าระบบ
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'logs') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/logs.php">
                <i class="fas fa-clipboard-list"></i> บันทึกการใช้งาน
            </a>
        </li>
        
        <li class="<?php echo (isset($activeMenu) && $activeMenu === 'backup') ? 'active' : ''; ?>">
            <a href="<?php echo getConfig('base_url'); ?>admin/backup.php">
                <i class="fas fa-database"></i> สำรองข้อมูล
            </a>
        </li>
        
        <li>
            <a href="<?php echo getConfig('base_url'); ?>logout.php">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </li>
    </ul>
</div>

<!-- Sidebar Toggle Button (for mobile) -->
<div class="sidebar-toggle">
    <button id="sidebarToggleBtn">
        <i class="fas fa-bars"></i>
    </button>
</div>

<script>
    document.getElementById('sidebarToggleBtn').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed');
        document.querySelector('.content-wrapper').classList.toggle('expanded');
    });
</script>
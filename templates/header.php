<?php
/**
 * header.php
 * 
 * Header template for the Thai Lottery Analysis system
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getConfig('site_title'); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo getConfig('base_url'); ?>assets/img/favicon.png" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo getConfig('base_url'); ?>assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link href="<?php echo getConfig('base_url') . $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getConfig('base_url'); ?>">
                <i class="fas fa-chart-line me-2"></i>
                <?php echo getConfig('site_title'); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'home') ? 'active' : ''; ?>" 
                           href="<?php echo getConfig('base_url'); ?>">
                            <i class="fas fa-home me-1"></i> หน้าหลัก
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'statistics') ? 'active' : ''; ?>" 
                           href="<?php echo getConfig('base_url'); ?>statistics.php">
                            <i class="fas fa-chart-bar me-1"></i> สถิติ
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'predictions') ? 'active' : ''; ?>" 
                           href="<?php echo getConfig('base_url'); ?>predictions.php">
                            <i class="fas fa-crystal-ball me-1"></i> ทำนาย
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'history') ? 'active' : ''; ?>" 
                           href="<?php echo getConfig('base_url'); ?>history.php">
                            <i class="fas fa-history me-1"></i> ประวัติการทำนาย
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($activePage) && $activePage === 'results') ? 'active' : ''; ?>" 
                           href="<?php echo getConfig('base_url'); ?>results.php">
                            <i class="fas fa-ticket-alt me-1"></i> ผลรางวัล
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo getConfig('base_url'); ?>admin/">
                                            <i class="fas fa-cog me-1"></i> จัดการระบบ
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo getConfig('base_url'); ?>profile.php">
                                        <i class="fas fa-user-cog me-1"></i> โปรไฟล์
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo getConfig('base_url'); ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-1"></i> ออกจากระบบ
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getConfig('base_url'); ?>login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> เข้าสู่ระบบ
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Message -->
    <?php $flashMessage = getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show">
                <?php echo $flashMessage['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Container -->
    <div class="container py-4">
        <?php if (isset($pageTitle)): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                <?php if (isset($pageActions)): ?>
                    <div class="page-actions">
                        <?php echo $pageActions; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>


<?php
/**
 * functions.php
 * 
 * Core utility functions for the Thai Lottery Analysis application
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Format date to Thai display format
 * 
 * @param string $date Date in YYYY-MM-DD format
 * @return string Formatted date in Thai format
 */
function formatThaiDisplayDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $thaiMonths = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];
    
    $day = date('j', $timestamp);
    $month = $thaiMonths[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543; // Convert to Buddhist year
    
    return "$day $month $year";
}

/**
 * Get Thai day of week
 * 
 * @param string|DateTime $date Date in YYYY-MM-DD format or DateTime object
 * @return string Day of week in Thai
 */
function getThaiDayOfWeek($date) {
    if (empty($date)) return '';
    
    // If DateTime object is passed, convert to string
    if ($date instanceof DateTime) {
        $date = $date->format('Y-m-d');
    }
    
    $timestamp = strtotime($date);
    $dayNum = date('N', $timestamp); // 1 (Monday) to 7 (Sunday)
    
    $thaiDays = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์'
    ];
    
    return $thaiDays[$dayNum];
}

/**
 * Format number with thousand separator
 * 
 * @param int|float $number Number to format
 * @param int $decimals Number of decimal places
 * @return string Formatted number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, '.', ',');
}

/**
 * Calculate percentage
 * 
 * @param int|float $part Part value
 * @param int|float $total Total value
 * @param int $decimals Number of decimal places
 * @return float Percentage
 */
function calculatePercentage($part, $total, $decimals = 2) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, $decimals);
}

/**
 * Generate HTML for a progress bar
 * 
 * @param float $percentage Percentage value (0-100)
 * @param string $color Bar color class
 * @param bool $showText Whether to show percentage text
 * @return string HTML markup for progress bar
 */
function generateProgressBar($percentage, $color = 'primary', $showText = true) {
    $percentage = min(100, max(0, $percentage)); // Ensure within 0-100
    
    $html = '<div class="progress">';
    $html .= '<div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . $percentage . '%"';
    $html .= ' aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100"></div>';
    $html .= '</div>';
    
    if ($showText) {
        $html .= '<span class="text-' . $color . ' small">' . $percentage . '%</span>';
    }
    
    return $html;
}

/**
 * Check if user has admin access
 * 
 * @return bool True if user has admin access
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Generate pagination HTML
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @return string HTML markup for pagination
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" aria-label="Previous">';
    $html .= '<span aria-hidden="true">&laquo;</span></a></li>';
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $startPage + 4);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" aria-label="Next">';
    $html .= '<span aria-hidden="true">&raquo;</span></a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Validate date format (YYYY-MM-DD)
 * 
 * @param string $date Date to validate
 * @return bool True if date format is valid
 */
function isValidDateFormat($date) {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return checkdate($matches[2], $matches[3], $matches[1]);
    }
    return false;
}

/**
 * Generate a random token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get current page URL
 * 
 * @return string Current page URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return "$protocol://$host$uri";
}

/**
 * Redirect to another page
 * 
 * @param string $url URL to redirect to
 * @param bool $permanent Whether to use permanent (301) redirect
 */
function redirect($url, $permanent = false) {
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    exit;
}

/**
 * Set a flash message in session
 * 
 * @param string $type Message type (success, info, warning, danger)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'created' => time()
    ];
}

/**
 * Get and clear flash message from session
 * 
 * @return array|null Flash message array or null if not set
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        // Ensure message isn't too old (max 5 minutes)
        if (time() - $message['created'] < 300) {
            return $message;
        }
    }
    return null;
}

/**
 * Sanitize and validate form input
 * 
 * @param string $input Input data
 * @param string $type Validation type (text, email, number, date)
 * @return string|null Sanitized input or null if invalid
 */
function sanitizeInput($input, $type = 'text') {
    $input = trim($input);
    
    switch ($type) {
        case 'text':
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
        case 'email':
            $email = filter_var($input, FILTER_SANITIZE_EMAIL);
            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
            
        case 'number':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? $input : null;
            
        case 'date':
            return isValidDateFormat($input) ? $input : null;
            
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Format memory usage for display
 * 
 * @param int $bytes Memory usage in bytes
 * @return string Formatted memory usage
 */
function formatMemoryUsage($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Log application errors to file
 * 
 * @param string $message Error message
 * @param string $file File where error occurred
 * @param int $line Line where error occurred
 */
function logError($message, $file = '', $line = 0) {
    $logPath = getConfig('log_path');
    $date = date('Y-m-d H:i:s');
    $logFile = $logPath . 'errors_' . date('Y-m-d') . '.log';
    
    // Create log directory if it doesn't exist
    if (!file_exists($logPath)) {
        mkdir($logPath, 0755, true);
    }
    
    $location = $file ? " in $file" : "";
    $location .= $line ? " on line $line" : "";
    
    $logEntry = "[$date] ERROR: $message$location" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Set custom error handler
 */
function setCustomErrorHandler() {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }
        
        logError($errstr, $errfile, $errline);
        
        if (getConfig('debug_mode')) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>Error:</strong> $errstr in $errfile on line $errline";
            echo "</div>";
        }
    });
}

/**
 * Check if cache exists and is valid
 * 
 * @param string $cacheKey Cache key
 * @param int $lifetime Cache lifetime in seconds
 * @return bool True if cache exists and is valid
 */
function isCacheValid($cacheKey, $lifetime = 3600) {
    if (!getConfig('cache_enabled')) {
        return false;
    }
    
    $cachePath = getConfig('cache_path');
    $cacheFile = $cachePath . md5($cacheKey) . '.cache';
    
    if (file_exists($cacheFile)) {
        $modTime = filemtime($cacheFile);
        if ((time() - $modTime) < $lifetime) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get data from cache
 * 
 * @param string $cacheKey Cache key
 * @return mixed|null Cached data or null if not found
 */
function getCache($cacheKey) {
    if (!getConfig('cache_enabled')) {
        return null;
    }
    
    $cachePath = getConfig('cache_path');
    $cacheFile = $cachePath . md5($cacheKey) . '.cache';
    
    if (file_exists($cacheFile)) {
        $data = file_get_contents($cacheFile);
        return unserialize($data);
    }
    
    return null;
}

/**
 * Set data in cache
 * 
 * @param string $cacheKey Cache key
 * @param mixed $data Data to cache
 * @return bool Success status
 */
function setCache($cacheKey, $data) {
    if (!getConfig('cache_enabled')) {
        return false;
    }
    
    $cachePath = getConfig('cache_path');
    
    // Create cache directory if it doesn't exist
    if (!file_exists($cachePath)) {
        mkdir($cachePath, 0755, true);
    }
    
    $cacheFile = $cachePath . md5($cacheKey) . '.cache';
    $serializedData = serialize($data);
    
    return file_put_contents($cacheFile, $serializedData) !== false;
}

/**
 * Clear cache for a specific key
 * 
 * @param string $cacheKey Cache key
 * @return bool Success status
 */
function clearCache($cacheKey) {
    $cachePath = getConfig('cache_path');
    $cacheFile = $cachePath . md5($cacheKey) . '.cache';
    
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return true;
}

/**
 * Clear all cache files
 * 
 * @return bool Success status
 */
function clearAllCache() {
    $cachePath = getConfig('cache_path');
    
    if (!file_exists($cachePath)) {
        return true;
    }
    
    $files = glob($cachePath . '*.cache');
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

// Initialize custom error handler
setCustomErrorHandler();
?>


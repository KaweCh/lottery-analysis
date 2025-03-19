<?php
/**
 * config.php
 * 
 * Configuration settings for the Thai Lottery Analysis system
 */

// ใส่ก่อนโค้ดหลัก
ini_set('memory_limit', '5120M');

// Application settings
$config = [
    // Site information
    'site_title' => 'วิเคราะห์หวย - ระบบวิเคราะห์และทำนายผลสลากกินแบ่งรัฐบาล',
    'site_description' => 'ระบบวิเคราะห์สถิติและทำนายผลรางวัลสลากกินแบ่งรัฐบาลด้วยสถิติและการเรียนรู้ของเครื่อง',
    'admin_email' => 'admin@example.com',
    
    // Path settings
    'base_url' => 'http://localhost/lottery-analysis/',
    'base_path' => __DIR__ . '/../',
    'public_path' => __DIR__ . '/../public/',
    'template_path' => __DIR__ . '/../templates/',
    'log_path' => __DIR__ . '/../logs/',
    
    // System settings
    'debug_mode' => true,
    'timezone' => 'Asia/Bangkok',
    'language' => 'th',
    'date_format' => 'd/m/Y',
    'session_lifetime' => 3600, // 1 hour
    
    // Analysis settings
    'min_entries_day' => 20,    // Minimum entries for day of week analysis
    'min_entries_date' => 20,   // Minimum entries for date analysis
    'min_entries_month' => 10,  // Minimum entries for month analysis
    'min_entries_combined' => 10,  // Minimum entries for combined analysis
    'lookback_period' => 50,    // Default lookback period for pattern analysis
    
    // Prediction settings
    'confidence_threshold' => 60,  // Minimum confidence (%) for strong predictions
    'prediction_update_frequency' => 86400,  // Update predictions every 24 hours
    'store_prediction_history' => true,  // Whether to store prediction history
    
    // Visualization settings
    'chart_primary_color' => '#4e73df',
    'chart_secondary_color' => '#36b9cc',
    'chart_accent_color' => '#f6c23e',
    'chart_text_color' => '#5a5c69',
    'display_limit' => 10,  // Default number of items to display
    
    // Accuracy tracking settings
    'accuracy_evaluation_delay' => 86400,  // Evaluate accuracy 24 hours after draw
    'accuracy_history_period' => 180,  // Track accuracy history for 6 months
    
    // Machine learning settings
    'enable_ml_predictions' => true,
    'ml_weight' => 0.4,  // Weight of ML predictions vs statistical predictions
    'retraining_frequency' => 2592000,  // Retrain ML models every 30 days
    
    // API settings
    'api_enabled' => false,
    'api_key_required' => true,
    'api_rate_limit' => 100,  // Requests per day
    
    // Caching settings
    'cache_enabled' => true,
    'cache_lifetime' => 3600,  // 1 hour
    'cache_path' => __DIR__ . '/../cache/',
];

// Initialize application
date_default_timezone_set($config['timezone']);
ini_set('display_errors', $config['debug_mode'] ? 1 : 0);
error_reporting($config['debug_mode'] ? E_ALL : 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => $config['session_lifetime'],
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
    ]);
}

/**
 * Get configuration value
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function getConfig($key, $default = null) {
    global $config;
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 * @return bool Success status
 */
function logMessage($message, $level = 'info') {
    $logPath = getConfig('log_path');
    $date = date('Y-m-d H:i:s');
    $logFile = $logPath . date('Y-m-d') . '.log';
    
    // Create log directory if it doesn't exist
    if (!file_exists($logPath)) {
        mkdir($logPath, 0755, true);
    }
    
    $logEntry = "[$date] [$level] $message" . PHP_EOL;
    return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
}

/**
 * Clean and validate user input
 * 
 * @param mixed $input Input to clean
 * @param string $type Expected type
 * @return mixed Cleaned input
 */
function cleanInput($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT);
        
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        
        case 'boolean':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        
        case 'html':
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        case 'string':
        default:
            if (is_string($input)) {
                $input = trim($input);
                // Remove potentially dangerous characters
                $input = preg_replace('/[^\p{L}\p{N}\s\.\,\-\_\?\!\:\;\(\)\'\"]/u', '', $input);
                return $input;
            }
            return '';
    }
}
?>
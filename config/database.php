<?php
/**
 * database.php
 * 
 * Database connection and related functions for the Thai Lottery Analysis system
 */

// Database configuration
$db_config = [
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname'   => 'lotto_process',
    'charset'  => 'utf8mb4',
    'port'     => 3306
];

// Create database connection
function connectDatabase() {
    global $db_config;
    
    $conn = new mysqli(
        $db_config['host'],
        $db_config['username'],
        $db_config['password'],
        $db_config['dbname'],
        $db_config['port']
    );
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset($db_config['charset']);
    
    return $conn;
}

// Global database connection
$conn = connectDatabase();

/**
 * Get Thai day of week from numeric day
 * 
 * @param int $numericDay Numeric day (1-7, where 1 is Monday)
 * @return string Thai day name
 */
function getDayOfWeekThai($numericDay) {
    $thaiDays = [
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
        7 => 'อาทิตย์'
    ];
    
    return isset($thaiDays[$numericDay]) ? $thaiDays[$numericDay] : '';
}

/**
 * Convert Western year to Buddhist year (CE + 543)
 * 
 * @param int $westernYear Western year (CE)
 * @return int Buddhist year (BE)
 */
function convertToBuddhistYear($westernYear) {
    return $westernYear + 543;
}

/**
 * Format date to Thai Buddhist format
 * 
 * @param string $date Date in YYYY-MM-DD format
 * @return string Date in Thai Buddhist format
 */
function formatThaiDate($date) {
    $timestamp = strtotime($date);
    $westernYear = date('Y', $timestamp);
    $buddhistYear = convertToBuddhistYear($westernYear);
    
    return $buddhistYear . '-' . date('m-d', $timestamp);
}

/**
 * Escape string for database queries
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

/**
 * Get the latest lottery results
 * 
 * @param int $limit Number of records to return
 * @return array Latest lottery results
 */
function getLatestLotteryResults($limit = 10) {
    global $conn;
    
    $sql = "SELECT * FROM lotto_records ORDER BY dateValue DESC LIMIT " . intval($limit);
    $result = $conn->query($sql);
    
    $records = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    return $records;
}

/**
 * Get the next lottery draw date
 * 
 * @return string Next lottery draw date (YYYY-MM-DD)
 */
function getNextDrawDate() {
    $today = new DateTime();
    $day = (int)$today->format('d');
    $month = (int)$today->format('m');
    $year = (int)$today->format('Y');
    
    // Thai lottery is drawn on the 1st and 16th of each month
    if ($day < 16) {
        // Next draw is on the 16th of current month
        $nextDraw = new DateTime("$year-$month-16");
    } else {
        // Next draw is on the 1st of next month
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;
        $nextDraw = new DateTime("$nextYear-$nextMonth-01");
    }
    
    return $nextDraw->format('Y-m-d');
}

/**
 * Check if results exist for a specific date
 * 
 * @param string $date Date to check (YYYY-MM-DD)
 * @return bool True if results exist, false otherwise
 */
function resultExistsForDate($date) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count FROM lotto_records WHERE dateValue = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}
?>

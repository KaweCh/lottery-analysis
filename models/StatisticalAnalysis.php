<?php
/**
 * StatisticalAnalysis.php
 * 
 * Model for statistical analysis of lottery data
 */
namespace Models;

class StatisticalAnalysis
{
    private $conn;
    private $lotteryData;
    
    /**
     * Constructor
     */
    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->lotteryData = new LotteryData($connection);
    }
    
    /**
     * Analyze digit frequency distribution
     * 
     * @param string $field Database field to analyze
     * @param array $filters Filter parameters
     * @return array Frequency distribution analysis
     */
    public function analyzeDigitFrequency($field, $filters = [])
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Build SQL query with filters
        $sql = "SELECT `$field`, COUNT(*) as count FROM lotto_records WHERE `$field` IS NOT NULL";
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $sql .= " AND dateValue >= '" . $this->conn->real_escape_string($filters['start_date']) . "'";
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND dateValue <= '" . $this->conn->real_escape_string($filters['end_date']) . "'";
        }
        
        if (!empty($filters['day_of_week'])) {
            $sql .= " AND day_of_week = '" . $this->conn->real_escape_string($filters['day_of_week']) . "'";
        }
        
        if (!empty($filters['date_day'])) {
            $sql .= " AND DAY(dateValue) = " . intval($filters['date_day']);
        }
        
        if (!empty($filters['date_month'])) {
            $sql .= " AND MONTH(dateValue) = " . intval($filters['date_month']);
        }
        
        // Group and order
        $sql .= " GROUP BY `$field` ORDER BY count DESC";
        
        // Apply limit if specified
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        $distribution = [];
        $totalCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $distribution[] = [
                'digit' => $row[$field],
                'count' => intval($row['count'])
            ];
            $totalCount += intval($row['count']);
        }
        
        // Calculate percentages
        foreach ($distribution as &$item) {
            $item['percentage'] = ($totalCount > 0) ? round(($item['count'] / $totalCount) * 100, 2) : 0;
        }
        
        // Save analysis history
        $this->saveAnalysisHistory('digit_frequency', [
            'field' => $field,
            'filters' => $filters
        ], [
            'total_count' => $totalCount,
            'distribution_count' => count($distribution)
        ]);
        
        return [
            'status' => 'success',
            'distribution' => $distribution,
            'total_count' => $totalCount,
            'field' => $field,
            'filters' => $filters
        ];
    }
    
    /**
     * Analyze digit position frequency
     * 
     * @param string $field Database field to analyze
     * @param array $filters Filter parameters
     * @return array Position-based frequency analysis
     */
    public function analyzeDigitPositionFrequency($field, $filters = [])
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize', 'first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Determine number of positions based on field
        $positions = 0;
        switch ($field) {
            case 'first_prize':
                $positions = 6;
                break;
            case 'first_prize_last3':
            case 'last3f':
            case 'last3b':
                $positions = 3;
                break;
            case 'last2':
                $positions = 2;
                break;
        }
        
        // Build SQL query with filters
        $sql = "SELECT `$field` FROM lotto_records WHERE `$field` IS NOT NULL";
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $sql .= " AND dateValue >= '" . $this->conn->real_escape_string($filters['start_date']) . "'";
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND dateValue <= '" . $this->conn->real_escape_string($filters['end_date']) . "'";
        }
        
        if (!empty($filters['day_of_week'])) {
            $sql .= " AND day_of_week = '" . $this->conn->real_escape_string($filters['day_of_week']) . "'";
        }
        
        if (!empty($filters['date_day'])) {
            $sql .= " AND DAY(dateValue) = " . intval($filters['date_day']);
        }
        
        if (!empty($filters['date_month'])) {
            $sql .= " AND MONTH(dateValue) = " . intval($filters['date_month']);
        }
        
        // Order by date
        $sql .= " ORDER BY dateValue DESC";
        
        // Apply limit if specified
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        // Initialize position frequency arrays
        $positionFrequency = [];
        for ($i = 0; $i < $positions; $i++) {
            $positionFrequency[$i] = array_fill(0, 10, 0); // 0-9 for each position
        }
        
        $totalCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $digits = str_pad($row[$field], $positions, '0', STR_PAD_LEFT);
            
            for ($i = 0; $i < $positions; $i++) {
                $digit = intval($digits[$i]);
                $positionFrequency[$i][$digit]++;
            }
            
            $totalCount++;
        }
        
        // Calculate percentages for each position
        $positionPercentages = [];
        for ($i = 0; $i < $positions; $i++) {
            $positionPercentages[$i] = [];
            for ($j = 0; $j < 10; $j++) {
                $positionPercentages[$i][$j] = ($totalCount > 0) ? 
                    round(($positionFrequency[$i][$j] / $totalCount) * 100, 2) : 0;
            }
        }
        
        // Save analysis history
        $this->saveAnalysisHistory('position_frequency', [
            'field' => $field,
            'filters' => $filters
        ], [
            'total_count' => $totalCount,
            'positions' => $positions
        ]);
        
        return [
            'status' => 'success',
            'frequency' => $positionFrequency,
            'percentages' => $positionPercentages,
            'total_count' => $totalCount,
            'positions' => $positions,
            'field' => $field,
            'filters' => $filters
        ];
    }
    
    /**
     * Analyze digit pair frequency
     * 
     * @param string $field Database field to analyze
     * @param array $filters Filter parameters
     * @return array Pair frequency analysis
     */
    public function analyzeDigitPairFrequency($field, $filters = [])
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Build SQL query with filters
        $sql = "SELECT `$field` FROM lotto_records WHERE `$field` IS NOT NULL";
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $sql .= " AND dateValue >= '" . $this->conn->real_escape_string($filters['start_date']) . "'";
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND dateValue <= '" . $this->conn->real_escape_string($filters['end_date']) . "'";
        }
        
        if (!empty($filters['day_of_week'])) {
            $sql .= " AND day_of_week = '" . $this->conn->real_escape_string($filters['day_of_week']) . "'";
        }
        
        if (!empty($filters['date_day'])) {
            $sql .= " AND DAY(dateValue) = " . intval($filters['date_day']);
        }
        
        if (!empty($filters['date_month'])) {
            $sql .= " AND MONTH(dateValue) = " . intval($filters['date_month']);
        }
        
        // Order by date
        $sql .= " ORDER BY dateValue DESC";
        
        // Apply limit if specified
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        // Initialize pair frequency array
        $pairFrequency = [];
        $totalCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $value = trim($row[$field]);
            $length = strlen($value);
            
            // Process all possible pairs in the value
            for ($i = 0; $i < $length - 1; $i++) {
                $pair = substr($value, $i, 2);
                
                if (!isset($pairFrequency[$pair])) {
                    $pairFrequency[$pair] = 0;
                }
                
                $pairFrequency[$pair]++;
            }
            
            $totalCount++;
        }
        
        // Sort by frequency
        arsort($pairFrequency);
        
        // Calculate percentages
        $pairPercentages = [];
        foreach ($pairFrequency as $pair => $count) {
            $pairPercentages[$pair] = ($totalCount > 0) ? round(($count / $totalCount) * 100, 2) : 0;
        }
        
        // Format results
        $formattedPairs = [];
        foreach ($pairFrequency as $pair => $count) {
            $formattedPairs[] = [
                'pair' => $pair,
                'count' => $count,
                'percentage' => $pairPercentages[$pair]
            ];
        }
        
        // Save analysis history
        $this->saveAnalysisHistory('pair_frequency', [
            'field' => $field,
            'filters' => $filters
        ], [
            'total_count' => $totalCount,
            'pairs_count' => count($formattedPairs)
        ]);
        
        return [
            'status' => 'success',
            'pairs' => $formattedPairs,
            'total_count' => $totalCount,
            'field' => $field,
            'filters' => $filters
        ];
    }
    
    /**
     * Analyze patterns by day of week
     * 
     * @param string $field Database field to analyze
     * @param string $dayOfWeek Day of week in Thai
     * @param int $minEntries Minimum number of entries required
     * @return array Day-based pattern analysis
     */
    public function analyzeDayOfWeekPatterns($field, $dayOfWeek, $minEntries = 20)
    {
        $filters = [
            'day_of_week' => $dayOfWeek,
            'limit' => 200
        ];
        
        $analysis = $this->analyzeDigitFrequency($field, $filters);
        
        if ($analysis['status'] !== 'success') {
            return $analysis;
        }
        
        if ($analysis['total_count'] < $minEntries) {
            return [
                'status' => 'error',
                'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
                'entries_found' => $analysis['total_count']
            ];
        }
        
        // Add day of week info
        $analysis['day_of_week'] = $dayOfWeek;
        
        return $analysis;
    }
    
    /**
     * Analyze patterns by date of month
     * 
     * @param string $field Database field to analyze
     * @param int $dateDay Day of month (1-31)
     * @param int $minEntries Minimum number of entries required
     * @return array Date-based pattern analysis
     */
    public function analyzeDatePatterns($field, $dateDay, $minEntries = 20)
    {
        $filters = [
            'date_day' => $dateDay,
            'limit' => 200
        ];
        
        $analysis = $this->analyzeDigitFrequency($field, $filters);
        
        if ($analysis['status'] !== 'success') {
            return $analysis;
        }
        
        if ($analysis['total_count'] < $minEntries) {
            return [
                'status' => 'error',
                'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
                'entries_found' => $analysis['total_count']
            ];
        }
        
        // Add date day info
        $analysis['date_day'] = $dateDay;
        
        return $analysis;
    }
    
    /**
     * Analyze patterns by month
     * 
     * @param string $field Database field to analyze
     * @param int $month Month (1-12)
     * @param int $minEntries Minimum number of entries required
     * @return array Month-based pattern analysis
     */
    public function analyzeMonthPatterns($field, $month, $minEntries = 10)
    {
        $filters = [
            'date_month' => $month,
            'limit' => 100
        ];
        
        $analysis = $this->analyzeDigitFrequency($field, $filters);
        
        if ($analysis['status'] !== 'success') {
            return $analysis;
        }
        
        if ($analysis['total_count'] < $minEntries) {
            return [
                'status' => 'error',
                'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
                'entries_found' => $analysis['total_count']
            ];
        }
        
        // Add month info
        $analysis['month'] = $month;
        
        return $analysis;
    }
    
    /**
     * Analyze patterns with combined criteria
     * 
     * @param string $field Database field to analyze
     * @param array $criteria Criteria array (day_of_week, date_day, date_month)
     * @param int $minEntries Minimum number of entries required
     * @return array Combined criteria pattern analysis
     */
    public function analyzeCombinedPatterns($field, $criteria, $minEntries = 10)
    {
        $filters = [];
        
        if (!empty($criteria['day_of_week'])) {
            $filters['day_of_week'] = $criteria['day_of_week'];
        }
        
        if (!empty($criteria['date_day'])) {
            $filters['date_day'] = $criteria['date_day'];
        }
        
        if (!empty($criteria['date_month'])) {
            $filters['date_month'] = $criteria['date_month'];
        }
        
        $filters['limit'] = 100;
        
        $analysis = $this->analyzeDigitFrequency($field, $filters);
        
        if ($analysis['status'] !== 'success') {
            return $analysis;
        }
        
        if ($analysis['total_count'] < $minEntries) {
            return [
                'status' => 'error',
                'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
                'entries_found' => $analysis['total_count']
            ];
        }
        
        // Add criteria info
        $analysis['criteria'] = $criteria;
        
        return $analysis;
    }
    
    /**
     * Identify recurring patterns in digit sequences
     * 
     * @param string $field Database field to analyze
     * @param int $lookbackPeriod Number of previous draws to analyze
     * @return array Pattern analysis results
     */
    public function identifyRecurringPatterns($field, $lookbackPeriod = 50)
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Get lottery results for analysis
        $sql = "SELECT dateValue, `$field` FROM lotto_records 
                WHERE `$field` IS NOT NULL 
                ORDER BY dateValue DESC 
                LIMIT " . intval($lookbackPeriod);
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        // Extract the digit sequences
        $sequences = [];
        $dates = [];
        
        while ($row = $result->fetch_assoc()) {
            if (!empty($row[$field])) {
                $sequences[] = trim($row[$field]);
                $dates[] = $row['dateValue'];
            }
        }
        
        // Reverse arrays to have chronological order
        $sequences = array_reverse($sequences);
        $dates = array_reverse($dates);
        
        // Look for repeating patterns
        $patterns = [];
        $maxPatternLength = min(5, floor(count($sequences) / 2));
        
        for ($patternLength = 2; $patternLength <= $maxPatternLength; $patternLength++) {
            for ($startPos = 0; $startPos < count($sequences) - $patternLength - 1; $startPos++) {
                $pattern = array_slice($sequences, $startPos, $patternLength);
                $patternStr = implode('-', $pattern);
                
                // Look for this pattern in the remaining sequence
                for ($checkPos = $startPos + $patternLength; $checkPos < count($sequences) - $patternLength + 1; $checkPos++) {
                    $checkPattern = array_slice($sequences, $checkPos, $patternLength);
                    $checkPatternStr = implode('-', $checkPattern);
                    
                    if ($patternStr === $checkPatternStr) {
                        // Pattern found
                        if (!isset($patterns[$patternStr])) {
                            $patterns[$patternStr] = [
                                'pattern' => $pattern,
                                'occurrences' => 0,
                                'last_seen' => $startPos,
                                'positions' => [],
                                'dates' => []
                            ];
                        }
                        
                        $patterns[$patternStr]['occurrences']++;
                        $patterns[$patternStr]['positions'][] = $checkPos;
                        $patterns[$patternStr]['dates'][] = $dates[$checkPos];
                        
                        if ($checkPos < $patterns[$patternStr]['last_seen']) {
                            $patterns[$patternStr]['last_seen'] = $checkPos;
                        }
                    }
                }
            }
        }
        
        // Sort patterns by number of occurrences
        uasort($patterns, function($a, $b) {
            return $b['occurrences'] - $a['occurrences'];
        });
        
        // Save analysis history
        $this->saveAnalysisHistory('pattern_recognition', [
            'field' => $field,
            'lookback_period' => $lookbackPeriod
        ], [
            'patterns_found' => count($patterns),
            'sequences_analyzed' => count($sequences)
        ]);
        
        return [
            'status' => 'success',
            'patterns' => $patterns,
            'analyzed_period' => count($sequences),
            'field' => $field,
            'dates' => $dates
        ];
    }
    
    /**
     * Get digit trend analysis (increasing/decreasing patterns)
     * 
     * @param string $field Database field to analyze
     * @param int $lookbackPeriod Number of previous draws to analyze
     * @return array Trend analysis results
     */
    public function getDigitTrendAnalysis($field, $lookbackPeriod = 50)
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Get lottery results for analysis
        $sql = "SELECT dateValue, `$field` FROM lotto_records 
                WHERE `$field` IS NOT NULL 
                ORDER BY dateValue DESC 
                LIMIT " . intval($lookbackPeriod);
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        // Extract the digit sequences
        $sequences = [];
        $dates = [];
        
        while ($row = $result->fetch_assoc()) {
            if (!empty($row[$field])) {
                $sequences[] = trim($row[$field]);
                $dates[] = $row['dateValue'];
            }
        }
        
        // Reverse arrays to have chronological order
        $sequences = array_reverse($sequences);
        $dates = array_reverse($dates);
        
        // Get the numeric value of each sequence
        $numericValues = array_map('intval', $sequences);
        
        // Analyze trends
        $trends = [
            'increasing' => [],
            'decreasing' => [],
            'oscillating' => [],
            'stable' => []
        ];
        
        // Calculate differences between consecutive draws
        $differences = [];
        for ($i = 1; $i < count($numericValues); $i++) {
            $diff = $numericValues[$i] - $numericValues[$i - 1];
            $differences[] = [
                'from' => $dates[$i - 1],
                'to' => $dates[$i],
                'diff' => $diff,
                'from_value' => $numericValues[$i - 1],
                'to_value' => $numericValues[$i]
            ];
        }
        
        // Identify consecutive increasing or decreasing patterns
        $currentPattern = '';
        $patternStart = 0;
        $patternLength = 1;
        
        for ($i = 1; $i < count($differences); $i++) {
            $prevDiff = $differences[$i - 1]['diff'];
            $currDiff = $differences[$i]['diff'];
            
            if (($prevDiff > 0 && $currDiff > 0) || ($prevDiff < 0 && $currDiff < 0)) {
                // Continuing the same pattern
                if ($currentPattern === '') {
                    $currentPattern = ($prevDiff > 0) ? 'increasing' : 'decreasing';
                    $patternStart = $i - 1;
                }
                $patternLength++;
            } else {
                // Pattern changed
                if ($patternLength >= 3) {
                    // Record the pattern
                    $trends[$currentPattern][] = [
                        'start' => $patternStart,
                        'length' => $patternLength,
                        'start_date' => $dates[$patternStart],
                        'end_date' => $dates[$patternStart + $patternLength],
                        'values' => array_slice($numericValues, $patternStart, $patternLength + 1)
                    ];
                }
                
                $currentPattern = '';
                $patternLength = 1;
            }
        }
        
        // Record final pattern if any
        if ($patternLength >= 3 && $currentPattern !== '') {
            $trends[$currentPattern][] = [
                'start' => $patternStart,
                'length' => $patternLength,
                'start_date' => $dates[$patternStart],
                'end_date' => $dates[$patternStart + $patternLength],
                'values' => array_slice($numericValues, $patternStart, $patternLength + 1)
            ];
        }
        
        // Identify oscillating patterns (alternating up and down)
        for ($i = 2; $i < count($differences); $i++) {
            $diff1 = $differences[$i - 2]['diff'];
            $diff2 = $differences[$i - 1]['diff'];
            $diff3 = $differences[$i]['diff'];
            
            if (($diff1 * $diff2 < 0) && ($diff2 * $diff3 < 0)) {
                // Alternating signs - oscillating pattern
                $trends['oscillating'][] = [
                    'start' => $i - 2,
                    'values' => [
                        $numericValues[$i - 2],
                        $numericValues[$i - 1],
                        $numericValues[$i],
                        $numericValues[$i + 1]
                    ],
                    'dates' => [
                        $dates[$i - 2],
                        $dates[$i - 1],
                        $dates[$i],
                        $dates[$i + 1]
                    ]
                ];
            }
        }
        
        // Identify stable patterns (values within a small range)
        $rangeThreshold = strlen($sequences[0]) == 3 ? 100 : 10; // Adjust based on field length
        
        for ($i = 0; $i < count($numericValues) - 3; $i++) {
            $values = array_slice($numericValues, $i, 4);
            $minValue = min($values);
            $maxValue = max($values);
            
            if (($maxValue - $minValue) <= $rangeThreshold) {
                $trends['stable'][] = [
                    'start' => $i,
                    'values' => $values,
                    'dates' => array_slice($dates, $i, 4),
                    'range' => $maxValue - $minValue
                ];
            }
        }
        
        // Format for trend visualization
        $trendPoints = [];
        foreach ($numericValues as $index => $value) {
            $trendPoints[] = [
                'date' => $dates[$index],
                'value' => $value
            ];
        }
        
        // Save analysis history
        $this->saveAnalysisHistory('trend_analysis', [
            'field' => $field,
            'lookback_period' => $lookbackPeriod
        ], [
            'increasing_trends' => count($trends['increasing']),
            'decreasing_trends' => count($trends['decreasing']),
            'oscillating_trends' => count($trends['oscillating']),
            'stable_trends' => count($trends['stable'])
        ]);
        
        return [
            'status' => 'success',
            'sequences' => $sequences,
            'dates' => $dates,
            'trends' => $trends,
            'differences' => $differences,
            'trend_points' => $trendPoints,
            'field' => $field
        ];
    }
    
    /**
     * Calculate summary statistics for lottery fields
     * 
     * @param string $field Field to analyze
     * @param int $period Number of previous draws to analyze
     * @return array Summary statistics
     */
    public function calculateSummaryStatistics($field, $period = 100)
    {
        // Validate field to prevent SQL injection
        $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($field, $validFields)) {
            return [
                'status' => 'error',
                'message' => 'ฟิลด์ที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Get lottery results for analysis
        $sql = "SELECT `$field` FROM lotto_records 
                WHERE `$field` IS NOT NULL 
                ORDER BY dateValue DESC 
                LIMIT " . intval($period);
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        // Extract the digit values
        $values = [];
        
        while ($row = $result->fetch_assoc()) {
            if (!empty($row[$field])) {
                $values[] = intval($row[$field]);
            }
        }
        
        if (empty($values)) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลสำหรับการวิเคราะห์'
            ];
        }
        
        // Calculate statistics
        $count = count($values);
        $sum = array_sum($values);
        $mean = $sum / $count;
        
        // Calculate median
        sort($values);
        $middle = floor(($count - 1) / 2);
        $median = ($count % 2) ? $values[$middle] : 
                  (($values[$middle] + $values[$middle + 1]) / 2);
        
        // Calculate mode
        $valueFrequency = array_count_values($values);
        arsort($valueFrequency);
        $mode = key($valueFrequency);
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow(($value - $mean), 2);
        }
        $variance /= $count;
        $stdDev = sqrt($variance);
        
        // Find min and max
        $min = min($values);
        $max = max($values);
        
        // Find range
        $range = $max - $min;
        
        return [
            'status' => 'success',
            'field' => $field,
            'count' => $count,
            'mean' => $mean,
            'median' => $median,
            'mode' => $mode,
            'std_dev' => $stdDev,
            'min' => $min,
            'max' => $max,
            'range' => $range,
            'period' => $period
        ];
    }
    
    /**
     * Save statistical analysis history to database
     * 
     * @param string $analysisType Type of analysis
     * @param array $parameters Analysis parameters
     * @param array $results Analysis results
     * @return bool Success status
     */
    private function saveAnalysisHistory($analysisType, $parameters, $results)
    {
        $sql = "INSERT INTO statistical_history 
                (calculation_date, calculation_type, parameters, result_summary) 
                VALUES (NOW(), ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $paramJson = json_encode($parameters);
        $resultJson = json_encode($results);
        
        $stmt->bind_param("sss", $analysisType, $paramJson, $resultJson);
        
        return $stmt->execute();
    }
    
    /**
     * Get statistical analysis history from database
     * 
     * @param string $analysisType Type of analysis (optional)
     * @param int $limit Maximum number of records to return
     * @return array Analysis history records
     */
    public function getAnalysisHistory($analysisType = null,$limit = 10)
    {
        $sql = "SELECT * FROM statistical_history ";
        
        if ($analysisType !== null) {
            $sql .= "WHERE calculation_type = '" . $this->conn->real_escape_string($analysisType) . "' ";
        }
        
        $sql .= "ORDER BY calculation_date DESC LIMIT " . intval($limit);
        
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }
        
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['parameters'] = json_decode($row['parameters'], true);
            $row['result_summary'] = json_decode($row['result_summary'], true);
            $history[] = $row;
        }
        
        return [
            'status' => 'success',
            'history' => $history,
            'count' => count($history)
        ];
    }
}

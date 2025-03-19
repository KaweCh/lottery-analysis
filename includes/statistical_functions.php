<?php

/**
 * statistical_functions.php
 * 
 * Statistical analysis functions for Thai lottery data
 */

require_once __DIR__ . '/functions.php';

/**
 * Get digit distribution for a specific field
 * 
 * @param string $field Database field to analyze
 * @param int $digits Number of digits (2 or 3)
 * @param array $filters Additional filters
 * @return array Digit distribution data
 */
function getDigitDistribution($field, $digits = 3, $filters = [])
{
    global $conn;

    // Validate field to prevent SQL injection
    $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($field, $validFields)) {
        return [
            'status' => 'error',
            'message' => 'Invalid field specified'
        ];
    }

    // Build SQL query with filters
    $sql = "SELECT `$field`, COUNT(*) as count FROM lotto_records WHERE `$field` IS NOT NULL";

    // Apply filters
    if (!empty($filters['start_date'])) {
        $sql .= " AND dateValue >= '" . $conn->real_escape_string($filters['start_date']) . "'";
    }

    if (!empty($filters['end_date'])) {
        $sql .= " AND dateValue <= '" . $conn->real_escape_string($filters['end_date']) . "'";
    }

    if (!empty($filters['day_of_week'])) {
        $sql .= " AND day_of_week = '" . $conn->real_escape_string($filters['day_of_week']) . "'";
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

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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
        $item['percentage'] = calculatePercentage($item['count'], $totalCount, 2);
    }

    return [
        'status' => 'success',
        'distribution' => $distribution,
        'total_count' => $totalCount,
        'field' => $field,
        'digits' => $digits,
        'filters' => $filters
    ];
}

/**
 * Analyze digit frequency by position
 * 
 * @param string $field Database field to analyze
 * @param array $filters Additional filters
 * @return array Position-based frequency analysis
 */
function getDigitPositionFrequency($field, $filters = [])
{
    global $conn;

    // Validate field to prevent SQL injection
    $validFields = ['first_prize', 'first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($field, $validFields)) {
        return [
            'status' => 'error',
            'message' => 'Invalid field specified'
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
        $sql .= " AND dateValue >= '" . $conn->real_escape_string($filters['start_date']) . "'";
    }

    if (!empty($filters['end_date'])) {
        $sql .= " AND dateValue <= '" . $conn->real_escape_string($filters['end_date']) . "'";
    }

    if (!empty($filters['day_of_week'])) {
        $sql .= " AND day_of_week = '" . $conn->real_escape_string($filters['day_of_week']) . "'";
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

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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
            $positionPercentages[$i][$j] = calculatePercentage($positionFrequency[$i][$j], $totalCount, 2);
        }
    }

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
 * Analyze digit patterns based on day of week
 * 
 * @param string $field Database field to analyze
 * @param string $dayOfWeek Day of week in Thai
 * @param int $minEntries Minimum entries required for analysis
 * @return array Day-based pattern analysis
 */
function getDayOfWeekPatterns($field, $dayOfWeek, $minEntries = 20)
{
    $filters = [
        'day_of_week' => $dayOfWeek,
        'limit' => 200 // Get a good sample size
    ];

    $distribution = getDigitDistribution($field, null, $filters);

    if ($distribution['status'] === 'error') {
        return $distribution;
    }

    if ($distribution['total_count'] < $minEntries) {
        return [
            'status' => 'error',
            'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
            'entries_found' => $distribution['total_count']
        ];
    }

    return $distribution;
}

/**
 * Analyze digit patterns based on date of month
 * 
 * @param string $field Database field to analyze
 * @param int $dateDay Day of month (1-31)
 * @param int $minEntries Minimum entries required for analysis
 * @return array Date-based pattern analysis
 */
function getDatePatterns($field, $dateDay, $minEntries = 20)
{
    $filters = [
        'date_day' => $dateDay,
        'limit' => 200 // Get a good sample size
    ];

    $distribution = getDigitDistribution($field, null, $filters);

    if ($distribution['status'] === 'error') {
        return $distribution;
    }

    if ($distribution['total_count'] < $minEntries) {
        return [
            'status' => 'error',
            'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
            'entries_found' => $distribution['total_count']
        ];
    }

    return $distribution;
}

/**
 * Analyze digit patterns based on month
 * 
 * @param string $field Database field to analyze
 * @param int $month Month (1-12)
 * @param int $minEntries Minimum entries required for analysis
 * @return array Month-based pattern analysis
 */
function getMonthPatterns($field, $month, $minEntries = 10)
{
    $filters = [
        'date_month' => $month,
        'limit' => 100 // Get a good sample size
    ];

    $distribution = getDigitDistribution($field, null, $filters);

    if ($distribution['status'] === 'error') {
        return $distribution;
    }

    if ($distribution['total_count'] < $minEntries) {
        return [
            'status' => 'error',
            'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
            'entries_found' => $distribution['total_count']
        ];
    }

    return $distribution;
}

/**
 * Analyze patterns with combined criteria
 * 
 * @param string $field Database field to analyze
 * @param array $criteria Criteria array (day_of_week, date_day, date_month)
 * @param int $minEntries Minimum entries required for analysis
 * @return array Combined criteria pattern analysis
 */
function getCombinedPatterns($field, $criteria, $minEntries = 10)
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

    $distribution = getDigitDistribution($field, null, $filters);

    if ($distribution['status'] === 'error') {
        return $distribution;
    }

    if ($distribution['total_count'] < $minEntries) {
        return [
            'status' => 'error',
            'message' => "ไม่มีข้อมูลเพียงพอสำหรับการวิเคราะห์ (ต้องการอย่างน้อย $minEntries รายการ)",
            'entries_found' => $distribution['total_count']
        ];
    }

    return $distribution;
}

/**
 * Identify recurring patterns in previous draws
 * 
 * @param string $field Database field to analyze
 * @param int $lookbackPeriod Number of previous draws to analyze
 * @return array Pattern analysis results
 */
function identifyRecurringPatterns($field, $lookbackPeriod = 50)
{
    global $conn;

    // Validate field to prevent SQL injection
    $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($field, $validFields)) {
        return [
            'status' => 'error',
            'message' => 'Invalid field specified'
        ];
    }

    // Get lottery results for analysis
    $sql = "SELECT dateValue, `$field` FROM lotto_records 
            WHERE `$field` IS NOT NULL 
            ORDER BY dateValue DESC 
            LIMIT " . intval($lookbackPeriod);

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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
    uasort($patterns, function ($a, $b) {
        return $b['occurrences'] - $a['occurrences'];
    });

    return [
        'status' => 'success',
        'patterns' => $patterns,
        'analyzed_period' => count($sequences),
        'field' => $field,
        'dates' => $dates
    ];
}

/**
 * Calculate pair frequency in specified field
 * 
 * @param string $field Database field to analyze
 * @param array $filters Additional filters
 * @return array Pair frequency analysis
 */
function getDigitPairFrequency($field, $filters = [])
{
    global $conn;

    // Validate field to prevent SQL injection
    $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($field, $validFields)) {
        return [
            'status' => 'error',
            'message' => 'Invalid field specified'
        ];
    }

    // Build SQL query with filters
    $sql = "SELECT `$field` FROM lotto_records WHERE `$field` IS NOT NULL";

    // Apply filters
    if (!empty($filters['start_date'])) {
        $sql .= " AND dateValue >= '" . $conn->real_escape_string($filters['start_date']) . "'";
    }

    if (!empty($filters['end_date'])) {
        $sql .= " AND dateValue <= '" . $conn->real_escape_string($filters['end_date']) . "'";
    }

    if (!empty($filters['day_of_week'])) {
        $sql .= " AND day_of_week = '" . $conn->real_escape_string($filters['day_of_week']) . "'";
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

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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
        $pairPercentages[$pair] = calculatePercentage($count, $totalCount, 2);
    }

    return [
        'status' => 'success',
        'frequency' => $pairFrequency,
        'percentages' => $pairPercentages,
        'total_count' => $totalCount,
        'field' => $field,
        'filters' => $filters
    ];
}

/**
 * Get digit trend analysis (increasing/decreasing patterns)
 * 
 * @param string $field Database field to analyze
 * @param int $lookbackPeriod Number of previous draws to analyze
 * @return array Trend analysis results
 */
function getDigitTrendAnalysis($field, $lookbackPeriod = 50)
{
    global $conn;

    // Validate field to prevent SQL injection
    $validFields = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($field, $validFields)) {
        return [
            'status' => 'error',
            'message' => 'Invalid field specified'
        ];
    }

    // Get lottery results for analysis
    $sql = "SELECT dateValue, `$field` FROM lotto_records 
            WHERE `$field` IS NOT NULL 
            ORDER BY dateValue DESC 
            LIMIT " . intval($lookbackPeriod);

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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

    // Analyze trends
    $trends = [
        'increasing' => [],
        'decreasing' => [],
        'oscillating' => [],
        'stable' => []
    ];

    // Get the numeric value of each sequence
    $numericValues = array_map('intval', $sequences);

    // Analyze the overall trend
    $trendPoints = [];
    foreach ($numericValues as $index => $value) {
        $trendPoints[] = [
            'date' => $dates[$index],
            'value' => $value
        ];
    }

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
                    'end_date' => $dates[$patternStart + $patternLength]
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
            'end_date' => $dates[$patternStart + $patternLength - 1]
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
 * Save statistical history to database
 * 
 * @param string $calculationType Type of calculation
 * @param array $parameters Calculation parameters
 * @param array $results Calculation results
 * @return bool Success status
 */
function saveStatisticalHistory($calculationType, $parameters, $results)
{
    global $conn;

    $sql = "INSERT INTO statistical_history 
            (calculation_date, calculation_type, parameters, result_summary) 
            VALUES (NOW(), ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $paramJson = json_encode($parameters);
    $resultJson = json_encode($results);

    $stmt->bind_param("sss", $calculationType, $paramJson, $resultJson);

    return $stmt->execute();
}

/**
 * Get statistical history from database
 * 
 * @param string $calculationType Type of calculation (optional)
 * @param int $limit Maximum number of records to return
 * @return array Statistical history records
 */
function getStatisticalHistory($calculationType = null, $limit = 10)
{
    global $conn;

    $sql = "SELECT * FROM statistical_history ";

    if ($calculationType !== null) {
        $sql .= "WHERE calculation_type = '" . $conn->real_escape_string($calculationType) . "' ";
    }

    $sql .= "ORDER BY calculation_date DESC LIMIT " . intval($limit);

    $result = $conn->query($sql);

    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
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
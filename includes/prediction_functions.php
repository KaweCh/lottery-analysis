<?php
/**
 * prediction_functions.php
 * 
 * Functions for generating lottery number predictions for the Thai lottery analysis system
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/statistical_functions.php';

/**
 * Generate predictions for digit type
 * 
 * @param string $digitType Type of digits to predict
 * @param string $targetDate Target date for prediction (YYYY-MM-DD)
 * @return array Predictions with confidence scores
 */
function generatePredictions($digitType, $targetDate) {
    // Validate digit type
    $validDigitTypes = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($digitType, $validDigitTypes)) {
        return [
            'status' => 'error',
            'message' => 'ประเภทเลขที่ระบุไม่ถูกต้อง'
        ];
    }
    
    // Validate target date
    if (!isValidDateFormat($targetDate)) {
        return [
            'status' => 'error',
            'message' => 'รูปแบบวันที่ไม่ถูกต้อง'
        ];
    }
    
    // Parse target date
    $date = new DateTime($targetDate);
    $dayOfWeek = getThaiDayOfWeek($targetDate);
    $day = $date->format('d');
    $month = $date->format('m');
    
    // Get analysis for different patterns
    $dayAnalysis = getDayOfWeekPatterns($digitType, $dayOfWeek, 10);
    $dateAnalysis = getDatePatterns($digitType, $day, 10);
    $monthAnalysis = getMonthPatterns($digitType, $month, 5);
    
    $combinedCriteria = [
        'day_of_week' => $dayOfWeek,
        'date_day' => $day,
        'date_month' => $month
    ];
    
    $combinedAnalysis = getCombinedPatterns($digitType, $combinedCriteria, 3);
    
    // Get pattern analysis
    $patternAnalysis = identifyRecurringPatterns($digitType, 50);
    
    // Get pair frequency analysis
    $pairAnalysis = getDigitPairFrequency($digitType, ['limit' => 100]);
    
    // Get digit position frequency
    $positionAnalysis = null;
    if ($digitType !== 'last2') {
        $digits = ($digitType === 'first_prize_last3' || $digitType === 'last3f' || $digitType === 'last3b') ? 3 : 2;
        $positionAnalysis = getDigitPositionFrequency($digitType, ['limit' => 100]);
    }
    
    // Get trend analysis
    $trendAnalysis = getDigitTrendAnalysis($digitType, 20);
    
    // Combine results with weighting
    $predictions = [];
    $weights = [
        'day_of_week' => 0.15,
        'date' => 0.10,
        'month' => 0.10,
        'combined' => 0.25,
        'patterns' => 0.15,
        'pairs' => 0.15,
        'position' => 0.05,
        'trend' => 0.05
    ];
    
    // Process day of week analysis
    if ($dayAnalysis['status'] === 'success') {
        foreach (array_slice($dayAnalysis['distribution'], 0, 10, true) as $item) {
            $digit = $item['digit'];
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += ($item['percentage'] / 100) * $weights['day_of_week'];
        }
    }
    
    // Process date analysis
    if ($dateAnalysis['status'] === 'success') {
        foreach (array_slice($dateAnalysis['distribution'], 0, 10, true) as $item) {
            $digit = $item['digit'];
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += ($item['percentage'] / 100) * $weights['date'];
        }
    }
    
    // Process month analysis
    if ($monthAnalysis['status'] === 'success') {
        foreach (array_slice($monthAnalysis['distribution'], 0, 10, true) as $item) {
            $digit = $item['digit'];
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += ($item['percentage'] / 100) * $weights['month'];
        }
    }
    
    // Process combined analysis
    if ($combinedAnalysis['status'] === 'success') {
        foreach (array_slice($combinedAnalysis['distribution'], 0, 10, true) as $item) {
            $digit = $item['digit'];
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += ($item['percentage'] / 100) * $weights['combined'];
        }
    }
    
    // Process pattern analysis
    if ($patternAnalysis['status'] === 'success') {
        $patternCount = 0;
        foreach ($patternAnalysis['patterns'] as $patternStr => $patternData) {
            if ($patternCount >= 5) break;
            
            $lastDigit = end($patternData['pattern']);
            if (!isset($predictions[$lastDigit])) {
                $predictions[$lastDigit] = 0;
            }
            
            $patternWeight = min(1, $patternData['occurrences'] / 10);
            $predictions[$lastDigit] += $patternWeight * $weights['patterns'];
            $patternCount++;
        }
    }
    
    // Process pair frequency analysis
    if ($pairAnalysis['status'] === 'success') {
        $digitLength = ($digitType === 'last2') ? 2 : 3;
        $requiredPairs = [];
        
        // Generate all possible digit combinations
        if ($digitLength === 2) {
            for ($i = 0; $i <= 9; $i++) {
                for ($j = 0; $j <= 9; $j++) {
                    $requiredPairs[$i . $j] = 0;
                }
            }
        } else {
            for ($i = 0; $i <= 9; $i++) {
                for ($j = 0; $j <= 9; $j++) {
                    for ($k = 0; $k <= 9; $k++) {
                        $requiredPairs[$i . $j . $k] = 0;
                    }
                }
            }
        }
        
        // Fill in found pair frequencies
        foreach ($pairAnalysis['frequency'] as $pair => $count) {
            if (strlen($pair) === 2) {
                foreach ($requiredPairs as $fullDigit => $value) {
                    if (strpos($fullDigit, $pair) !== false) {
                        $requiredPairs[$fullDigit] += ($pairAnalysis['percentages'][$pair] / 100) * $weights['pairs'];
                    }
                }
            }
        }
        
        // Merge pair predictions with main predictions
        foreach ($requiredPairs as $digit => $weight) {
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += $weight;
        }
    }
    
    // Process position frequency analysis
    if ($positionAnalysis !== null && $positionAnalysis['status'] === 'success') {
        $digitLength = $positionAnalysis['positions'];
        $allDigits = [];
        
        // Generate all possible digit combinations for the specified length
        for ($i = 0; $i < pow(10, $digitLength); $i++) {
            $allDigits[] = str_pad($i, $digitLength, '0', STR_PAD_LEFT);
        }
        
        foreach ($allDigits as $digit) {
            $positionScore = 0;
            
            for ($i = 0; $i < $digitLength; $i++) {
                $positionValue = intval($digit[$i]);
                $positionScore += ($positionAnalysis['percentages'][$i][$positionValue] / 100);
            }
            
            $positionScore /= $digitLength;
            
            if (!isset($predictions[$digit])) {
                $predictions[$digit] = 0;
            }
            $predictions[$digit] += $positionScore * $weights['position'];
        }
    }
    
    // Process trend analysis
    if ($trendAnalysis['status'] === 'success') {
        // Identify digits that are part of increasing/decreasing trends
        foreach ($trendAnalysis['trends']['increasing'] as $trend) {
            if (isset($trend['values']) && is_array($trend['values'])) {
                $lastValue = end($trend['values']);
                
                // Convert to appropriate string format
                $trendDigit = str_pad($lastValue, ($digitType === 'last2' ? 2 : 3), '0', STR_PAD_LEFT);
                
                if (!isset($predictions[$trendDigit])) {
                    $predictions[$trendDigit] = 0;
                }
                
                $predictions[$trendDigit] += (min(1, $trend['length'] / 5)) * $weights['trend'];
            }
        }
        
        foreach ($trendAnalysis['trends']['decreasing'] as $trend) {
            if (isset($trend['values']) && is_array($trend['values'])) {
                $lastValue = end($trend['values']);
                
                // Convert to appropriate string format
                $trendDigit = str_pad($lastValue, ($digitType === 'last2' ? 2 : 3), '0', STR_PAD_LEFT);
                
                if (!isset($predictions[$trendDigit])) {
                    $predictions[$trendDigit] = 0;
                }
                
                $predictions[$trendDigit] += (min(1, $trend['length'] / 5)) * $weights['trend'];
            }
        }
    }
    
    // Sort predictions by confidence
    arsort($predictions);
    
    // Format final predictions
    $formattedPredictions = [];
    $count = 0;
    foreach (array_slice($predictions, 0, 20, true) as $digit => $confidence) {
        $formattedPredictions[] = [
            'digit' => $digit,
            'confidence' => round($confidence * 100, 2),
            'rank' => ++$count
        ];
    }
    
    // Store prediction in database
    storePredictions($formattedPredictions, $digitType, $targetDate);
    
    // Calculate analysis summary
    $analysisSummary = [
        'day_analysis_used' => $dayAnalysis['status'] === 'success',
        'date_analysis_used' => $dateAnalysis['status'] === 'success',
        'month_analysis_used' => $monthAnalysis['status'] === 'success',
        'combined_analysis_used' => $combinedAnalysis['status'] === 'success',
        'pattern_analysis_used' => $patternAnalysis['status'] === 'success',
        'pair_analysis_used' => $pairAnalysis['status'] === 'success',
        'position_analysis_used' => $positionAnalysis !== null && $positionAnalysis['status'] === 'success',
        'trend_analysis_used' => $trendAnalysis['status'] === 'success',
        'pattern_count' => $patternAnalysis['status'] === 'success' ? count($patternAnalysis['patterns']) : 0,
        'day_pattern_count' => $dayAnalysis['status'] === 'success' ? $dayAnalysis['total_count'] : 0,
        'date_pattern_count' => $dateAnalysis['status'] === 'success' ? $dateAnalysis['total_count'] : 0,
        'month_pattern_count' => $monthAnalysis['status'] === 'success' ? $monthAnalysis['total_count'] : 0,
        'combined_pattern_count' => $combinedAnalysis['status'] === 'success' ? $combinedAnalysis['total_count'] : 0
    ];
    
    // Save statistical history
    saveStatisticalHistory('prediction', [
        'digit_type' => $digitType,
        'target_date' => $targetDate,
        'day_of_week' => $dayOfWeek,
        'date_day' => $day,
        'date_month' => $month
    ], $analysisSummary);
    
    return [
        'status' => 'success',
        'predictions' => $formattedPredictions,
        'target_date' => $targetDate,
        'digit_type' => $digitType,
        'day_of_week' => $dayOfWeek,
        'date_day' => $day,
        'date_month' => $month,
        'analysis_summary' => $analysisSummary
    ];
}

/**
 * Store predictions in the database
 * 
 * @param array $predictions Predictions to store
 * @param string $digitType Type of digits predicted
 * @param string $targetDate Target date for prediction
 * @return bool Success status
 */
function storePredictions($predictions, $digitType, $targetDate) {
    global $conn;
    
    // First, delete any existing predictions for this target date and digit type
    $sql = "DELETE FROM lottery_predictions 
            WHERE target_draw_date = ? AND digit_type = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $targetDate, $digitType);
    $stmt->execute();
    
    // Insert new predictions
    $sql = "INSERT INTO lottery_predictions 
            (prediction_date, target_draw_date, prediction_type, digit_type, 
             predicted_digits, confidence) 
            VALUES (NOW(), ?, 'statistical', ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($predictions as $prediction) {
        $digit = $prediction['digit'];
        $confidence = $prediction['confidence'];
        $stmt->bind_param("sssd", $targetDate, $digitType, $digit, $confidence);
        $stmt->execute();
    }
    
    return true;
}

/**
 * Get stored predictions for a specific target date
 * 
 * @param string $targetDate Target date for predictions
 * @param string $digitType Type of digits (optional)
 * @return array Stored predictions
 */
function getStoredPredictions($targetDate, $digitType = null) {
    global $conn;
    
    $sql = "SELECT * FROM lottery_predictions WHERE target_draw_date = ?";
    
    if ($digitType !== null) {
        $sql .= " AND digit_type = ?";
    }
    
    $sql .= " ORDER BY confidence DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($digitType !== null) {
        $stmt->bind_param("ss", $targetDate, $digitType);
    } else {
        $stmt->bind_param("s", $targetDate);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return [
            'status' => 'error',
            'message' => 'Database query failed: ' . $conn->error,
            'sql' => $sql
        ];
    }
    
    $predictions = [];
    
    while ($row = $result->fetch_assoc()) {
        $predictions[] = $row;
    }
    
    return [
        'status' => 'success',
        'predictions' => $predictions,
        'target_date' => $targetDate,
        'digit_type' => $digitType,
        'count' => count($predictions)
    ];
}

/**
 * Evaluate prediction accuracy against actual results
 * 
 * @param string $drawDate Date of the lottery draw
 * @return array Accuracy evaluation
 */
function evaluatePredictionAccuracy($drawDate) {
    global $conn;
    
    // Get actual results for this draw
    $sql = "SELECT * FROM lotto_records WHERE dateValue = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $drawDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'status' => 'error',
            'message' => 'ไม่พบผลรางวัลสำหรับวันที่ระบุ'
        ];
    }
    
    $actualResults = $result->fetch_assoc();
    
    // Get predictions for this draw
    $sql = "SELECT * FROM lottery_predictions WHERE target_draw_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $drawDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'status' => 'error',
            'message' => 'ไม่พบการทำนายสำหรับวันที่ระบุ'
        ];
    }
    
    $predictions = [];
    while ($row = $result->fetch_assoc()) {
        $predictions[] = $row;
    }
    
    // Evaluate each prediction
    $accuracy = [
        'first_prize_last3' => ['correct' => 0, 'total' => 0],
        'last3f' => ['correct' => 0, 'total' => 0],
        'last3b' => ['correct' => 0, 'total' => 0],
        'last2' => ['correct' => 0, 'total' => 0]
    ];
    
    $correctPredictions = [];
    $incorrectPredictions = [];
    
    foreach ($predictions as $prediction) {
        $digitType = $prediction['digit_type'];
        $predictedDigit = $prediction['predicted_digits'];
        $actualDigit = $actualResults[$digitType];
        
        // Mark prediction as correct or incorrect
        $isCorrect = ($predictedDigit === $actualDigit);
        
        // Update prediction record
        $sql = "UPDATE lottery_predictions 
                SET was_correct = ? 
                WHERE prediction_id = ?";
        $stmt = $conn->prepare($sql);
        $correct = $isCorrect ? 1 : 0;
        $stmt->bind_param("ii", $correct, $prediction['prediction_id']);
        $stmt->execute();
        
        // Update accuracy counters
        if (isset($accuracy[$digitType])) {
            $accuracy[$digitType]['total']++;
            if ($isCorrect) {
                $accuracy[$digitType]['correct']++;
                $correctPredictions[] = $prediction;
            } else {
                $incorrectPredictions[] = $prediction;
            }
        }
    }
    
    // Calculate overall accuracy
    $totalCorrect = 0;
    $totalPredictions = 0;
    
    foreach ($accuracy as $type => $counts) {
        $totalCorrect += $counts['correct'];
        $totalPredictions += $counts['total'];
    }
    
    $overallAccuracy = ($totalPredictions > 0) ? ($totalCorrect / $totalPredictions) * 100 : 0;
    
    // Store accuracy record
    $sql = "INSERT INTO prediction_accuracy 
            (prediction_type, period_start, period_end, 
             total_predictions, correct_predictions, accuracy_percentage) 
            VALUES ('statistical', ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiid", $drawDate, $drawDate, $totalPredictions, $totalCorrect, $overallAccuracy);
    $stmt->execute();
    
    return [
        'status' => 'success',
        'draw_date' => $drawDate,
        'accuracy' => $accuracy,
        'overall_accuracy' => $overallAccuracy,
        'correct_predictions' => $correctPredictions,
        'incorrect_predictions' => $incorrectPredictions,
        'actual_results' => $actualResults
    ];
}

/**
 * Get accuracy history
 * 
 * @param string $period Period to return (weekly, monthly, yearly, all)
 * @return array Accuracy history
 */
function getAccuracyHistory($period = 'all') {
    global $conn;
    
    $sql = "SELECT * FROM prediction_accuracy";
    
    if ($period !== 'all') {
        $startDate = null;
        $today = new DateTime();
        
        switch ($period) {
            case 'weekly':
                $startDate = clone $today;
                $startDate->modify('-7 days');
                break;
                
            case 'monthly':
                $startDate = clone $today;
                $startDate->modify('-30 days');
                break;
                
            case 'yearly':
                $startDate = clone $today;
                $startDate->modify('-365 days');
                break;
        }
        
        if ($startDate !== null) {
            $sql .= " WHERE period_end >= '" . $startDate->format('Y-m-d') . "'";
        }
    }
    
    $sql .= " ORDER BY period_end ASC";
    
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
        $history[] = $row;
    }
    
    // Calculate overall metrics
    $totalPredictions = 0;
    $totalCorrect = 0;
    
    foreach ($history as $record) {
        $totalPredictions += $record['total_predictions'];
        $totalCorrect += $record['correct_predictions'];
    }
    
    $overallAccuracy = ($totalPredictions > 0) ? ($totalCorrect / $totalPredictions) * 100 : 0;
    
    return [
        'status' => 'success',
        'history' => $history,
        'overall_accuracy' => $overallAccuracy,
        'total_predictions' => $totalPredictions,
        'total_correct' => $totalCorrect,
        'count' => count($history)
    ];
}

/**
 * Get summary statistics for dashboard
 * 
 * @return array Summary statistics
 */
function getSummaryStatistics() {
    global $conn;
    
    // Get total predictions
    $sql = "SELECT COUNT(*) as total FROM lottery_predictions";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $totalPredictions = $row['total'];
    
    // Get accuracy metrics
    $accuracyHistory = getAccuracyHistory();
    $averageAccuracy = $accuracyHistory['overall_accuracy'];
    
    // Get next draw date
    $nextDrawDate = getNextDrawDate();
    
    // Get total data records
    $sql = "SELECT COUNT(*) as total FROM lotto_records";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $totalDataRecords = $row['total'];
    
    return [
        'total_predictions' => $totalPredictions,
        'average_accuracy' => $averageAccuracy,
        'next_draw_date' => $nextDrawDate,
        'total_data_records' => $totalDataRecords
    ];
}

/**
 * Process new lottery results and update predictions
 * 
 * @param array $newResults New lottery results
 * @return array Processing results
 */
function processNewResults($newResults) {
    global $conn;
    
    // Validate required fields
    $requiredFields = ['date', 'day_of_week', 'first_prize_last3', 'first_prize'];
    
    foreach ($requiredFields as $field) {
        if (empty($newResults[$field])) {
            return [
                'status' => 'error',
                'message' => "ข้อมูลไม่ครบถ้วน: ไม่พบฟิลด์ $field"
            ];
        }
    }
    
    // Check if record already exists
    $dateValue = $newResults['date'];
    $sql = "SELECT COUNT(*) as count FROM lotto_records WHERE dateValue = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dateValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $exists = ($row['count'] > 0);
    
    if ($exists) {
        // Update existing record
        $sql = "UPDATE lotto_records SET 
                day_of_week = ?,
                first_prize_last3 = ?,
                first_prize = ?,
                last3f = ?,
                last3b = ?,
                last2 = ?,
                near1_1 = ?,
                near1_2 = ?
                WHERE dateValue = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss",
            $newResults['day_of_week'],
            $newResults['first_prize_last3'],
            $newResults['first_prize'],
            $newResults['last3f'],
            $newResults['last3b'],
            $newResults['last2'],
            $newResults['near1_1'],
            $newResults['near1_2'],
            $dateValue
        );
    } else {
        // Insert new record
        $sql = "INSERT INTO lotto_records 
                (dateValue, date, day_of_week, first_prize_last3, first_prize, 
                 last3f, last3b, last2, near1_1, near1_2) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        // Convert date to Buddhist year
        $dateObj = new DateTime($dateValue);
        $westernYear = $dateObj->format('Y');
        $buddhistYear = $westernYear + 543;
        $thaiDate = $buddhistYear . $dateObj->format('-m-d');
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssss",
            $dateValue,
            $thaiDate,
            $newResults['day_of_week'],
            $newResults['first_prize_last3'],
            $newResults['first_prize'],
            $newResults['last3f'],
            $newResults['last3b'],
            $newResults['last2'],
            $newResults['near1_1'],
            $newResults['near1_2']
        );
    }
    
    $success = $stmt->execute();
    
    if (!$success) {
        return [
            'status' => 'error',
            'message' => 'Database operation failed: ' . $conn->error
        ];
    }
    
    // Evaluate prediction accuracy for this date
    $accuracyResults = evaluatePredictionAccuracy($dateValue);
    
    // Generate predictions for next draw
    $nextDrawDate = getNextDrawDate();
    $digitTypes = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    
    $newPredictions = [];
    foreach ($digitTypes as $digitType) {
        $newPredictions[$digitType] = generatePredictions($digitType, $nextDrawDate);
    }
    
    return [
        'status' => 'success',
        'message' => $exists ? 'อัปเดตผลรางวัลสำเร็จ' : 'เพิ่มผลรางวัลใหม่สำเร็จ',
        'date' => $dateValue,
        'accuracy_results' => $accuracyResults,
        'new_predictions' => $newPredictions
    ];
}

/**
 * Generate learning-based predictions using historical accuracy
 * 
 * @param string $digitType Type of digits to predict
 * @param string $targetDate Target date for prediction
 * @return array Learning-based predictions
 */
function generateLearningPredictions($digitType, $targetDate) {
    global $conn;
    
    // Get statistical predictions first
    $statisticalPredictions = generatePredictions($digitType, $targetDate);
    
    if ($statisticalPredictions['status'] !== 'success') {
        return $statisticalPredictions;
    }
    
    // Get historical accuracy for different methods
    $accuracyData = getAccuracyHistory('yearly');
    
    if ($accuracyData['status'] !== 'success' || $accuracyData['count'] < 3) {
        // Not enough historical data, fallback to statistical predictions
        return $statisticalPredictions;
    }
    
    // Get past correct predictions to learn from patterns
    $sql = "SELECT p.* FROM lottery_predictions p 
            WHERE p.was_correct = 1 
            AND p.digit_type = ? 
            ORDER BY p.prediction_date DESC 
            LIMIT 50";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $digitType);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No correct historical predictions, fallback to statistical
        return $statisticalPredictions;
    }
    
    $correctPredictions = [];
    while ($row = $result->fetch_assoc()) {
        $correctPredictions[] = $row;
    }
    
    // Analyze which statistical methods were most successful
    $methodSuccessRate = [];
    
    // Get statistical history for correct predictions
    $historyIds = [];
    foreach ($correctPredictions as $prediction) {
        $date = $prediction['prediction_date'];
        $historyIds[] = "calculation_date LIKE '" . substr($date, 0, 10) . "%'";
    }
    
    if (empty($historyIds)) {
        return $statisticalPredictions;
    }
    
    $sql = "SELECT * FROM statistical_history 
            WHERE calculation_type = 'prediction' 
            AND (" . implode(" OR ", $historyIds) . ")";
            
    $result = $conn->query($sql);
    
    if (!$result) {
        return $statisticalPredictions;
    }
    
    $successfulMethods = [];
    
    while ($row = $result->fetch_assoc()) {
        $paramData = json_decode($row['parameters'], true);
        $resultData = json_decode($row['result_summary'], true);
        
        if ($paramData['digit_type'] === $digitType) {
            foreach ($resultData as $method => $used) {
                if ($used && strpos($method, '_used') !== false) {
                    $methodName = str_replace('_used', '', $method);
                    
                    if (!isset($successfulMethods[$methodName])) {
                        $successfulMethods[$methodName] = 0;
                    }
                    
                    $successfulMethods[$methodName]++;
                }
            }
        }
    }
    
    // Calculate success rate for each method
    $totalSuccessfulPredictions = count($correctPredictions);
    foreach ($successfulMethods as $method => $count) {
        $methodSuccessRate[$method] = $count / $totalSuccessfulPredictions;
    }
    
    // Adjust weights based on historical performance
    $weights = [
        'day_of_week' => 0.15,
        'date' => 0.10,
        'month' => 0.10,
        'combined' => 0.25,
        'patterns' => 0.15,
        'pairs' => 0.15,
        'position' => 0.05,
        'trend' => 0.05
    ];
    
    // Apply learning adjustments to weights
    $totalRate = array_sum($methodSuccessRate);
    if ($totalRate > 0) {
        foreach ($methodSuccessRate as $method => $rate) {
            if (isset($weights[$method])) {
                // Increase weights for more successful methods
                $weights[$method] *= (1 + ($rate / $totalRate));
            }
        }
        
        // Normalize weights to ensure they sum to 1
        $totalWeight = array_sum($weights);
        foreach ($weights as &$weight) {
            $weight = $weight / $totalWeight;
        }
    }
    
    // Generate new predictions with adjusted weights
    // For simplicity, we'll reuse the statistical predictions but adjust confidence scores
    $adjustedPredictions = $statisticalPredictions['predictions'];
    
    foreach ($adjustedPredictions as &$prediction) {
        // Apply a learning factor based on past correct predictions
        foreach ($correctPredictions as $correctPrediction) {
            if ($correctPrediction['predicted_digits'] === $prediction['digit']) {
                // Boost confidence for digits that were correctly predicted before
                $prediction['confidence'] *= 1.2;
                break;
            }
        }
    }
    
    // Resort by adjusted confidence
    usort($adjustedPredictions, function($a, $b) {
        return $b['confidence'] - $a['confidence'];
    });
    
    // Renumber rankings
    foreach ($adjustedPredictions as $key => &$prediction) {
        $prediction['rank'] = $key + 1;
    }
    
    // Store the learning-based predictions
    storePredictions(array_slice($adjustedPredictions, 0, 20), $digitType, $targetDate);
    
    return [
        'status' => 'success',
        'predictions' => $adjustedPredictions,
        'target_date' => $targetDate,
        'digit_type' => $digitType,
        'analysis_summary' => $statisticalPredictions['analysis_summary'],
        'learning_method' => 'historical_accuracy',
        'adjusted_weights' => $weights
    ];
}
?>
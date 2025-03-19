<?php
/**
 * AccuracyTracker.php
 * 
 * Model for tracking and evaluating prediction accuracy
 */
namespace Models;

class AccuracyTracker
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
     * Evaluate prediction accuracy against actual results
     * 
     * @param string $drawDate Date of the lottery draw
     * @return array Accuracy evaluation
     */
    public function evaluatePredictionAccuracy($drawDate)
    {
        // Get actual results for this draw
        $result = $this->lotteryData->getResultByDate($drawDate);
        
        if ($result['status'] !== 'success') {
            return [
                'status' => 'error',
                'message' => 'ไม่พบผลรางวัลสำหรับวันที่ระบุ'
            ];
        }
        
        $actualResults = $result['record'];
        
        // Get predictions for this draw
        $sql = "SELECT * FROM lottery_predictions WHERE target_draw_date = ?";
        $stmt = $this->conn->prepare($sql);
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
            $stmt = $this->conn->prepare($sql);
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
        
        $stmt = $this->conn->prepare($sql);
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
     * Compare prediction methods' performance
     * 
     * @param int $period Number of days to analyze
     * @return array Performance comparison
     */
    public function compareMethodsPerformance($period = 180)
    {
        // Get evaluation data for the period
        $startDate = new \DateTime();
        $startDate->modify("-$period days");
        $startDateStr = $startDate->format('Y-m-d');
        
        $sql = "SELECT * FROM prediction_accuracy 
                WHERE period_end >= ? 
                ORDER BY period_end ASC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $startDateStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลการประเมินความแม่นยำในช่วงเวลาที่ระบุ'
            ];
        }
        
        $accuracyData = [];
        while ($row = $result->fetch_assoc()) {
            $accuracyData[] = $row;
        }
        
        // Get statistical history for the period
        $sql = "SELECT sh.* FROM statistical_history sh
                INNER JOIN lottery_predictions lp ON sh.calculation_date LIKE CONCAT(LEFT(lp.prediction_date, 10), '%')
                WHERE lp.target_draw_date >= ?
                AND sh.calculation_type = 'prediction'
                ORDER BY sh.calculation_date ASC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $startDateStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $methodPerformance = [
            'day_of_week' => ['used' => 0, 'correct' => 0],
            'date' => ['used' => 0, 'correct' => 0],
            'month' => ['used' => 0, 'correct' => 0],
            'combined' => ['used' => 0, 'correct' => 0],
            'patterns' => ['used' => 0, 'correct' => 0],
            'pairs' => ['used' => 0, 'correct' => 0],
            'position' => ['used' => 0, 'correct' => 0],
            'trend' => ['used' => 0, 'correct' => 0]
        ];
        
        $usedMethods = [];
        
        while ($row = $result->fetch_assoc()) {
            $predictionDate = substr($row['calculation_date'], 0, 10);
            $resultData = json_decode($row['result_summary'], true);
            
            // Track which methods were used
            foreach ($resultData as $method => $used) {
                if ($used && strpos($method, '_used') !== false) {
                    $methodName = str_replace('_used', '', $method);
                    
                    if (isset($methodPerformance[$methodName])) {
                        $methodPerformance[$methodName]['used']++;
                        
                        // Store used methods for this prediction
                        if (!isset($usedMethods[$predictionDate])) {
                            $usedMethods[$predictionDate] = [];
                        }
                        $usedMethods[$predictionDate][] = $methodName;
                    }
                }
            }
        }
        
        // Get correct predictions
        $sql = "SELECT * FROM lottery_predictions 
                WHERE target_draw_date >= ? 
                AND was_correct = 1";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $startDateStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $predictionDate = substr($row['prediction_date'], 0, 10);
            
            // Credit all methods that contributed to this correct prediction
            if (isset($usedMethods[$predictionDate])) {
                foreach ($usedMethods[$predictionDate] as $method) {
                    $methodPerformance[$method]['correct']++;
                }
            }
        }
        
        // Calculate success rates
        foreach ($methodPerformance as $method => &$data) {
            $data['success_rate'] = ($data['used'] > 0) ? ($data['correct'] / $data['used']) * 100 : 0;
        }
        
        // Sort by success rate
        uasort($methodPerformance, function($a, $b) {
            return $b['success_rate'] - $a['success_rate'];
        });
        
        return [
            'status' => 'success',
            'period_days' => $period,
            'method_performance' => $methodPerformance,
            'accuracy_data' => $accuracyData
        ];
    }
    
    /**
     * Get top performing digits 
     * 
     * @param string $digitType Type of digits to analyze
     * @param int $period Number of days to analyze
     * @return array Top performing digits
     */
    public function getTopPerformingDigits($digitType, $period = 180)
    {
        // Validate digit type
        $validDigitTypes = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
        if (!in_array($digitType, $validDigitTypes)) {
            return [
                'status' => 'error',
                'message' => 'ประเภทเลขที่ระบุไม่ถูกต้อง'
            ];
        }
        
        // Get evaluation period
        $startDate = new \DateTime();
        $startDate->modify("-$period days");
        $startDateStr = $startDate->format('Y-m-d');
        
        // Get all predictions in the period
        $sql = "SELECT predicted_digits, was_correct 
                FROM lottery_predictions 
                WHERE target_draw_date >= ? 
                AND digit_type = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $startDateStr, $digitType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $digitPerformance = [];
        
        while ($row = $result->fetch_assoc()) {
            $digit = $row['predicted_digits'];
            $correct = $row['was_correct'];
            
            if (!isset($digitPerformance[$digit])) {
                $digitPerformance[$digit] = [
                    'predicted' => 0,
                    'correct' => 0
                ];
            }
            
            $digitPerformance[$digit]['predicted']++;
            if ($correct) {
                $digitPerformance[$digit]['correct']++;
            }
        }
        
        // Calculate success rates
        foreach ($digitPerformance as $digit => &$data) {
            $data['success_rate'] = ($data['predicted'] > 0) ? 
                ($data['correct'] / $data['predicted']) * 100 : 0;
        }
        
        // Sort by success rate and then by number of correct predictions
        uasort($digitPerformance, function($a, $b) {
            if ($a['correct'] === $b['correct']) {
                return $b['success_rate'] - $a['success_rate'];
            }
            return $b['correct'] - $a['correct'];
        });
        
        // Convert to array format for easier use
        $formattedPerformance = [];
        foreach ($digitPerformance as $digit => $data) {
            $formattedPerformance[] = [
                'digit' => $digit,
                'predicted' => $data['predicted'],
                'correct' => $data['correct'],
                'success_rate' => round($data['success_rate'], 2)
            ];
        }
        
        return [
            'status' => 'success',
            'digit_type' => $digitType,
            'period_days' => $period,
            'digit_performance' => $formattedPerformance
        ];
    }
    
    /**
     * Generate accuracy report for a specific period
     * 
     * @param string $period Period to analyze (weekly, monthly, yearly, all)
     * @return array Accuracy report
     */
    public function generateAccuracyReport($period = 'monthly')
    {
        // Determine date range
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        
        switch ($period) {
            case 'weekly':
                $startDate->modify('-7 days');
                $periodName = 'รายสัปดาห์';
                break;
                
            case 'monthly':
                $startDate->modify('-30 days');
                $periodName = 'รายเดือน';
                break;
                
            case 'yearly':
                $startDate->modify('-365 days');
                $periodName = 'รายปี';
                break;
                
            case 'all':
            default:
                $startDate->modify('-10 years'); // Long enough to get all records
                $periodName = 'ทั้งหมด';
                break;
        }
        
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');
        
        // Get accuracy data for the period
        $sql = "SELECT * FROM prediction_accuracy 
                WHERE period_end BETWEEN ? AND ? 
                ORDER BY period_end ASC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $startDateStr, $endDateStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $accuracyData = [];
        $totalPredictions = 0;
        $totalCorrect = 0;
        
        while ($row = $result->fetch_assoc()) {
            $accuracyData[] = $row;
            $totalPredictions += $row['total_predictions'];
            $totalCorrect += $row['correct_predictions'];
        }
        
        $overallAccuracy = ($totalPredictions > 0) ? ($totalCorrect / $totalPredictions) * 100 : 0;
        
        // Get digit type performance
        $digitTypePerformance = [
            'first_prize_last3' => ['total' => 0, 'correct' => 0],
            'last3f' => ['total' => 0, 'correct' => 0],
            'last3b' => ['total' => 0, 'correct' => 0],
            'last2' => ['total' => 0, 'correct' => 0]
        ];
        
        $sql = "SELECT digit_type, COUNT(*) as total, 
                SUM(was_correct) as correct 
                FROM lottery_predictions 
                WHERE target_draw_date BETWEEN ? AND ?
                GROUP BY digit_type";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $startDateStr, $endDateStr);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $digitType = $row['digit_type'];
            if (isset($digitTypePerformance[$digitType])) {
                $digitTypePerformance[$digitType]['total'] = $row['total'];
                $digitTypePerformance[$digitType]['correct'] = $row['correct'];
            }
        }
        
        // Calculate success rates
        foreach ($digitTypePerformance as $type => &$data) {
            $data['success_rate'] = ($data['total'] > 0) ? 
                ($data['correct'] / $data['total']) * 100 : 0;
        }
        
        // Get method performance comparison
        $methodsComparison = $this->compareMethodsPerformance(($period === 'all') ? 3650 : 
            ($period === 'yearly' ? 365 : ($period === 'monthly' ? 30 : 7)));
        
        // Format the report
        return [
            'status' => 'success',
            'period_name' => $periodName,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'accuracy_data' => $accuracyData,
            'total_predictions' => $totalPredictions,
            'total_correct' => $totalCorrect,
            'overall_accuracy' => round($overallAccuracy, 2),
            'digit_type_performance' => $digitTypePerformance,
            'methods_comparison' => ($methodsComparison['status'] === 'success') ?
                $methodsComparison['method_performance'] : []
        ];
    }
}
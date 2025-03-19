<?php

/**
 * LotteryData.php
 * 
 * Model for handling lottery data retrieval and management
 */

namespace Models;

class LotteryData
{
    private $conn;

    /**
     * Constructor
     */
    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Get lottery records filtered by parameters
     * 
     * @param array $params Filter parameters
     * @return array Filtered lottery records
     */
    public function getRecords($params = [])
    {
        $sql = "SELECT * FROM lotto_records WHERE 1=1";

        // Apply filters if provided
        if (!empty($params['day_of_week'])) {
            $sql .= " AND day_of_week = '" . $this->conn->real_escape_string($params['day_of_week']) . "'";
        }

        if (!empty($params['date_day'])) {
            $sql .= " AND DAY(dateValue) = " . intval($params['date_day']);
        }

        if (!empty($params['date_month'])) {
            $sql .= " AND MONTH(dateValue) = " . intval($params['date_month']);
        }

        if (!empty($params['start_date'])) {
            $sql .= " AND dateValue >= '" . $this->conn->real_escape_string($params['start_date']) . "'";
        }

        if (!empty($params['end_date'])) {
            $sql .= " AND dateValue <= '" . $this->conn->real_escape_string($params['end_date']) . "'";
        }

        // Order by
        $sql .= " ORDER BY " . (!empty($params['order_by']) ? $params['order_by'] : 'dateValue DESC');

        // Apply limit if provided
        if (!empty($params['limit'])) {
            $sql .= " LIMIT " . intval($params['limit']);
        }

        $result = $this->conn->query($sql);
        $records = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
        }

        return [
            'status' => 'success',
            'records' => $records,
            'count' => count($records),
            'sql' => $sql
        ];
    }

    /**
     * Get latest lottery results
     * 
     * @param int $limit Number of results to retrieve
     * @return array Latest lottery results
     */
    public function getLatestResults($limit = 10)
    {
        return $this->getRecords(['limit' => $limit]);
    }

    /**
     * Get specific result by date
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @return array|null Result or null if not found
     */
    public function getResultByDate($date)
    {
        $sql = "SELECT * FROM lotto_records WHERE dateValue = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return [
                'status' => 'success',
                'record' => $result->fetch_assoc()
            ];
        }

        return [
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลสำหรับวันที่ระบุ'
        ];
    }

    /**
     * Check if results exist for a specific date
     * 
     * @param string $date Date to check (YYYY-MM-DD)
     * @return bool True if results exist
     */
    public function resultExistsForDate($date)
    {
        $sql = "SELECT COUNT(*) as count FROM lotto_records WHERE dateValue = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['count'] > 0;
    }

    /**
     * Get count of all records
     * 
     * @return int Total number of records
     */
    public function getTotalRecords()
    {
        $sql = "SELECT COUNT(*) as total FROM lotto_records";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();

        return $row['total'];
    }

    /**
     * Insert new lottery result
     * 
     * @param array $data Result data
     * @return array Operation status
     */
    public function insertResult($data)
    {
        // Required fields
        $requiredFields = ['dateValue', 'date', 'day_of_week', 'first_prize_last3', 'first_prize'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'status' => 'error',
                    'message' => "ข้อมูลไม่ครบถ้วน: ไม่พบฟิลด์ $field"
                ];
            }
        }

        // Check if result already exists
        if ($this->resultExistsForDate($data['dateValue'])) {
            return [
                'status' => 'error',
                'message' => 'มีข้อมูลสำหรับวันที่นี้อยู่แล้ว'
            ];
        }

        $sql = "INSERT INTO lotto_records 
                (dateValue, date, day_of_week, first_prize_last3, first_prize, 
                 last3f, last3b, last2, near1_1, near1_2) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssss",
            $data['dateValue'],
            $data['date'],
            $data['day_of_week'],
            $data['first_prize_last3'],
            $data['first_prize'],
            $data['last3f'],
            $data['last3b'],
            $data['last2'],
            $data['near1_1'],
            $data['near1_2']
        );

        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'เพิ่มข้อมูลสำเร็จ',
                'id' => $this->conn->insert_id
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มข้อมูล: ' . $this->conn->error
            ];
        }
    }

    /**
     * Update existing lottery result
     * 
     * @param array $data Result data
     * @return array Operation status
     */
    public function updateResult($data)
    {
        // Check if date is provided
        if (!isset($data['dateValue']) || empty($data['dateValue'])) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบวันที่ที่ต้องการอัพเดต'
            ];
        }

        // Check if result exists
        if (!$this->resultExistsForDate($data['dateValue'])) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลสำหรับวันที่ที่ระบุ'
            ];
        }

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

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss",
            $data['day_of_week'],
            $data['first_prize_last3'],
            $data['first_prize'],
            $data['last3f'],
            $data['last3b'],
            $data['last2'],
            $data['near1_1'],
            $data['near1_2'],
            $data['dateValue']
        );

        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'อัพเดตข้อมูลสำเร็จ'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการอัพเดตข้อมูล: ' . $this->conn->error
            ];
        }
    }

    /**
     * Delete lottery result
     * 
     * @param string $date Date of result to delete (YYYY-MM-DD)
     * @return array Operation status
     */
    public function deleteResult($date)
    {
        // Check if result exists
        if (!$this->resultExistsForDate($date)) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลสำหรับวันที่ที่ระบุ'
            ];
        }

        $sql = "DELETE FROM lotto_records WHERE dateValue = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $date);

        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'ลบข้อมูลสำเร็จ'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $this->conn->error
            ];
        }
    }

    /**
     * Get the next lottery draw date
     * 
     * @return string Next lottery draw date (YYYY-MM-DD)
     */
    public function getNextDrawDate()
    {
        $today = new \DateTime();
        $day = (int)$today->format('d');
        $month = (int)$today->format('m');
        $year = (int)$today->format('Y');

        // Thai lottery is drawn on the 1st and 16th of each month
        if ($day < 16) {
            // Next draw is on the 16th of current month
            $nextDraw = new \DateTime("$year-$month-16");
        } else {
            // Next draw is on the 1st of next month
            $nextMonth = $month == 12 ? 1 : $month + 1;
            $nextYear = $month == 12 ? $year + 1 : $year;
            $nextDraw = new \DateTime("$nextYear-$nextMonth-01");
        }

        return $nextDraw->format('Y-m-d');
    }

    /**
     * Get previous draw dates
     * 
     * @param int $count Number of previous draw dates to retrieve
     * @return array Previous draw dates
     */
    public function getPreviousDrawDates($count = 5)
    {
        $sql = "SELECT dateValue FROM lotto_records 
                ORDER BY dateValue DESC 
                LIMIT " . intval($count);

        $result = $this->conn->query($sql);
        $dates = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dates[] = $row['dateValue'];
            }
        }

        return $dates;
    }

    /**
     * Import data from CSV file
     * 
     * @param string $filename CSV filename
     * @return array Import status
     */
    public function importFromCSV($filename)
    {
        if (!file_exists($filename)) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบไฟล์ที่ระบุ'
            ];
        }

        $file = fopen($filename, 'r');
        if (!$file) {
            return [
                'status' => 'error',
                'message' => 'ไม่สามารถเปิดไฟล์ได้'
            ];
        }

        $headers = fgetcsv($file);
        $imported = 0;
        $errors = 0;

        while (($row = fgetcsv($file)) !== FALSE) {
            $data = array_combine($headers, $row);

            // Process data and insert/update
            $result = $this->insertResult($data);

            if ($result['status'] === 'success') {
                $imported++;
            } else {
                $errors++;
            }
        }

        fclose($file);

        return [
            'status' => 'success',
            'imported' => $imported,
            'errors' => $errors,
            'message' => "นำเข้าข้อมูลสำเร็จ $imported รายการ, ผิดพลาด $errors รายการ"
        ];
    }

    /**
     * Get overall accuracy statistics
     * 
     * @return array Accuracy statistics
     */
    public function getAccuracyStatistics()
    {
        global $conn;

        // Get total predictions and correct predictions
        $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN was_correct = 1 THEN 1 ELSE 0 END) as correct 
            FROM lottery_predictions 
            WHERE was_correct IS NOT NULL";

        $result = $this->conn->query($sql);

        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }

        $row = $result->fetch_assoc();
        $totalPredictions = $row['total'];
        $totalCorrect = $row['correct'];
        $overallAccuracy = ($totalPredictions > 0) ? ($totalCorrect / $totalPredictions) * 100 : 0;

        // Calculate accuracy trend (compared to last month)
        $currentMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));

        $sqlCurrentMonth = "SELECT COUNT(*) as total, SUM(CASE WHEN was_correct = 1 THEN 1 ELSE 0 END) as correct 
                         FROM lottery_predictions 
                         WHERE was_correct IS NOT NULL
                         AND prediction_date >= '$currentMonth'";

        $sqlLastMonth = "SELECT COUNT(*) as total, SUM(CASE WHEN was_correct = 1 THEN 1 ELSE 0 END) as correct 
                      FROM lottery_predictions 
                      WHERE was_correct IS NOT NULL
                      AND prediction_date >= '$lastMonth' AND prediction_date < '$currentMonth'";

        $resultCurrentMonth = $this->conn->query($sqlCurrentMonth);
        $resultLastMonth = $this->conn->query($sqlLastMonth);

        $currentMonthAccuracy = 0;
        $lastMonthAccuracy = 0;

        if ($resultCurrentMonth && $row = $resultCurrentMonth->fetch_assoc()) {
            $currentMonthAccuracy = ($row['total'] > 0) ? ($row['correct'] / $row['total']) * 100 : 0;
        }

        if ($resultLastMonth && $row = $resultLastMonth->fetch_assoc()) {
            $lastMonthAccuracy = ($row['total'] > 0) ? ($row['correct'] / $row['total']) * 100 : 0;
        }

        $trend = $currentMonthAccuracy - $lastMonthAccuracy;

        // Find the best prediction method
        $sqlMethods = "SELECT prediction_type, COUNT(*) as total, 
                   SUM(CASE WHEN was_correct = 1 THEN 1 ELSE 0 END) as correct 
                   FROM lottery_predictions 
                   WHERE was_correct IS NOT NULL
                   GROUP BY prediction_type";

        $resultMethods = $this->conn->query($sqlMethods);

        $bestMethod = null;
        $bestMethodAccuracy = 0;

        if ($resultMethods) {
            while ($row = $resultMethods->fetch_assoc()) {
                $methodAccuracy = ($row['total'] > 0) ? ($row['correct'] / $row['total']) * 100 : 0;

                if ($methodAccuracy > $bestMethodAccuracy) {
                    $bestMethodAccuracy = $methodAccuracy;
                    $bestMethod = $row['prediction_type'];
                }
            }
        }

        return [
            'status' => 'success',
            'total_predictions' => $totalPredictions,
            'total_correct' => $totalCorrect,
            'overall_accuracy' => $overallAccuracy,
            'trend' => $trend,
            'best_method' => $bestMethod,
            'best_method_accuracy' => $bestMethodAccuracy
        ];
    }

    /**
     * Get digit type performance comparison
     * 
     * @return array Digit type performance data
     */
    public function getDigitTypePerformance()
    {
        $sql = "SELECT digit_type, COUNT(*) as total, 
            SUM(CASE WHEN was_correct = 1 THEN 1 ELSE 0 END) as correct 
            FROM lottery_predictions 
            WHERE was_correct IS NOT NULL
            GROUP BY digit_type";

        $result = $this->conn->query($sql);

        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'ข้อผิดพลาดในการสืบค้นฐานข้อมูล: ' . $this->conn->error,
                'sql' => $sql
            ];
        }

        $digitTypes = [];

        while ($row = $result->fetch_assoc()) {
            $accuracy = ($row['total'] > 0) ? ($row['correct'] / $row['total']) * 100 : 0;

            $digitTypes[$row['digit_type']] = [
                'total' => $row['total'],
                'correct' => $row['correct'],
                'accuracy' => $accuracy
            ];
        }

        // Sort by accuracy (highest first)
        uasort($digitTypes, function ($a, $b) {
            return $b['accuracy'] <=> $a['accuracy'];
        });

        return [
            'status' => 'success',
            'digit_types' => $digitTypes
        ];
    }

    
}

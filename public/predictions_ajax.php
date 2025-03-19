<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/PredictionModel.php';

// Error handling
header('Content-Type: application/json');

try {
    // Initialize models
    $connection = connectDatabase();
    $predictionModel = new Models\PredictionModel($connection);

    // Validate and sanitize input
    $digitType = isset($_GET['digit_type']) ? cleanInput($_GET['digit_type']) : 'first_prize_last3';
    $targetDrawDate = isset($_GET['draw_date']) ? cleanInput($_GET['draw_date']) : null;

    // Validate digit type
    $validDigitTypes = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
    if (!in_array($digitType, $validDigitTypes)) {
        throw new Exception('ประเภทเลขไม่ถูกต้อง');
    }

    // Validate target draw date
    if (!$targetDrawDate || !isValidDateFormat($targetDrawDate)) {
        $targetDrawDate = (new DateTime())->modify('+1 day')->format('Y-m-d');
    }

    // Fetch predictions by target draw date
    $predictions = $predictionModel->getPredictionsByTargetDrawDate($digitType, $targetDrawDate);

    // Return predictions
    echo json_encode($predictions);

} catch (Exception $e) {
    // Handle any unexpected errors
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
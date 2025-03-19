<?php
/**
 * predictions.php
 * 
 * Predictions page for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'ทำนายผลสลาก';
$activePage = 'predictions';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LotteryData.php';
require_once __DIR__ . '/../models/PredictionModel.php';
require_once __DIR__ . '/../models/MachineLearningModel.php'; // เพิ่มการอ้างอิงไฟล์ MachineLearningModel

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);
$predictionModel = new Models\PredictionModel($connection);

// Get next draw date
$nextDrawDate = $lotteryData->getNextDrawDate();
$nextDrawDateFormatted = formatThaiDisplayDate($nextDrawDate);

// Check if there's a prediction request
$predictions = null;
$digitalType = isset($_GET['digit_type']) ? cleanInput($_GET['digit_type']) : 'first_prize_last3';
$drawDate = isset($_GET['draw_date']) ? cleanInput($_GET['draw_date']) : $nextDrawDate;
$predictionMethod = isset($_GET['prediction_method']) ? cleanInput($_GET['prediction_method']) : 'statistical';

// Check digit type
$validDigitTypes = ['first_prize_last3', 'last3f', 'last3b', 'last2'];
if (!in_array($digitalType, $validDigitTypes)) {
    $digitalType = 'first_prize_last3';
}

// Check prediction method
$validMethods = ['statistical', 'machine_learning', 'ensemble'];
if (!in_array($predictionMethod, $validMethods)) {
    $predictionMethod = 'statistical';
}

// If the form is submitted, generate predictions
$predictionSubmitted = isset($_GET['draw_date']) && !empty($_GET['draw_date']);

if ($predictionSubmitted) {
    // Generate predictions based on selected method
    switch ($predictionMethod) {
        case 'machine_learning':
            // ใช้การทำนายด้วย Machine Learning
            $predictions = $predictionModel->generateMachineLearningPrediction($digitalType, $drawDate);
            break;
            
        case 'ensemble':
            // ใช้การทำนายแบบผสมผสาน (Ensemble)
            $predictions = $predictionModel->generateEnsemblePrediction($digitalType, $drawDate);
            break;
            
        case 'statistical':
        default:
            // ใช้การทำนายแบบสถิติ (ค่าเริ่มต้น)
            $predictions = $predictionModel->generatePredictions($digitalType, $drawDate);
            break;
    }
}

// Get existing predictions if not generating new ones
if (!$predictionSubmitted) {
    $existingPredictions = $predictionModel->getStoredPredictions($nextDrawDate, $digitalType);
    
    if ($existingPredictions['status'] === 'success' && count($existingPredictions['predictions']) > 0) {
        $predictions = $existingPredictions;
    }
}

// Get digit type labels
$digitTypeLabels = [
    'first_prize_last3' => 'เลขท้าย 3 ตัว (รางวัลที่ 1)',
    'last3f' => 'เลขหน้า 3 ตัว',
    'last3b' => 'เลขท้าย 3 ตัว',
    'last2' => 'เลขท้าย 2 ตัว'
];

// Method labels
$methodLabels = [
    'statistical' => 'การวิเคราะห์สถิติ',
    'machine_learning' => 'การเรียนรู้ของเครื่อง',
    'ensemble' => 'ผสมผสานทุกวิธี'
];

// Set additional CSS and JS
$additionalCss = ['public/css/charts.css'];
$additionalJs = ['public/js/predictions.js', 'public/js/charts.js'];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Prediction Form Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">ทำนายผลรางวัล</h6>
    </div>
    <div class="card-body">
        <form id="predictionForm" method="get" action="predictions.php">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">ประเภทเลข</label>
                    <div class="digit-type-toggle d-flex">
                        <div class="btn-group w-100" role="group">
                            <input type="hidden" name="digit_type" value="<?php echo $digitalType; ?>">
                            
                            <button type="button" class="btn btn-outline-primary <?php echo $digitalType === 'first_prize_last3' ? 'active' : ''; ?>" data-digit-type="first_prize_last3">
                                เลขท้าย 3 ตัว (รางวัลที่ 1)
                            </button>
                            
                            <button type="button" class="btn btn-outline-primary <?php echo $digitalType === 'last3f' ? 'active' : ''; ?>" data-digit-type="last3f">
                                เลขหน้า 3 ตัว
                            </button>
                            
                            <button type="button" class="btn btn-outline-primary <?php echo $digitalType === 'last3b' ? 'active' : ''; ?>" data-digit-type="last3b">
                                เลขท้าย 3 ตัว
                            </button>
                            
                            <button type="button" class="btn btn-outline-primary <?php echo $digitalType === 'last2' ? 'active' : ''; ?>" data-digit-type="last2">
                                เลขท้าย 2 ตัว
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="drawDate" class="form-label">งวดวันที่</label>
                    <input type="date" class="form-control" id="drawDate" name="draw_date" value="<?php echo $drawDate; ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="prediction_method" class="form-label">วิธีการทำนาย</label>
                    <select class="form-control" id="prediction_method" name="prediction_method">
                        <?php foreach ($methodLabels as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $predictionMethod === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-magic mr-1"></i> ทำนายผล
                    </button>
                </div>
            </div>
            
            <!-- คำอธิบายวิธีการทำนาย -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="method-explanation p-2 border rounded bg-light small">
                        <div class="method-explanation-statistical" <?php echo $predictionMethod !== 'statistical' ? 'style="display:none;"' : ''; ?>>
                            <i class="fas fa-chart-bar mr-1"></i> <strong>การวิเคราะห์สถิติ:</strong> 
                            ใช้การวิเคราะห์ข้อมูลสถิติย้อนหลัง วันที่ออก วันในสัปดาห์ รูปแบบที่เกิดซ้ำ และความถี่ในการออกรางวัล
                        </div>
                        <div class="method-explanation-machine_learning" <?php echo $predictionMethod !== 'machine_learning' ? 'style="display:none;"' : ''; ?>>
                            <i class="fas fa-brain mr-1"></i> <strong>การเรียนรู้ของเครื่อง:</strong> 
                            ใช้อัลกอริทึม Machine Learning ในการเรียนรู้รูปแบบที่ซับซ้อนจากข้อมูลประวัติทั้งหมดเพื่อสร้างการทำนาย
                        </div>
                        <div class="method-explanation-ensemble" <?php echo $predictionMethod !== 'ensemble' ? 'style="display:none;"' : ''; ?>>
                            <i class="fas fa-layer-group mr-1"></i> <strong>ผสมผสานทุกวิธี:</strong> 
                            รวมผลการทำนายจากหลายวิธีเข้าด้วยกัน และให้น้ำหนักตามประวัติความแม่นยำ เพื่อให้ได้ผลลัพธ์ที่แม่นยำที่สุด
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <div class="form-text text-muted mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            ระบบจะทำนายผลรางวัลโดยใช้ข้อมูลสถิติย้อนหลังประกอบกับเทคนิคการเรียนรู้ของเครื่อง
        </div>
    </div>
</div>

<!-- Prediction Results -->
<div class="predictions-content">
    <?php if ($predictions && $predictions['status'] === 'success' && !empty($predictions['predictions'])): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    ผลการทำนาย <?php echo $digitTypeLabels[$digitalType]; ?> งวดวันที่ <?php echo formatThaiDisplayDate($drawDate); ?>
                    <span class="badge bg-info ml-2"><?php echo $methodLabels[$predictionMethod] ?? 'สถิติ'; ?></span>
                </h6>
                <div>
                    <span class="badge bg-info" id="selectedCount">0</span> เลือก
                    <button id="printSelected" class="btn btn-sm btn-outline-primary ml-2" disabled>
                        <i class="fas fa-print"></i> พิมพ์ที่เลือก
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        คลิกที่เลขเพื่อเลือก สามารถเลือกได้หลายเลข
                    </div>
                </div>
                
                <div class="row">
                    <?php foreach ($predictions['predictions'] as $prediction): ?>
                        <div class="col-md-3 col-sm-4 col-6 mb-4">
                            <?php
                            // Determine card color based on confidence
                            $cardColor = 'primary';
                            if ($prediction['confidence'] >= 75) {
                                $cardColor = 'success';
                            } elseif ($prediction['confidence'] >= 50) {
                                $cardColor = 'primary';
                            } elseif ($prediction['confidence'] >= 25) {
                                $cardColor = 'warning';
                            } else {
                                $cardColor = 'danger';
                            }
                            
                            include __DIR__ . '/../templates/components/prediction_card.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Prediction Analysis Charts -->
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <?php
                        $chartId = 'predictionConfidenceChart';
                        $title = 'ความเชื่อมั่นของการทำนาย - ' . $digitTypeLabels[$digitalType];
                        $description = 'แสดงระดับความเชื่อมั่นของการทำนายเรียงลำดับจากมากไปน้อย';
                        include __DIR__ . '/../templates/components/chart_container.php';
                        
                        // Add JavaScript to create chart
                        $inlineJs = "
                            document.addEventListener('DOMContentLoaded', function() {
                                createPredictionChart('predictionConfidenceChart', " . json_encode($predictions) . ");
                            });
                        ";
                        ?>
                    </div>
                </div>
                
                <!-- แสดงข้อมูลเพิ่มเติมสำหรับ Ensemble Prediction -->
                <?php if ($predictionMethod === 'ensemble' && isset($predictions['model_contributions'])): ?>
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">การมีส่วนร่วมของแต่ละโมเดล</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($predictions['model_contributions'] as $model => $contribution): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-left-info shadow h-100 py-2">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                            <?php echo $model === 'statistical' ? 'การวิเคราะห์สถิติ' : 
                                                                ($model === 'machine_learning' ? 'การเรียนรู้ของเครื่อง' : 
                                                                ($model === 'time_series' ? 'วิเคราะห์อนุกรมเวลา' : $model)); ?>
                                                        </div>
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                            <?php echo number_format($contribution * 100, 2); ?>%
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas <?php echo $model === 'statistical' ? 'fa-chart-bar' : 
                                                            ($model === 'machine_learning' ? 'fa-brain' : 
                                                            ($model === 'time_series' ? 'fa-chart-line' : 'fa-cog')); ?> fa-2x text-gray-300"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Analysis Methods -->
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">วิธีการวิเคราะห์ที่ใช้</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>วิธีการวิเคราะห์</th>
                                                <th>ใช้งาน</th>
                                                <th>จำนวนข้อมูล</th>
                                                <?php if ($predictionMethod === 'ensemble'): ?>
                                                <th>น้ำหนัก</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $analysisMethods = [
                                                'day_analysis_used' => ['name' => 'วิเคราะห์ตามวันในสัปดาห์', 'count' => $predictions['analysis_summary']['day_pattern_count'] ?? 0],
                                                'date_analysis_used' => ['name' => 'วิเคราะห์ตามวันที่', 'count' => $predictions['analysis_summary']['date_pattern_count'] ?? 0],
                                                'month_analysis_used' => ['name' => 'วิเคราะห์ตามเดือน', 'count' => $predictions['analysis_summary']['month_pattern_count'] ?? 0],
                                                'combined_analysis_used' => ['name' => 'วิเคราะห์แบบผสมผสาน', 'count' => $predictions['analysis_summary']['combined_pattern_count'] ?? 0],
                                                'pattern_analysis_used' => ['name' => 'วิเคราะห์รูปแบบ', 'count' => $predictions['analysis_summary']['pattern_count'] ?? 0],
                                                'position_analysis_used' => ['name' => 'วิเคราะห์ตำแหน่งตัวเลข', 'count' => ''],
                                                'trend_analysis_used' => ['name' => 'วิเคราะห์แนวโน้ม', 'count' => ''],
                                                'ml_random_forest_used' => ['name' => 'Random Forest (ML)', 'count' => ''],
                                                'ml_neural_network_used' => ['name' => 'Neural Network (ML)', 'count' => ''],
                                                'ml_time_series_used' => ['name' => 'Time Series Analysis (ML)', 'count' => '']
                                            ];
                                            
                                            foreach ($analysisMethods as $key => $method):
                                                $used = isset($predictions['analysis_summary'][$key]) && $predictions['analysis_summary'][$key];
                                                // ไม่แสดงวิธี ML ถ้าไม่ได้เลือกใช้ ML หรือ Ensemble
                                                if (strpos($key, 'ml_') === 0 && $predictionMethod === 'statistical') {
                                                    continue;
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $method['name']; ?></td>
                                                <td class="text-center">
                                                    <?php if ($used): ?>
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?php echo $method['count']; ?></td>
                                                <?php if ($predictionMethod === 'ensemble'): ?>
                                                <td class="text-center">
                                                    <?php 
                                                    $weight = isset($predictions['method_weights'][$key]) ? 
                                                        number_format($predictions['method_weights'][$key] * 100, 1) . '%' : '-';
                                                    echo $weight;
                                                    ?>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Machine Learning Model Insights (แสดงเฉพาะเมื่อใช้ ML หรือ Ensemble) -->
                <?php if (($predictionMethod === 'machine_learning' || $predictionMethod === 'ensemble') && 
                          isset($predictions['ml_insights'])): ?>
                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Insights จาก Machine Learning</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2">ปัจจัยที่มีอิทธิพลต่อการทำนาย</h5>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ปัจจัย</th>
                                                    <th>ความสำคัญ (%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($predictions['ml_insights']['feature_importance'] as $feature => $importance): ?>
                                                <tr>
                                                    <td><?php echo $feature; ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 15px;">
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                 style="width: <?php echo $importance * 100; ?>%" 
                                                                 aria-valuenow="<?php echo $importance * 100; ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo number_format($importance * 100, 1); ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="border-bottom pb-2">ช่วงความเชื่อมั่น</h5>
                                        <p>ช่วงความเชื่อมั่น 95% สำหรับตัวเลขที่ทำนาย:</p>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>เลข</th>
                                                    <th>ความเชื่อมั่น (%)</th>
                                                    <th>ช่วงความเชื่อมั่น</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $topPredictions = array_slice($predictions['predictions'], 0, 5);
                                                foreach ($topPredictions as $prediction):
                                                    $confidenceInterval = $predictions['ml_insights']['confidence_intervals'][$prediction['digit']] ?? [0, 0];
                                                ?>
                                                <tr>
                                                    <td><strong><?php echo $prediction['digit']; ?></strong></td>
                                                    <td><?php echo number_format($prediction['confidence'], 2); ?>%</td>
                                                    <td><?php echo number_format($confidenceInterval[0], 2); ?>% - <?php echo number_format($confidenceInterval[1], 2); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        
                                        <h5 class="border-bottom pb-2 mt-4">ความแม่นยำของโมเดล</h5>
                                        <p>ความแม่นยำในการทดสอบ: <strong><?php echo number_format($predictions['ml_insights']['model_accuracy'] * 100, 2); ?>%</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($predictionSubmitted): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <?php echo isset($predictions['message']) ? $predictions['message'] : 'ไม่สามารถทำนายผลได้ในขณะนี้ โปรดลองใหม่ภายหลัง'; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            กรุณาเลือกประเภทเลขและวันที่ต้องการทำนาย จากนั้นกดปุ่ม "ทำนายผล"
        </div>
    <?php endif; ?>
</div>

<!-- Script for printing selected predictions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to print selected button
    const printSelectedButton = document.getElementById('printSelected');
    if (printSelectedButton) {
        printSelectedButton.addEventListener('click', function() {
            printSelectedPredictions();
        });
    }
    
    // Add change event to prediction method select
    const predictionMethodSelect = document.getElementById('prediction_method');
    if (predictionMethodSelect) {
        predictionMethodSelect.addEventListener('change', function() {
            // Hide all method explanations
            document.querySelectorAll('[class^="method-explanation-"]').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected method explanation
            const selectedMethod = this.value;
            const explanationElement = document.querySelector('.method-explanation-' + selectedMethod);
            if (explanationElement) {
                explanationElement.style.display = 'block';
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
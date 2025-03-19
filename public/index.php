<?php

/**
 * index.php
 * 
 * Main page / Dashboard for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'หน้าหลัก';
$activePage = 'home';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LotteryData.php';
require_once __DIR__ . '/../models/PredictionModel.php';
require_once __DIR__ . '/../models/AccuracyTracker.php';

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);
$predictionModel = new Models\PredictionModel($connection);
$accuracyTracker = new Models\AccuracyTracker($connection);

// Define digit type labels
$digitTypeLabels = [
    'first_prize_last3' => 'เลขท้าย 3 ตัว (รางวัลที่ 1)',
    'last3f' => 'เลขหน้า 3 ตัว',
    'last3b' => 'เลขท้าย 3 ตัว',
    'last2' => 'เลขท้าย 2 ตัว'
];

// Get summary statistics
$summaryStats = $predictionModel->getSummaryStatistics();

// Get next draw date
$nextDrawDate = $lotteryData->getNextDrawDate();
$nextDrawDateFormatted = formatThaiDisplayDate($nextDrawDate);

// Get latest results
$latestResults = $lotteryData->getLatestResults(5);

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow-lg">
                <div class="card-body text-info">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <h1 class="h3 mb-2">ระบบวิเคราะห์สลากกินแบ่งรัฐบาล</h1>
                            <p class="lead mb-0">ระบบอัจฉริยะที่ใช้การเรียนรู้ของเครื่องและข้อมูลสถิติเพื่อทำนายผลสลากกินแบ่ง</p>
                        </div>
                        <div class="col-md-3 text-end d-none d-md-block">
                            <i class="fas fa-chart-line fa-4x text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">การทำนายทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summaryStats['total_predictions']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ความแม่นยำเฉลี่ย</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summaryStats['average_accuracy'], 2); ?>%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bullseye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">งวดถัดไป</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $nextDrawDateFormatted; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">ข้อมูลทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($summaryStats['total_data_records']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
        <!-- Latest Results -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ผลรางวัลล่าสุด</h6>
                    <a href="results.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list mr-1"></i> ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>รางวัลที่ 1</th>
                                    <th>เลขท้าย 3 ตัว</th>
                                    <th>เลขท้าย 2 ตัว</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestResults['records'] as $result): ?>
                                    <tr>
                                        <td><?php echo formatThaiDisplayDate($result['dateValue']); ?></td>
                                        <td class="text-center font-weight-bold"><?php echo $result['first_prize']; ?></td>
                                        <td class="text-center"><?php echo $result['first_prize_last3']; ?></td>
                                        <td class="text-center"><?php echo $result['last2']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Prediction Form -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">ทำนายผลรางวัลด่วน</h6>
                </div>
                <div class="card-body">
                    <form action="predictions.php" method="get" class="quick-prediction-form">
                        <div class="mb-3">
                            <label class="form-label">ประเภทเลข</label>
                            <div class="btn-group w-100 mb-3" role="group">
                                <?php foreach ($digitTypeLabels as $key => $label): ?>
                                    <input type="radio" class="btn-check" name="digit_type"
                                        id="<?php echo $key; ?>"
                                        value="<?php echo $key; ?>"
                                        <?php echo $key === 'first_prize_last3' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="<?php echo $key; ?>">
                                        <?php echo str_replace(['เลขท้าย', 'เลขหน้า'], ['ท้าย', 'หน้า'], $label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="drawDate" class="form-label">งวดวันที่</label>
                            <input type="date" class="form-control" id="drawDate" name="draw_date"
                                value="<?php echo $nextDrawDate; ?>">
                        </div>

                        <div class="alert alert-info alert-sm" role="alert">
                            <small>
                                <i class="fas fa-info-circle mr-1"></i>
                                ระบบใช้การเรียนรู้ของเครื่องและข้อมูลสถิติเพื่อทำนายผล
                            </small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-magic mr-1"></i> ทำนายตอนนี้
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Predictions and Accuracy Section -->
    <div class="row">
        <!-- Latest Predictions -->
        <div class="col-xl-12 col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        การทำนายล่าสุด  
                        <small id="selectedDrawDateDisplay" class="text-muted ml-2"></small>
                    </h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                            id="predictionOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i> ตัวเลือก
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="predictionOptionsDropdown">
                            <li>
                                <div class="dropdown-item">
                                    <label for="predictDrawDate" class="form-label">เลือกวันที่</label>
                                    <input type="date" class="form-control form-control-sm"
                                        id="predictDrawDate" value="<?php echo $nextDrawDate; ?>">
                                </div>
                            </li>
                            <li>
                                <div class="dropdown-item">
                                    <button class="btn btn-primary btn-sm w-100" id="loadPredictionBtn">
                                        <i class="fas fa-sync me-1"></i> โหลดการทำนาย
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="predictionsContainer" class="row">
                        <?php foreach ($digitTypeLabels as $key => $label): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card border shadow-sm">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="m-0 font-weight-bold text-primary"><?php echo $label; ?></h6>
                                    </div>
                                    <div class="card-body" id="predictions-<?php echo $key; ?>">
                                        <div class="text-center py-4">
                                            <i class="fas fa-magic fa-3x text-gray-300 mb-3"></i>
                                            <p class="text-muted">กรุณาโหลดการทำนาย</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accuracy Chart -->
        <div class="col-xl-12 col-lg-12 mb-4">
            <?php
            // Get accuracy history for chart
            $accuracyHistory = $accuracyTracker->getAccuracyHistory('monthly');

            // Set up chart data if history exists
            if ($accuracyHistory['status'] === 'success' && count($accuracyHistory['history']) > 0) {
                $chartId = 'accuracyChart';
                $title = 'ความแม่นยำของการทำนาย';
                $description = 'แนวโน้มความแม่นยำของการทำนายในช่วง 6 เดือนที่ผ่านมา';
                include __DIR__ . '/../templates/components/chart_container.php';

                // Add JavaScript to create chart
                $inlineJs = "
                    document.addEventListener('DOMContentLoaded', function() {
                        createAccuracyChart('accuracyChart', " . json_encode($accuracyHistory) . ");
                    });
                ";
            } else {
                // Fallback if no accuracy history
                echo '
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">ความแม่นยำของการทำนาย</h6>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-4x text-gray-300 mb-3"></i>
                        <p class="text-muted">ไม่มีข้อมูลความแม่นยำในขณะนี้</p>
                        <small class="text-muted">ระบบจะเก็บสถิติและแสดงความแม่นยำเมื่อมีข้อมูลเพียงพอ</small>
                    </div>
                </div>
                ';
            }
            ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const drawDateInput = document.getElementById('predictDrawDate');
        const loadPredictionBtn = document.getElementById('loadPredictionBtn');
        const digitTypes = <?php echo json_encode(array_keys($digitTypeLabels)); ?>;
        const selectedDrawDateDisplay = document.getElementById('selectedDrawDateDisplay');

        // Set default draw date
        drawDateInput.value = '<?php echo $nextDrawDate; ?>';

        // Load predictions
        loadPredictionBtn.addEventListener('click', function() {
            const drawDate = drawDateInput.value;

            // Update the displayed date
            const formattedDate = new Date(drawDate).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            selectedDrawDateDisplay.textContent = `(${formattedDate})`

            // Show loading for all prediction containers
            digitTypes.forEach(digitType => {
                const container = document.getElementById(`predictions-${digitType}`);
                container.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="text-muted mt-2">กำลังโหลดการทำนาย...</p>
                </div>
            `;
            });

            // Fetch predictions for each digit type
            digitTypes.forEach(digitType => {
                fetch(`public/predictions_ajax.php?digit_type=${digitType}&draw_date=${drawDate}`)
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById(`predictions-${digitType}`);

                        if (data.status === 'success' && data.predictions && data.predictions.length > 0) {
                            let predictionHtml = '<div class="row g-2">';
                            data.predictions.slice(0, 6).forEach(prediction => {
                                // Convert confidence to a number and handle potential string input
                                const confidence = Number(prediction.confidence);

                                // Determine confidence color and class
                                let colorClass = confidence >= 75 ? 'success' :
                                    (confidence >= 50 ? 'primary' : 'warning');
                                let textColor = confidence >= 75 ? 'text-white' : 'text-dark';

                                predictionHtml += `
                        <div class="col-md-4">
                            <div class="card border-left-${colorClass} shadow-sm">
                                <div class="card-body text-center">
                                    <div class="h4 mb-2 font-weight-bold ${textColor}">${prediction.digit}</div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-${colorClass}" 
                                             role="progressbar" 
                                             style="width: ${confidence}%" 
                                             aria-valuenow="${confidence}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">${confidence.toFixed(2)}% ความเชื่อมั่น</small>
                                </div>
                            </div>
                        </div>
                    `;
                            });
                            predictionHtml += '</div>';
                            container.innerHTML = predictionHtml;
                        } else {
                            container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-calculator fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">ไม่มีการทำนายสำหรับงวดนี้</p>
                    </div>
                `;
                        }
                    })
                    .catch(error => {
                        const container = document.getElementById(`predictions-${digitType}`);
                        console.error('Error:', error);
                        container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <p class="text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                    <small class="text-muted">${error.message}</small>
                </div>
            `;
                    });
            });

        });

        // Trigger initial load when page loads
        loadPredictionBtn.click();
    });
</script>

<?php
// Include footer
$additionalJs = ['public/js/charts.js'];
include __DIR__ . '/../templates/footer.php';
?>
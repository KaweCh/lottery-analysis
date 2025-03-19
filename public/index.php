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
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/LotteryData.php';
require_once __DIR__ . '/models/PredictionModel.php';
require_once __DIR__ . '/models/AccuracyTracker.php';

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);
$predictionModel = new Models\PredictionModel($connection);
$accuracyTracker = new Models\AccuracyTracker($connection);

// Get summary statistics
$summaryStats = $predictionModel->getSummaryStatistics();

// Get next draw date
$nextDrawDate = $lotteryData->getNextDrawDate();
$nextDrawDateFormatted = formatThaiDisplayDate($nextDrawDate);

// Get latest results
$latestResults = $lotteryData->getLatestResults(5);

// Get latest predictions
$latestPredictions = $predictionModel->getStoredPredictions($nextDrawDate, 'first_prize_last3');
$latestPredictions2 = $predictionModel->getStoredPredictions($nextDrawDate, 'last2');

// Include header
include __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-xl-12 mb-4">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="text-white">ยินดีต้อนรับ</h2>
                        <p class="mb-0">ระบบวิเคราะห์และทำนายผลสลากกินแบ่งรัฐบาล โดยใช้ข้อมูลสถิติและการเรียนรู้ของเครื่อง</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Stats Cards -->
<div class="row">
    <?php 
    // Total Predictions Card
    include_once __DIR__ . '/templates/components/stat_card.php';
    $title = "การทำนายทั้งหมด";
    $value = number_format($summaryStats['total_predictions']);
    $icon = "fas fa-calculator";
    $colorClass = "primary";
    $footerText = "เก็บข้อมูลทั้งหมด";
    $footerIcon = "fas fa-database";
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Accuracy Card
    $title = "ความแม่นยำเฉลี่ย";
    $value = number_format($summaryStats['average_accuracy'], 2) . "%";
    $icon = "fas fa-bullseye";
    $colorClass = "success";
    $footerText = "ประเมินจากการทำนายที่ผ่านมาทั้งหมด";
    $footerIcon = "fas fa-history";
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Next Draw Card
    $title = "งวดถัดไป";
    $value = $nextDrawDateFormatted;
    $icon = "fas fa-calendar";
    $colorClass = "info";
    $footerText = getThaiDayOfWeek(new DateTime($nextDrawDate));
    $footerIcon = "fas fa-calendar-day";
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Data Records Card
    $title = "ข้อมูลทั้งหมด";
    $value = number_format($summaryStats['total_data_records']);
    $icon = "fas fa-database";
    $colorClass = "warning";
    $footerText = "จากปี 2550 - ปัจจุบัน";
    $footerIcon = "fas fa-history";
    include __DIR__ . '/templates/components/stat_card.php';
    ?>
</div>

<div class="row">
    <!-- Latest Results -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ผลรางวัลล่าสุด</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
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
                <div class="text-center mt-3">
                    <a href="results.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list mr-1"></i> ดูทั้งหมด
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Latest Predictions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">การทำนายงวดที่ <?php echo $nextDrawDateFormatted; ?></h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="predictionTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="predictionTypeDropdown">
                        <h6 class="dropdown-header">ประเภทเลข:</h6>
                        <a class="dropdown-item preview-toggle active" data-target="first_prize_last3" href="#">เลขท้าย 3 ตัว</a>
                        <a class="dropdown-item preview-toggle" data-target="last2" href="#">เลขท้าย 2 ตัว</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="predictions.php">
                            <i class="fas fa-external-link-alt fa-sm fa-fw mr-1"></i> ไปที่หน้าทำนาย
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="first_prize_last3_preview" class="prediction-preview">
                    <div class="row">
                        <?php 
                        if ($latestPredictions['status'] === 'success' && count($latestPredictions['predictions']) > 0):
                            // Show top 6 predictions
                            $topPredictions = array_slice($latestPredictions['predictions'], 0, 6);
                            foreach ($topPredictions as $prediction):
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-<?php echo $prediction['confidence'] >= 75 ? 'success' : ($prediction['confidence'] >= 50 ? 'primary' : 'warning'); ?> shadow py-2">
                                <div class="card-body text-center">
                                    <div class="h3 font-weight-bold"><?php echo $prediction['predicted_digits']; ?></div>
                                    <div class="progress mb-1" style="height: 10px;">
                                        <div class="progress-bar bg-<?php echo $prediction['confidence'] >= 75 ? 'success' : ($prediction['confidence'] >= 50 ? 'primary' : 'warning'); ?>" 
                                             role="progressbar" style="width: <?php echo $prediction['confidence']; ?>%" 
                                             aria-valuenow="<?php echo $prediction['confidence']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-xs text-muted"><?php echo $prediction['confidence']; ?>% ความเชื่อมั่น</div>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-calculator fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">ยังไม่มีการทำนายสำหรับงวดนี้</p>
                            <a href="predictions.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-magic mr-1"></i> ทำนายตอนนี้
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div id="last2_preview" class="prediction-preview" style="display: none;">
                    <div class="row">
                        <?php 
                        if ($latestPredictions2['status'] === 'success' && count($latestPredictions2['predictions']) > 0):
                            // Show top 6 predictions
                            $topPredictions = array_slice($latestPredictions2['predictions'], 0, 6);
                            foreach ($topPredictions as $prediction):
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-<?php echo $prediction['confidence'] >= 75 ? 'success' : ($prediction['confidence'] >= 50 ? 'primary' : 'warning'); ?> shadow py-2">
                                <div class="card-body text-center">
                                    <div class="h3 font-weight-bold"><?php echo $prediction['predicted_digits']; ?></div>
                                    <div class="progress mb-1" style="height: 10px;">
                                        <div class="progress-bar bg-<?php echo $prediction['confidence'] >= 75 ? 'success' : ($prediction['confidence'] >= 50 ? 'primary' : 'warning'); ?>" 
                                             role="progressbar" style="width: <?php echo $prediction['confidence']; ?>%" 
                                             aria-valuenow="<?php echo $prediction['confidence']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-xs text-muted"><?php echo $prediction['confidence']; ?>% ความเชื่อมั่น</div>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-calculator fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">ยังไม่มีการทำนายสำหรับงวดนี้</p>
                            <a href="predictions.php?digit_type=last2" class="btn btn-primary btn-sm">
                                <i class="fas fa-magic mr-1"></i> ทำนายตอนนี้
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="predictions.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-magic mr-1"></i> ทำนายเพิ่มเติม
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Accuracy Chart -->
    <div class="col-xl-6 col-lg-6">
        <?php
        // Get accuracy history for chart
        $accuracyHistory = $accuracyTracker->getAccuracyHistory('monthly');
        
        // Set up chart data if history exists
        if ($accuracyHistory['status'] === 'success' && count($accuracyHistory['history']) > 0) {
            $chartId = 'accuracyChart';
            $title = 'ความแม่นยำของการทำนาย';
            $description = 'แนวโน้มความแม่นยำของการทำนายในช่วง 6 เดือนที่ผ่านมา';
            include __DIR__ . '/templates/components/chart_container.php';
            
            // Add JavaScript to create chart
            $inlineJs = "
                document.addEventListener('DOMContentLoaded', function() {
                    createAccuracyChart('accuracyChart', " . json_encode($accuracyHistory) . ");
                });
            ";
        }
        ?>
    </div>
    
    <!-- Quick Prediction Form -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ทำนายผลรางวัลด่วน</h6>
            </div>
            <div class="card-body">
                <form action="predictions.php" method="get" class="quick-prediction-form">
                    <div class="mb-3">
                        <label class="form-label">ประเภทเลข</label>
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="digit_type" id="firstPrizeLast3" value="first_prize_last3" checked>
                            <label class="btn btn-outline-primary" for="firstPrizeLast3">เลขท้าย 3 ตัว</label>
                            
                            <input type="radio" class="btn-check" name="digit_type" id="last3f" value="last3f">
                            <label class="btn btn-outline-primary" for="last3f">เลขหน้า 3 ตัว</label>
                            
                            <input type="radio" class="btn-check" name="digit_type" id="last3b" value="last3b">
                            <label class="btn btn-outline-primary" for="last3b">เลขท้าย 3 ตัว</label>
                            
                            <input type="radio" class="btn-check" name="digit_type" id="last2" value="last2">
                            <label class="btn btn-outline-primary" for="last2">เลขท้าย 2 ตัว</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="drawDate" class="form-label">งวดวันที่</label>
                        <input type="date" class="form-control" id="drawDate" name="draw_date" value="<?php echo $nextDrawDate; ?>">
                    </div>
                    
                    <div class="form-text text-muted mb-3">
                        ระบบจะทำนายผลรางวัลโดยใช้ข้อมูลสถิติย้อนหลังประกอบกับเทคนิคการเรียนรู้ของเครื่อง
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

<script>
// Toggle prediction preview
document.addEventListener('DOMContentLoaded', function() {
    const previewToggles = document.querySelectorAll('.preview-toggle');
    
    previewToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active toggle
            previewToggles.forEach(t => t.classList.remove('active'));
            toggle.classList.add('active');
            
            // Get target preview
            const target = toggle.getAttribute('data-target');
            
            // Hide all previews
            document.querySelectorAll('.prediction-preview').forEach(preview => {
                preview.style.display = 'none';
            });
            
            // Show target preview
            document.getElementById(target + '_preview').style.display = 'block';
        });
    });
});
</script>

<?php
// Include footer
$additionalJs = ['assets/js/charts.js'];
include __DIR__ . '/templates/footer.php';
?>

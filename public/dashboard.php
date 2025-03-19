
<?php
/**
 * dashboard.php
 * 
 * Admin dashboard page for the Thai Lottery Analysis system
 */

// Check admin access
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Set page title and active menu
$pageTitle = 'แดชบอร์ด';
$activeMenu = 'dashboard';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LotteryData.php';
require_once __DIR__ . '/../models/PredictionModel.php';
require_once __DIR__ . '/../models/AccuracyTracker.php';
require_once __DIR__ . '/../models/StatisticalAnalysis.php';

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);
$predictionModel = new Models\PredictionModel($connection);
$accuracyTracker = new Models\AccuracyTracker($connection);
$statisticalAnalysis = new Models\StatisticalAnalysis($connection);

// Get admin dashboard statistics
$totalRecords = $lotteryData->getTotalRecords();
$totalPredictions = $predictionModel->getTotalPredictions();
$accuracyStats = $accuracyTracker->getAccuracyStatistics();
$nextDrawDate = $lotteryData->getNextDrawDate();
$nextDrawDateFormatted = formatThaiDisplayDate($nextDrawDate);

// Get latest 5 lottery records
$latestResults = $lotteryData->getLatestResults(5);

// Get method performance data
$methodPerformance = $accuracyTracker->compareMethodsPerformance(180);

// Get system activity log
$logFile = getConfig('log_path') . 'system_' . date('Y-m-d') . '.log';
$logEntries = [];

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $logLines = explode(PHP_EOL, $logContent);
    $logEntries = array_slice(array_filter($logLines), -10);
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="admin-content">
    <!-- Include sidebar -->
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="admin-header">
            <h1>แดชบอร์ด</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">หน้าหลัก</a></li>
                    <li class="breadcrumb-item active" aria-current="page">แดชบอร์ด</li>
                </ol>
            </nav>
        </div>
        
        <!-- Summary Stats Cards -->
        <div class="row">
            <?php 
            // Total Records Card
            include_once __DIR__ . '/../templates/components/stat_card.php';
            $title = "ข้อมูลทั้งหมด";
            $value = number_format($totalRecords);
            $icon = "fas fa-database";
            $colorClass = "primary";
            $footerText = "จำนวนผลรางวัลที่เก็บบันทึก";
            $footerIcon = "fas fa-ticket-alt";
            include __DIR__ . '/../templates/components/stat_card.php';
            
            // Total Predictions Card
            $title = "การทำนายทั้งหมด";
            $value = number_format($totalPredictions);
            $icon = "fas fa-calculator";
            $colorClass = "info";
            $footerText = "การทำนายตั้งแต่เริ่มต้นระบบ";
            $footerIcon = "fas fa-magic";
            include __DIR__ . '/../templates/components/stat_card.php';
            
            // Accuracy Card
            $title = "ความแม่นยำเฉลี่ย";
            $value = number_format($accuracyStats['overall_accuracy'], 2) . "%";
            $icon = "fas fa-bullseye";
            $colorClass = "success";
            $footerText = "ประเมินจากการทำนายทั้งหมด";
            $footerIcon = "fas fa-chart-line";
            include __DIR__ . '/../templates/components/stat_card.php';
            
            // Next Draw Card
            $title = "งวดถัดไป";
            $value = $nextDrawDateFormatted;
            $icon = "fas fa-calendar";
            $colorClass = "warning";
            $footerText = getThaiDayOfWeek(new DateTime($nextDrawDate));
            $footerIcon = "fas fa-calendar-day";
            include __DIR__ . '/../templates/components/stat_card.php';
            ?>
        </div>
        
        <div class="row">
            <!-- Latest Results -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">ผลรางวัลล่าสุด</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                               data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">การจัดการ:</div>
                                <a class="dropdown-item" href="lottery_results.php">จัดการผลรางวัล</a>
                                <a class="dropdown-item" href="lottery_results.php?action=add">เพิ่มผลรางวัลใหม่</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="import.php">นำเข้าข้อมูลจาก CSV</a>
                            </div>
                        </div>
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
                            <a href="lottery_results.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-list mr-1"></i> จัดการผลรางวัล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Accuracy Chart -->
            <div class="col-lg-6 mb-4">
                <?php
                // Get accuracy history for chart
                $accuracyHistory = $accuracyTracker->getAccuracyHistory('monthly');
                
                // Set up chart data if history exists
                if ($accuracyHistory['status'] === 'success' && count($accuracyHistory['history']) > 0) {
                    $chartId = 'adminAccuracyChart';
                    $title = 'ความแม่นยำของการทำนาย';
                    $description = 'แนวโน้มความแม่นยำของการทำนายในช่วง 6 เดือนที่ผ่านมา';
                    include __DIR__ . '/../templates/components/chart_container.php';
                    
                    // Add JavaScript to create chart
                    $inlineJs = "
                        document.addEventListener('DOMContentLoaded', function() {
                            createAccuracyChart('adminAccuracyChart', " . json_encode($accuracyHistory) . ");
                        });
                    ";
                }
                ?>
            </div>
        </div>
        
        <div class="row">
            <!-- Method Performance -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">ประสิทธิภาพวิธีการวิเคราะห์</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($methodPerformance['status'] === 'success'): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>วิธีการวิเคราะห์</th>
                                            <th>จำนวนครั้งที่ใช้</th>
                                            <th>ถูกต้อง</th>
                                            <th>ความแม่นยำ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($methodPerformance['method_performance'] as $method => $data): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $methodNames = [
                                                    'day_of_week' => 'วิเคราะห์ตามวันในสัปดาห์',
                                                    'date' => 'วิเคราะห์ตามวันที่',
                                                    'month' => 'วิเคราะห์ตามเดือน',
                                                    'combined' => 'วิเคราะห์แบบผสมผสาน',
                                                    'patterns' => 'วิเคราะห์รูปแบบ',
                                                    'pairs' => 'วิเคราะห์คู่ตัวเลข',
                                                    'position' => 'วิเคราะห์ตำแหน่งตัวเลข',
                                                    'trend' => 'วิเคราะห์แนวโน้ม'
                                                ];
                                                echo $methodNames[$method] ?? $method;
                                                ?>
                                            </td>
                                            <td class="text-center"><?php echo $data['used']; ?></td>
                                            <td class="text-center"><?php echo $data['correct']; ?></td>
                                            <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar <?php echo $data['success_rate'] >= 60 ? 'bg-success' : ($data['success_rate'] >= 40 ? 'bg-info' : 'bg-warning'); ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $data['success_rate']; ?>%"
                                                         aria-valuenow="<?php echo $data['success_rate']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo number_format($data['success_rate'], 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">ไม่พบข้อมูลประสิทธิภาพวิธีการวิเคราะห์</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- System Logs -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">บันทึกการทำงานของระบบล่าสุด</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($logEntries)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>เวลา</th>
                                            <th>ประเภท</th>
                                            <th>ข้อความ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logEntries as $entry): ?>
                                        <tr>
                                            <td><?php echo $entry['time']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $entry['type'] === 'info' ? 'info' : ($entry['type'] === 'warning' ? 'warning' : 'danger'); ?>">
                                                    <?php echo $entry['type']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $entry['message']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">ไม่พบบันทึกการทำงานของระบบ</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <a href="logs.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-clipboard-list mr-1"></i> ดูบันทึกทั้งหมด
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

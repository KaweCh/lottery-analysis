<?php
/**
 * history.php
 * 
 * Prediction history page for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'ประวัติการทำนาย';
$activePage = 'history';

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

// Handle pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle filters
$filters = [];

if (isset($_GET['digit_type']) && !empty($_GET['digit_type'])) {
    $filters['digit_type'] = cleanInput($_GET['digit_type']);
}

if (isset($_GET['prediction_method']) && !empty($_GET['prediction_method'])) {
    $filters['prediction_method'] = cleanInput($_GET['prediction_method']);
}

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = cleanInput($_GET['status']);
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $filters['start_date'] = cleanInput($_GET['start_date']);
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $filters['end_date'] = cleanInput($_GET['end_date']);
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

// Status labels
$statusLabels = [
    '1' => 'ถูกต้อง',
    '0' => 'ไม่ถูกต้อง',
    'null' => 'รอผล'
];

// Get prediction history
$history = $predictionModel->getPredictionHistory($filters, $limit, $offset);
$totalRecords = $history['total_count'];
$totalPages = ceil($totalRecords / $limit);

// Get accuracy statistics
$accuracyStats = $accuracyTracker->getAccuracyStatistics();

// Set additional CSS and JS
$additionalCss = ['public/css/charts.css'];
$additionalJs = ['public/js/charts.js'];

// Set page actions
$pageActions = '
<div class="btn-group">
    <button type="button" id="printHistory" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-print mr-1"></i> พิมพ์
    </button>
    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv mr-1"></i> ส่งออก CSV</a></li>
        <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf mr-1"></i> ส่งออก PDF</a></li>
    </ul>
</div>';

// Include header
include __DIR__ . '/templates/header.php';
?>

<!-- Filter Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ตัวกรองข้อมูล</h6>
    </div>
    <div class="card-body">
        <form id="filterForm" method="get" action="history.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="digit_type" class="form-label">ประเภทเลข</label>
                    <select name="digit_type" id="digit_type" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($digitTypeLabels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($filters['digit_type']) && $filters['digit_type'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="prediction_method" class="form-label">วิธีการทำนาย</label>
                    <select name="prediction_method" id="prediction_method" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($methodLabels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($filters['prediction_method']) && $filters['prediction_method'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">สถานะ</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($statusLabels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($filters['status']) && $filters['status'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?php echo isset($filters['start_date']) ? $filters['start_date'] : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo isset($filters['end_date']) ? $filters['end_date'] : ''; ?>">
                </div>
                
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> กรองข้อมูล
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">รีเซ็ต</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics Cards -->
<div class="row">
    <?php 
    // Total Predictions Card
    $title = "การทำนายทั้งหมด";
    $value = number_format($history['total_all_predictions']);
    $icon = "fas fa-calculator";
    $colorClass = "primary";
    $footerText = "จำนวนการทำนายทั้งหมด";
    $footerIcon = "fas fa-history";
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Correct Predictions Card
    $title = "ทำนายถูกต้อง";
    $value = number_format($accuracyStats['total_correct']);
    $icon = "fas fa-check-circle";
    $colorClass = "success";
    $footerText = number_format($accuracyStats['overall_accuracy'], 2) . "% ความแม่นยำเฉลี่ย";
    $footerIcon = "fas fa-bullseye";
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Accuracy Trend Card
    $title = "แนวโน้มความแม่นยำ";
    $trendValue = $accuracyStats['trend'] > 0 ? "เพิ่มขึ้น" : ($accuracyStats['trend'] < 0 ? "ลดลง" : "คงที่");
    $trendIcon = $accuracyStats['trend'] > 0 ? "fas fa-arrow-up" : ($accuracyStats['trend'] < 0 ? "fas fa-arrow-down" : "fas fa-equals");
    $value = $trendValue . " " . abs(number_format($accuracyStats['trend'], 2)) . "%";
    $icon = "fas fa-chart-line";
    $colorClass = $accuracyStats['trend'] > 0 ? "success" : ($accuracyStats['trend'] < 0 ? "danger" : "info");
    $footerText = "เทียบกับ 30 วันที่ผ่านมา";
    $footerIcon = $trendIcon;
    include __DIR__ . '/templates/components/stat_card.php';
    
    // Best Method Card
    $title = "วิธีที่แม่นยำที่สุด";
    $bestMethod = $accuracyStats['best_method'] ? $methodLabels[$accuracyStats['best_method']] : "ไม่มีข้อมูล";
    $value = $bestMethod;
    $icon = "fas fa-trophy";
    $colorClass = "warning";
    $footerText = $accuracyStats['best_method_accuracy'] ? number_format($accuracyStats['best_method_accuracy'], 2) . "% ความแม่นยำ" : "";
    $footerIcon = "fas fa-medal";
    include __DIR__ . '/templates/components/stat_card.php';
    ?>
</div>

<!-- Accuracy Chart -->
<div class="row">
    <div class="col-lg-12">
        <?php
        // Get accuracy history for chart
        $accuracyHistory = $accuracyTracker->getAccuracyHistory('monthly');
        
        if ($accuracyHistory['status'] === 'success' && count($accuracyHistory['history']) > 0) {
            $chartId = 'accuracyHistoryChart';
            $title = 'ประวัติความแม่นยำในการทำนาย';
            $description = 'แสดงความแม่นยำของการทำนายในแต่ละเดือนที่ผ่านมา';
            include __DIR__ . '/templates/components/chart_container.php';
            
            // Add JavaScript to create chart
            $inlineJs = "
                document.addEventListener('DOMContentLoaded', function() {
                    createAccuracyChart('accuracyHistoryChart', " . json_encode($accuracyHistory) . ");
                });
            ";
        }
        ?>
    </div>
</div>

<!-- History Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">ประวัติการทำนาย</h6>
        <span class="badge bg-primary"><?php echo number_format($totalRecords); ?> รายการ</span>
    </div>
    <div class="card-body">
        <?php if ($history['status'] === 'success' && count($history['predictions']) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>วันที่ทำนาย</th>
                            <th>งวดวันที่</th>
                            <th>ประเภทเลข</th>
                            <th>เลขที่ทำนาย</th>
                            <th>ความเชื่อมั่น</th>
                            <th>วิธีการทำนาย</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['predictions'] as $prediction): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($prediction['prediction_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($prediction['target_draw_date'])); ?></td>
                                <td>
                                    <?php echo isset($digitTypeLabels[$prediction['digit_type']]) ? 
                                        $digitTypeLabels[$prediction['digit_type']] : $prediction['digit_type']; ?>
                                </td>
                                <td class="font-weight-bold"><?php echo $prediction['predicted_digits']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php 
                                            echo $prediction['confidence'] >= 75 ? 'bg-success' : 
                                                ($prediction['confidence'] >= 50 ? 'bg-info' : 
                                                ($prediction['confidence'] >= 25 ? 'bg-warning' : 'bg-danger')); 
                                            ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $prediction['confidence']; ?>%" 
                                             aria-valuenow="<?php echo $prediction['confidence']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo number_format($prediction['confidence'], 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo isset($methodLabels[$prediction['prediction_type']]) ? 
                                        $methodLabels[$prediction['prediction_type']] : $prediction['prediction_type']; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($prediction['was_correct'] === null): ?>
                                        <span class="badge bg-secondary">รอผล</span>
                                    <?php elseif ($prediction['was_correct'] == 1): ?>
                                        <span class="badge bg-success">ถูกต้อง</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ไม่ถูกต้อง</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="predictions.php?digit_type=<?php echo $prediction['digit_type']; ?>&draw_date=<?php echo $prediction['target_draw_date']; ?>&prediction_method=<?php echo $prediction['prediction_type']; ?>" 
                                       class="btn btn-sm btn-primary" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : ''; ?><?php echo isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . 
                                (isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : '') . 
                                (isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : '') . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">1</a></li>';
                            
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . 
                                (isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : '') . 
                                (isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : '') . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">' . $i . '</a></li>';
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                            
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . 
                                (isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : '') . 
                                (isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : '') . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : ''; ?><?php echo isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : ''; ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-history fa-3x text-gray-300 mb-3"></i>
                <p class="text-muted">ไม่พบประวัติการทำนาย</p>
                <a href="predictions.php" class="btn btn-primary mt-3">
                    <i class="fas fa-magic me-1"></i> ทำนายตอนนี้
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Method Comparison -->
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">เปรียบเทียบประสิทธิภาพวิธีการทำนาย</h6>
            </div>
            <div class="card-body">
                <?php
                // Get method performance data
                $methodPerformance = $accuracyTracker->compareMethodsPerformance(180);
                
                if ($methodPerformance['status'] === 'success'):
                ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>วิธีการทำนาย</th>
                                    <th>จำนวนครั้งที่ใช้</th>
                                    <th>ถูกต้อง</th>
                                    <th>ความแม่นยำ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($methodPerformance['method_performance'] as $method => $data): ?>
                                <tr>
                                    <td>
                                        <?php echo isset($methodLabels[$method]) ? $methodLabels[$method] : $method; ?>
                                    </td>
                                    <td class="text-center"><?php echo $data['used']; ?></td>
                                    <td class="text-center"><?php echo $data['correct']; ?></td>
                                    <td>
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
                        <p class="text-muted">ไม่พบข้อมูลเพียงพอสำหรับการเปรียบเทียบ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">ประเภทเลขที่ทำนายแม่นยำที่สุด</h6>
            </div>
            <div class="card-body">
                <?php
                // Get digit type performance data
                $digitTypePerformance = $accuracyTracker->getDigitTypePerformance();
                
                if ($digitTypePerformance['status'] === 'success'):
                ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ประเภทเลข</th>
                                    <th>จำนวนการทำนาย</th>
                                    <th>ถูกต้อง</th>
                                    <th>ความแม่นยำ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($digitTypePerformance['digit_types'] as $type => $data): ?>
                                <tr>
                                    <td>
                                        <?php echo isset($digitTypeLabels[$type]) ? $digitTypeLabels[$type] : $type; ?>
                                    </td>
                                    <td class="text-center"><?php echo $data['total']; ?></td>
                                    <td class="text-center"><?php echo $data['correct']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $data['accuracy'] >= 60 ? 'bg-success' : ($data['accuracy'] >= 40 ? 'bg-info' : 'bg-warning'); ?>" 
                                                role="progressbar" 
                                                style="width: <?php echo $data['accuracy']; ?>%"
                                                aria-valuenow="<?php echo $data['accuracy']; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                <?php echo number_format($data['accuracy'], 1); ?>%
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
                        <p class="text-muted">ไม่พบข้อมูลเพียงพอสำหรับการเปรียบเทียบ</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Handle print button click
document.getElementById('printHistory')?.addEventListener('click', function() {
    window.print();
});

// Handle export CSV button click
document.getElementById('exportCSV')?.addEventListener('click', function() {
    window.location.href = 'export.php?type=history&format=csv<?php 
        echo isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : ''; 
        echo isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : ''; 
        echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; 
        echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; 
        echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; 
    ?>';
});

// Handle export PDF button click
document.getElementById('exportPDF')?.addEventListener('click', function() {
    window.location.href = 'export.php?type=history&format=pdf<?php 
        echo isset($_GET['digit_type']) ? '&digit_type=' . $_GET['digit_type'] : ''; 
        echo isset($_GET['prediction_method']) ? '&prediction_method=' . $_GET['prediction_method'] : ''; 
        echo isset($_GET['status']) ? '&status=' . $_GET['status'] : ''; 
        echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; 
        echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; 
    ?>';
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
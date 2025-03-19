
<?php
/**
 * statistics.php
 * 
 * Statistics page for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'สถิติ';
$activePage = 'statistics';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LotteryData.php';
require_once __DIR__ . '/../models/StatisticalAnalysis.php';

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);
$statAnalysis = new Models\StatisticalAnalysis($connection);

// Define additional CSS and JS
$additionalCss = ['public/css/charts.css'];
$additionalJs = ['public/js/statistics.js', 'assets/js/charts.js'];

// Check for filter parameters
$filters = [];
$activeTab = 'frequency';

if (isset($_GET['tab'])) {
    $activeTab = $_GET['tab'];
}

if (isset($_GET['field'])) {
    $filters['field'] = cleanInput($_GET['field']);
} else {
    $filters['field'] = 'first_prize_last3';
}

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $filters['start_date'] = cleanInput($_GET['start_date']);
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $filters['end_date'] = cleanInput($_GET['end_date']);
}

if (isset($_GET['day_of_week']) && !empty($_GET['day_of_week'])) {
    $filters['day_of_week'] = cleanInput($_GET['day_of_week']);
}

// Get field labels
$fieldLabels = [
    'first_prize_last3' => 'เลขท้าย 3 ตัว (รางวัลที่ 1)',
    'last3f' => 'เลขหน้า 3 ตัว',
    'last3b' => 'เลขท้าย 3 ตัว',
    'last2' => 'เลขท้าย 2 ตัว'
];

// Get Thai days of week
$thaiDays = [
    'จันทร์' => 'วันจันทร์',
    'อังคาร' => 'วันอังคาร',
    'พุธ' => 'วันพุธ',
    'พฤหัสบดี' => 'วันพฤหัสบดี',
    'ศุกร์' => 'วันศุกร์',
    'เสาร์' => 'วันเสาร์',
    'อาทิตย์' => 'วันอาทิตย์'
];

// Set up page actions
$pageActions = '
<div class="btn-group">
    <button type="button" id="printStatistics" class="btn btn-outline-primary btn-sm">
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
include __DIR__ . '/../templates/header.php';
?>

<!-- Filter Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ตัวกรองข้อมูล</h6>
    </div>
    <div class="card-body">
        <form id="filterForm" method="get" action="statistics.php">
            <input type="hidden" name="tab" value="<?php echo $activeTab; ?>">
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="field" class="form-label">ประเภทเลข</label>
                    <select name="field" id="field" class="form-control">
                        <?php foreach ($fieldLabels as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $filters['field'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="day_of_week" class="form-label">วันในสัปดาห์</label>
                    <select name="day_of_week" id="day_of_week" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($thaiDays as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($filters['day_of_week']) && $filters['day_of_week'] === $key ? 'selected' : ''; ?>>
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
            </div>
            
            <div class="row">
                <div class="col-md-12 text-end">
                    <button type="reset" class="btn btn-outline-secondary">รีเซ็ต</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-1"></i> กรองข้อมูล
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs statistics-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'frequency' ? 'active' : ''; ?>" href="#tab-frequency">
            <i class="fas fa-chart-bar mr-1"></i> ความถี่
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'positions' ? 'active' : ''; ?>" href="#tab-positions">
            <i class="fas fa-th mr-1"></i> ตำแหน่งตัวเลข
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'patterns' ? 'active' : ''; ?>" href="#tab-patterns">
            <i class="fas fa-shapes mr-1"></i> รูปแบบ
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'trends' ? 'active' : ''; ?>" href="#tab-trends">
            <i class="fas fa-chart-line mr-1"></i> แนวโน้ม
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'summary' ? 'active' : ''; ?>" href="#tab-summary">
            <i class="fas fa-clipboard-list mr-1"></i> สรุป
        </a>
    </li>
</ul>

<div class="statistics-content">
    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Frequency Tab -->
        <div class="tab-pane fade <?php echo $activeTab === 'frequency' ? 'show active' : ''; ?>" id="tab-frequency">
            <div class="row">
                <div class="col-lg-8">
                    <?php
                    // Get frequency distribution
                    $freqAnalysis = $statAnalysis->analyzeDigitFrequency($filters['field'], $filters);
                    
                    if ($freqAnalysis['status'] === 'success') {
                        $chartId = 'frequencyChart';
                        $title = 'การกระจายความถี่ - ' . $fieldLabels[$filters['field']];
                        $description = 'แสดงความถี่ของตัวเลขที่ออกในช่วงเวลาที่กำหนด';
                        include __DIR__ . '/../templates/components/chart_container.php';
                        
                        // Add JavaScript to create chart
                        $inlineJs = "
                            document.addEventListener('DOMContentLoaded', function() {
                                createFrequencyChart('frequencyChart', " . json_encode($freqAnalysis) . ");
                            });
                        ";
                    } else {
                        echo '<div class="alert alert-warning">' . $freqAnalysis['message'] . '</div>';
                    }
                    ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">ตัวเลขที่ออกบ่อยที่สุด</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($freqAnalysis['status'] === 'success'): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>เลข</th>
                                                <th>จำนวนครั้ง</th>
                                                <th>เปอร์เซ็นต์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $topDigits = array_slice($freqAnalysis['distribution'], 0, 10);
                                            foreach ($topDigits as $item):
                                            ?>
                                            <tr>
                                                <td class="font-weight-bold"><?php echo $item['digit']; ?></td>
                                                <td class="text-center"><?php echo $item['count']; ?></td>
                                                <td class="text-center"><?php echo number_format($item['percentage'], 2); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-muted small mt-2">
                                    ข้อมูลทั้งหมด: <?php echo $freqAnalysis['total_count']; ?> รายการ
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-bar fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted"><?php echo $freqAnalysis['message']; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Positions Tab -->
        <div class="tab-pane fade <?php echo $activeTab === 'positions' ? 'show active' : ''; ?>" id="tab-positions">
            <div class="row">
                <div class="col-lg-12">
                    <?php
                    // Check if this is a digit type with positions
                    if (in_array($filters['field'], ['first_prize_last3', 'last3f', 'last3b', 'last2'])) {
                        // Get position frequency
                        $positionAnalysis = $statAnalysis->analyzeDigitPositionFrequency($filters['field'], $filters);
                        
                        if ($positionAnalysis['status'] === 'success') {
                            $chartId = 'positionHeatmap';
                            $title = 'ความถี่ของตัวเลขในแต่ละตำแหน่ง - ' . $fieldLabels[$filters['field']];
                            $description = 'แสดงความถี่ของตัวเลข 0-9 ในแต่ละตำแหน่งของ' . $fieldLabels[$filters['field']];
                            include __DIR__ . '/../templates/components/chart_container.php';
                            
                            // Add JavaScript to create heatmap
                            $inlineJs = "
                                document.addEventListener('DOMContentLoaded', function() {
                                    createPositionHeatmap('positionHeatmap', " . json_encode($positionAnalysis) . ");
                                });
                            ";
                        } else {
                            echo '<div class="alert alert-warning">' . $positionAnalysis['message'] . '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">ประเภทเลขนี้ไม่สามารถวิเคราะห์ตำแหน่งตัวเลขได้</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Patterns Tab -->
        <div class="tab-pane fade <?php echo $activeTab === 'patterns' ? 'show active' : ''; ?>" id="tab-patterns">
            <div class="row">
                <div class="col-lg-8">
                    <?php
                    // Get recurring patterns
                    $patternAnalysis = $statAnalysis->identifyRecurringPatterns($filters['field'], 100);
                    
                    if ($patternAnalysis['status'] === 'success' && count($patternAnalysis['patterns']) > 0) {
                        $chartId = 'patternChart';
                        $title = 'รูปแบบที่พบบ่อย - ' . $fieldLabels[$filters['field']];
                        $description = 'แสดงรูปแบบการออกที่พบบ่อยในช่วง 100 งวดย้อนหลัง';
                        include __DIR__ . '/../templates/components/chart_container.php';
                        
                        // Add JavaScript to create pattern chart
                        $inlineJs = "
                            document.addEventListener('DOMContentLoaded', function() {
                                createPatternChart('patternChart', " . json_encode($patternAnalysis) . ");
                            });
                        ";
                    } else {
                        echo '<div class="alert alert-warning">ไม่พบรูปแบบที่น่าสนใจ หรือข้อมูลไม่เพียงพอ</div>';
                    }
                    ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">รูปแบบที่พบบ่อย</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($patternAnalysis['status'] === 'success' && count($patternAnalysis['patterns']) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>รูปแบบ</th>
                                                <th>จำนวนที่พบ</th>
                                                <th>ล่าสุด</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $topPatterns = array_slice($patternAnalysis['patterns'], 0, 10, true);
                                            foreach ($topPatterns as $patternStr => $patternData):
                                            ?>
                                            <tr>
                                                <td class="font-weight-bold"><?php echo implode('-', $patternData['pattern']); ?></td>
                                                <td class="text-center"><?php echo $patternData['occurrences']; ?></td>
                                                <td class="text-center">
                                                    <?php 
                                                    $lastDate = isset($patternData['dates']) && count($patternData['dates']) > 0 ? 
                                                    $patternData['dates'][count($patternData['dates']) - 1] : '';
                                                    echo $lastDate ? formatThaiDisplayDate($lastDate) : '-';
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-muted small mt-2">
                                    วิเคราะห์จาก: <?php echo $patternAnalysis['analyzed_period']; ?> งวด
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shapes fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted">ไม่พบรูปแบบที่น่าสนใจ หรือข้อมูลไม่เพียงพอ</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Trends Tab -->
        <div class="tab-pane fade <?php echo $activeTab === 'trends' ? 'show active' : ''; ?>" id="tab-trends">
            <div class="row">
                <div class="col-lg-8">
                    <?php
                    // Get digit trend analysis
                    $trendAnalysis = $statAnalysis->getDigitTrendAnalysis($filters['field'], 50);
                    
                    if ($trendAnalysis['status'] === 'success') {
                        $chartId = 'trendChart';
                        $title = 'แนวโน้มการออก - ' . $fieldLabels[$filters['field']];
                        $description = 'แสดงแนวโน้มเพิ่มขึ้น/ลดลงของตัวเลขที่ออกในช่วง 50 งวดที่ผ่านมา';
                        include __DIR__ . '/../templates/components/chart_container.php';
                        
                        // Add JavaScript to create trend chart
                        $inlineJs = "
                            document.addEventListener('DOMContentLoaded', function() {
                                createTrendLineChart('trendChart', " . json_encode($trendAnalysis) . ");
                            });
                        ";
                    } else {
                        echo '<div class="alert alert-warning">' . $trendAnalysis['message'] . '</div>';
                    }
                    ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">แนวโน้มที่น่าสนใจ</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($trendAnalysis['status'] === 'success'): ?>
                                <h5 class="border-bottom pb-2">แนวโน้มเพิ่มขึ้น</h5>
                                <?php if (count($trendAnalysis['trends']['increasing']) > 0): ?>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ช่วงวันที่</th>
                                                    <th>การเปลี่ยนแปลง</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $topIncreasing = array_slice($trendAnalysis['trends']['increasing'], 0, 3);
                                                foreach ($topIncreasing as $trend):
                                                    $startValue = isset($trend['values'][0]) ? $trend['values'][0] : '';
                                                    $endValue = isset($trend['values'][count($trend['values']) - 1]) ? $trend['values'][count($trend['values']) - 1] : '';
                                                    $change = $endValue - $startValue;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo formatThaiDisplayDate($trend['start_date']); ?> ถึง 
                                                        <?php echo formatThaiDisplayDate($trend['end_date']); ?>
                                                    </td>
                                                    <td class="text-success">
                                                        <?php echo $startValue; ?> <i class="fas fa-arrow-right"></i> <?php echo $endValue; ?> 
                                                        (+<?php echo $change; ?>)
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">ไม่พบแนวโน้มเพิ่มขึ้นที่น่าสนใจ</p>
                                <?php endif; ?>
                                
                                <h5 class="border-bottom pb-2">แนวโน้มลดลง</h5>
                                <?php if (count($trendAnalysis['trends']['decreasing']) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ช่วงวันที่</th>
                                                    <th>การเปลี่ยนแปลง</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $topDecreasing = array_slice($trendAnalysis['trends']['decreasing'], 0, 3);
                                                foreach ($topDecreasing as $trend):
                                                    $startValue = isset($trend['values'][0]) ? $trend['values'][0] : '';
                                                    $endValue = isset($trend['values'][count($trend['values']) - 1]) ? $trend['values'][count($trend['values']) - 1] : '';
                                                    $change = $startValue - $endValue;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo formatThaiDisplayDate($trend['start_date']); ?> ถึง 
                                                        <?php echo formatThaiDisplayDate($trend['end_date']); ?>
                                                    </td>
                                                    <td class="text-danger">
                                                        <?php echo $startValue; ?> <i class="fas fa-arrow-right"></i> <?php echo $endValue; ?> 
                                                        (-<?php echo $change; ?>)
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">ไม่พบแนวโน้มลดลงที่น่าสนใจ</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-line fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-muted">ไม่สามารถวิเคราะห์แนวโน้มได้</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary Tab -->
        <div class="tab-pane fade <?php echo $activeTab === 'summary' ? 'show active' : ''; ?>" id="tab-summary">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">สรุปสถิติพื้นฐาน</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get summary statistics
                            $summaryStats = $statAnalysis->calculateSummaryStatistics($filters['field']);
                            
                            if ($summaryStats['status'] === 'success'):
                            ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                    <tr>
                                            <th>จำนวนข้อมูล</th>
                                            <td><?php echo number_format($summaryStats['count']); ?> รายการ</td>
                                        </tr>
                                        <tr>
                                            <th>ค่าเฉลี่ย (Mean)</th>
                                            <td><?php echo number_format($summaryStats['mean'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ค่ากลาง (Median)</th>
                                            <td><?php echo number_format($summaryStats['median'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ค่าฐานนิยม (Mode)</th>
                                            <td><?php echo $summaryStats['mode']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>ส่วนเบี่ยงเบนมาตรฐาน</th>
                                            <td><?php echo number_format($summaryStats['std_dev'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ค่าต่ำสุด</th>
                                            <td><?php echo $summaryStats['min']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>ค่าสูงสุด</th>
                                            <td><?php echo $summaryStats['max']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>พิสัย (Range)</th>
                                            <td><?php echo $summaryStats['range']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning"><?php echo $summaryStats['message']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">สรุปการวิเคราะห์</h6>
                        </div>
                        <div class="card-body">
                            <ul class="analysis-summary">
                                <?php if ($freqAnalysis['status'] === 'success'): ?>
                                    <?php
                                    $topDigit = array_keys($freqAnalysis['distribution'])[0] ?? 'ไม่พบข้อมูล';
                                    $topPercentage = isset($freqAnalysis['distribution'][$topDigit]) ? 
                                        $freqAnalysis['distribution'][$topDigit]['percentage'] : 0;
                                    ?>
                                    <li>
                                        <strong>เลขที่ออกบ่อยที่สุด:</strong> 
                                        <?php echo $topDigit; ?> (<?php echo number_format($topPercentage, 2); ?>%)
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($patternAnalysis['status'] === 'success' && count($patternAnalysis['patterns']) > 0): ?>
                                    <?php
                                    $topPattern = array_keys($patternAnalysis['patterns'])[0] ?? '';
                                    $pattern = $patternAnalysis['patterns'][$topPattern]['pattern'] ?? [];
                                    $patternStr = implode('-', $pattern);
                                    $occurrences = $patternAnalysis['patterns'][$topPattern]['occurrences'] ?? 0;
                                    ?>
                                    <li>
                                        <strong>รูปแบบที่พบบ่อยที่สุด:</strong> 
                                        <?php echo $patternStr; ?> (<?php echo $occurrences; ?> ครั้ง)
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($trendAnalysis['status'] === 'success'): ?>
                                    <?php
                                    $increasingCount = count($trendAnalysis['trends']['increasing']);
                                    $decreasingCount = count($trendAnalysis['trends']['decreasing']);
                                    $trendDirection = $increasingCount > $decreasingCount ? 'เพิ่มขึ้น' : 'ลดลง';
                                    ?>
                                    <li>
                                        <strong>แนวโน้มโดยรวม:</strong> 
                                        <?php echo $trendDirection; ?>
                                        (แนวโน้มเพิ่มขึ้น: <?php echo $increasingCount; ?>, 
                                        แนวโน้มลดลง: <?php echo $decreasingCount; ?>)
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($positionAnalysis) && $positionAnalysis['status'] === 'success'): ?>
                                    <li>
                                        <strong>ตำแหน่งตัวเลข:</strong>
                                        <ul class="position-summary">
                                            <?php 
                                            $positions = $positionAnalysis['positions'];
                                            for ($i = 0; $i < $positions; $i++):
                                                $positionData = $positionAnalysis['percentages'][$i];
                                                arsort($positionData);
                                                $topDigitInPosition = key($positionData);
                                                $topPercentageInPosition = current($positionData);
                                            ?>
                                            <li>
                                                ตำแหน่งที่ <?php echo $i + 1; ?>: เลข <?php echo $topDigitInPosition; ?> 
                                                (<?php echo number_format($topPercentageInPosition, 2); ?>%)
                                            </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
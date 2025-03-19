<?php
/**
 * results.php
 * 
 * Lottery Results page for the Thai Lottery Analysis system
 */

// Set page title and active menu
$pageTitle = 'ผลรางวัล';
$activePage = 'results';

// Include functions and models
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/LotteryData.php';

// Initialize models
$connection = connectDatabase();
$lotteryData = new Models\LotteryData($connection);

// Function to convert day display name to database value
function convertDayOfWeek($displayDayName) {
    // ตัวช่วยแปลงจากชื่อเต็มเป็นชื่อย่อในฐานข้อมูล
    $dayMapping = [
        'วันจันทร์' => 'จันทร์',
        'วันอังคาร' => 'อังคาร',
        'วันพุธ' => 'พุธ', 
        'วันพฤหัสบดี' => 'พฤหัสบดี',
        'วันศุกร์' => 'ศุกร์',
        'วันเสาร์' => 'เสาร์',
        'วันอาทิตย์' => 'อาทิตย์'
    ];
    
    // ถ้ามีการแม็ปไว้ในตาราง ให้ใช้ค่าที่แม็ปไว้
    if (isset($dayMapping[$displayDayName])) {
        return $dayMapping[$displayDayName];
    }
    
    // ถ้าไม่มีในตาราง แต่ขึ้นต้นด้วย "วัน" ให้ตัด "วัน" ออก
    if (mb_substr($displayDayName, 0, 3, 'UTF-8') === 'วัน') {
        return mb_substr($displayDayName, 3, null, 'UTF-8');
    }
    
    // ถ้าไม่ตรงกับเงื่อนไขไหนเลย ส่งค่าเดิมกลับไป
    return $displayDayName;
}

// Handle pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // Results per page
$offset = ($page - 1) * $limit;

// Handle filters
$filters = [
    'limit' => $limit,
    'offset' => $offset,
    'order_by' => 'dateValue DESC'
];

// Apply additional filters if provided
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $filters['start_date'] = cleanInput($_GET['start_date']);
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $filters['end_date'] = cleanInput($_GET['end_date']);
}

if (isset($_GET['day_of_week']) && !empty($_GET['day_of_week'])) {
    // ใช้ตัวแปรจาก $_GET โดยตรงเพื่อหลีกเลี่ยงปัญหาสระหาย
    $dayOfWeek = $_GET['day_of_week'];
    $filters['day_of_week'] = convertDayOfWeek($dayOfWeek);
}

// Get results
$resultsData = $lotteryData->getRecords($filters);

// Count filtered records for pagination
$countFilters = $filters;
unset($countFilters['limit'], $countFilters['offset']); // Remove limit and offset for counting
$countFilters['count_only'] = true; // Add flag to get count only
$totalRecords = $lotteryData->getRecordCount($countFilters);
$totalPages = ceil($totalRecords / $limit);

// Thai days of week with display names
$thaiDays = [
    '' => 'ทั้งหมด',
    'จันทร์' => 'วันจันทร์',
    'อังคาร' => 'วันอังคาร',
    'พุธ' => 'วันพุธ',
    'พฤหัสบดี' => 'วันพฤหัสบดี',
    'ศุกร์' => 'วันศุกร์',
    'เสาร์' => 'วันเสาร์',
    'อาทิตย์' => 'วันอาทิตย์'
];

// Additional CSS and JS
$additionalCss = ['public/css/charts.css'];
$additionalJs = ['public/js/main.js'];

// Include header
include __DIR__ . '/../templates/header.php';
?>

<!-- Filter Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">ตัวกรองข้อมูล</h6>
    </div>
    <div class="card-body">
        <form id="filterForm" method="get" action="results.php">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="day_of_week" class="form-label">วันในสัปดาห์</label>
                    <select name="day_of_week" id="day_of_week" class="form-control">
                        <?php foreach ($thaiDays as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($_GET['day_of_week']) && convertDayOfWeek($_GET['day_of_week']) === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> กรองข้อมูล
                    </button>
                    <button type="button" id="resetFilter" class="btn btn-outline-secondary">รีเซ็ต</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lottery Results Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">ผลรางวัลสลากกินแบ่งรัฐบาล</h6>
        <span class="badge bg-primary"><?php echo number_format($totalRecords); ?> รายการ</span>
    </div>
    <div class="card-body">
        <?php if ($resultsData['status'] === 'success' && !empty($resultsData['records'])): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>งวดวันที่</th>
                            <th>วันในสัปดาห์</th>
                            <th>รางวัลที่ 1</th>
                            <th>เลขท้าย 3 ตัว</th>
                            <th>เลขหน้า 3 ตัว</th>
                            <th>เลขท้าย 2 ตัว</th>
                            <th>ใกล้เคียงรางวัลที่ 1</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultsData['records'] as $result): ?>
                            <tr>
                                <td><?php echo formatThaiDisplayDate($result['dateValue']); ?></td>
                                <td><?php echo $result['day_of_week']; ?></td>
                                <td class="text-center font-weight-bold"><?php echo $result['first_prize']; ?></td>
                                <td class="text-center"><?php echo $result['first_prize_last3']; ?></td>
                                <td class="text-center"><?php echo $result['last3f']; ?></td>
                                <td class="text-center"><?php echo $result['last2']; ?></td>
                                <td class="text-center">
                                    <?php echo $result['near1_1'] . ', ' . $result['near1_2']; ?>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php 
                                echo isset($_GET['day_of_week']) ? '&day_of_week=' . urlencode($_GET['day_of_week']) : ''; 
                                echo isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : ''; 
                                echo isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : ''; 
                            ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . urlencode($_GET['day_of_week']) : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') . 
                                '">1</a></li>';
                            
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . urlencode($_GET['day_of_week']) : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') . 
                                '">' . $i . '</a></li>';
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                            
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . urlencode($_GET['day_of_week']) : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') . 
                                '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php 
                                echo isset($_GET['day_of_week']) ? '&day_of_week=' . urlencode($_GET['day_of_week']) : ''; 
                                echo isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : ''; 
                                echo isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : ''; 
                            ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-ticket-alt fa-3x text-gray-300 mb-3"></i>
                <p class="text-muted">ไม่พบข้อมูลผลรางวัล</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics Summary -->
<div class="row">
    <?php 
    // Total Records Card
    $title = "ข้อมูลทั้งหมด";
    $value = number_format($totalRecords);
    $icon = "fas fa-database";
    $colorClass = "primary";
    $footerText = "จำนวนผลรางวัลที่เก็บบันทึก";
    $footerIcon = "fas fa-ticket-alt";
    include __DIR__ . '/../templates/components/stat_card.php';
    
    // Earliest Record Card
    $earliestRecord = $lotteryData->getRecords(['limit' => 1, 'order_by' => 'dateValue ASC'])['records'][0] ?? null;
    $title = "งวดแรกสุด";
    $value = $earliestRecord ? formatThaiDisplayDate($earliestRecord['dateValue']) : '-';
    $icon = "fas fa-calendar-check";
    $colorClass = "success";
    $footerText = "งวดแรกที่บันทึก";
    $footerIcon = "fas fa-history";
    include __DIR__ . '/../templates/components/stat_card.php';
    
    // Most Recent Record Card
    $latestRecord = $lotteryData->getRecords(['limit' => 1, 'order_by' => 'dateValue DESC'])['records'][0] ?? null;
    $title = "งวดล่าสุด";
    $value = $latestRecord ? formatThaiDisplayDate($latestRecord['dateValue']) : '-';
    $icon = "fas fa-calendar-alt";
    $colorClass = "info";
    $footerText = "งวดล่าสุดที่บันทึก";
    $footerIcon = "fas fa-calendar-day";
    include __DIR__ . '/../templates/components/stat_card.php';
    
    // Next Draw Date Card
    $nextDrawDate = $lotteryData->getNextDrawDate();
    $title = "งวดถัดไป";
    $value = formatThaiDisplayDate($nextDrawDate);
    $icon = "fas fa-calendar-plus";
    $colorClass = "warning";
    $footerText = getThaiDayOfWeek(new DateTime($nextDrawDate));
    $footerIcon = "fas fa-calendar-day";
    include __DIR__ . '/../templates/components/stat_card.php';
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form reset button
    document.getElementById('resetFilter').addEventListener('click', function() {
        // Clear all form inputs
        document.getElementById('day_of_week').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        
        // Submit the form
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php
// Add the getRecordCount method to LotteryData.php model
// This method should be added to models/LotteryData.php

/*
public function getRecordCount($filters = [])
{
    $sql = "SELECT COUNT(*) as count FROM lotto_records WHERE 1=1";
    
    // Apply filters
    if (!empty($filters['day_of_week'])) {
        $sql .= " AND day_of_week = '" . $this->conn->real_escape_string($filters['day_of_week']) . "'";
    }

    if (!empty($filters['start_date'])) {
        $sql .= " AND dateValue >= '" . $this->conn->real_escape_string($filters['start_date']) . "'";
    }

    if (!empty($filters['end_date'])) {
        $sql .= " AND dateValue <= '" . $this->conn->real_escape_string($filters['end_date']) . "'";
    }
    
    $result = $this->conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return intval($row['count']);
    }
    
    return 0;
}
*/

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
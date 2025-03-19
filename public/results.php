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
    $filters['day_of_week'] = cleanInput($_GET['day_of_week']);
}

// Get results
$resultsData = $lotteryData->getRecords($filters);

// Get total records for pagination
$totalRecords = $lotteryData->getTotalRecords();
$totalPages = ceil($totalRecords / $limit);

// Thai days of week
$thaiDays = [
    'จันทร์' => 'วันจันทร์',
    'อังคาร' => 'วันอังคาร',
    'พุธ' => 'วันพุธ',
    'พฤหัสบดี' => 'วันพฤหัสบดี',
    'ศุกร์' => 'วันศุกร์',
    'เสาร์' => 'วันเสาร์',
    'อาทิตย์' => 'วันอาทิตย์'
];

// Set page actions
$pageActions = '
<div class="btn-group">
    <button type="button" id="printResults" class="btn btn-outline-primary btn-sm">
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
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($thaiDays as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo isset($_GET['day_of_week']) && $_GET['day_of_week'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" id="start_date" class="form-control datepicker" 
                           value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date" class="form-control datepicker" 
                           value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> กรองข้อมูล
                    </button>
                    <button type="reset" class="btn btn-outline-secondary">รีเซ็ต</button>
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
                                echo isset($_GET['day_of_week']) ? '&day_of_week=' . $_GET['day_of_week'] : ''; 
                                echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; 
                                echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; 
                            ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . $_GET['day_of_week'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">1</a></li>';
                            
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . $_GET['day_of_week'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">' . $i . '</a></li>';
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><a class="page-link">...</a></li>';
                            }
                            
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . 
                                (isset($_GET['day_of_week']) ? '&day_of_week=' . $_GET['day_of_week'] : '') . 
                                (isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : '') . 
                                (isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '') . 
                                '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php 
                                echo isset($_GET['day_of_week']) ? '&day_of_week=' . $_GET['day_of_week'] : ''; 
                                echo isset($_GET['start_date']) ? '&start_date=' . $_GET['start_date'] : ''; 
                                echo isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : ''; 
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
    // Handle print button
    document.getElementById('printResults')?.addEventListener('click', function() {
        window.print();
    });

    // Handle CSV export
    document.getElementById('exportCSV')?.addEventListener('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('export', 'csv');
        window.location.href = url.toString();
    });

    // Handle PDF export
    document.getElementById('exportPDF')?.addEventListener('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('export', 'pdf');
        window.location.href = url.toString();
    });

    // Handle form reset
    document.querySelector('#filterForm button[type="reset"]')?.addEventListener('click', function() {
        // Clear input fields
        document.getElementById('day_of_week').selectedIndex = 0;
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';

        // Optional: Trigger form submission to reset results
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php 
// Handle export functionality
if (isset($_GET['export'])) {
    $exportType = cleanInput($_GET['export']);
    
    // Remove export parameter from filters
    unset($_GET['export']);
    
    // Get all records for export (remove pagination)
    $exportFilters = $filters;
    unset($exportFilters['limit'], $exportFilters['offset']);
    $exportData = $lotteryData->getRecords($exportFilters);
    
    if ($exportData['status'] === 'success') {
        $records = $exportData['records'];
        
        if ($exportType === 'csv') {
            // CSV Export
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="lottery_results_' . date('YmdHis') . '.csv"');
            
            // Open output stream
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, [
                'วันที่', 
                'วันในสัปดาห์', 
                'รางวัลที่ 1', 
                'เลขท้าย 3 ตัว', 
                'เลขหน้า 3 ตัว', 
                'เลขท้าย 2 ตัว', 
                'ใกล้เคียงรางวัลที่ 1'
            ]);
            
            // Output rows
            foreach ($records as $record) {
                fputcsv($output, [
                    formatThaiDisplayDate($record['dateValue']),
                    $record['day_of_week'],
                    $record['first_prize'],
                    $record['first_prize_last3'],
                    $record['last3f'],
                    $record['last2'],
                    $record['near1_1'] . ', ' . $record['near1_2']
                ]);
            }
            
            fclose($output);
            exit;
        } elseif ($exportType === 'pdf') {
            // PDF Export using TCPDF or similar library
            require_once __DIR__ . '/../vendor/autoload.php';
            
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('ผลรางวัลสลากกินแบ่งรัฐบาล');
            $pdf->SetSubject('ผลรางวัลสลากกินแบ่งรัฐบาล');
            
            // Set default header data
            $pdf->SetHeaderData('', 0, 'ผลรางวัลสลากกินแบ่งรัฐบาล', date('Y-m-d H:i:s'));
            
            // Set header and footer fonts
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
            
            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            
            // Set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            
            // Add a page
            $pdf->AddPage('L'); // Landscape orientation
            
            // Set font
            $pdf->SetFont('thsarabun', '', 12);
            
            // Create table HTML
            $html = '<table border="1" cellpadding="4">
                <thead>
                    <tr style="background-color:#f1f1f1;">
                        <th>วันที่</th>
                        <th>วันในสัปดาห์</th>
                        <th>รางวัลที่ 1</th>
                        <th>เลขท้าย 3 ตัว</th>
                        <th>เลขหน้า 3 ตัว</th>
                        <th>เลขท้าย 2 ตัว</th>
                        <th>ใกล้เคียงรางวัลที่ 1</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($records as $record) {
                $html .= '<tr>
                    <td>' . formatThaiDisplayDate($record['dateValue']) . '</td>
                    <td>' . $record['day_of_week'] . '</td>
                    <td>' . $record['first_prize'] . '</td>
                    <td>' . $record['first_prize_last3'] . '</td>
                    <td>' . $record['last3f'] . '</td>
                    <td>' . $record['last2'] . '</td>
                    <td>' . $record['near1_1'] . ', ' . $record['near1_2'] . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
            
            // Print text using writeHTMLCell()
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Close and output PDF document
            $pdf->Output('lottery_results_' . date('YmdHis') . '.pdf', 'D');
            exit;
        }
    }
}

// Include footer
include __DIR__ . '/../templates/footer.php';
?>
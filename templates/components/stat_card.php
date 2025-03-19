<?php
/**
 * stat_card.php
 * 
 * Component for displaying statistical cards in dashboard
 * 
 * @param string $title Card title
 * @param string $value Card value
 * @param string $icon Card icon (Font Awesome class)
 * @param string $colorClass Card color class (primary, success, info, warning, danger)
 * @param string $footerText Optional footer text
 * @param string $footerIcon Optional footer icon (Font Awesome class)
 */

// Validate required variables
if (!isset($title)) {
    $title = 'ข้อมูลสถิติ';
}

if (!isset($value)) {
    $value = '-';
}

// Set default values for optional variables
$icon = $icon ?? 'fas fa-calendar';
$colorClass = $colorClass ?? 'primary';
?>

<div class="col-xl-3 col-md-6 mb-4">
    <div class="card border-left-<?php echo $colorClass; ?> shadow h-100 py-2">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-<?php echo $colorClass; ?> text-uppercase mb-1">
                        <?php echo htmlspecialchars($title); ?>
                    </div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo htmlspecialchars($value); ?>
                    </div>
                </div>
                <div class="col-auto">
                    <i class="<?php echo $icon; ?> fa-2x text-gray-300"></i>
                </div>
            </div>
            
            <?php if (isset($footerText)): ?>
                <div class="card-footer bg-transparent border-top-0 mt-3 px-0 pb-0">
                    <div class="text-xs text-muted">
                        <?php if (isset($footerIcon)): ?>
                            <i class="<?php echo $footerIcon; ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($footerText); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
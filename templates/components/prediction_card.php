
<?php
/**
 * prediction_card.php
 * 
 * Component for displaying prediction cards
 * 
 * @param array $prediction Prediction data (digit, confidence, etc.)
 * @param string $digitType Type of digits (first_prize_last3, last3f, last3b, last2)
 * @param bool $wasCorrect Whether the prediction was correct (for historical predictions)
 * @param int $rank The rank of the prediction (optional)
 */

// Set default was_correct value if not provided
$wasCorrect = isset($wasCorrect) ? $wasCorrect : null;

$digitType = isset($_GET['digit_type']) ? $_GET['digit_type'] : 'last3b'; // ค่าเริ่มต้น

// Set digit type label
$digitTypeLabels = [
    'first_prize_last3' => 'เลขท้าย 3 ตัว (รางวัลที่ 1)',
    'last3f' => 'เลขหน้า 3 ตัว',
    'last3b' => 'เลขท้าย 3 ตัว',
    'last2' => 'เลขท้าย 2 ตัว'
];

$digitTypeLabel = isset($digitTypeLabels[$digitType]) ? $digitTypeLabels[$digitType] : $digitType;

// Set color class based on confidence
$colorClass = 'primary';
if (isset($prediction['confidence'])) {
    if ($prediction['confidence'] >= 80) {
        $colorClass = 'success';
    } elseif ($prediction['confidence'] >= 60) {
        $colorClass = 'info';
    } elseif ($prediction['confidence'] >= 40) {
        $colorClass = 'warning';
    } else {
        $colorClass = 'danger';
    }
}

// Override color if prediction was evaluated
if ($wasCorrect === true) {
    $colorClass = 'success';
} elseif ($wasCorrect === false) {
    $colorClass = 'danger';
}
?>

<div class="prediction-card card border-<?php echo $colorClass; ?> mb-3">
    <div class="card-header bg-<?php echo $colorClass; ?> text-white d-flex justify-content-between align-items-center">
        <h5 class="m-0">
            <?php if (isset($rank)): ?>
                <span class="badge bg-white text-<?php echo $colorClass; ?> me-2"><?php echo $rank; ?></span>
            <?php endif; ?>
            <?php echo $digitTypeLabel; ?>
        </h5>
        <?php if ($wasCorrect !== null): ?>
            <span class="badge bg-white text-<?php echo $colorClass; ?>">
                <?php if ($wasCorrect): ?>
                    <i class="fas fa-check me-1"></i> ถูกต้อง
                <?php else: ?>
                    <i class="fas fa-times me-1"></i> ไม่ถูกต้อง
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 text-center">
                <h1 class="display-6 prediction-digit"><?php echo $prediction['digit']; ?></h1>
                <p class="text-muted mb-0">เลขที่ทำนาย</p>
            </div>
            
            <div class="col-md-6">
                <?php if (isset($prediction['confidence'])): ?>
                    <h5 class="text-center mb-3">ความเชื่อมั่น</h5>
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-<?php echo $colorClass; ?>" role="progressbar" 
                             style="width: <?php echo $prediction['confidence']; ?>%;" 
                             aria-valuenow="<?php echo $prediction['confidence']; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo $prediction['confidence']; ?>%
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($prediction['prediction_date'])): ?>
                    <p class="text-muted text-center mt-3">
                        <i class="far fa-calendar-alt me-1"></i> 
                        ทำนายเมื่อ: <?php echo date('d/m/Y H:i', strtotime($prediction['prediction_date'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (isset($prediction['target_draw_date'])): ?>
        <div class="card-footer bg-light">
            <small class="text-muted">
                <i class="far fa-calendar-check me-1"></i> 
                งวดวันที่: <?php echo date('d/m/Y', strtotime($prediction['target_draw_date'])); ?>
            </small>
        </div>
    <?php endif; ?>
</div>
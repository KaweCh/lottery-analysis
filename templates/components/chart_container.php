
<?php
/**
 * chart_container.php
 * 
 * Component for displaying charts
 * 
 * @param string $title Chart title
 * @param string $id Chart ID (must be unique)
 * @param string $description Optional description
 * @param string $content Optional HTML content to include before the chart
 * @param bool $collapsible Whether the chart is collapsible (default: true)
 */
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo $title; ?></h6>
        
        <?php if (isset($collapsible) && $collapsible): ?>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenu<?php echo $id; ?>"
                   data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenu<?php echo $id; ?>">
                    <div class="dropdown-header">ตัวเลือก:</div>
                    <a class="dropdown-item" href="#" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $id; ?>">
                        <i class="fas fa-compress fa-sm fa-fw me-2 text-gray-400"></i>
                        ซ่อน/แสดง
                    </a>
                    <a class="dropdown-item" href="#" onclick="downloadChart('<?php echo $id; ?>')">
                        <i class="fas fa-download fa-sm fa-fw me-2 text-gray-400"></i>
                        ดาวน์โหลด
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="<?php echo (isset($collapsible) && $collapsible) ? 'collapse show' : ''; ?>" id="collapse<?php echo $id; ?>">
        <div class="card-body">
            <?php if (isset($description)): ?>
                <p class="mb-4"><?php echo $description; ?></p>
            <?php endif; ?>
            
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php endif; ?>
            
            <div class="chart-area">
                <canvas id="<?php echo $id; ?>"></canvas>
            </div>
        </div>
    </div>
</div>

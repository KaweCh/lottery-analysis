<?php
/**
 * footer.php
 * 
 * Footer template for the Thai Lottery Analysis system
 */
?>

    </div><!-- End Main Container -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo getConfig('site_title'); ?> - 
                        ระบบวิเคราะห์และทำนายผลรางวัลสลากกินแบ่งรัฐบาล
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="" class="text-muted me-3">
                        <i class="fas fa-info-circle me-1"></i> เกี่ยวกับเรา
                    </a>
                    <a href="" class="text-muted me-3">
                        <i class="fas fa-envelope me-1"></i> ติดต่อ
                    </a>
                    <a href="" class="text-muted">
                        <i class="fas fa-lock me-1"></i> นโยบายความเป็นส่วนตัว
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for some legacy components) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo getConfig('base_url'); ?>public/js/main.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additionalJs)): ?>
        <?php foreach ($additionalJs as $js): ?>
            <script src="<?php echo getConfig('base_url') . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inlineJs)): ?>
        <script>
            <?php echo $inlineJs; ?>
        </script>
    <?php endif; ?>
</body>
</html>
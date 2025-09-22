<?php
// includes/footer.php - Common footer template
?>
    
    <!-- JavaScript Files -->
    <script src="assets/js/main.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="assets/js/<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional footer content -->
    <?php if (isset($additional_footer)): ?>
        <?php echo $additional_footer; ?>
    <?php endif; ?>
    
</body>
</html>

<?php
/**
 * Footer Template
 * Engineering Department Management System
 */
?>

    <!-- Bootstrap 5 JS Bundle & Main JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js?v=<?= file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : time() ?>"></script>

    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?= APP_URL . '/' . $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Initialize Lucide icons if present
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>

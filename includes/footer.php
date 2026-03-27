<?php
/**
 * FOOTER INCLUDE - Reusable Footer Component
 * 
 * This file provides:
 * - Consistent footer across all pages
 * - Dynamic links based on user status
 * - Copyright information
 * 
 * Usage: include('includes/footer.php');
 */
?>

    <!-- FOOTER SECTION -->
    <footer>
        <div class="container text-center">
            <div class="d-flex justify-content-center gap-4 mb-4">
                <a href="<?php echo $base_path ?? ''; ?>index.php" class="footer-link">Home</a>
                <a href="<?php echo $base_path ?? ''; ?>terms.php" class="footer-link">Privacy Policy</a>
                <a href="<?php echo $base_path ?? ''; ?>terms.php" class="footer-link">Terms of Service</a>
                <?php if (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff'): ?>
                    <a href="<?php echo $base_path ?? ''; ?>staff/staff.php" class="footer-link text-hazard">Staff Portal</a>
                <?php elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                    <a href="<?php echo $base_path ?? ''; ?>admin/Admin_Landing_Page.php" class="footer-link text-hazard">Admin Portal</a>
                <?php endif; ?>
            </div>
            <div class="d-flex justify-content-center gap-3 mb-3">
                <a href="https://www.instagram.com/dfit.stop/" target="_blank" rel="noopener noreferrer" class="footer-link d-flex align-items-center gap-2">
                    <i class="fa-brands fa-instagram"></i> @dfit.stop
                </a>
                <span class="text-muted">|</span>
                <a href="https://web.facebook.com/dfitstop" target="_blank" rel="noopener noreferrer" class="footer-link d-flex align-items-center gap-2">
                    <i class="fa-brands fa-facebook"></i> dfitstop
                </a>
            </div>
            <p class="text-muted small mb-0">
                &copy; <?php echo date('Y'); ?> FIT-STOP FITNESS CENTER.
                <?php if ($is_logged_in ?? false): ?>
                    <br><span class="text-success">● Online</span>
                <?php endif; ?>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php 
    // Allow pages to inject custom JavaScript
    if (isset($custom_js)) {
        echo $custom_js;
    }
    ?>
</body>
</html>
